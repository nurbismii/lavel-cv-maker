<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    private const NEUTRAL_LINK_MESSAGE = 'Jika email terdaftar, tautan reset password telah dikirim.';
    private const VPEOPLE_ERROR_MESSAGE = 'Password tidak dapat direset karena akun V-People tidak valid atau sedang tidak tersedia.';
    private const GENERAL_RESET_ERROR_MESSAGE = 'Reset password sedang tidak tersedia. Silakan coba lagi.';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.connections.vpeople' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        DB::purge('vpeople');
        DB::reconnect('vpeople');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('vpeople_nik_encrypted')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::connection('vpeople')->create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('nik_karyawan');
            $table->string('email');
            $table->string('password');
            $table->timestamp('updated_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        ResetPassword::$createUrlCallback = null;
        ResetPassword::$toMailCallback = null;

        parent::tearDown();
    }

    public function test_password_reset_request_form_is_available_to_guests(): void
    {
        $this->get('/forgot-password')
            ->assertOk();
    }

    public function test_password_reset_forms_provide_accessible_recovery_controls(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Lupa password?')
            ->assertSee(route('password.request'))
            ->assertSee('btn btn-link link-primary fw-semibold d-inline-flex align-items-center', false)
            ->assertSee('style="min-height: 44px"', false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('<form method="POST" action="' . route('password.email') . '"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('for="email"', false)
            ->assertSee('name="email"', false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('Jika email terdaftar, tautan reset password akan dikirim.')
            ->assertDontSee('role="status"', false)
            ->assertSee('data-loading-text=', false);

        $resetResponse = $this->get(route('password.reset', ['token' => 'token-123', 'email' => 'user@example.test']))
            ->assertOk()
            ->assertSee('<form method="POST" action="' . route('password.update') . '"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('<input type="hidden" name="token" value="token-123">', false)
            ->assertSee('name="email"', false)
            ->assertSee('value="user@example.test"', false)
            ->assertSee('readonly', false)
            ->assertSee('readonly-field', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('autocomplete="new-password"', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('data-password-target="password"', false)
            ->assertSee('data-password-target="password_confirmation"', false)
            ->assertSee('aria-label="Tampilkan password"', false)
            ->assertSee('aria-label="Tampilkan konfirmasi password"', false)
            ->assertSee('js/password-toggle.js', false)
            ->assertSee('data-loading-text=', false);

        $this->assertSame(2, substr_count($resetResponse->getContent(), 'data-password-toggle'));
        $this->assertSame(
            2,
            preg_match_all(
                '/<button\s+type="button"[^>]*data-password-toggle[^>]*aria-pressed="false"/s',
                $resetResponse->getContent()
            )
        );
    }

    public function test_password_reset_request_sends_the_framework_notification(): void
    {
        Notification::fake();
        $user = $this->localUser();
        $token = null;

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => '  USER@EXAMPLE.TEST  '])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('success', self::NEUTRAL_LINK_MESSAGE);

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function ($notification) use (&$token) {
                $token = $notification->token;

                return true;
            }
        );

        $this->assertIsString($token);
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_password_reset_request_for_unknown_email_uses_the_same_neutral_response(): void
    {
        Notification::fake();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'missing@example.test'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('success', self::NEUTRAL_LINK_MESSAGE);

        Notification::assertNothingSent();
    }

    public function test_password_reset_request_delivery_failure_uses_the_same_neutral_response_as_unknown_email(): void
    {
        $unknownResponse = $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'missing@example.test']);
        $unknownRedirect = $unknownResponse->headers->get('Location');
        $unknownSuccess = $unknownResponse->getSession()->get('success');

        $unknownResponse
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('success', self::NEUTRAL_LINK_MESSAGE)
            ->assertSessionMissing('error');

        $this->app['session.store']->flush();
        $this->localUser();
        ResetPassword::toMailUsing(function () {
            throw new RuntimeException('mail generation failed');
        });

        $failureResponse = $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'user@example.test']);

        $failureResponse
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('success', $unknownSuccess)
            ->assertSessionMissing('error');
        $this->assertSame($unknownRedirect, $failureResponse->headers->get('Location'));
    }

    public function test_password_reset_request_post_route_is_throttled(): void
    {
        $route = Route::getRoutes()->getByName('password.email');

        $this->assertNotNull($route);
        $this->assertContains('throttle:5,1', $route->gatherMiddleware());
    }

    public function test_password_reset_form_receives_token_and_email(): void
    {
        $this->get('/reset-password/token-123?email=user%40example.test')
            ->assertOk()
            ->assertViewIs('auth.reset-password')
            ->assertViewHas('token', 'token-123')
            ->assertViewHas('email', 'user@example.test');
    }

    public function test_password_reset_update_post_route_is_throttled(): void
    {
        $route = Route::getRoutes()->getByName('password.update');

        $this->assertNotNull($route);
        $this->assertContains('throttle:5,1', $route->gatherMiddleware());
    }

    public function test_all_password_reset_routes_are_restricted_to_guests(): void
    {
        foreach (['password.request', 'password.email', 'password.reset', 'password.update'] as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertContains('guest', $route->gatherMiddleware(), $name . ' must use guest middleware.');
        }
    }

    public function test_password_reset_rejects_an_invalid_token_without_changing_either_hash(): void
    {
        $user = $this->localUser();
        $this->insertVPeopleAccount();
        $oldLocalHash = $user->password;
        $oldVPeopleHash = $this->vpeoplePassword();

        $this->from('/reset-password/invalid-token?email=user%40example.test')
            ->post('/reset-password', $this->resetPayload('invalid-token'))
            ->assertRedirect('/reset-password/invalid-token?email=user%40example.test')
            ->assertSessionHasErrors('email');

        $this->assertSame($oldLocalHash, $user->fresh()->password);
        $this->assertSame($oldVPeopleHash, $this->vpeoplePassword());
    }

    public function test_password_reset_rejects_a_password_shorter_than_eight_characters(): void
    {
        $user = $this->localUser();
        $this->insertVPeopleAccount();
        $token = Password::createToken($user);

        $payload = $this->resetPayload($token, 'short');
        $this->from('/reset-password/' . $token . '?email=user%40example.test')
            ->post('/reset-password', $payload)
            ->assertSessionHasErrors('password');

        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_password_reset_rejects_a_mismatched_confirmation(): void
    {
        $user = $this->localUser();
        $this->insertVPeopleAccount();
        $token = Password::createToken($user);
        $payload = $this->resetPayload($token);
        $payload['password_confirmation'] = 'Password-Lain-123';

        $this->from('/reset-password/' . $token . '?email=user%40example.test')
            ->post('/reset-password', $payload)
            ->assertSessionHasErrors('password');

        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_password_reset_keeps_token_when_local_user_is_not_linked_to_vpeople(): void
    {
        $user = $this->localUser(['vpeople_nik_encrypted' => null]);
        $oldLocalHash = $user->password;
        $token = Password::createToken($user);

        $this->post('/reset-password', $this->resetPayload($token))
            ->assertSessionHas('error', self::VPEOPLE_ERROR_MESSAGE);

        $this->assertSame($oldLocalHash, $user->fresh()->password);
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_password_reset_keeps_token_when_vpeople_account_is_missing(): void
    {
        $user = $this->localUser();
        $oldLocalHash = $user->password;
        $token = Password::createToken($user);

        $this->post('/reset-password', $this->resetPayload($token))
            ->assertSessionHas('error', self::VPEOPLE_ERROR_MESSAGE);

        $this->assertSame($oldLocalHash, $user->fresh()->password);
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_password_reset_keeps_token_when_vpeople_email_does_not_match(): void
    {
        $user = $this->localUser();
        $this->insertVPeopleAccount(['email' => 'other@example.test']);
        $oldLocalHash = $user->password;
        $oldVPeopleHash = $this->vpeoplePassword();
        $token = Password::createToken($user);

        $this->post('/reset-password', $this->resetPayload($token))
            ->assertSessionHas('error', self::VPEOPLE_ERROR_MESSAGE);

        $this->assertSame($oldLocalHash, $user->fresh()->password);
        $this->assertSame($oldVPeopleHash, $this->vpeoplePassword());
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_password_reset_keeps_token_when_vpeople_update_fails(): void
    {
        $user = $this->localUser();
        $this->insertVPeopleAccount();
        $oldLocalHash = $user->password;
        $oldVPeopleHash = $this->vpeoplePassword();
        $token = Password::createToken($user);
        DB::connection('vpeople')->statement(
            "CREATE TRIGGER fail_vpeople_password_update BEFORE UPDATE OF password ON users BEGIN SELECT RAISE(ABORT, 'vpeople write failed'); END"
        );

        $this->post('/reset-password', $this->resetPayload($token))
            ->assertSessionHas('error', self::GENERAL_RESET_ERROR_MESSAGE);

        $this->assertSame($oldLocalHash, $user->fresh()->password);
        $this->assertSame($oldVPeopleHash, $this->vpeoplePassword());
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_password_reset_writes_the_same_hash_and_consumes_token_on_success(): void
    {
        Event::fake([PasswordResetEvent::class]);
        $user = $this->localUser();
        $this->insertVPeopleAccount();
        $token = Password::createToken($user);

        $this->post('/reset-password', $this->resetPayload($token))
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Password berhasil direset. Silakan masuk dengan password baru.');

        $localHash = $user->fresh()->password;
        $vpeopleHash = $this->vpeoplePassword();
        $this->assertSame($localHash, $vpeopleHash);
        $this->assertTrue(Hash::check('Password-Baru-123', $localHash));
        $this->assertFalse(Hash::check('Password-Lama-123', $localHash));
        $this->assertFalse(Password::tokenExists($user, $token));
        Event::assertDispatched(PasswordResetEvent::class, function ($event) use ($user) {
            return $event->user->is($user);
        });
    }

    public function test_password_reset_compensates_vpeople_and_keeps_token_after_local_failure(): void
    {
        $user = $this->localUser();
        $this->insertVPeopleAccount();
        $oldLocalHash = $user->password;
        $oldVPeopleHash = $this->vpeoplePassword();
        $token = Password::createToken($user);
        DB::statement(
            "CREATE TRIGGER fail_local_password_update BEFORE UPDATE OF password ON users BEGIN SELECT RAISE(ABORT, 'local write failed'); END"
        );

        $this->post('/reset-password', $this->resetPayload($token))
            ->assertSessionHas('error', self::GENERAL_RESET_ERROR_MESSAGE);

        $this->assertSame($oldLocalHash, $user->fresh()->password);
        $this->assertSame($oldVPeopleHash, $this->vpeoplePassword());
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    private function localUser(array $overrides = []): User
    {
        $encryptedNik = array_key_exists('vpeople_nik_encrypted', $overrides)
            ? $overrides['vpeople_nik_encrypted']
            : Crypt::encryptString('EMP001');
        unset($overrides['vpeople_nik_encrypted']);

        $user = new User(array_merge([
            'name' => 'Reset User',
            'email' => 'user@example.test',
            'password' => Hash::make('Password-Lama-123'),
        ], $overrides));
        $user->vpeople_nik_encrypted = $encryptedNik;
        $user->save();

        return $user;
    }

    private function insertVPeopleAccount(array $overrides = []): void
    {
        DB::connection('vpeople')->table('users')->insert(array_merge([
            'id' => 'vp-1',
            'nik_karyawan' => 'EMP001',
            'email' => 'user@example.test',
            'password' => Hash::make('Password-Lama-123'),
            'updated_at' => '2026-08-18 10:00:00',
        ], $overrides));
    }

    private function resetPayload(string $token, string $password = 'Password-Baru-123'): array
    {
        return [
            'token' => $token,
            'email' => 'user@example.test',
            'password' => $password,
            'password_confirmation' => $password,
        ];
    }

    private function vpeoplePassword(): string
    {
        return DB::connection('vpeople')->table('users')->value('password');
    }
}

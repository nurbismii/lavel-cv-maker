<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PurgeUnverifiedUsersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'auth.verification.expire' => 60,
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('email_verification_expires_at')->nullable()->index();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_only_deletes_unverified_users_whose_deadline_has_passed()
    {
        Carbon::setTestNow('2026-08-15 12:00:00');

        $expired = User::factory()->unverified()->create([
            'email_verification_expires_at' => now()->subSecond(),
        ]);
        $active = User::factory()->unverified()->create([
            'email_verification_expires_at' => now()->addSecond(),
        ]);
        $verified = User::factory()->create([
            'email_verification_expires_at' => now()->subHour(),
        ]);
        $legacyExpired = User::factory()->unverified()->create([
            'email_verification_expires_at' => null,
            'created_at' => now()->subMinutes(61),
            'updated_at' => now()->subMinutes(61),
        ]);

        $this->artisan('users:purge-unverified')
            ->expectsOutput('2 akun belum terverifikasi berhasil dihapus.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['id' => $expired->id]);
        $this->assertDatabaseMissing('users', ['id' => $legacyExpired->id]);
        $this->assertDatabaseHas('users', ['id' => $active->id]);
        $this->assertDatabaseHas('users', ['id' => $verified->id]);
    }

    public function test_dry_run_does_not_delete_expired_accounts()
    {
        $expired = User::factory()->unverified()->create([
            'email_verification_expires_at' => now()->subMinute(),
        ]);

        $this->artisan('users:purge-unverified', ['--dry-run' => true])
            ->expectsOutput('Ditemukan 1 akun belum terverifikasi yang kedaluwarsa (dry-run).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => $expired->id]);
    }

    public function test_sending_a_new_verification_email_renews_the_deadline()
    {
        Notification::fake();
        Carbon::setTestNow('2026-08-15 12:00:00');

        $user = User::factory()->unverified()->create([
            'email_verification_expires_at' => now()->subMinute(),
        ]);

        $user->sendEmailVerificationNotification();

        $this->assertSame(
            '2026-08-15 13:00:00',
            $user->fresh()->email_verification_expires_at->format('Y-m-d H:i:s')
        );
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }
}

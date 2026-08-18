<?php

namespace Tests\Unit;

use App\Exceptions\VPeopleAccountException;
use App\Models\User;
use App\Services\SynchronizedPasswordResetService;
use App\Services\VPeopleAccountService;
use App\Services\VPeopleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class VPeoplePasswordSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'database.connections.vpeople' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge();
        DB::reconnect();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('vpeople_nik_encrypted')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        DB::purge('vpeople');
        DB::reconnect('vpeople');
        Schema::connection('vpeople')->create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('nik_karyawan');
            $table->string('email');
            $table->string('password');
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function test_password_reset_account_rejects_user_without_linked_nik(): void
    {
        $this->expectException(VPeopleAccountException::class);
        $this->service()->passwordResetAccount(new User(['email' => 'user@example.test']));
    }

    public function test_password_reset_account_rejects_email_mismatch(): void
    {
        DB::connection('vpeople')->table('users')->insert($this->account(['email' => 'other@example.test']));
        $user = new User(['email' => 'user@example.test']);
        $user->vpeople_nik_encrypted = Crypt::encryptString('EMP001');

        $this->expectException(VPeopleAccountException::class);
        $this->service()->passwordResetAccount($user);
    }

    public function test_replace_and_restore_password_hash_use_snapshot_value(): void
    {
        DB::connection('vpeople')->table('users')->insert($this->account());
        $user = new User(['email' => 'user@example.test']);
        $user->vpeople_nik_encrypted = Crypt::encryptString('EMP001');
        $snapshot = $this->service()->passwordResetAccount($user);

        $this->service()->replacePasswordHash($snapshot, 'new-hash');
        $this->assertSame('new-hash', DB::connection('vpeople')->table('users')->value('password'));

        $this->service()->restorePasswordHash($snapshot, 'new-hash');
        $this->assertSame('old-hash', DB::connection('vpeople')->table('users')->value('password'));
    }

    public function test_replace_rejects_a_stale_password_snapshot(): void
    {
        DB::connection('vpeople')->table('users')->insert($this->account());
        $user = new User(['email' => 'user@example.test']);
        $user->vpeople_nik_encrypted = Crypt::encryptString('EMP001');
        $snapshot = $this->service()->passwordResetAccount($user);
        DB::connection('vpeople')->table('users')->update(['password' => 'concurrent-hash']);

        $this->expectException(VPeopleAccountException::class);
        $this->service()->replacePasswordHash($snapshot, 'new-hash');
    }

    public function test_synchronized_reset_writes_one_hash_to_both_databases(): void
    {
        $user = $this->localUser();
        DB::connection('vpeople')->table('users')->insert($this->account());

        $result = $this->resetService()->reset($user, 'Password-Baru-123');
        $localHash = $result->fresh()->password;
        $externalHash = DB::connection('vpeople')->table('users')->value('password');

        $this->assertSame($localHash, $externalHash);
        $this->assertTrue(Hash::check('Password-Baru-123', $localHash));
    }

    public function test_local_write_failure_restores_vpeople_hash(): void
    {
        $user = $this->localUser();
        DB::connection('vpeople')->table('users')->insert($this->account());
        DB::statement("CREATE TRIGGER fail_local_password_update BEFORE UPDATE OF password ON users BEGIN SELECT RAISE(ABORT, 'local write failed'); END");

        try {
            $this->resetService()->reset($user, 'Password-Baru-123');
            $this->fail('Reset seharusnya gagal.');
        } catch (\Throwable $exception) {
            $this->assertSame('old-hash', DB::connection('vpeople')->table('users')->value('password'));
            $this->assertSame('local-old-hash', $user->fresh()->password);
        }
    }

    private function service(): VPeopleAccountService
    {
        return new VPeopleAccountService(Mockery::mock(VPeopleService::class));
    }

    private function resetService(): SynchronizedPasswordResetService
    {
        return new SynchronizedPasswordResetService($this->service());
    }

    private function localUser(): User
    {
        $user = new User([
            'email' => 'user@example.test',
            'password' => 'local-old-hash',
            'vpeople_nik_encrypted' => Crypt::encryptString('EMP001'),
        ]);
        $user->save();

        return $user;
    }

    private function account(array $overrides = []): array
    {
        return array_merge([
            'id' => 'vp-1',
            'nik_karyawan' => 'EMP001',
            'email' => 'user@example.test',
            'password' => 'old-hash',
            'updated_at' => '2026-08-18 10:00:00',
        ], $overrides);
    }
}

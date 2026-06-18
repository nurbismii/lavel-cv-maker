<?php

namespace App\Services;

use App\Exceptions\VPeopleAccountException;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class VPeopleAccountService
{
    private const ADMIN_ROLE_NAMES = [
        'administrator',
        'super admin',
        'hr',
    ];

    /**
     * @var VPeopleService
     */
    private $vpeopleService;

    public function __construct(VPeopleService $vpeopleService)
    {
        $this->vpeopleService = $vpeopleService;
    }

    public function accountSyncEnabled(): bool
    {
        return filter_var(config('services.vpeople.account_sync_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function loginEnabled(): bool
    {
        return filter_var(config('services.vpeople.login_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function ensureAccountForEmployee(array $employee, string $email, string $password): array
    {
        return $this->ensureAccountForEmployeeWithPasswordHash(
            $employee,
            $email,
            Hash::make($password)
        );
    }

    public function ensureAccountForUser(User $user): array
    {
        if (!$user->vpeople_nik_encrypted) {
            throw new VPeopleAccountException('Data NIK V-People tidak ditemukan pada akun Vitae.');
        }

        try {
            $nik = Crypt::decryptString($user->vpeople_nik_encrypted);
        } catch (Throwable $exception) {
            throw new VPeopleAccountException('Data NIK V-People pada akun Vitae tidak valid.');
        }

        $employee = $this->vpeopleService->findActiveEmployeeByNik($nik);

        if (!$employee) {
            throw new VPeopleAccountException('Data karyawan V-People tidak aktif atau tidak ditemukan.');
        }

        return DB::connection('vpeople')->transaction(function () use ($employee, $user) {
            return $this->ensureAccountForEmployeeWithPasswordHash($employee, $user->email, $user->password);
        });
    }

    public function assertAccountCanBeProvisioned(array $employee, string $email): void
    {
        $nik = (string) $employee['nik'];
        $email = strtolower(trim($email));

        $existingByNik = DB::connection('vpeople')
            ->table('users')
            ->where('nik_karyawan', $nik)
            ->first();

        if ($existingByNik) {
            if (strtolower((string) $existingByNik->email) !== $email) {
                throw new VPeopleAccountException('Akun V-People untuk NIK ini sudah memakai email lain.');
            }

            return;
        }

        $existingByEmail = DB::connection('vpeople')
            ->table('users')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($existingByEmail) {
            throw new VPeopleAccountException('Email ini sudah digunakan oleh akun V-People lain.');
        }

        $roleId = $this->defaultRoleId();
        $this->assertSelfRegisterRoleIsAllowed($roleId);
    }

    public function ensureAccountForEmployeeWithPasswordHash(array $employee, string $email, string $passwordHash): array
    {
        $nik = (string) $employee['nik'];
        $email = strtolower(trim($email));

        $this->assertAccountCanBeProvisioned($employee, $email);

        $existingByNik = DB::connection('vpeople')
            ->table('users')
            ->where('nik_karyawan', $nik)
            ->first();

        if ($existingByNik) {
            return [
                'account' => (array) $existingByNik,
                'created' => false,
            ];
        }

        $roleId = $this->defaultRoleId();

        $id = $this->makeUniqueUserId();
        $now = now();

        DB::connection('vpeople')
            ->table('users')
            ->insert([
                'id' => $id,
                'name' => $employee['name'],
                'nik_karyawan' => $nik,
                'email' => $email,
                'password' => $passwordHash,
                'status' => $this->defaultStatus(),
                'email_verified_at' => null,
                'terakhir_login' => null,
                'role_id' => $roleId,
                'authorized_divisi_ids' => null,
                'authorized_departemen_ids' => null,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $account = DB::connection('vpeople')
            ->table('users')
            ->where('id', $id)
            ->first();

        return [
            'account' => (array) $account,
            'created' => true,
        ];
    }

    public function authenticate(string $identifier, string $password): ?array
    {
        $identifier = strtolower(trim($identifier));

        if ($identifier === '') {
            return null;
        }

        $account = DB::connection('vpeople')
            ->table('users')
            ->where('status', $this->defaultStatus())
            ->where(function ($query) use ($identifier) {
                $query->whereRaw('LOWER(email) = ?', [$identifier])
                    ->orWhere('nik_karyawan', $identifier);
            })
            ->first();

        if (!$account || !Hash::check($password, $account->password)) {
            return null;
        }

        $employee = $this->vpeopleService->findActiveEmployeeByNik((string) $account->nik_karyawan);

        if (!$employee) {
            return null;
        }

        return [
            'account' => (array) $account,
            'employee' => $employee,
        ];
    }

    private function defaultRoleId(): ?int
    {
        $roleId = config('services.vpeople.default_role_id');

        if ($roleId === null || $roleId === '') {
            return null;
        }

        return (int) $roleId;
    }

    private function defaultStatus(): string
    {
        return (string) config('services.vpeople.default_status', 'aktif');
    }

    private function assertSelfRegisterRoleIsAllowed(?int $roleId): void
    {
        if (!$roleId) {
            return;
        }

        $role = DB::connection('vpeople')
            ->table('roles')
            ->where('id', $roleId)
            ->where('status', '1')
            ->first();

        if (!$role) {
            throw new VPeopleAccountException('Role default V-People tidak valid atau tidak aktif.');
        }

        if (in_array(strtolower($role->permission_role), self::ADMIN_ROLE_NAMES, true)) {
            throw new VPeopleAccountException('Role default V-People terlalu tinggi untuk registrasi mandiri.');
        }
    }

    private function makeUniqueUserId(): string
    {
        do {
            $id = Str::random(32);
        } while (
            DB::connection('vpeople')
            ->table('users')
            ->where('id', $id)
            ->exists()
        );

        return $id;
    }
}

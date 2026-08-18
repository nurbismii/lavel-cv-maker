<?php

namespace App\Services;

use App\Exceptions\VPeopleAccountException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SynchronizedPasswordResetService
{
    private $vpeopleAccounts;

    public function __construct(VPeopleAccountService $vpeopleAccounts)
    {
        $this->vpeopleAccounts = $vpeopleAccounts;
    }

    public function reset(User $user, string $plainPassword): User
    {
        $account = $this->vpeopleAccounts->passwordResetAccount($user);
        $oldLocalHash = (string) $user->password;
        $newHash = Hash::make($plainPassword);
        $this->vpeopleAccounts->replacePasswordHash($account, $newHash);

        try {
            $updated = DB::connection($user->getConnectionName())
                ->table($user->getTable())
                ->where($user->getKeyName(), $user->getKey())
                ->where('password', $oldLocalHash)
                ->update([
                    'password' => $newHash,
                    'remember_token' => Str::random(60),
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new RuntimeException('Local password changed concurrently.');
            }
        } catch (Throwable $localException) {
            try {
                $this->vpeopleAccounts->restorePasswordHash($account, $newHash);
            } catch (Throwable $restoreException) {
                Log::critical('V-People password compensation failed.', [
                    'user_id' => $user->getKey(),
                    'exception' => get_class($restoreException),
                ]);

                throw new VPeopleAccountException('Sinkronisasi password gagal dipulihkan.', 0, $restoreException);
            }

            throw $localException;
        }

        return $user->fresh();
    }
}

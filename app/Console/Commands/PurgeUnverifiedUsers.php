<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class PurgeUnverifiedUsers extends Command
{
    protected $signature = 'users:purge-unverified {--dry-run : Tampilkan jumlah akun tanpa menghapus data}';

    protected $description = 'Hapus akun yang melewati batas waktu verifikasi email.';

    public function handle(): int
    {
        $now = now();
        $expirationMinutes = max(1, (int) config('auth.verification.expire', 60));
        $legacyCutoff = $now->copy()->subMinutes($expirationMinutes);
        $query = $this->expiredUnverifiedUsers($now, $legacyCutoff);

        if ($this->option('dry-run')) {
            $this->info(sprintf(
                'Ditemukan %d akun belum terverifikasi yang kedaluwarsa (dry-run).',
                $query->count()
            ));

            return self::SUCCESS;
        }

        $deleted = 0;

        $query->select('id')->chunkById(200, function ($users) use ($now, $legacyCutoff, &$deleted) {
            $deleted += $this->expiredUnverifiedUsers($now, $legacyCutoff)
                ->whereKey($users->modelKeys())
                ->delete();
        });

        $this->info(sprintf('%d akun belum terverifikasi berhasil dihapus.', $deleted));

        return self::SUCCESS;
    }

    private function expiredUnverifiedUsers($now, $legacyCutoff): Builder
    {
        return User::query()
            ->whereNull('email_verified_at')
            ->where(function (Builder $query) use ($now, $legacyCutoff) {
                $query->where('email_verification_expires_at', '<=', $now)
                    ->orWhere(function (Builder $legacyQuery) use ($legacyCutoff) {
                        $legacyQuery->whereNull('email_verification_expires_at')
                            ->where('created_at', '<=', $legacyCutoff);
                    });
            });
    }
}

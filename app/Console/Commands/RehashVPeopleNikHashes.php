<?php

namespace App\Console\Commands;

use App\Services\CvUserProvisioningService;
use App\Services\VPeopleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RehashVPeopleNikHashes extends Command
{
    protected $signature = 'vitae:rehash-vpeople-niks
        {--dry-run : Hitung akun yang perlu dimigrasikan tanpa mengubah data}
        {--chunk=200 : Jumlah karyawan V-People per batch}';

    protected $description = 'Migrasikan hash NIK akun Vitae dari APP_KEY ke key integrasi khusus.';

    public function handle(CvUserProvisioningService $provisioningService): int
    {
        if (!$provisioningService->usesDedicatedNikHashKey()) {
            $this->error('VPEOPLE_NIK_HASH_KEY belum dikonfigurasi.');

            return 1;
        }

        $chunkSize = max(10, min(1000, (int) $this->option('chunk')));
        $dryRun = (bool) $this->option('dry-run');
        $checked = 0;
        $migrated = 0;
        $alreadyMigrated = 0;
        $withoutAccount = 0;

        DB::connection('vpeople')
            ->table('employees')
            ->select(['nik'])
            ->where('status_resign', VPeopleService::ACTIVE_RESIGN_STATUS)
            ->whereNotNull('nik')
            ->where('nik', '<>', '')
            ->chunkById($chunkSize, function ($employees) use (
                $provisioningService,
                $dryRun,
                &$checked,
                &$migrated,
                &$alreadyMigrated,
                &$withoutAccount
            ) {
                foreach ($employees as $employee) {
                    $checked++;
                    $newHash = $provisioningService->hashNik((string) $employee->nik);
                    $legacyHash = $provisioningService->legacyHashNik((string) $employee->nik);

                    $alreadyExists = DB::table('users')
                        ->where('vpeople_nik_hash', $newHash)
                        ->exists();

                    if ($alreadyExists) {
                        $alreadyMigrated++;
                        continue;
                    }

                    $legacyUserId = DB::table('users')
                        ->where('vpeople_nik_hash', $legacyHash)
                        ->value('id');

                    if (!$legacyUserId) {
                        $withoutAccount++;
                        continue;
                    }

                    if (!$dryRun) {
                        DB::table('users')
                            ->where('id', $legacyUserId)
                            ->where('vpeople_nik_hash', $legacyHash)
                            ->update(['vpeople_nik_hash' => $newHash]);
                    }

                    $migrated++;
                }
            }, 'nik', 'nik');

        $this->info(sprintf(
            'Hash NIK: checked=%d, migrated=%d, already_migrated=%d, without_account=%d%s',
            $checked,
            $migrated,
            $alreadyMigrated,
            $withoutAccount,
            $dryRun ? ' (dry-run)' : ''
        ));

        return 0;
    }
}

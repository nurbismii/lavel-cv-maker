<?php

namespace App\Services;

use App\Models\CvProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VPeopleService
{
    public const ACTIVE_RESIGN_STATUS = 'AKTIF';

    private $organizationMasterAvailable;

    public function findActiveEmployeeByNikAndBirthDate(string $nik, string $birthDate): ?array
    {
        $birthDate = Carbon::parse($birthDate)->format('Y-m-d');

        $employee = $this->activeEmployeeQuery()
            ->where('employees.nik', $nik)
            ->whereDate('employees.tgl_lahir', $birthDate)
            ->first();

        if (!$employee) {
            return null;
        }

        return $this->mapEmployee((array) $employee);
    }

    public function findActiveEmployeeByNik(string $nik): ?array
    {
        $employee = $this->activeEmployeeQuery()
            ->where('employees.nik', $nik)
            ->first();

        if (!$employee) {
            return null;
        }

        return $this->mapEmployee((array) $employee);
    }

    private function activeEmployeeQuery()
    {
        $query = DB::connection('vpeople')
            ->table('employees')
            ->leftJoin('departemens', 'employees.departemen_id', '=', 'departemens.id')
            ->leftJoin('divisis', 'employees.divisi_id', '=', 'divisis.id');

        $columns = [
                'employees.nik',
                'employees.nama_karyawan',
                'employees.tgl_lahir',
                'employees.no_ktp',
                'employees.no_kk',
                'employees.jenis_kelamin',
                'employees.agama',
                'employees.status_perkawinan',
                'employees.nama_ibu_kandung',
                'employees.nama_bapak',
                'employees.status_karyawan',
                'employees.status_resign',
                'employees.no_telp',
                'employees.alamat_domisili',
                'employees.alamat_ktp',
                'employees.area_kerja',
                'employees.jabatan',
                'employees.posisi',
                'employees.entry_date',
                'employees.pendidikan_terakhir',
                'employees.nama_instansi_pendidikan',
                'employees.jurusan',
                'employees.tanggal_kelulusan',
                'departemens.departemen',
                'divisis.nama_divisi',
        ];

        if ($this->organizationMasterAvailable()) {
            $query
                ->leftJoin('job_titles as employee_job_titles', 'employees.job_title_id', '=', 'employee_job_titles.id')
                ->leftJoin('job_levels as default_job_levels', 'employee_job_titles.job_level_id', '=', 'default_job_levels.id')
                ->leftJoin('organization_positions as employee_positions', 'employees.organization_position_id', '=', 'employee_positions.id')
                ->leftJoin('job_levels as position_job_levels', 'employee_positions.job_level_id', '=', 'position_job_levels.id');

            $columns = array_merge($columns, [
                'employees.job_title_id',
                'employees.organization_position_id',
                'employee_job_titles.code as job_title_code',
                'employee_job_titles.name as master_job_title_name',
                'employee_job_titles.name_zh as master_job_title_name_zh',
                'employee_positions.position_name as master_position_name',
                DB::raw('COALESCE(position_job_levels.code, default_job_levels.code) as job_level_code'),
                DB::raw('COALESCE(position_job_levels.rank, default_job_levels.rank) as job_level_rank'),
                'employees.updated_at as organization_updated_at',
            ]);
        } else {
            $columns = array_merge($columns, [
                DB::raw('NULL as job_title_id'),
                DB::raw('NULL as organization_position_id'),
                DB::raw('NULL as job_title_code'),
                DB::raw('NULL as master_job_title_name'),
                DB::raw('NULL as master_job_title_name_zh'),
                DB::raw('NULL as master_position_name'),
                DB::raw('NULL as job_level_code'),
                DB::raw('NULL as job_level_rank'),
                DB::raw('NULL as organization_updated_at'),
            ]);
        }

        return $query
            ->select($columns)
            ->where('employees.status_resign', self::ACTIVE_RESIGN_STATUS);
    }

    private function organizationMasterAvailable(): bool
    {
        if ($this->organizationMasterAvailable !== null) {
            return $this->organizationMasterAvailable;
        }

        $schema = Schema::connection('vpeople');

        return $this->organizationMasterAvailable = $schema->hasTable('job_titles')
            && $schema->hasTable('job_levels')
            && $schema->hasTable('organization_positions')
            && $schema->hasColumn('organization_positions', 'position_name')
            && $schema->hasColumn('employees', 'job_title_id')
            && $schema->hasColumn('employees', 'organization_position_id');
    }

    private function mapEmployee(array $employee): array
    {
        $masterJobTitle = $this->nullableTrim($employee['master_job_title_name'] ?? null);
        $masterJobTitleZh = $this->nullableTrim($employee['master_job_title_name_zh'] ?? null);
        $jobTitle = $masterJobTitle
            ? $masterJobTitle . ($masterJobTitleZh ? ' ' . $masterJobTitleZh : '')
            : $this->nullableTrim($employee['jabatan'] ?? null);
        $ktpAddress = $this->nullableTrim($employee['alamat_ktp'] ?? null);
        $domicileAddress = $this->nullableTrim($employee['alamat_domisili'] ?? null);
        $address = $domicileAddress ?: $ktpAddress;

        return [
            'nik' => $employee['nik'],
            'name' => $employee['nama_karyawan'],
            'birth_date' => $employee['tgl_lahir'],
            'ktp_number' => $this->digitsOnly($employee['no_ktp'] ?? null),
            'family_card_number' => $this->digitsOnly($employee['no_kk'] ?? null),
            'gender' => $employee['jenis_kelamin'],
            'religion' => CvProfile::normalizeReligion($employee['agama'] ?? null),
            'marital_status' => $employee['status_perkawinan'],
            'mother_name' => $this->nullableTrim($employee['nama_ibu_kandung'] ?? null),
            'spouse_name' => $this->nullableTrim($employee['nama_bapak'] ?? null),
            'contract_status' => $employee['status_karyawan'],
            'resign_status' => $employee['status_resign'],
            'phone' => $employee['no_telp'],
            'ktp_address' => $ktpAddress,
            'domicile_same_as_ktp' => $ktpAddress && $address === $ktpAddress,
            'address' => $address,
            'work_area' => $employee['area_kerja'],
            'department' => $employee['departemen'],
            'division' => $employee['nama_divisi'],
            'job_title' => $jobTitle,
            'position' => $this->nullableTrim($employee['master_position_name'] ?? null)
                ?: $this->nullableTrim($employee['posisi'] ?? null),
            'job_title_id' => $employee['job_title_id'] ? (int) $employee['job_title_id'] : null,
            'job_title_code' => $employee['job_title_code'] ?? null,
            'organization_position_id' => $employee['organization_position_id'] ? (int) $employee['organization_position_id'] : null,
            'job_level_code' => $employee['job_level_code'] ?? null,
            'job_level_rank' => isset($employee['job_level_rank']) ? (int) $employee['job_level_rank'] : null,
            'organization_updated_at' => $employee['organization_updated_at'] ?? null,
            'entry_date' => $this->dateOrNull($employee['entry_date'] ?? null),
            'education_level' => $employee['pendidikan_terakhir'],
            'education_institution' => $employee['nama_instansi_pendidikan'],
            'education_major' => $employee['jurusan'],
            'graduation_date' => $employee['tanggal_kelulusan'],
        ];
    }

    private function digitsOnly($value): ?string
    {
        if (!$value) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits ?: null;
    }

    private function nullableTrim($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function dateOrNull($value): ?string
    {
        if (!$value || $value === '0000-00-00') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return null;
        }
    }
}

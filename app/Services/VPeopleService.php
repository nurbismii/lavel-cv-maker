<?php

namespace App\Services;

use App\Models\CvProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VPeopleService
{
    public const ACTIVE_RESIGN_STATUS = 'AKTIF';

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
        return DB::connection('vpeople')
            ->table('employees')
            ->leftJoin('departemens', 'employees.departemen_id', '=', 'departemens.id')
            ->leftJoin('divisis', 'employees.divisi_id', '=', 'divisis.id')
            ->select([
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
            ])
            ->where('employees.status_resign', self::ACTIVE_RESIGN_STATUS);
    }

    private function mapEmployee(array $employee): array
    {
        $position = $employee['jabatan'] ?: $employee['posisi'];
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
            'position' => $position,
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

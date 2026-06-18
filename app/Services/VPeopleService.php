<?php

namespace App\Services;

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
                'employees.jenis_kelamin',
                'employees.status_perkawinan',
                'employees.status_karyawan',
                'employees.status_resign',
                'employees.no_telp',
                'employees.alamat_domisili',
                'employees.alamat_ktp',
                'employees.area_kerja',
                'employees.jabatan',
                'employees.posisi',
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
        $address = $employee['alamat_domisili'] ?: $employee['alamat_ktp'];

        return [
            'nik' => $employee['nik'],
            'name' => $employee['nama_karyawan'],
            'birth_date' => $employee['tgl_lahir'],
            'gender' => $employee['jenis_kelamin'],
            'marital_status' => $employee['status_perkawinan'],
            'contract_status' => $employee['status_karyawan'],
            'resign_status' => $employee['status_resign'],
            'phone' => $employee['no_telp'],
            'address' => $address,
            'work_area' => $employee['area_kerja'],
            'department' => $employee['departemen'],
            'division' => $employee['nama_divisi'],
            'position' => $position,
            'education_level' => $employee['pendidikan_terakhir'],
            'education_institution' => $employee['nama_instansi_pendidikan'],
            'education_major' => $employee['jurusan'],
            'graduation_date' => $employee['tanggal_kelulusan'],
        ];
    }
}

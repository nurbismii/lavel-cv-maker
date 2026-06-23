<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class VPeopleOrganizationService
{
    public function departments(): array
    {
        return DB::connection('vpeople')
            ->table('departemens')
            ->selectRaw('CAST(id AS CHAR) as id, departemen as name')
            ->whereNotNull('departemen')
            ->where('departemen', '<>', '')
            ->orderBy('departemen')
            ->get()
            ->map(function ($item) {
                return $this->option($item->id, $item->name);
            })
            ->toArray();
    }

    public function divisions(?string $departmentId): array
    {
        $departmentId = $this->cleanId($departmentId);

        if (!$departmentId) {
            return [];
        }

        return DB::connection('vpeople')
            ->table('divisis')
            ->selectRaw('CAST(id AS CHAR) as id, nama_divisi as name')
            ->where('departemen_id', $departmentId)
            ->whereNotNull('nama_divisi')
            ->where('nama_divisi', '<>', '')
            ->orderBy('nama_divisi')
            ->get()
            ->map(function ($item) {
                return $this->option($item->id, $item->name);
            })
            ->toArray();
    }

    public function positions(?string $departmentId, ?string $divisionId): array
    {
        $departmentId = $this->cleanId($departmentId);
        $divisionId = $this->cleanId($divisionId);

        if (!$departmentId && !$divisionId) {
            return [];
        }

        $query = DB::connection('vpeople')
            ->table('employees')
            ->selectRaw("DISTINCT TRIM(COALESCE(NULLIF(jabatan, ''), NULLIF(posisi, ''))) as name")
            ->where('status_resign', VPeopleService::ACTIVE_RESIGN_STATUS)
            ->whereRaw("TRIM(COALESCE(NULLIF(jabatan, ''), NULLIF(posisi, ''))) <> ''");

        if ($divisionId) {
            $query->where('divisi_id', $divisionId);
        } elseif ($departmentId) {
            $query->where('departemen_id', $departmentId);
        }

        return $query
            ->orderBy('name')
            ->limit(200)
            ->get()
            ->map(function ($item) {
                return $this->option($item->name, $item->name);
            })
            ->toArray();
    }

    public function findDepartmentIdByName(?string $name): ?string
    {
        $name = $this->cleanName($name);

        if (!$name) {
            return null;
        }

        $item = DB::connection('vpeople')
            ->table('departemens')
            ->selectRaw('CAST(id AS CHAR) as id')
            ->whereRaw('LOWER(TRIM(departemen)) = ?', [strtolower($name)])
            ->first();

        return $item ? (string) $item->id : null;
    }

    public function findDivisionIdByName(?string $departmentId, ?string $name): ?string
    {
        $departmentId = $this->cleanId($departmentId);
        $name = $this->cleanName($name);

        if (!$departmentId || !$name) {
            return null;
        }

        $item = DB::connection('vpeople')
            ->table('divisis')
            ->selectRaw('CAST(id AS CHAR) as id')
            ->where('departemen_id', $departmentId)
            ->whereRaw('LOWER(TRIM(nama_divisi)) = ?', [strtolower($name)])
            ->first();

        return $item ? (string) $item->id : null;
    }

    private function cleanId($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function cleanName($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function option($id, $name): array
    {
        return [
            'id' => (string) $id,
            'name' => trim((string) $name),
        ];
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class VPeopleOrganizationService
{
    private const WORK_AREA_LABELS = [
        'VDNI' => 'PT VDNI',
        'VDNIP' => 'PT VDNIP',
    ];

    public static function supportedWorkAreaCodes(): array
    {
        return array_keys(self::WORK_AREA_LABELS);
    }

    public static function normalizeWorkArea($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', ' ', $value));
        $normalized = preg_replace('/^PT\s+/', '', $normalized);

        return array_key_exists($normalized, self::WORK_AREA_LABELS) ? $normalized : null;
    }

    public static function workAreaLabel($value): ?string
    {
        $code = self::normalizeWorkArea($value);

        return $code ? self::WORK_AREA_LABELS[$code] : null;
    }

    public static function workAreaOptions(): array
    {
        return array_map(function ($code, $name) {
            return [
                'id' => $code,
                'name' => $name,
            ];
        }, array_keys(self::WORK_AREA_LABELS), self::WORK_AREA_LABELS);
    }

    public function workAreas(): array
    {
        return self::workAreaOptions();
    }

    public function departments(?string $workArea = null): array
    {
        $companyId = $this->companyIdForWorkArea($workArea);

        if ($this->cleanId($workArea) && !$companyId) {
            return [];
        }

        $query = DB::connection('vpeople')
            ->table('departemens')
            ->selectRaw('CAST(id AS CHAR) as id, departemen as name')
            ->whereNotNull('departemen')
            ->where('departemen', '<>', '');

        if ($companyId) {
            $query->where('perusahaan_id', $companyId);
        }

        return $query
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

    public function findDepartmentIdByName(?string $name, ?string $workArea = null): ?string
    {
        $name = $this->cleanName($name);
        $companyId = $this->companyIdForWorkArea($workArea);

        if (!$name) {
            return null;
        }

        if ($this->cleanId($workArea) && !$companyId) {
            return null;
        }

        $query = DB::connection('vpeople')
            ->table('departemens')
            ->selectRaw('CAST(id AS CHAR) as id')
            ->whereRaw('LOWER(TRIM(departemen)) = ?', [strtolower($name)]);

        if ($companyId) {
            $query->where('perusahaan_id', $companyId);
        }

        $item = $query->first();

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

    private function companyIdForWorkArea(?string $workArea): ?string
    {
        $workArea = self::normalizeWorkArea($workArea);

        if (!$workArea) {
            return null;
        }

        $item = DB::connection('vpeople')
            ->table('perusahaan')
            ->selectRaw('CAST(id AS CHAR) as id')
            ->whereRaw('UPPER(TRIM(kode_perusahaan)) = ?', [$workArea])
            ->first();

        return $item ? (string) $item->id : null;
    }

    private function option($id, $name): array
    {
        return [
            'id' => (string) $id,
            'name' => trim((string) $name),
        ];
    }
}

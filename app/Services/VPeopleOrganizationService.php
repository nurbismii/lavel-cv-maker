<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    public function jobTitles(?string $departmentId, ?string $divisionId): array
    {
        $departmentId = $this->cleanId($departmentId);
        $divisionId = $this->cleanId($divisionId);

        if (!$departmentId && !$divisionId) {
            return [];
        }

        if (
            Schema::connection('vpeople')->hasTable('organization_positions')
            && Schema::connection('vpeople')->hasTable('job_titles')
            && Schema::connection('vpeople')->hasTable('job_levels')
        ) {
            $masterQuery = DB::connection('vpeople')
                ->table('organization_positions')
                ->join('job_titles', 'organization_positions.job_title_id', '=', 'job_titles.id')
                ->join('job_levels as default_levels', 'job_titles.job_level_id', '=', 'default_levels.id')
                ->leftJoin('job_levels as override_levels', 'organization_positions.job_level_id', '=', 'override_levels.id')
                ->select([
                    'job_titles.id',
                    'job_titles.name',
                    'job_titles.name_zh',
                    DB::raw('COALESCE(override_levels.code, default_levels.code) as level_code'),
                    DB::raw('COALESCE(override_levels.rank, default_levels.rank) as level_rank'),
                ])
                ->where('organization_positions.is_active', true)
                ->where('job_titles.is_active', true)
                ->where(function ($query) {
                    $query->whereNull('organization_positions.effective_from')
                        ->orWhereDate('organization_positions.effective_from', '<=', today());
                })
                ->where(function ($query) {
                    $query->whereNull('organization_positions.effective_until')
                        ->orWhereDate('organization_positions.effective_until', '>=', today());
                });

            $divisionId
                ? $masterQuery->where('organization_positions.divisi_id', $divisionId)
                : $masterQuery->where('organization_positions.departemen_id', $departmentId);

            return $masterQuery
                ->distinct()
                ->orderBy('job_titles.name')
                ->limit(500)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => (string) $item->id,
                        'name' => trim($item->name . ($item->name_zh ? ' ' . $item->name_zh : '')),
                        'level_code' => $item->level_code,
                        'level_rank' => (int) $item->level_rank,
                    ];
                })
                ->toArray();
        }

        return [];
    }

    public function positions(?string $departmentId, ?string $divisionId): array
    {
        $departmentId = $this->cleanId($departmentId);
        $divisionId = $this->cleanId($divisionId);

        if (!$departmentId && !$divisionId) {
            return [];
        }

        if (
            Schema::connection('vpeople')->hasTable('organization_positions')
            && Schema::connection('vpeople')->hasColumn('organization_positions', 'position_name')
        ) {
            $masterQuery = DB::connection('vpeople')
                ->table('organization_positions')
                ->select(['id', 'code', 'position_name'])
                ->where('is_active', true)
                ->whereNotNull('position_name')
                ->where('position_name', '<>', '')
                ->where(function ($query) {
                    $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', today());
                })
                ->where(function ($query) {
                    $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', today());
                });

            $divisionId
                ? $masterQuery->where('divisi_id', $divisionId)
                : $masterQuery->where('departemen_id', $departmentId);

            $masterPositions = $masterQuery
                ->orderBy('sort_order')
                ->orderBy('position_name')
                ->limit(500)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => (string) $item->id,
                        'name' => trim($item->position_name),
                        'label' => trim($item->position_name . ' (' . $item->code . ')'),
                    ];
                })
                ->toArray();

            if ($masterPositions) {
                return $masterPositions;
            }
        }

        $query = DB::connection('vpeople')
            ->table('employees')
            ->selectRaw("DISTINCT TRIM(posisi) as name")
            ->where('status_resign', VPeopleService::ACTIVE_RESIGN_STATUS)
            ->whereNotNull('posisi')
            ->whereRaw("TRIM(posisi) <> ''");

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
                return ['id' => null, 'name' => $item->name, 'label' => $item->name];
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

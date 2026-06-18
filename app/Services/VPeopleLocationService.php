<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VPeopleLocationService
{
    public function provinces(): array
    {
        return DB::connection('vpeople')
            ->table('master_provinsi')
            ->selectRaw('CAST(id AS CHAR) as id, provinsi as name')
            ->orderBy('provinsi')
            ->get()
            ->map(function ($item) {
                return $this->option($item->id, $item->name);
            })
            ->toArray();
    }

    public function regencies(?string $provinceId): array
    {
        $provinceId = $this->cleanId($provinceId);

        if (!$provinceId) {
            return [];
        }

        return DB::connection('vpeople')
            ->table('master_kabupaten')
            ->selectRaw('CAST(id AS CHAR) as id, kabupaten as name')
            ->where('id_provinsi', $provinceId)
            ->orderBy('kabupaten')
            ->get()
            ->map(function ($item) {
                return $this->option($item->id, $item->name);
            })
            ->toArray();
    }

    public function districts(?string $regencyId): array
    {
        $regencyId = $this->cleanId($regencyId);

        if (!$regencyId) {
            return [];
        }

        return DB::connection('vpeople')
            ->table('master_kecamatan')
            ->selectRaw('CAST(id AS CHAR) as id, kecamatan as name')
            ->where('id_kabupaten', $regencyId)
            ->orderBy('kecamatan')
            ->get()
            ->map(function ($item) {
                return $this->option($item->id, $item->name);
            })
            ->toArray();
    }

    public function villages(?string $districtId): array
    {
        $districtId = $this->cleanId($districtId);

        if (!$districtId) {
            return [];
        }

        return DB::connection('vpeople')
            ->table('master_kelurahan')
            ->selectRaw('CAST(id AS CHAR) as id, kelurahan as name')
            ->where('id_kecamatan', $districtId)
            ->orderBy('kelurahan')
            ->get()
            ->map(function ($item) {
                return $this->option($item->id, $item->name);
            })
            ->toArray();
    }

    public function resolveSelection(array $input): array
    {
        $provinceId = $this->cleanId($input['province_id'] ?? null);
        $regencyId = $this->cleanId($input['regency_id'] ?? null);
        $districtId = $this->cleanId($input['district_id'] ?? null);
        $villageId = $this->cleanId($input['village_id'] ?? null);

        $resolved = [
            'province_id' => null,
            'province_name' => null,
            'regency_id' => null,
            'regency_name' => null,
            'district_id' => null,
            'district_name' => null,
            'village_id' => null,
            'village_name' => null,
        ];

        if (!$provinceId && !$regencyId && !$districtId && !$villageId) {
            return $resolved;
        }

        $errors = [];

        $province = $this->province($provinceId);

        if (!$province) {
            $errors['province_id'] = 'Provinsi tidak valid.';
        } else {
            $resolved['province_id'] = $province['id'];
            $resolved['province_name'] = $province['name'];
        }

        if ($regencyId) {
            $regency = $province ? $this->regency($regencyId, $province['id']) : null;

            if (!$regency) {
                $errors['regency_id'] = 'Kabupaten/kota tidak sesuai dengan provinsi.';
            } else {
                $resolved['regency_id'] = $regency['id'];
                $resolved['regency_name'] = $regency['name'];
            }
        }

        if ($districtId) {
            $district = $resolved['regency_id'] ? $this->district($districtId, $resolved['regency_id']) : null;

            if (!$district) {
                $errors['district_id'] = 'Kecamatan tidak sesuai dengan kabupaten/kota.';
            } else {
                $resolved['district_id'] = $district['id'];
                $resolved['district_name'] = $district['name'];
            }
        }

        if ($villageId) {
            $village = $resolved['district_id'] ? $this->village($villageId, $resolved['district_id']) : null;

            if (!$village) {
                $errors['village_id'] = 'Kelurahan/desa tidak sesuai dengan kecamatan.';
            } else {
                $resolved['village_id'] = $village['id'];
                $resolved['village_name'] = $village['name'];
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $resolved;
    }

    private function province(?string $provinceId): ?array
    {
        if (!$provinceId) {
            return null;
        }

        $item = DB::connection('vpeople')
            ->table('master_provinsi')
            ->selectRaw('CAST(id AS CHAR) as id, provinsi as name')
            ->where('id', $provinceId)
            ->first();

        return $item ? $this->option($item->id, $item->name) : null;
    }

    private function regency(string $regencyId, string $provinceId): ?array
    {
        $item = DB::connection('vpeople')
            ->table('master_kabupaten')
            ->selectRaw('CAST(id AS CHAR) as id, kabupaten as name')
            ->where('id', $regencyId)
            ->where('id_provinsi', $provinceId)
            ->first();

        return $item ? $this->option($item->id, $item->name) : null;
    }

    private function district(string $districtId, string $regencyId): ?array
    {
        $item = DB::connection('vpeople')
            ->table('master_kecamatan')
            ->selectRaw('CAST(id AS CHAR) as id, kecamatan as name')
            ->where('id', $districtId)
            ->where('id_kabupaten', $regencyId)
            ->first();

        return $item ? $this->option($item->id, $item->name) : null;
    }

    private function village(string $villageId, string $districtId): ?array
    {
        $item = DB::connection('vpeople')
            ->table('master_kelurahan')
            ->selectRaw('CAST(id AS CHAR) as id, kelurahan as name')
            ->where('id', $villageId)
            ->where('id_kecamatan', $districtId)
            ->first();

        return $item ? $this->option($item->id, $item->name) : null;
    }

    private function cleanId($value): ?string
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

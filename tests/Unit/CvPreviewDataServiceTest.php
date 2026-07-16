<?php

namespace Tests\Unit;

use App\Models\CvProfile;
use App\Models\User;
use App\Services\CvPreviewDataService;
use Tests\TestCase;

class CvPreviewDataServiceTest extends TestCase
{
    public function test_it_returns_only_the_domicile_address_when_toggle_is_enabled()
    {
        $profile = $this->profile([
            'address' => "Jl. Domisili No. 10\nBlok A",
            'ktp_address' => 'Jl. KTP No. 20',
            'domicile_same_as_ktp' => true,
            'village_name' => 'Wawatu',
            'district_name' => 'Moramo',
            'regency_name' => 'Konawe Selatan',
            'province_name' => 'Sulawesi Tenggara',
        ]);

        $data = (new CvPreviewDataService())->build($profile, new User());

        $this->assertSame([
            [
                'label' => 'Alamat Domisili',
                'value' => "Jl. Domisili No. 10\nBlok A\nWawatu, Moramo, Konawe Selatan, Sulawesi Tenggara",
            ],
        ], $data['addresses']);
    }

    public function test_it_returns_domicile_and_distinct_ktp_addresses()
    {
        $profile = $this->profile([
            'address' => "Jl. Domisili No. 10\nBlok A",
            'ktp_address' => "Jl. KTP No. 20\nRT 02",
            'domicile_same_as_ktp' => false,
            'village_name' => 'Wawatu',
            'district_name' => 'Moramo',
            'regency_name' => 'Konawe Selatan',
            'province_name' => 'Sulawesi Tenggara',
        ]);

        $data = (new CvPreviewDataService())->build($profile, new User());

        $this->assertSame([
            [
                'label' => 'Alamat Domisili',
                'value' => "Jl. Domisili No. 10\nBlok A\nWawatu, Moramo, Konawe Selatan, Sulawesi Tenggara",
            ],
            [
                'label' => 'Alamat Sesuai KTP',
                'value' => "Jl. KTP No. 20\nRT 02",
            ],
        ], $data['addresses']);
    }

    public function test_it_does_not_duplicate_legacy_equivalent_addresses_with_different_case_or_spacing()
    {
        $profile = $this->profile([
            'address' => "Jl. Merdeka  No. 10\nRT 02",
            'ktp_address' => "  jl. merdeka no. 10  rt 02 ",
            'domicile_same_as_ktp' => false,
            'village_name' => 'Wawatu',
            'district_name' => 'Moramo',
        ]);

        $data = (new CvPreviewDataService())->build($profile, new User());

        $this->assertSame([
            [
                'label' => 'Alamat Domisili',
                'value' => "Jl. Merdeka No. 10\nRT 02\nWawatu, Moramo",
            ],
        ], $data['addresses']);
    }

    private function profile(array $attributes): CvProfile
    {
        $profile = new CvProfile($attributes);

        foreach (['experiences', 'educations', 'certifications', 'languages', 'projects', 'organizations'] as $relation) {
            $profile->setRelation($relation, collect());
        }

        return $profile;
    }
}

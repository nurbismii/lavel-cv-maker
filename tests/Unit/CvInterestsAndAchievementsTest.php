<?php

namespace Tests\Unit;

use App\Http\Requests\SaveCvProfileRequest;
use App\Models\CvProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CvInterestsAndAchievementsTest extends TestCase
{
    public function test_unknown_interest_category_is_rejected(): void
    {
        $validator = $this->validator([
            'hobbies' => ['unknown' => 'Tidak valid'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('hobbies', $validator->errors()->toArray());
    }

    public function test_started_achievement_requires_all_main_fields(): void
    {
        $validator = $this->validator([
            'achievements' => [[
                'field' => 'sports',
                'achievement_type' => 'Turnamen Futsal',
            ]],
        ]);

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('achievements.0.rank', $errors);
        $this->assertArrayHasKey('achievements.0.level', $errors);
        $this->assertArrayHasKey('achievements.0.period', $errors);
    }

    public function test_achievement_can_be_left_empty(): void
    {
        $validator = $this->validator([
            'achievements' => [],
        ]);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray()));
    }

    public function test_complete_interests_and_achievement_are_valid(): void
    {
        $validator = $this->validator([
            'hobbies' => ['sports' => 'Futsal', 'other' => 'Berkebun'],
            'talents' => ['arts' => 'Melukis'],
            'achievements' => [[
                'field' => 'sports',
                'achievement_type' => 'Turnamen Futsal',
                'rank' => 'Juara 1',
                'level' => 'province',
                'period' => '2025-05',
            ]],
        ]);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray()));
    }

    public function test_new_wizard_step_and_repeatable_template_are_available(): void
    {
        $view = file_get_contents(resource_path('views/cv/edit.blade.php'));
        $templates = file_get_contents(resource_path('views/cv/partials/templates.blade.php'));
        $script = file_get_contents(public_path('js/cv-form.js'));

        $this->assertStringContainsString('data-wizard-step-target="interests"', $view);
        $this->assertStringContainsString('data-wizard-panel="interests"', $view);
        $this->assertStringContainsString('data-wizard-total>9', $view);
        $this->assertStringContainsString('name="hobbies[{{ $value }}]"', $view);
        $this->assertStringContainsString('name="talents[{{ $value }}]"', $view);
        $this->assertStringContainsString('data-repeat-template="achievements"', $templates);
        $this->assertStringContainsString('data-repeat-allow-empty', $view);
        $this->assertStringContainsString('Bagian ini boleh dikosongkan.', $view);
        $this->assertStringContainsString('function validateInterestsWizardPanel', $script);
        $this->assertStringContainsString("if (panelKey === 'interests')", $script);
        $this->assertStringContainsString("renderLivePreviewSection('Minat & Prestasi'", $script);
    }

    private function validator(array $data)
    {
        $request = SaveCvProfileRequest::create('/cv/draft', 'POST', array_merge([
            'full_name' => 'Karyawan Test',
            'birth_date' => '1990-01-01',
            'ktp_number' => '7401010101010101',
            'family_card_number' => '7401010101010102',
            'bank_account_number' => '1234567890',
            'npwp_number' => '123456789012345',
            'birth_place' => 'Kendari',
            'gender' => 'L',
            'height_cm' => '170',
            'weight_kg' => '65',
            'blood_type' => CvProfile::BLOOD_TYPES[0],
            'religion' => CvProfile::RELIGIONS[0],
            'marital_status' => 'Belum Kawin',
            'mother_name' => 'Ibu Test',
            'phone' => '081234567890',
            'email' => 'karyawan@example.com',
            'ktp_address' => 'Jl. KTP No. 10',
            'rt' => '007',
            'rw' => '012',
            'province_id' => '74',
            'regency_id' => '7401',
            'district_id' => '7401010',
            'village_id' => '7401010001',
            'address' => 'Jl. Domisili No. 10',
            'work_area' => 'VDNI',
            'department' => 'Human Resources',
            'division' => 'HR Operations',
            'job_title' => 'STAFF',
            'position' => 'HR Staff',
            'profile_summary' => 'Profesional HR berpengalaman.',
            'technical_skills' => 'Microsoft Excel',
            'experiences' => [[
                'position' => 'HR Staff',
                'company' => 'PT VDNI',
                'department' => 'Human Resources',
                'division' => 'HR Operations',
                'start_month' => '2020-01',
                'is_current' => '1',
                'responsibilities' => 'Mengelola administrasi karyawan',
            ]],
            'educations' => [[
                'level' => 'S1',
                'institution' => 'Universitas Test',
                'major' => 'Manajemen',
                'graduation_year' => '2019',
            ]],
            'emergency_contacts' => [[
                'phone' => '081234567890',
                'name' => 'Siti Santoso',
                'relationship' => 'Orang Tua',
            ]],
        ], $data));
        $request->setContainer($this->app);
        $request->files->set('photo', UploadedFile::fake()->image('photo.jpg', 300, 400));
        $request->files->set('documents', [
            'ktp' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
            'family_card' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
            'npwp' => UploadedFile::fake()->create('npwp.pdf', 100, 'application/pdf'),
            'diploma' => [UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf')],
        ]);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        $request->withValidator($validator);

        return $validator;
    }
}

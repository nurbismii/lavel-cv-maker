<?php

namespace Tests\Unit;

use App\Http\Requests\SaveCvProfileRequest;
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
            'email' => 'karyawan@example.com',
        ], $data));
        $request->setContainer($this->app);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        $request->withValidator($validator);

        return $validator;
    }
}

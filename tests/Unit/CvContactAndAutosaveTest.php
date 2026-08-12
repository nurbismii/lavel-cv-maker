<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CvContactAndAutosaveTest extends TestCase
{
    public function test_cv_form_contains_rt_rw_phone_normalization_and_step_autosave(): void
    {
        $view = file_get_contents(__DIR__ . '/../../resources/views/cv/edit.blade.php');
        $script = file_get_contents(__DIR__ . '/../../public/js/cv-form.js');

        $this->assertStringContainsString('name="rt"', $view);
        $this->assertStringContainsString('name="rw"', $view);
        $this->assertStringContainsString('data-indonesian-phone', $view);
        $this->assertStringContainsString('data-autosave-url', $view);
        $this->assertStringContainsString("{ selector: '[name=\"rt\"]', label: 'RT' }", $script);
        $this->assertStringContainsString("{ selector: '[name=\"rw\"]', label: 'RW' }", $script);
        $this->assertStringContainsString('function normalizeIndonesianPhone', $script);
        $this->assertStringContainsString('function autosaveWizardStep', $script);
        $this->assertStringContainsString("setWizardStep(index, options);", $script);
    }

    public function test_pdf_photo_uses_a_fixed_four_by_five_frame(): void
    {
        $pdf = file_get_contents(__DIR__ . '/../../resources/views/cv/pdf.blade.php');
        $template = file_get_contents(__DIR__ . '/../../resources/views/cv/templates/hris.blade.php');

        $this->assertStringContainsString('width: 32mm;', $pdf);
        $this->assertStringContainsString('height: 40mm;', $pdf);
        $this->assertStringContainsString('position: absolute;', $pdf);
        $this->assertStringContainsString('right: 0;', $pdf);
        $this->assertStringContainsString('cv-output-photo-cell', $template);
    }
}

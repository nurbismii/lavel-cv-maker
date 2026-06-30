<?php

namespace Tests\Unit;

use Tests\TestCase;

class CvFormDesignCssTest extends TestCase
{
    private function css(): string
    {
        return file_get_contents(public_path('css/app.css'));
    }

    public function test_cv_form_uses_compact_professional_design_tokens()
    {
        $css = $this->css();

        $this->assertStringContainsString('--app-radius: 10px;', $css);
        $this->assertStringContainsString('--app-shadow: 0 1px 2px rgba(15, 23, 42, .05), 0 8px 18px rgba(15, 23, 42, .04);', $css);
        $this->assertStringContainsString('--app-surface-subtle: #f8fafc;', $css);
    }

    public function test_cv_form_mobile_actions_prevent_text_overflow()
    {
        $css = $this->css();

        $this->assertStringContainsString('@media (max-width: 768px)', $css);
        $this->assertStringContainsString('.cv-wizard-action-group .btn', $css);
        $this->assertStringContainsString('white-space: normal;', $css);
        $this->assertStringContainsString('min-height: 44px;', $css);
    }
}

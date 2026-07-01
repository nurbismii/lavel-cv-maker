<?php

namespace Tests\Unit;

use Tests\TestCase;

class CvWizardStepScrollTest extends TestCase
{
    private function script(): string
    {
        return file_get_contents(public_path('js/cv-form.js'));
    }

    public function test_active_wizard_step_is_scrolled_into_view_when_step_changes()
    {
        $script = $this->script();
        $setWizardStepSource = $this->setWizardStepSource($script);

        $this->assertStringContainsString('function scrollActiveWizardStepIntoView', $script);
        $this->assertStringContainsString('function scheduleActiveWizardStepScroll', $script);
        $this->assertStringContainsString("window.matchMedia('(prefers-reduced-motion: reduce)')", $script);
        $this->assertStringContainsString('rail.scrollTo({', $script);
        $this->assertStringContainsString('rail.scrollLeft = targetLeft;', $script);
        $this->assertStringContainsString('scheduleActiveWizardStepScroll(elements, activeKey);', $setWizardStepSource);
    }

    private function setWizardStepSource(string $script): string
    {
        $start = strpos($script, 'function setWizardStep');
        $end = strpos($script, 'function initWizard', $start);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($script, $start, $end - $start);
    }
}

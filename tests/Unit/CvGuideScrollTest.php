<?php

namespace Tests\Unit;

use Tests\TestCase;

class CvGuideScrollTest extends TestCase
{
    private function script(): string
    {
        return file_get_contents(public_path('js/cv-form.js'));
    }

    public function test_guide_highlight_scrolls_target_into_view_immediately_before_popup_opens()
    {
        $script = $this->script();

        $this->assertStringContainsString('function scrollGuideTargetIntoView', $script);
        $this->assertStringContainsString("document.documentElement.style.scrollBehavior = 'auto';", $script);
        $this->assertStringContainsString("document.body.style.scrollBehavior = 'auto';", $script);
        $this->assertStringContainsString('document.documentElement.style.scrollBehavior = previousHtmlScrollBehavior;', $script);
        $this->assertStringContainsString('document.body.style.scrollBehavior = previousBodyScrollBehavior;', $script);
        $this->assertStringContainsString('document.documentElement.scrollTop = scrollTop;', $script);
        $this->assertStringContainsString('document.body.scrollTop = scrollTop;', $script);
        $this->assertStringContainsString('window.scrollTo(0, scrollTop);', $script);
        $this->assertStringNotContainsString('behavior:', $this->highlightGuideTargetSource($script));
    }

    public function test_guide_rescrolls_target_after_sweetalert_opens()
    {
        $script = $this->script();
        $didOpenSource = $this->didOpenSource($script);

        $this->assertStringContainsString('scheduleGuideTargetScroll(target, popupPosition);', $didOpenSource);
    }

    private function highlightGuideTargetSource(string $script): string
    {
        $start = strpos($script, 'function highlightGuideTarget');
        $end = strpos($script, 'function finishGuide', $start);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($script, $start, $end - $start);
    }

    private function didOpenSource(string $script): string
    {
        $start = strpos($script, 'didOpen: function ()');
        $end = strpos($script, 'willClose: function ()', $start);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($script, $start, $end - $start);
    }
}

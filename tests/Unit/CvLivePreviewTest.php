<?php

namespace Tests\Unit;

use Tests\TestCase;

class CvLivePreviewTest extends TestCase
{
    private function editView(): string
    {
        return file_get_contents(resource_path('views/cv/edit.blade.php'));
    }

    private function script(): string
    {
        return file_get_contents(public_path('js/cv-form.js'));
    }

    private function css(): string
    {
        return file_get_contents(public_path('css/app.css'));
    }

    public function test_cv_edit_page_has_live_preview_active_by_default()
    {
        $view = $this->editView();

        $this->assertStringContainsString('data-live-preview-toggle', $view);
        $this->assertStringContainsString('id="cvLivePreviewPanel"', $view);
        $this->assertStringContainsString('data-live-preview-panel', $view);
        $this->assertStringContainsString('data-live-preview-output', $view);
        $this->assertStringContainsString('data-live-preview-status', $view);
        $this->assertStringContainsString('data-live-preview-nik', $view);
        $this->assertStringContainsString('aria-expanded="true"', $view);
        $this->assertStringContainsString('<span data-live-preview-toggle-text>Sembunyikan Preview</span>', $view);
        $this->assertStringContainsString('<span class="badge badge-vpeople" data-live-preview-status>Aktif</span>', $view);
        $this->assertStringNotContainsString('data-live-preview-panel hidden', $view);
    }

    public function test_live_preview_script_collects_and_renders_form_data_without_auto_save()
    {
        $script = $this->script();

        foreach ([
            'function initLivePreview',
            'function toggleLivePreview',
            'function scheduleLivePreviewUpdate',
            'function collectLivePreviewData',
            'function renderLivePreview',
            'function renderLivePreviewSection',
            'function livePreviewRows',
            'function cleanLivePreviewMultilineText',
            'function escapeHtml',
            'livePreviewEnabled = true',
            'updateLivePreview();',
            'data-live-preview-output',
        ] as $expected) {
            $this->assertStringContainsString($expected, $script);
        }

        $this->assertStringNotContainsString('fetch(', $this->livePreviewSource($script));
        $this->assertStringContainsString("fieldName === 'responsibilities'", $script);
    }

    public function test_live_preview_has_scoped_responsive_styles()
    {
        $css = $this->css();

        foreach ([
            '.cv-live-preview-panel',
            '.cv-live-preview-shell',
            '.cv-live-preview-paper',
            '.cv-live-preview-empty',
            '@media (max-width: 991.98px)',
        ] as $expected) {
            $this->assertStringContainsString($expected, $css);
        }
    }

    public function test_cv_edit_page_uses_wider_preview_column_and_compact_completion_summary()
    {
        $view = $this->editView();
        $css = $this->css();

        $this->assertStringContainsString('class="col-lg-7"', $view);
        $this->assertStringContainsString('class="col-lg-5"', $view);
        $this->assertStringContainsString('cv-completion-summary', $view);
        $this->assertStringContainsString('cv-completion-missing', $view);
        $this->assertStringNotContainsString('class="cv-checklist"', $view);
        $this->assertStringNotContainsString('<div class="alert alert-warning mt-4 mb-0">', $view);

        $this->assertStringContainsString('.cv-completion-summary', $css);
        $this->assertStringContainsString('.cv-completion-missing', $css);
        $this->assertStringContainsString('.cv-live-preview-shell', $css);
        $this->assertStringContainsString('max-height: 74vh;', $css);
    }

    private function livePreviewSource(string $script): string
    {
        $start = strpos($script, 'function initLivePreview');
        $end = strpos($script, 'function photoElements', $start);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($script, $start, $end - $start);
    }
}

<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CvPhotoCropMobileTest extends TestCase
{
    public function test_photo_cropper_is_initialized_responsively_after_modal_is_visible(): void
    {
        $view = file_get_contents(__DIR__ . '/../../resources/views/cv/edit.blade.php');
        $script = file_get_contents(__DIR__ . '/../../public/js/cv-form.js');
        $css = file_get_contents(__DIR__ . '/../../public/css/app.css');

        $this->assertStringContainsString('modal-fullscreen-sm-down', $view);
        $this->assertStringContainsString('function initializePhotoCropper', $script);
        $this->assertStringContainsString("addEventListener('shown.bs.modal'", $script);
        $this->assertStringNotContainsString('minContainerWidth: 720', $script);
        $this->assertStringNotContainsString('minContainerHeight: 520', $script);
        $this->assertStringContainsString("matchMedia('(max-width: 575.98px)')", $script);
        $this->assertStringContainsString('@media (max-width: 575.98px)', $css);
        $this->assertStringContainsString('touch-action: none;', $css);
    }
}

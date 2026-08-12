<?php

namespace Tests\Unit;

use Tests\TestCase;

class CvMaritalDocumentVisibilityTest extends TestCase
{
    public function test_marital_document_fields_follow_selected_marital_status(): void
    {
        $view = file_get_contents(resource_path('views/cv/edit.blade.php'));
        $script = file_get_contents(public_path('js/cv-form.js'));

        $this->assertStringContainsString('data-marital-document-section', $view);
        $this->assertStringContainsString('data-marital-document-type="marriage"', $view);
        $this->assertStringContainsString('data-marital-document-type="divorce"', $view);
        $this->assertStringContainsString("'Dokumen Status Cerai'", $view);

        $this->assertStringContainsString('function maritalDocumentType', $script);
        $this->assertStringContainsString('function syncMaritalDocument', $script);
        $this->assertStringContainsString("return 'divorce';", $script);
        $this->assertStringContainsString("return 'marriage';", $script);
        $this->assertStringContainsString('section.hidden = !activeType;', $script);
        $this->assertStringContainsString('field.disabled = !isActive;', $script);
    }
}

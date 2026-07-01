<?php

namespace Tests\Unit;

use Tests\TestCase;

class CvWizardRequiredStepValidationTest extends TestCase
{
    private function script(): string
    {
        return file_get_contents(public_path('js/cv-form.js'));
    }

    private function viewFile(string $path): string
    {
        return file_get_contents(resource_path('views/' . $path));
    }

    public function test_wizard_validates_repeatable_extra_and_document_steps()
    {
        $script = $this->script();

        $this->assertStringContainsString('function validateExtrasWizardPanel', $script);
        $this->assertStringContainsString('function validateDocumentsWizardPanel', $script);
        $this->assertStringContainsString("if (panelKey === 'extras')", $script);
        $this->assertStringContainsString("if (panelKey === 'documents' && !(options || {}).skipDocuments)", $script);
        $this->assertStringContainsString("validateRepeatRows(panel, 'languages'", $script);
        $this->assertStringContainsString("validateRepeatRows(panel, 'projects'", $script);
        $this->assertStringContainsString("validateRepeatRows(panel, 'organizations'", $script);
        $this->assertStringContainsString('data-document-required', $this->viewFile('cv/edit.blade.php'));
        $this->assertStringContainsString('data-document-has-file', $this->viewFile('cv/edit.blade.php'));
    }

    public function test_preview_submit_validates_cv_steps_but_skips_missing_documents()
    {
        $script = $this->script();
        $edit = $this->viewFile('cv/edit.blade.php');

        $this->assertStringContainsString('function validateWizardSubmit', $script);
        $this->assertStringContainsString('validateWizardPath(elements, elements.panels.length', $script);
        $this->assertStringContainsString('function submitterSkipsDocumentsValidation', $script);
        $this->assertStringContainsString('skipDocuments', $script);
        $this->assertStringContainsString("event.target.id !== 'cvForm'", $script);
        $this->assertStringContainsString('data-wizard-submit-skip-validation', $edit);
        $this->assertStringContainsString('data-wizard-submit-skip-documents', $edit);
        $this->assertStringContainsString('Simpan Draft', $edit);
    }

    public function test_existing_document_action_uses_view_label_instead_of_download()
    {
        $edit = $this->viewFile('cv/edit.blade.php');

        $this->assertStringContainsString('bi-eye me-1"></i> Lihat', $edit);
        $this->assertStringNotContainsString('bi-download me-1"></i> Download', $edit);
    }

    public function test_cv_form_marks_blocking_fields_as_required()
    {
        $edit = $this->viewFile('cv/edit.blade.php');
        $education = $this->viewFile('cv/partials/education-row.blade.php');
        $experience = $this->viewFile('cv/partials/experience-row.blade.php');

        foreach ([
            'Nama Lengkap',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Status Pernikahan',
            'No. HP',
            'Email',
            'Alamat Domisili Lengkap',
        ] as $label) {
            $this->assertRequiredLabel($edit, $label);
        }

        $this->assertStringContainsString('<label class="form-label cv-required-label" for="profile_summary">', $edit);
        $this->assertStringContainsString('Ringkasan Profil', $edit);
        $this->assertStringContainsString('<textarea id="profile_summary" name="profile_summary"', $edit);

        foreach (['Jenjang', 'Nama Institusi', 'Lulus'] as $label) {
            $this->assertRequiredLabel($education, $label);
        }

        foreach (['Nama Posisi/Jabatan', 'Nama Perusahaan', 'Mulai', 'Selesai', 'Job Description'] as $label) {
            $this->assertRequiredLabel($experience, $label);
        }
    }

    private function assertRequiredLabel(string $contents, string $label): void
    {
        $pattern = '/<label class="form-label[^"]*">\s*'
            . preg_quote($label, '/')
            . '\s*<span class="required-indicator" aria-hidden="true">\*<\/span>\s*'
            . '<span class="visually-hidden"> wajib diisi<\/span>\s*'
            . '<\/label>/u';

        $this->assertMatchesRegularExpression($pattern, $contents);
    }
}

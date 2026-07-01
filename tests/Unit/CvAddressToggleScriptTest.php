<?php

namespace Tests\Unit;

use Tests\TestCase;

class CvAddressToggleScriptTest extends TestCase
{
    private function script(): string
    {
        return file_get_contents(public_path('js/cv-form.js'));
    }

    public function test_domicile_address_toggle_syncs_from_ktp_address()
    {
        $script = $this->script();

        $this->assertStringContainsString('function syncDomicileAddressFields', $script);
        $this->assertStringContainsString('[data-domicile-same-toggle]', $script);
        $this->assertStringContainsString('[data-ktp-address]', $script);
        $this->assertStringContainsString('[data-domicile-address]', $script);
        $this->assertStringContainsString('domicileField.readOnly = useKtpAddress;', $script);
        $this->assertStringContainsString('domicileField.value = ktpField.value;', $script);
        $this->assertStringContainsString('syncDomicileAddressFields();', $script);
    }
}

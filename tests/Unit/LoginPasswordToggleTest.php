<?php

namespace Tests\Unit;

use Tests\TestCase;

class LoginPasswordToggleTest extends TestCase
{
    public function test_login_form_has_accessible_password_toggle(): void
    {
        $view = file_get_contents(resource_path('views/auth/login.blade.php'));

        $this->assertSame(1, substr_count($view, 'data-password-toggle'));
        $this->assertStringContainsString('data-password-target="password"', $view);
        $this->assertStringContainsString('aria-label="Tampilkan password"', $view);
        $this->assertStringContainsString('aria-pressed="false"', $view);
        $this->assertStringContainsString("asset('js/password-toggle.js')", $view);
    }
}

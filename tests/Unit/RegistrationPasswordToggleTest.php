<?php

namespace Tests\Unit;

use Tests\TestCase;

class RegistrationPasswordToggleTest extends TestCase
{
    public function test_registration_form_has_accessible_password_toggles(): void
    {
        $view = file_get_contents(resource_path('views/auth/register.blade.php'));
        $script = file_get_contents(public_path('js/password-toggle.js'));

        $this->assertSame(2, substr_count($view, 'data-password-toggle'));
        $this->assertStringContainsString('data-password-target="password"', $view);
        $this->assertStringContainsString('data-password-target="password_confirmation"', $view);
        $this->assertSame(2, substr_count($view, 'aria-pressed="false"'));
        $this->assertStringContainsString("input.type = showPassword ? 'text' : 'password';", $script);
        $this->assertStringContainsString("icon.classList.toggle('bi-eye-slash', showPassword);", $script);
    }
}

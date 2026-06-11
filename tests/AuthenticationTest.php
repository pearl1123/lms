<?php

/**
 * Authentication smoke tests — file/class presence.
 */
class AuthenticationTest extends PHPUnit\Framework\TestCase
{
    public function test_auth_controller_exists()
    {
        $this->assertFileExists(APPPATH . 'controllers/Auth.php');
    }

    public function test_password_reset_service_exists()
    {
        $this->assertFileExists(APPPATH . 'services/Password_reset_service.php');
    }

    public function test_login_view_uses_branding_helper()
    {
        $html = file_get_contents(APPPATH . 'views/auth/login.php');
        $this->assertStringContainsString('ka_branding_settings', $html);
    }
}

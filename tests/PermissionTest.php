<?php

class PermissionTest extends PHPUnit\Framework\TestCase
{
    public function test_ka_controller_exists()
    {
        $this->assertFileExists(APPPATH . 'core/KA_Controller.php');
    }

    public function test_certificates_controller_checks_view_permission()
    {
        $src = file_get_contents(APPPATH . 'controllers/Certificates.php');
        $this->assertStringContainsString("certificates.view", $src);
    }

    public function test_leaderboard_route_points_to_controller()
    {
        $routes = file_get_contents(APPPATH . 'config/routes.php');
        $this->assertStringContainsString("leaderboard'] = 'leaderboard/index'", $routes);
    }
}

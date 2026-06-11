<?php

class PointsTest extends PHPUnit\Framework\TestCase
{
    public function test_points_schema_migration_exists()
    {
        $this->assertFileExists(APPPATH . 'sql/migration_enhancement_phase6.sql');
        $sql = file_get_contents(APPPATH . 'sql/migration_enhancement_phase6.sql');
        $this->assertStringContainsString('user_points', $sql);
        $this->assertStringContainsString('points_transactions', $sql);
    }

    public function test_points_service_exists()
    {
        $this->assertFileExists(APPPATH . 'services/Points_service.php');
        $this->assertFileExists(APPPATH . 'services/Leaderboard_service.php');
    }
}

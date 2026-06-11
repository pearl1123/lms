<?php

class CourseTest extends PHPUnit\Framework\TestCase
{
    public function test_course_model_has_retake_reset()
    {
        $src = file_get_contents(APPPATH . 'models/course_model.php');
        $this->assertStringContainsString('reset_all_modules_for_retake', $src);
    }

    public function test_etd_retake_service_exists()
    {
        $this->assertFileExists(APPPATH . 'services/Etd_retake_service.php');
    }

    public function test_category_hierarchy_helpers_exist()
    {
        $src = file_get_contents(APPPATH . 'models/course_model.php');
        $this->assertStringContainsString('get_category_descendant_ids', $src);
    }
}

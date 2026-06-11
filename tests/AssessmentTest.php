<?php

class AssessmentTest extends PHPUnit\Framework\TestCase
{
    public function test_essay_submission_service_exists()
    {
        $this->assertFileExists(APPPATH . 'services/Essay_submission_service.php');
    }

    public function test_assessments_builder_supports_essay_mode()
    {
        $js = file_get_contents(FCPATH . 'assets/js/assessments.js');
        $this->assertStringContainsString('essay_response_mode', $js);
        $this->assertStringContainsString('mfEssayMode', $js);
    }

    public function test_attempt_order_model_exists()
    {
        $this->assertFileExists(APPPATH . 'models/Assessment_attempt_order_model.php');
    }
}

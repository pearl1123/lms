<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Awards points on domain events.
 */
class Points_listener {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('points_service');
    }

    public function onCourseCompleted(array $event)
    {
        $this->CI->points_service->on_course_completed(
            (int) ($event['user_id'] ?? 0),
            (int) ($event['course_id'] ?? 0)
        );
    }

    public function onCertificateIssued(array $event)
    {
        $this->CI->points_service->on_certificate_issued(
            (int) ($event['user_id'] ?? 0),
            (int) ($event['certificate_id'] ?? 0)
        );
    }

    public function onAssessmentPassed(array $event)
    {
        $this->CI->points_service->on_assessment_passed(
            (int) ($event['user_id'] ?? 0),
            (int) ($event['assessment_id'] ?? 0)
        );
    }
}

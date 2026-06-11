<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Award points for LMS achievements (idempotent per reference).
 */
class Points_service {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Points_model', 'points_model');
    }

    public function schema_ready()
    {
        return $this->CI->points_model->schema_ready();
    }

    /**
     * @param int $user_id
     * @param int $course_id
     */
    public function on_course_completed($user_id, $course_id)
    {
        if ( ! $this->schema_ready()) {
            return 0;
        }

        return $this->CI->points_model->award(
            (int) $user_id,
            'course_completion',
            'course',
            (int) $course_id,
            'Completed course #' . (int) $course_id
        );
    }

    /**
     * @param int $user_id
     * @param int $assessment_id
     */
    public function on_assessment_passed($user_id, $assessment_id)
    {
        if ( ! $this->schema_ready()) {
            return 0;
        }

        return $this->CI->points_model->award(
            (int) $user_id,
            'assessment_pass',
            'assessment',
            (int) $assessment_id,
            'Passed assessment #' . (int) $assessment_id
        );
    }

    /**
     * @param int $user_id
     * @param int $certificate_id
     */
    public function on_certificate_issued($user_id, $certificate_id)
    {
        if ( ! $this->schema_ready()) {
            return 0;
        }

        return $this->CI->points_model->award(
            (int) $user_id,
            'certificate_issued',
            'certificate',
            (int) $certificate_id,
            'Certificate #' . (int) $certificate_id
        );
    }

    /**
     * @param int $user_id
     * @return int
     */
    public function get_user_total($user_id)
    {
        return $this->CI->points_model->get_user_total((int) $user_id);
    }
}

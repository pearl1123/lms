<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ETD retake orchestration — full-course vs assessment-only reset.
 * Does not archive certificates (unlike enrollment reset).
 */
class Etd_retake_service {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Course_model', 'course_model');
        $this->CI->load->model('assessment_model');
    }

    /**
     * Reset all modules, checkpoints, and assessment attempts for a course retake.
     *
     * @param int $user_id
     * @param int $course_id
     * @return bool
     */
    public function execute_full_course_retake($user_id, $course_id)
    {
        $uid = (int) $user_id;
        $cid = (int) $course_id;
        if ($uid < 1 || $cid < 1) {
            return false;
        }

        log_message('debug', 'ETD full-course retake: user=' . $uid . ' course=' . $cid);

        $this->CI->course_model->reset_all_modules_for_retake($uid, $cid);
        $this->_clear_course_assessment_attempts($uid, $cid);

        return true;
    }

    /**
     * @param int $user_id
     * @param int $assessment_id
     */
    public function execute_assessment_only_retake($user_id, $assessment_id)
    {
        return (bool) $this->CI->assessment_model->clear_user_assessment_attempt((int) $user_id, (int) $assessment_id);
    }

    /**
     * @param int $user_id
     * @param int $course_id
     */
    private function _clear_course_assessment_attempts($user_id, $course_id)
    {
        $module_ids = $this->CI->db
            ->select('id')
            ->from('course_modules')
            ->where('course_id', (int) $course_id)
            ->where('archived', 0)
            ->get()
            ->result_array();
        $module_ids = array_map('intval', array_column($module_ids, 'id'));
        if ($module_ids === []) {
            return;
        }

        $assessments = $this->CI->db
            ->select('id')
            ->from('lib_assessments')
            ->where_in('module_id', $module_ids)
            ->where('archived', 0)
            ->get()
            ->result_array();

        foreach ($assessments as $row) {
            $aid = (int) ($row['id'] ?? 0);
            if ($aid > 0) {
                $this->CI->assessment_model->clear_user_assessment_attempt((int) $user_id, $aid);
            }
        }
    }
}

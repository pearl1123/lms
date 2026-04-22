<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Assessment domain service — single system for exams (pre/post) and video checkpoints.
 *
 * Unified concepts (PHP; database unchanged):
 *   - exam: DB type `pre` or `post` (multi-question assessments in Assessments UI).
 *   - video_checkpoint: DB type `checkpoint` with context `video` (course player).
 *
 * Models are registered on the CI super-object in {@see __construct()}.
 *
 * Loaded via {@see $this->load->library('assessment_service')} (see `application/libraries/Assessment_service.php` shim).
 */
class Assessment_service {

    /**
     * CodeIgniter super-object (`get_instance()`).
     *
     * @var \CI_Controller&object{
     *     course_model: \Course_model,
     *     assessment_model: \assessment_model,
     *     video_checkpoint_model: \Module_video_checkpoint_model
     * }
     */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('ka_format');
        $this->CI->load->model('Course_model', 'course_model');
        $this->CI->load->model('Module_video_checkpoint_model', 'video_checkpoint_model');
        $this->CI->load->model('assessment_model');
    }

    public function pass_threshold()
    {
        return ka_assessment_pass_threshold();
    }

    /**
     * Post-assessment / catalog pass: average score meets threshold and nothing pending review.
     *
     * @param array $result assessment_model::get_result shape
     */
    public function is_passing_assessment_result(array $result)
    {
        $pending = (int) ($result['pending'] ?? 0);
        $score   = (float) ($result['score'] ?? 0);

        return $pending === 0 && $score >= $this->pass_threshold();
    }

    /** DB `type` values for standard timed assessments */
    public static function db_type_is_exam($type)
    {
        return in_array((string) $type, ['pre', 'post'], true);
    }

    /** DB `type` for embedded video checkpoints */
    public static function db_type_is_video_checkpoint($type)
    {
        return (string) $type === 'checkpoint';
    }

    /**
     * Unified domain labels (no DB migration): timed exams vs embedded video checkpoints.
     *
     * @return string[]
     */
    public static function domain_types()
    {
        return ['exam', 'video_checkpoint'];
    }

    /**
     * Single bundle for the course module player: pre/post gating, post rows + pass/fail,
     * video checkpoint payload + URLs (Assessments controller — no Courses proxy).
     *
     * @param int    $user_id
     * @param string $user_role
     * @param int    $module_id
     * @param object $module course_modules row
     * @return array{
     *   pre_assessment:?object,
     *   pre_blocked:bool,
     *   post_assessments:object[],
     *   assessment_pass_threshold:float,
     *   youtube_video_id:?string,
     *   video_checkpoint_payload:array,
     *   video_checkpoint_passed_ids:int[],
     *   video_checkpoint_required_cnt:int,
     *   video_checkpoint_gate:bool,
     *   video_checkpoint_submit_url:string,
     *   video_checkpoint_json_url:string
     * }
     */
    public function course_module_play_context($user_id, $user_role, $module_id, $module)
    {
        $mid = (int) $module_id;

        $exam = $this->_course_module_exam_browse_context((int) $user_id, (string) $user_role, $mid);
        $vc   = $this->_course_module_video_checkpoint_context((int) $user_id, $mid, $module);

        return array_merge($exam, $vc, [
            'assessment_pass_threshold' => $this->pass_threshold(),
        ]);
    }

    /**
     * Pre/post exam summary for the module player (fetches module assessments here only).
     *
     * @return array{pre_assessment:?object,pre_blocked:bool,post_assessments:object[]}
     */
    private function _course_module_exam_browse_context($user_id, $user_role, $module_id)
    {
        $uid  = (int) $user_id;
        $role = strtolower((string) $user_role);
        $mid  = (int) $module_id;

        $pre_list = $this->CI->course_model->get_assessments($mid, 'pre');
        $post_raw = $this->CI->course_model->get_assessments($mid, 'post');

        $pre_assessment = ! empty($pre_list) ? $pre_list[0] : null;
        $pre_blocked    = false;
        if ($pre_assessment && $role === 'employee') {
            $pre_blocked = ! $this->CI->assessment_model->has_answered($uid, (int) $pre_assessment->id);
        }

        $post_assessments = [];
        foreach ($post_raw as $pa) {
            $aid      = (int) $pa->id;
            $has_done = $this->CI->assessment_model->has_answered($uid, $aid);
            $result   = $has_done
                ? $this->CI->assessment_model->get_result($uid, $aid)
                : ['score' => 0, 'scored' => 0, 'total' => 0, 'pending' => 0];
            $pa->has_done = $has_done;
            $pa->result   = $result;
            $pa->passed   = $has_done && $this->is_passing_assessment_result($result);
            $post_assessments[] = $pa;
        }

        return [
            'pre_assessment'   => $pre_assessment,
            'pre_blocked'      => $pre_blocked,
            'post_assessments' => $post_assessments,
        ];
    }

    /**
     * @return array{
     *   youtube_video_id:?string,
     *   video_checkpoint_payload:array,
     *   video_checkpoint_passed_ids:int[],
     *   video_checkpoint_required_cnt:int,
     *   video_checkpoint_gate:bool,
     *   video_checkpoint_submit_url:string,
     *   video_checkpoint_json_url:string
     * }
     */
    private function _course_module_video_checkpoint_context($user_id, $module_id, $module)
    {
        $vm  = $this->CI->video_checkpoint_model;
        $mid = (int) $module_id;
        $yid = Module_video_checkpoint_model::extract_youtube_video_id((string) ($module->content_path ?? ''));

        $payload      = [];
        $passed_ids   = [];
        $required_cnt = 0;
        $gate         = false;

        if (($module->content_type ?? '') === 'video' && $yid !== null) {
            $required_cnt = $vm->count_required_checkpoints($mid);
            $gate         = $required_cnt > 0;
            $payload      = $vm->get_public_checkpoints_payload($mid);
            $passed_ids   = $vm->get_passed_checkpoint_assessment_ids((int) $user_id, $mid);
        }

        return [
            'youtube_video_id'              => $yid,
            'video_checkpoint_payload'      => $payload,
            'video_checkpoint_passed_ids'   => $passed_ids,
            'video_checkpoint_required_cnt' => $required_cnt,
            'video_checkpoint_gate'         => $gate,
            'video_checkpoint_submit_url'   => base_url('index.php/assessments/video_checkpoint_submit'),
            'video_checkpoint_json_url'     => base_url('index.php/assessments/video_checkpoints/' . $mid),
        ];
    }

    /**
     * Whether the user may mark the module complete (required video checkpoints satisfied).
     */
    public function employee_may_complete_module_with_video_checkpoints($user_id, $module)
    {
        $vm = $this->CI->video_checkpoint_model;
        if ( ! $vm->is_youtube_module($module)) {
            return true;
        }
        $mid = (int) $module->id;
        if ( ! $vm->has_required_checkpoints($mid)) {
            return true;
        }

        return $vm->user_passed_all_required((int) $user_id, $mid);
    }
}

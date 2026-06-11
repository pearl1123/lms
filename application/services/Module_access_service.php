<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Module access gates — sequential progression and custom prerequisites.
 */
class Module_access_service {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Course_model', 'course_model');
    }

    /**
     * @param int $user_id
     * @param int $module_id
     * @return array{allowed:bool,blocked_by_module_id:?int,message:string}
     */
    public function can_access_module($user_id, $module_id)
    {
        $uid = (int) $user_id;
        $mid = (int) $module_id;
        $ok  = ['allowed' => true, 'blocked_by_module_id' => null, 'message' => ''];

        if ($uid < 1 || $mid < 1) {
            return ['allowed' => false, 'blocked_by_module_id' => null, 'message' => 'Invalid module.'];
        }

        $module = $this->CI->course_model->get_module($mid);
        if ( ! $module) {
            return ['allowed' => false, 'blocked_by_module_id' => null, 'message' => 'Module not found.'];
        }

        $course = $this->CI->course_model->get_course((int) $module->course_id);
        if ( ! $course) {
            return $ok;
        }

        $enforce = 1;
        if ($this->CI->db->field_exists('enforce_sequential_modules', 'courses')) {
            $enforce = (int) ($course->enforce_sequential_modules ?? 1);
        }

        $modules = $this->CI->course_model->get_modules((int) $module->course_id, $uid);
        if (empty($modules)) {
            return $ok;
        }

        $required_ids = [];

        if ($enforce === 1) {
            foreach ($modules as $m) {
                if ((int) $m->id === $mid) {
                    break;
                }
                $required_ids[] = (int) $m->id;
            }
        }

        if ($this->CI->db->table_exists('course_module_prerequisites')) {
            $rows = $this->CI->db
                ->select('prerequisite_module_id')
                ->from('course_module_prerequisites')
                ->where('module_id', $mid)
                ->where('archived', 0)
                ->get()
                ->result();
            foreach ($rows as $r) {
                $pid = (int) ($r->prerequisite_module_id ?? 0);
                if ($pid > 0 && ! in_array($pid, $required_ids, true)) {
                    $required_ids[] = $pid;
                }
            }
        }

        foreach ($required_ids as $req_mid) {
            $prog = $this->CI->course_model->get_module_progress($uid, $req_mid);
            $status = $prog ? (string) ($prog->status ?? '') : 'not_started';
            if ($status !== 'completed') {
                return [
                    'allowed'              => false,
                    'blocked_by_module_id' => $req_mid,
                    'message'              => 'Complete the previous module before opening this one.',
                ];
            }
        }

        return $ok;
    }
}

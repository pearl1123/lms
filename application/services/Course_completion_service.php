<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Single source of truth for user-course state.
 */
class Course_completion_service
{
    /** @var CI_Controller&object{course_model:Course_model,assessment_model:assessment_model,certificate_model:certificate_model,assessment_service:Assessment_service} */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Course_model', 'course_model');
        $this->CI->load->model('assessment_model');
        $this->CI->load->model('certificate_model');
    }

    /**
     * @return array{
     *   user_id:int,course_id:int,status:string,progress_percent:int,is_completed:bool,is_certificate_eligible:bool,
     *   next_module_id:?int,resume:array,module_states:array,assessment_state:array
     * }
     */
    public function evaluate_user_course_state($user_id, $course_id)
    {
        $uid = (int) $user_id;
        $cid = (int) $course_id;

        $out = [
            'user_id'                 => $uid,
            'course_id'               => $cid,
            'status'                  => 'not_started',
            'progress_percent'        => 0,
            'is_completed'            => false,
            'is_certificate_eligible' => false,
            'next_module_id'          => null,
            'resume'                  => [
                'module_id'          => null,
                'content_type'       => '',
                'timestamp_seconds'  => 0,
                'page_number'        => null,
                'slide_index'        => null,
            ],
            'module_states'           => [],
            'assessment_state'        => ['pre' => 'pending', 'post' => 'pending'],
        ];

        if ($uid < 1 || $cid < 1) {
            return $out;
        }

        $enrollment = $this->CI->course_model->get_enrollment($uid, $cid);
        $enrollment_status = $this->normalize_enrollment_status($enrollment ? ($enrollment->status ?? '') : '');
        $out['status'] = $enrollment_status;
        if ($enrollment_status === 'archived' || $enrollment_status === 'rejected') {
            return $out;
        }

        $modules = $this->CI->course_model->get_modules($cid, $uid);
        if (empty($modules)) {
            return $out;
        }

        $sum_pct = 0;
        $weighted_pct = 0.0;
        $weight_total = 0.0;
        $completed_modules = 0;
        $pre_done_all = true;
        $post_done_all = true;
        $in_progress_mid = null;
        $next_mid = null;
        $resume_candidate = null;
        $resume_score = -1.0;

        foreach ($modules as $module) {
            $mid = (int) $module->id;
            $assessment_service = $this->_assessment_service();
            $flow = $assessment_service->get_module_flow_state($uid, $mid);
            $db_status = strtolower(trim((string) ($module->status ?? 'not_started')));
            if (! in_array($db_status, ['not_started', 'in_progress', 'completed'], true)) {
                $db_status = 'not_started';
            }

            // Course-level progress is strictly persisted state from module_progress.
            $module_done = ($db_status === 'completed');
            if ($module_done) {
                $completed_modules++;
            }
            $module_progress_percent = $module_done ? 100 : ($db_status === 'in_progress' ? 50 : 0);
            $module_status = $module_done ? 'completed' : ($db_status === 'in_progress' ? 'in_progress' : 'not_started');
            if ($module_status === 'in_progress' && $in_progress_mid === null) {
                $in_progress_mid = $mid;
            }
            if ($next_mid === null && $module_status !== 'completed') {
                $next_mid = $mid;
            }

            $out['module_states'][] = [
                'module_id'         => $mid,
                'status'            => $module_status,
                'completed'         => $module_done,
                'progress_percent'  => $module_progress_percent,
            ];

            $p = (int) $module_progress_percent;
            $w = max(0.0, (float) ($module->weight_percentage ?? 0));
            $sum_pct += $p;
            $weighted_pct += ($p * $w);
            $weight_total += $w;

            $pre_done_all = $pre_done_all && ! empty($flow['pre_assessment']['attempted']);
            $post_done_all = $post_done_all && ! empty($flow['post_assessment']['passed']);

            $resume_state = $this->CI->course_model->get_module_resume_state($uid, $mid);
            $resume_state = is_array($resume_state) ? $resume_state : [];
            $rpos = (float) ($resume_state['position'] ?? 0);
            $rtype = (string) ($resume_state['type'] ?? '');
            if ($rpos > 0 && ($module_status !== 'completed')) {
                $score = ($module_status === 'in_progress' ? 100000 : 50000) + $rpos;
                if ($score > $resume_score) {
                    $resume_score = $score;
                    $resume_candidate = [
                        'module_id' => $mid,
                        'type'      => $rtype,
                        'position'  => $rpos,
                        'content_type' => (string) ($module->content_type ?? ''),
                    ];
                }
            }

            if ($db_status === 'completed' && ! $module_done) {
                log_message('debug', 'module completion inconsistency user=' . $uid . ' course=' . $cid . ' module=' . $mid);
            }
        }

        $n = count($modules);
        if ($n > 0) {
            if (abs($weight_total - 100.0) <= 0.01) {
                $out['progress_percent'] = (int) round($weighted_pct / 100);
            } else {
                $out['progress_percent'] = (int) round($sum_pct / $n);
            }
        }
        $out['progress_percent'] = max(0, min(100, (int) $out['progress_percent']));

        $out['is_completed'] = ($n > 0 && $completed_modules === $n);
        $out['assessment_state'] = [
            'pre'  => $pre_done_all ? 'completed' : 'pending',
            'post' => $post_done_all ? 'completed' : 'pending',
        ];
        $out['next_module_id'] = $in_progress_mid !== null ? $in_progress_mid : $next_mid;

        if ($out['is_completed']) {
            $out['status'] = 'completed';
        } elseif ($out['progress_percent'] > 0) {
            $out['status'] = 'in_progress';
        } else {
            $out['status'] = 'not_started';
        }
        if ($enrollment_status === 'pending' && $out['status'] === 'not_started') {
            $out['status'] = 'not_started';
        }

        if ($resume_candidate !== null) {
            $out['resume'] = $this->format_resume_payload($resume_candidate);
        } elseif ($out['next_module_id'] !== null) {
            $next_module = $this->find_module($modules, (int) $out['next_module_id']);
            $out['resume']['module_id'] = (int) $out['next_module_id'];
            $out['resume']['content_type'] = (string) ($next_module->content_type ?? '');
        }

        $existing_cert = $this->CI->certificate_model->get_by_user_course($uid, $cid);
        $out['is_certificate_eligible'] = $out['is_completed'] && empty($existing_cert);
        if ( ! empty($existing_cert) && $out['is_completed']) {
            log_message('debug', 'certificate ineligible but previously eligible user=' . $uid . ' course=' . $cid);
        }

        return $out;
    }

    public function build_resume_url($user_id, $course_id, $return_q = '')
    {
        $state = $this->evaluate_user_course_state((int) $user_id, (int) $course_id);
        $mid = (int) ($state['resume']['module_id'] ?? 0);
        if ($mid < 1) {
            $mid = (int) ($state['next_module_id'] ?? 0);
        }
        if ($mid < 1) {
            return site_url('course/' . (int) $course_id);
        }

        $resume = $state['resume'] ?? [];
        $query = '';
        if ( ! empty($resume['timestamp_seconds'])) {
            $query = 't=' . (int) $resume['timestamp_seconds'];
        } elseif ( ! empty($resume['page_number'])) {
            $query = 'page=' . (int) $resume['page_number'];
        } elseif ( ! empty($resume['slide_index'])) {
            $query = 'slide=' . (int) $resume['slide_index'];
        }

        $parts = [];
        if ($query !== '') {
            parse_str($query, $parts);
        }
        if ($return_q !== '') {
            parse_str(ltrim($return_q, '?&'), $rq);
            if (is_array($rq)) {
                $parts = array_merge($parts, $rq);
            }
        }
        $url = site_url('courses/module/' . $mid);
        if ( ! empty($parts)) {
            $url .= '?' . http_build_query($parts);
        }

        return $url;
    }

    private function normalize_enrollment_status($status)
    {
        $st = strtolower(trim((string) $status));
        if ($st === 'rejected') return 'rejected';
        if (in_array($st, ['archived', 'cancelled', 'canceled', 'expired'], true)) return 'archived';
        return $st === '' ? 'not_started' : $st;
    }

    private function format_resume_payload(array $candidate)
    {
        $type = strtolower((string) ($candidate['type'] ?? ''));
        $pos = (float) ($candidate['position'] ?? 0);
        $resume = [
            'module_id'         => (int) ($candidate['module_id'] ?? 0),
            'content_type'      => (string) ($candidate['content_type'] ?? ''),
            'timestamp_seconds' => 0,
            'page_number'       => null,
            'slide_index'       => null,
        ];
        if ($type === 'video' || $type === 'audio') {
            $resume['timestamp_seconds'] = (int) floor($pos);
        } elseif ($type === 'pdf') {
            $resume['page_number'] = max(1, (int) floor($pos));
        } elseif ($type === 'slides') {
            $resume['slide_index'] = max(1, (int) floor($pos));
        }

        return $resume;
    }

    private function find_module(array $modules, $module_id)
    {
        foreach ($modules as $module) {
            if ((int) ($module->id ?? 0) === (int) $module_id) {
                return $module;
            }
        }

        return null;
    }

    /**
     * Lazy resolve to prevent circular service-loading recursion.
     *
     * @return Assessment_service
     */
    private function _assessment_service()
    {
        if ( ! isset($this->CI->assessment_service) || ! $this->CI->assessment_service) {
            $this->CI->load->library('assessment_service');
        }

        return $this->CI->assessment_service;
    }
}

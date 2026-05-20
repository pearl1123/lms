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

    /** @var array<string, array> Per-request cache for {@see get_course_progress_aggregate()} when modules are resolved internally. */
    private static $course_progress_agg_cache = [];

    /** Drop cached aggregate so the next {@see get_course_progress_aggregate()} recomputes from DB + flow. */
    public function invalidate_course_progress_aggregate_cache($user_id, $course_id)
    {
        $key = (int) $user_id . ':' . (int) $course_id;
        unset(self::$course_progress_agg_cache[$key]);
    }

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
     *   video_checkpoint_json_url:string,
     *   module_completion_state:array{
     *     pre_assessment_passed:bool,video_completed:bool,post_assessment_passed:bool,can_mark_complete:bool,
     *     flow?:array (full machine from get_module_flow_state)
     *   },
     *   module_flow_state:array (same as get_module_flow_state; single source of truth for player flow),
     *   video_module_progress_state:array (see get_video_module_progress_state)
     * }
     */
    public function course_module_play_context($user_id, $user_role, $module_id, $module)
    {
        $mid = (int) $module_id;

        $exam = $this->_course_module_exam_browse_context((int) $user_id, (string) $user_role, $mid, $module);
        $vc   = $this->_course_module_video_checkpoint_context((int) $user_id, $mid, $module);

        $flow = $this->get_module_flow_state((int) $user_id, $mid);

        return array_merge($exam, $vc, [
            'assessment_pass_threshold'   => $this->pass_threshold(),
            'module_completion_state'    => $this->get_module_completion_state((int) $user_id, $mid, $flow),
            'module_flow_state'           => $flow,
            'video_module_progress_state' => $this->get_video_module_progress_state((int) $user_id, $mid, $module, $flow),
        ]);
    }

    /**
     * Pre/post exam summary for the module player (fetches module assessments here only).
     *
     * @param object $module course_modules row (pre is optional; never gates video modules)
     * @return array{pre_assessment:?object,pre_blocked:bool,post_assessments:object[]}
     */
    private function _course_module_exam_browse_context($user_id, $user_role, $module_id, $module)
    {
        $uid  = (int) $user_id;
        $role = strtolower((string) $user_role);
        $mid  = (int) $module_id;

        $pre_list = $this->CI->course_model->get_assessments($mid, 'pre');
        $post_raw = $this->CI->course_model->get_assessments($mid, 'post');

        $pre_assessment = ! empty($pre_list) ? $pre_list[0] : null;
        $pre_blocked    = false;
        $is_video       = ($module && ($module->content_type ?? '') === 'video');
        $pre_required = ! $is_video || ! empty($pre_assessment->is_required);
        if ($pre_assessment && in_array($role, ['employee', 'student'], true) && $pre_required) {
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
     * Whether the user may mark the module complete — uses {@see get_module_flow_state()}.
     */
    public function employee_may_complete_module_with_video_checkpoints($user_id, $module)
    {
        if ( ! $module || ! isset($module->id)) {
            return false;
        }

        $flow = $this->get_module_flow_state((int) $user_id, (int) $module->id);

        return ! empty($flow['completion']['can_mark_complete']);
    }

    /**
     * Flat progress state for video learning flow (checkpoints via Module_video_checkpoint_model; no schema changes).
     *
     * Rules:
     * - Pre-assessment is optional unless the assessment row is marked required.
     *   Passing pre is not required for unlock/completion.
     * - Video completion is derived from required checkpoints:
     *   - no required checkpoints => completed
     *   - required checkpoints exist => all required must be passed
     * - Post-assessment is unlocked only after video is completed (video modules).
     * - {@see can_mark_complete} mirrors {@see get_module_flow_state()} completion (post pass only;
     *   post remains locked until video segment is complete, so progression stays strict).
     *
     * @param int         $user_id
     * @param int         $module_id
     * @param object|null $module    optional loaded row to avoid an extra get_module()
     * @param array|null  $flow      optional precomputed {@see get_module_flow_state()} to avoid duplicate work
     * @return array{
     *   pre_assessment_required:bool,
     *   pre_assessment_passed:bool,
     *   video_unlocked:bool,
     *   checkpoints_total:int,
     *   checkpoints_completed:int,
     *   video_completed:bool,
     *   post_assessment_unlocked:bool,
     *   post_assessment_required:bool,
     *   post_assessment_passed:bool,
     *   can_mark_complete:bool
     * }
     */
    public function get_video_module_progress_state($user_id, $module_id, $module = null, $flow = null)
    {
        $empty = [
            'pre_assessment_required'  => false,
            'pre_assessment_passed'    => false,
            'video_unlocked'           => false,
            'checkpoints_total'        => 0,
            'checkpoints_completed'    => 0,
            'video_completed'          => false,
            'post_assessment_unlocked' => false,
            'post_assessment_required' => true,
            'post_assessment_passed'   => false,
            'can_mark_complete'        => false,
        ];

        if ($flow === null) {
            $flow = $this->get_module_flow_state((int) $user_id, (int) $module_id);
        }

        if (empty($flow['video'])) {
            return $empty;
        }

        $pre_required = ! empty($flow['pre_assessment']['required']);

        return [
            'pre_assessment_required'  => $pre_required,
            'pre_assessment_passed'    => ! empty($flow['pre_assessment']['passed']),
            'video_unlocked'           => ! empty($flow['video']['unlocked']),
            'checkpoints_total'        => (int) ($flow['checkpoints']['total'] ?? 0),
            'checkpoints_completed'    => (int) ($flow['checkpoints']['completed'] ?? 0),
            'video_completed'          => ! empty($flow['video']['completed']),
            'post_assessment_unlocked' => ! empty($flow['post_assessment']['unlocked']),
            'post_assessment_required' => true,
            'post_assessment_passed'   => ! empty($flow['post_assessment']['passed']),
            'can_mark_complete'        => ! empty($flow['completion']['can_mark_complete']),
        ];
    }

    /**
     * Single source of truth for module learning flow (pre → video/checkpoints → post → completion).
     *
     * Phases:
     * 1. Pre — optional module config; if present, first attempt unlocks video; pass not required.
     * 2. Video — checkpoints report progress only; {@see video.completed} when all required checkpoints pass
     *    (or none configured on supported YouTube video modules).
     * 3. Post — locked on video modules until {@see video.completed}; pass uses {@see is_passing_assessment_result()}
     *    (threshold + no pending). Unlimited retries; model returns latest aggregate result.
     * 4. Completion — {@see can_mark_complete} is true only when every post assessment has passed.
     *
     * @param int $user_id
     * @param int $module_id
     * @return array{
     *   pre_assessment:array{attempted:bool,passed:bool,can_take:bool},
     *   video:array{unlocked:bool,completed:bool},
     *   checkpoints:array{total:int,completed:int},
     *   post_assessment:array{unlocked:bool,passed:bool,can_retry:bool,last_score:?float},
     *   completion:array{can_mark_complete:bool}
     * }
     */
    public function get_module_flow_state($user_id, $module_id)
    {
        $uid = (int) $user_id;
        $mid = (int) $module_id;

        $empty = [
            'pre_assessment' => ['attempted' => false, 'passed' => false, 'can_take' => false],
            'video'          => ['unlocked' => false, 'completed' => false],
            'checkpoints'    => ['total' => 0, 'completed' => 0],
            'post_assessment' => [
                'unlocked' => false, 'passed' => false, 'can_retry' => false, 'last_score' => null,
            ],
            'completion'     => ['can_mark_complete' => false],
        ];

        $module = $this->CI->course_model->get_module($mid);
        if ( ! $module) {
            return $empty;
        }

        return $this->_resolve_module_flow_state($uid, $mid, $module);
    }

    /**
     * Canonical gate for entering post-assessment in module flow.
     *
     * Conditions:
     * - video segment must be completed
     * - post-assessment must not already be passed
     *
     * @param int $user_id
     * @param int $module_id
     * @return bool
     */
    public function can_start_post_assessment($user_id, $module_id)
    {
        $flow = $this->get_module_flow_state((int) $user_id, (int) $module_id);

        return ! empty($flow['video']['completed'])
            && empty($flow['post_assessment']['passed']);
    }

    /**
     * Backward-compatible alias for legacy callers.
     *
     * @deprecated Use can_start_post_assessment()
     */
    public function can_access_post_assessment($user_id, $module_id)
    {
        return $this->can_start_post_assessment((int) $user_id, (int) $module_id);
    }

    /**
     * Pure projection from {@see get_module_flow_state()} — no self-calls, no nested service work.
     *
     * @param array $flow Same shape as {@see get_module_flow_state()}
     * @return array{
     *   checkpoints_total:int,
     *   checkpoints_completed:int,
     *   video_completed:bool,
     *   post_assessment_passed:bool,
     *   progress_percent:int
     * }
     */
    protected function module_progress_summary_from_flow(array $flow)
    {
        $checkpoints_total     = (int) ($flow['checkpoints']['total'] ?? 0);
        $checkpoints_completed = (int) ($flow['checkpoints']['completed'] ?? 0);
        $video_completed       = ! empty($flow['video']['completed']);
        $post_passed           = ! empty($flow['post_assessment']['passed']);

        if ($post_passed) {
            $progress_percent = 100;
        } elseif ($video_completed) {
            $progress_percent = 80;
        } elseif ($checkpoints_total > 0) {
            $progress_percent = (int) round(($checkpoints_completed / $checkpoints_total) * 80);
        } else {
            $progress_percent = 0;
        }

        return [
            'checkpoints_total'      => $checkpoints_total,
            'checkpoints_completed'  => $checkpoints_completed,
            'video_completed'        => $video_completed,
            'post_assessment_passed' => $post_passed,
            'progress_percent'       => $progress_percent,
        ];
    }

    /**
     * Unified module progress summary for LMS progress bars/cards.
     * Single source for module progress_percent: derived only from {@see get_module_flow_state()}.
     *
     * @param int $user_id
     * @param int $module_id
     * @return array{
     *   checkpoints_total:int,
     *   checkpoints_completed:int,
     *   video_completed:bool,
     *   post_assessment_passed:bool,
     *   progress_percent:int
     * }
     */
    public function get_module_progress_summary($user_id, $module_id)
    {
        $flow = $this->get_module_flow_state((int) $user_id, (int) $module_id);

        return $this->module_progress_summary_from_flow($flow);
    }

    /**
     * Course-level progress from {@see get_module_progress_summary()} only.
     * Uses module weights when active weights total 100%; otherwise falls back to the legacy mean.
     * Controllers must not reimplement this average.
     *
     * @param int $user_id
     * @param int $course_id
     * @param array<int, object>|null $modules Optional rows from {@see Course_model::get_modules()}
     * @return array{
     *   course_progress_percent:int,
     *   completed_modules:int,
     *   total_modules:int,
     *   module_summaries: array<int, array>
     * }
     */
    public function get_course_progress_aggregate($user_id, $course_id, $modules = null)
    {
        $uid = (int) $user_id;
        $cid = (int) $course_id;

        $cache_key            = $uid . ':' . $cid;
        $used_internal_modules = ($modules === null);
        if ($used_internal_modules && isset(self::$course_progress_agg_cache[$cache_key])) {
            return self::$course_progress_agg_cache[$cache_key];
        }

        if ($modules === null) {
            $modules = $this->CI->course_model->get_modules($cid, $uid);
        }

        $summaries  = [];
        $sum_pct      = 0;
        $weighted_pct = 0;
        $weight_total = 0;
        $completed    = 0;
        $n            = is_array($modules) ? count($modules) : 0;

        foreach ($modules as $m) {
            $mid = (int) $m->id;
            $s   = $this->get_module_progress_summary($uid, $mid);
            // DB completion is authoritative for LMS progress averages (flow-only % can stay at 80
            // while module_progress.status is completed — averaging then blocks course-level 100%).
            if (($m->status ?? '') === 'completed') {
                $s['progress_percent']       = 100;
                $s['video_completed']        = true;
                $s['post_assessment_passed'] = true;
            }
            $summaries[$mid] = $s;
            $progress = (int) ($s['progress_percent'] ?? 0);
            $weight   = max(0, (float) ($m->weight_percentage ?? 0));
            $sum_pct += $progress;
            $weighted_pct += $progress * $weight;
            $weight_total += $weight;
            if ($progress >= 100) {
                $completed++;
            }
        }

        if ($n === 0) {
            $course_pct = 0;
        } elseif (abs($weight_total - 100.0) <= 0.01) {
            $course_pct = (int) round($weighted_pct / 100);
        } elseif ($sum_pct === 0) {
            $course_pct = 0;
        } else {
            $course_pct = (int) round($sum_pct / $n);
        }

        $out = [
            'course_progress_percent' => $course_pct,
            'completed_modules'       => $completed,
            'total_modules'           => $n,
            'module_summaries'        => $summaries,
        ];

        $dbg = $out;
        if ( ! empty($dbg['module_summaries'])) {
            $sums = [];
            foreach ($dbg['module_summaries'] as $mid => $s) {
                $sums[(string) $mid] = [
                    'pct'  => $s['progress_percent'] ?? null,
                    'post' => ! empty($s['post_assessment_passed']),
                    'vid'  => ! empty($s['video_completed']),
                ];
            }
            $dbg['module_summaries'] = $sums;
        }
        log_message('debug', 'COURSE PROGRESS DEBUG: ' . json_encode($dbg));

        if ($used_internal_modules) {
            self::$course_progress_agg_cache[$cache_key] = $out;
        }

        return $out;
    }

    /**
     * Certificate / course-complete gate: DB-only (module_progress.status from get_modules()).
     * Do not use {@see get_course_progress_aggregate()} or flow summaries for this decision.
     *
     * @param int        $user_id
     * @param int        $course_id
     * @param object[]|null $modules_prefetched Optional rows from {@see Course_model::get_modules()}
     *                                       (fresh read after invalidate); if null, loads internally.
     */
    public function is_course_fully_completed($user_id, $course_id, $modules_prefetched = null)
    {
        $modules = $modules_prefetched;
        if ($modules === null) {
            $modules = $this->CI->course_model->get_modules((int) $course_id, (int) $user_id);
        }

        log_message('debug', 'CERT DEBUG MODULES: ' . json_encode($modules));

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $m) {
            if (($m->status ?? '') !== 'completed') {
                return false;
            }
        }

        return true;
    }

    /**
     * Legacy flat bundle + nested {@see get_module_flow_state()} under key `flow`.
     *
     * @param array|null $flow Optional precomputed flow from {@see get_module_flow_state()} (same request).
     * @return array{
     *   pre_assessment_passed:bool,
     *   video_completed:bool,
     *   post_assessment_passed:bool,
     *   can_mark_complete:bool,
     *   flow:array
     * }
     */
    public function get_module_completion_state($user_id, $module_id, $flow = null)
    {
        if ($flow === null) {
            $flow = $this->get_module_flow_state((int) $user_id, (int) $module_id);
        }

        return [
            'pre_assessment_passed'  => $flow['pre_assessment']['passed'],
            'video_completed'        => $flow['video']['completed'],
            'post_assessment_passed' => $flow['post_assessment']['passed'],
            'can_mark_complete'      => $flow['completion']['can_mark_complete'],
            'flow'                   => $flow,
        ];
    }

    /**
     * Core resolver for {@see get_module_flow_state()} — one ordered evaluation of pre / video / checkpoints / post.
     *
     * @param object $module course_modules row
     * @return array{
     *   pre_assessment:array{attempted:bool,passed:bool,can_take:bool},
     *   video:array{unlocked:bool,completed:bool},
     *   checkpoints:array{total:int,completed:int},
     *   post_assessment:array{unlocked:bool,passed:bool,can_retry:bool,last_score:?float},
     *   completion:array{can_mark_complete:bool}
     * }
     */
    private function _resolve_module_flow_state($uid, $mid, $module)
    {
        $uid = (int) $uid;
        $mid = (int) $mid;

        $vm            = $this->CI->video_checkpoint_model;
        $content_video = (($module->content_type ?? '') === 'video');

        // --- Phase 1: Pre-assessment (attempt gates video on video modules; pass optional) ---
        $pre_list = $this->CI->course_model->get_assessments($mid, 'pre');
        $pre      = ! empty($pre_list) ? $pre_list[0] : null;

        $pre_attempted = false;
        $pre_passed    = false;
        $pre_can_take  = false;

        if ($pre) {
            $pre_aid       = (int) $pre->id;
            $pre_attempted = $this->CI->assessment_model->has_answered($uid, $pre_aid);
            $pre_res       = $pre_attempted
                ? $this->CI->assessment_model->get_result($uid, $pre_aid)
                : ['score' => 0, 'scored' => 0, 'total' => 0, 'pending' => 0];
            $pre_passed   = $pre_attempted && $this->is_passing_assessment_result($pre_res);
            $pre_can_take = true;
        } else {
            $pre_attempted = true;
            $pre_passed    = true;
        }

        // --- Phase 2–3: Checkpoints (existing model; playback never hard-blocked by this service) ---
        $chk_total = 0;
        $chk_done  = 0;
        if ($content_video && $vm->is_youtube_module($module)) {
            $payload       = $vm->get_public_checkpoints_payload($mid);
            $passed_ids    = $vm->get_passed_checkpoint_assessment_ids($uid, $mid);
            $passed_lookup = [];
            foreach ($passed_ids as $pid) {
                $passed_lookup[(int) $pid] = true;
            }
            foreach ($payload as $cp) {
                if (empty($cp['is_required'])) {
                    continue;
                }
                $chk_total++;
                $cid = (int) ($cp['id'] ?? 0);
                if ($cid > 0 && ! empty($passed_lookup[$cid])) {
                    $chk_done++;
                }
            }
        }

        $video_completed = ($chk_total === 0) || ($chk_done >= $chk_total);

        // Video unlock: video modules are blocked only when the pre-assessment is explicitly required.
        $video_unlocked = true;
        $pre_required = ! $content_video || ! empty($pre->is_required);
        if ($content_video && $pre && $pre_required) {
            $video_unlocked = $pre_attempted;
        } elseif ( ! $content_video && $pre) {
            $video_unlocked = $pre_attempted;
        }

        // --- Phase 4: Post-assessment (locked until video segment complete on video modules) ---
        $post_raw = $this->CI->course_model->get_assessments($mid, 'post');

        $post_unlocked = ! $content_video || $video_completed;
        $post_passed   = false;
        $last_scores   = [];

        if ( ! $post_unlocked) {
            $post_passed = false;
        } elseif (empty($post_raw)) {
            $post_passed = true;
        } else {
            $post_passed = true;
            foreach ($post_raw as $pa) {
                $aid = (int) $pa->id;
                if ( ! $this->CI->assessment_model->has_answered($uid, $aid)) {
                    $post_passed = false;

                    continue;
                }
                $res = $this->CI->assessment_model->get_result($uid, $aid);
                $last_scores[] = (float) ($res['score'] ?? 0);
                if ( ! $this->is_passing_assessment_result($res)) {
                    $post_passed = false;
                }
            }
        }

        $last_score = null;
        if ($post_unlocked && ! empty($last_scores)) {
            $last_score = round(array_sum($last_scores) / count($last_scores), 2);
        }

        $post_can_retry = ! empty($post_raw) && $post_unlocked;

        // Completion: strict post pass only (post unreachable until video completes when posts exist on video modules).
        $can_mark_complete = $post_passed;

        return [
            'pre_assessment' => [
                'attempted' => $pre_attempted,
                'passed'    => $pre_passed,
                'can_take'  => $pre_can_take,
                'required'  => ! empty($pre) && ! empty($pre_required),
            ],
            'video' => [
                'unlocked'  => $video_unlocked,
                'completed' => $video_completed,
            ],
            'checkpoints' => [
                'total'     => $chk_total,
                'completed' => $chk_done,
            ],
            'post_assessment' => [
                'unlocked'   => $post_unlocked,
                'passed'     => $post_passed,
                'can_retry'  => $post_can_retry,
                'last_score' => $last_score,
            ],
            'completion' => [
                'can_mark_complete' => $can_mark_complete,
            ],
        ];
    }

    /**
     * Up to three distinct trigger seconds: early 5–30%, middle 30–70%, late 70–95% of video duration.
     * When duration is unknown or bands are infeasible, returns null (caller uses manual trigger_seconds).
     *
     * @param int|float|string $video_duration        length in seconds (> 0)
     * @param int[]            $occupied_seconds      existing triggers to avoid (optional)
     * @return int[]|null      three integers, or null
     */
    public function generate_checkpoint_distribution($video_duration, array $occupied_seconds = [])
    {
        return self::generate_random_checkpoint_trigger_seconds(
            (int) $video_duration,
            $occupied_seconds
        );
    }

    /**
     * Max unified video checkpoints allowed per module (see Module_video_checkpoint_model).
     */
    public function max_video_checkpoints_per_module()
    {
        return Module_video_checkpoint_model::MAX_VIDEO_CHECKPOINTS_PER_MODULE;
    }

    /**
     * Create three video checkpoints with randomized trigger_seconds (early / middle / late bands).
     * Does not create questions — admin adds MCQ on each assessment edit screen afterward.
     *
     * @param int   $module_id
     * @param int   $user_id   encoded_by / manager id
     * @param array $options   keys: title (string), is_required (truthy), video_duration_seconds (int > 0)
     * @return array{ok:bool, created_ids?:int[], message:string}
     */
    public function create_auto_distributed_video_checkpoints($module_id, $user_id, array $options)
    {
        $mid = (int) $module_id;
        $uid = (int) $user_id;
        $vm  = $this->CI->video_checkpoint_model;

        if ( ! $this->CI->assessment_model->assessments_checkpoint_schema_ready()) {
            return ['ok' => false, 'message' => 'Video checkpoints require the unified assessment columns on the server.'];
        }

        $max   = Module_video_checkpoint_model::MAX_VIDEO_CHECKPOINTS_PER_MODULE;
        $count = $vm->count_lib_video_checkpoints_for_module($mid);
        if ($count + 3 > $max) {
            return [
                'ok'      => false,
                'message' => 'Auto-generate creates 3 checkpoints. This module already has '
                    . $count . ' (max ' . $max . '). Remove or archive checkpoints until at least 3 slots are free, or add manually.',
            ];
        }

        $duration = (int) ($options['video_duration_seconds'] ?? 0);
        $occupied = $vm->get_lib_video_checkpoint_trigger_seconds_in_use($mid);
        $seconds  = $this->generate_checkpoint_distribution($duration, $occupied);

        if ($seconds === null) {
            return [
                'ok'      => false,
                'message' => 'Video duration is missing or too short for auto-placement (need room in 5–30%, 30–70%, and 70–95% of the timeline). Enter a longer duration in seconds, or create checkpoints manually.',
            ];
        }

        $titleBase = trim((string) ($options['title'] ?? 'Video checkpoint'));
        if ($titleBase === '') {
            $titleBase = 'Video checkpoint';
        }
        $req = ! empty($options['is_required']);

        $labels = [
            'Early segment (auto)',
            'Middle segment (auto)',
            'Late segment (auto)',
        ];

        $created = [];
        foreach ($seconds as $i => $ts) {
            $payload = [
                'module_id'        => $mid,
                'type'             => 'checkpoint',
                'title'            => $titleBase . ' — ' . $labels[$i],
                'encoded_by'       => $uid,
                'trigger_seconds'  => (int) $ts,
                'trigger_percent'  => 0,
                'is_required'      => $req ? 1 : 0,
                'sort_order'       => $i + 1,
            ];
            $new_id = $this->CI->assessment_model->create_assessment($payload);
            if ($new_id < 1) {
                return [
                    'ok'          => false,
                    'created_ids' => $created,
                    'message'     => 'Could not create all checkpoints (limit or duplicate timestamp). Partial creations may exist; review this module.',
                ];
            }
            $created[] = $new_id;
        }

        return [
            'ok'          => true,
            'created_ids' => $created,
            'message'     => 'Created 3 video checkpoints with randomized timestamps. Add one multiple-choice question to each.',
        ];
    }

    /**
     * Random placement: one second per band — early 5–30%, middle (after 30%) through 70%, late after 70% through 95%.
     * Disjoint integer ranges guarantee distinct values when each band has at least one integer.
     * Avoids seconds listed in $occupied_seconds (existing checkpoints). Re-rolls within a band up to $max_attempts_per_band.
     *
     * @param int   $duration_sec        total video length in seconds
     * @param int[] $occupied_seconds    timestamps already used on this module (>= 0)
     * @param int   $max_attempts_per_band
     * @return int[]|null three integers [early, middle, late] in timeline order, or null if not feasible
     */
    public static function generate_random_checkpoint_trigger_seconds(
        $duration_sec,
        array $occupied_seconds = [],
        $max_attempts_per_band = 80
    ) {
        $D = (int) $duration_sec;
        if ($D < 1) {
            return null;
        }

        $blocked = [];
        foreach ($occupied_seconds as $x) {
            $bx = (int) $x;
            if ($bx > 0) {
                $blocked[$bx] = true;
            }
        }

        $early_lo = max(1, (int) ceil($D * 0.05));
        $early_hi = (int) floor($D * 0.30);
        $mid_lo   = (int) floor($D * 0.30) + 1;
        $mid_hi   = (int) floor($D * 0.70);
        $late_lo  = (int) floor($D * 0.70) + 1;
        $late_hi  = (int) floor($D * 0.95);

        if ($early_hi < $early_lo || $mid_hi < $mid_lo || $late_hi < $late_lo) {
            return null;
        }

        $bands = [
            [$early_lo, $early_hi],
            [$mid_lo, $mid_hi],
            [$late_lo, $late_hi],
        ];

        $out = [];
        foreach ($bands as $band) {
            list($lo, $hi) = $band;
            $picked = self::_pick_random_second_in_band_avoiding($lo, $hi, $blocked, (int) $max_attempts_per_band);
            if ($picked === null) {
                return null;
            }
            $out[]        = $picked;
            $blocked[$picked] = true;
        }

        return $out;
    }

    /**
     * @param int   $lo
     * @param int   $hi
     * @param array $blocked map second => true
     * @param int   $max_attempts
     * @return int|null
     */
    private static function _pick_random_second_in_band_avoiding($lo, $hi, array $blocked, $max_attempts)
    {
        if ($lo > $hi) {
            return null;
        }

        for ($a = 0; $a < $max_attempts; $a++) {
            $v = random_int($lo, $hi);
            if (empty($blocked[$v])) {
                return $v;
            }
        }

        for ($v = $lo; $v <= $hi; $v++) {
            if (empty($blocked[$v])) {
                return $v;
            }
        }

        return null;
    }
}

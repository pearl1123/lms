<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Courses Controller
 * URL: index.php/courses
 *
 * Routes:
 *   GET  index.php/courses              → Course Catalog (all roles)
 *   GET  index.php/courses/view/{id}    → Course Detail
 *   GET  index.php/courses/enroll/{id}  → Self-enroll (employee only)
 *
 * @property CI_DB_mysqli_driver  $db
 * @property CI_Session           $session
 * @property CI_Input             $input
 * @property User_model           $user_model
 * @property Course_model          $course_model
 * @property Assessment_service    $assessment_service
 * @property Certificate_model     $certificate_model
 * @property Notification_model    $notification_model
 * @property Certificate_service   $certificate_service
 * @property Notification_service  $notification_service
 * @property Event_dispatcher      $event_dispatcher
 * @property CI_Output               $output   Loaded by CI_Controller (JSON helpers use $this->output)
 */
class Courses extends CI_Controller {

    /** @var object Authenticated user row */
    private $user;

    /**
     * Return JSON auth error for AJAX/fetch calls instead of HTML redirect.
     *
     * @param int    $status
     * @param string $message
     */
    private function _auth_json_error($status, $message)
    {
        $payload = json_encode([
            'success' => false,
            'message' => (string) $message,
            'auth'    => false,
        ]);

        $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json')
            ->set_output($payload !== false ? $payload : '{"success":false,"message":"Authentication required.","auth":false}');

        $this->output->_display();
        exit;
    }

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('User_model',   'user_model');
        $this->load->model('Course_model',             'course_model');
        $this->load->library('assessment_service');
        $this->load->helper('url');

        // ── Auth guard ────────────────────────────────────────
        $is_ajax = strtolower((string) $this->input->server('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
        $user_id = $this->session->userdata('user_id');
        if ( ! $user_id) {
            if ($is_ajax) {
                $this->_auth_json_error(401, 'Authentication required.');
                return;
            }
            redirect('auth/login');
        }

        $user = $this->user_model->get_user($user_id);

        if ( ! $user) {
            if ($is_ajax) {
                $this->_auth_json_error(401, 'Authentication required.');
                return;
            }
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        if ((int) $user->banned   === 1) {
            if ($is_ajax) {
                $this->_auth_json_error(403, 'Account is banned.');
                return;
            }
            $this->session->sess_destroy();
            redirect('auth/login');
        }
        if ($user->status !== 'active') {
            if ($is_ajax) {
                $this->_auth_json_error(403, 'Account is not active.');
                return;
            }
            $this->session->sess_destroy();
            redirect('auth/login');
        }
        if ((int) $user->DELETED  === 1) {
            if ($is_ajax) {
                $this->_auth_json_error(403, 'Account is unavailable.');
                return;
            }
            $this->session->sess_destroy();
            redirect('auth/login');
        }
        if ( ! empty($user->locked_until) && strtotime($user->locked_until) > time()) {
            if ($is_ajax) {
                $this->_auth_json_error(423, 'Account is temporarily locked.');
                return;
            }
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        $this->user = $user;

        // Keep session user array fresh so sidebar.php can read it
        $this->session->set_userdata('user', [
            'id'          => $user->id,
            'fullname'    => $user->fullname,
            'employee_id' => $user->employee_id,
            'role'        => $user->role,
            'status'      => $user->status,
        ]);
    }

    // =========================================================
    // index() — Course Catalog
    // URL: index.php/courses
    // =========================================================
    public function index()
    {
        $user = $this->user;

        // ── Filters from GET params ───────────────────────────
        $keyword    = trim($this->input->get('q')        ?? '');
        $filter_cat = (int) ($this->input->get('category') ?? 0);

        // ── Fetch all published courses ───────────────────────
        // get_catalog() returns courses with:
        //   category_name, modality_name, access_type_name,
        //   creator_name, total_modules, total_enrolled
        $courses = $this->course_model->get_catalog($keyword, $filter_cat);

        // ── Approved enrollments only (IDs) + full status map for employee CTAs ──
        $enrolled_ids  = $this->course_model->get_enrolled_ids($user->id);
        $status_map    = $this->course_model->get_user_enrollment_status_map($user->id);

        // ── Attach per-user enrollment & progress (Assessment_service aggregate only) ──
        $uid = (int) $user->id;
        foreach ($courses as $course) {
            $course->total_modules  = (int) ($course->total_modules  ?? 0);
            $course->total_enrolled = (int) ($course->total_enrolled ?? 0);
            $cid                    = (int) $course->id;
            $course->enrollment_status = $status_map[$cid] ?? null;
            $course->is_enrolled    = in_array($cid, $enrolled_ids, true);

            if ( ! $course->is_enrolled) {
                $course->progress_pct = 0;
            } elseif ($uid < 1 || $course->total_modules < 1) {
                $course->progress_pct = 0;
            } else {
                $modules              = $this->course_model->get_modules($cid, $uid);
                $agg                  = $this->assessment_service->get_course_progress_aggregate($uid, $cid, $modules);
                $course->progress_pct = (int) $agg['course_progress_percent'];
            }
        }

        // ── Hero strip stats ──────────────────────────────────
        $total_all       = count($courses);
        $total_enrolled  = count(array_filter($courses, fn($c) => $c->is_enrolled));
        $total_available = $total_all - $total_enrolled;

        // ── Categories for filter dropdown & pills ────────────
        $categories = $this->course_model->get_categories();

        $data = [
            'user'            => $user,
            'page_title'      => 'Course Catalog',
            'courses'         => $courses,
            'categories'      => $categories,
            'total_courses'   => $total_all,
            'total_enrolled'  => $total_enrolled,
            'total_available' => $total_available,
            'keyword'         => $keyword,
            'filter_cat'      => $filter_cat,
            'breadcrumbs'     => [
                ['label' => 'Dashboard',      'url' => 'dashboard'],
                ['label' => 'Course Catalog'],
            ],
            'view' => 'courses/catalog',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // view($id) — Course Detail
    // URL: index.php/courses/view/{id}
    // =========================================================
    public function view($id = null)
    {
        if ( ! $id) redirect('courses');

        $user = $this->user;
        $id   = (int) $id;

        // ── Fetch course with all joined lookups ──────────────
        // Returns: category_name, modality_name, access_type_name,
        //          creator_name (from aauth_users.fullname)
        $course = $this->course_model->get_course($id);
        if ( ! $course) show_404();

        // ── Modules with user's progress per module ───────────
        // Returns: id, title, description, content_type, content_path,
        //          weight_percentage, module_order,
        //          status (not_started/in_progress/completed),
        //          my_score, my_completed_at
        $modules = $this->course_model->get_modules($id, $user->id);
        $agg     = $this->assessment_service->get_course_progress_aggregate((int) $user->id, $id, $modules);
        foreach ($modules as $m) {
            $sid     = (int) $m->id;
            $summary = $agg['module_summaries'][$sid] ?? null;
            if ($summary) {
                $m->progress_percent      = (int) ($summary['progress_percent'] ?? 0);
                $m->checkpoints_total     = (int) ($summary['checkpoints_total'] ?? 0);
                $m->checkpoints_completed = (int) ($summary['checkpoints_completed'] ?? 0);
            } else {
                $m->progress_percent      = 0;
                $m->checkpoints_total     = 0;
                $m->checkpoints_completed = 0;
            }
        }

        // ── Enrollment row (or null) ──────────────────────────
        $enrollment         = $this->course_model->get_enrollment($user->id, $id);
        $enrollment_status  = $enrollment && isset($enrollment->status)
            ? (string) $enrollment->status
            : ($enrollment ? 'approved' : null);
        $is_enrolled        = $this->course_model->has_approved_enrollment($user->id, $id);

        // ── Progress stats (from service aggregate only) ─────
        $total_modules     = (int) $agg['total_modules'];
        $completed_modules = (int) $agg['completed_modules'];
        $progress_pct      = (int) $agg['course_progress_percent'];

        // ── Total enrolled students for this course ───────────
        $total_enrolled = $this->course_model->count_enrollments($id);

        $data = [
            'user'              => $user,
            'page_title'        => $course->title,
            'course'            => $course,
            'modules'           => $modules,
            'is_enrolled'        => $is_enrolled,
            'enrollment'         => $enrollment,
            'enrollment_status'  => $enrollment_status,
            'total_modules'     => $total_modules,
            'completed_modules' => $completed_modules,
            'progress_pct'      => $progress_pct,
            'total_enrolled'    => $total_enrolled,
            'breadcrumbs'       => [
                ['label' => 'Dashboard',      'url' => 'dashboard'],
                ['label' => 'Course Catalog', 'url' => 'courses'],
                ['label' => $course->title],
            ],
            'view' => 'courses/detail',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // enroll($id) — Self-enroll (employee only)
    // URL: index.php/courses/enroll/{id}
    // =========================================================
    public function enroll($id = null)
    {
        if ( ! $id) redirect('courses');

        $user = $this->user;
        $id   = (int) $id;

        // Only learner roles can self-enroll
        if ( ! in_array((string) $user->role, ['employee', 'student'], true)) {
            redirect('courses/view/' . $id);
        }

        // Course must exist and be published (archived = 0)
        $course = $this->course_model->get_course($id);
        if ( ! $course) show_404();

        log_message('debug', 'ENROLL REQUEST HIT: ' . json_encode([
            'user_id'   => (int) $user->id,
            'course_id' => (int) $id,
            'role'      => (string) $user->role,
        ]));

        $ok = $this->course_model->request_enrollment($user->id, $id);

        if ($ok) {
            $row = $this->course_model->get_enrollment((int) $user->id, $id);
            if ($row && (int) ($row->id ?? 0) > 0) {
                $this->load->library('event_dispatcher');
                $this->event_dispatcher->dispatch('enrollment.requested', [
                    'request_id' => (int) $row->id,
                    'student_id' => (int) $user->id,
                    'user_id'    => (int) $user->id,
                    'course_id'  => (int) $id,
                ]);
            }

            $this->session->set_flashdata(
                'success',
                'Enrollment request submitted for <strong>'
                . htmlspecialchars($course->title) . '</strong>. '
                . 'Your instructor will review it shortly.'
            );
        } else {
            $row = $this->course_model->get_enrollment($user->id, $id);
            $st  = $row && isset($row->status) ? (string) $row->status : '';
            if ($st === 'pending') {
                $this->session->set_flashdata(
                    'info',
                    'You already have a pending enrollment request for this course.'
                );
            } elseif ($st === 'approved') {
                $this->session->set_flashdata(
                    'info',
                    'You are already enrolled in this course.'
                );
            } else {
                $this->session->set_flashdata(
                    'error',
                    'Unable to submit an enrollment request. Please try again.'
                );
            }
        }

        redirect('courses/view/' . $id);
    }

    /**
     * Employee: explain that course access requires approval.
     * URL: index.php/courses/enrollment_pending/{course_id}
     */
    public function enrollment_pending($course_id = null)
    {
        $user = $this->user;
        $cid  = (int) $course_id;
        if ($cid < 1) {
            redirect('courses');
        }

        if ( ! in_array((string) $user->role, ['employee', 'student'], true)) {
            redirect('courses/view/' . $cid);
        }

        $course = $this->course_model->get_course($cid);
        if ( ! $course) {
            show_404();
        }

        if ($this->course_model->has_approved_enrollment($user->id, $cid)) {
            redirect('courses/view/' . $cid);
        }

        $data = [
            'user'         => $user,
            'page_title'   => 'Pending approval',
            'course'       => $course,
            'breadcrumbs'  => [
                ['label' => 'Dashboard',      'url' => 'dashboard'],
                ['label' => 'Course Catalog', 'url' => 'courses'],
                ['label' => $course->title,   'url' => 'courses/view/' . $cid],
                ['label' => 'Pending approval'],
            ],
            'view' => 'courses/enrollment_pending',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    /**
     * Module player — employees need approved enrollment.
     * URL: index.php/courses/module/{module_id}
     */
    public function module($module_id = null)
    {
        if ( ! $module_id) {
            redirect('courses');
        }

        $user = $this->user;
        $mid  = (int) $module_id;

        $module = $this->course_model->get_module($mid);
        if ( ! $module) {
            show_404();
        }

        $course = $this->course_model->get_course((int) $module->course_id);
        if ( ! $course) {
            show_404();
        }

        if ($user->role === 'employee') {
            if ( ! $this->course_model->has_approved_enrollment($user->id, (int) $module->course_id)) {
                redirect('courses/enrollment_pending/' . (int) $module->course_id);
            }
        }

        $all_modules = $this->course_model->get_modules((int) $module->course_id, $user->id);

        $prev_module = null;
        $next_module = null;
        $found       = false;
        foreach ($all_modules as $idx => $m) {
            if ((int) $m->id === $mid) {
                $found = true;
                if ($idx > 0) {
                    $prev_module = $all_modules[$idx - 1];
                }
                if (isset($all_modules[$idx + 1])) {
                    $next_module = $all_modules[$idx + 1];
                }
                break;
            }
        }
        if ( ! $found) {
            show_404();
        }

        $my_progress = $this->course_model->get_module_progress($user->id, $mid);

        $player = $this->assessment_service->course_module_play_context(
            (int) $user->id,
            (string) ($user->role ?? ''),
            $mid,
            $module
        );

        $data = [
            'user'              => $user,
            'page_title'        => $module->title,
            'module'            => $module,
            'course'            => $course,
            'all_modules'       => $all_modules,
            'prev_module'       => $prev_module,
            'next_module'       => $next_module,
            'my_progress'       => $my_progress,
            'pre_blocked'       => $player['pre_blocked'],
            'pre_assessment'    => $player['pre_assessment'],
            'post_assessments'  => $player['post_assessments'],
            'assessment_pass_threshold' => $player['assessment_pass_threshold'],
            'youtube_video_id'                => $player['youtube_video_id'],
            'video_checkpoint_payload'        => $player['video_checkpoint_payload'],
            'video_checkpoint_passed_ids'     => $player['video_checkpoint_passed_ids'],
            'video_checkpoint_required_cnt'   => $player['video_checkpoint_required_cnt'],
            'video_checkpoint_gate'           => $player['video_checkpoint_gate'],
            'video_checkpoint_submit_url'     => $player['video_checkpoint_submit_url'],
            'video_checkpoint_json_url'       => $player['video_checkpoint_json_url'],
            'module_completion_state'         => $player['module_completion_state'] ?? null,
            'can_start_post_assessment'       => $this->assessment_service->can_start_post_assessment((int) $user->id, $mid),
            'breadcrumbs'       => [
                ['label' => 'Dashboard',      'url' => 'dashboard'],
                ['label' => 'Course Catalog', 'url' => 'courses'],
                ['label' => $course->title,   'url' => 'courses/view/' . $course->id],
                ['label' => $module->title],
            ],
            'view' => 'courses/module',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    /**
     * GET JSON — course-level progress for live UI sync (aggregate only).
     * URL: index.php/courses/progress_state/{course_id}
     *
     * @param int|null $course_id
     */
    public function progress_state($course_id = null)
    {
        $cid  = (int) $course_id;
        $user = $this->user;

        if ($cid < 1) {
            return $this->_complete_module_json([
                'ok'      => false,
                'message' => 'Invalid course.',
            ]);
        }

        $course = $this->course_model->get_course($cid);
        if ( ! $course) {
            return $this->_complete_module_json([
                'ok'      => false,
                'message' => 'Course not found.',
            ]);
        }

        if ($user->role === 'employee') {
            if ( ! $this->course_model->has_approved_enrollment($user->id, $cid)) {
                return $this->_complete_module_json([
                    'ok'      => false,
                    'message' => 'Not enrolled.',
                ]);
            }
        }

        $uid     = (int) $user->id;
        $modules = $this->course_model->get_modules($cid, $uid);
        $agg     = $this->assessment_service->get_course_progress_aggregate($uid, $cid, $modules);

        return $this->_complete_module_json([
            'ok'                      => true,
            'course_progress_percent' => (int) $agg['course_progress_percent'],
            'completed_modules'       => (int) $agg['completed_modules'],
            'total_modules'           => (int) $agg['total_modules'],
        ]);
    }

    /**
     * GET JSON — live module flow state for reactive UI updates.
     * URL: index.php/courses/module_state/{module_id}
     *
     * @param int|null $module_id
     */
    public function module_state($module_id = null)
    {
        $mid  = (int) $module_id;
        $user = $this->user;

        if ($mid < 1) {
            return $this->_complete_module_json([
                'ok'      => false,
                'message' => 'Invalid module.',
            ]);
        }

        $module = $this->course_model->get_module($mid);
        if ( ! $module) {
            return $this->_complete_module_json([
                'ok'      => false,
                'message' => 'Module not found.',
            ]);
        }

        if ($user->role === 'employee') {
            if ( ! $this->course_model->has_approved_enrollment($user->id, (int) $module->course_id)) {
                return $this->_complete_module_json([
                    'ok'      => false,
                    'message' => 'Not approved for this course.',
                ]);
            }
        }

        $summary = $this->assessment_service->get_module_progress_summary((int) $user->id, $mid);

        return $this->_complete_module_json([
            'ok'                        => true,
            'checkpoints_total'         => (int) $summary['checkpoints_total'],
            'checkpoints_completed'     => (int) $summary['checkpoints_completed'],
            'video_completed'           => ! empty($summary['video_completed']),
            'post_assessment_passed'    => ! empty($summary['post_assessment_passed']),
            'progress_percent'          => (int) $summary['progress_percent'],
            'can_start_post_assessment' => $this->assessment_service->can_start_post_assessment((int) $user->id, $mid),
        ]);
    }

    /**
     * POST JSON — mark module complete (employee with approved enrollment).
     * URL: index.php/courses/complete_module/{module_id}
     */
    public function complete_module($module_id = null)
    {
        log_message('debug', 'COMPLETE MODULE HIT: ' . (int) $module_id);

        if (strtolower((string) $this->input->method()) !== 'post') {
            show_404();
        }

        $user = $this->user;
        $mid  = (int) $module_id;
        if ($mid < 1) {
            return $this->_complete_module_json(['success' => false, 'message' => 'Invalid module.']);
        }

        $module = $this->course_model->get_module($mid);
        if ( ! $module) {
            return $this->_complete_module_json(['success' => false, 'message' => 'Module not found.']);
        }

        if ($user->role === 'employee') {
            if ( ! $this->course_model->has_approved_enrollment($user->id, (int) $module->course_id)) {
                return $this->_complete_module_json(['success' => false, 'message' => 'Not approved for this course.']);
            }
        }

        if ( ! $this->assessment_service->employee_may_complete_module_with_video_checkpoints(
            (int) $user->id,
            $module
        )) {
            return $this->_complete_module_json([
                'success' => false,
                'message' => 'Complete all requirements for this module (required video checkpoints and passing post-assessment) before marking complete.',
            ]);
        }

        $this->course_model->complete_module($user->id, $mid, null);

        $course_id = (int) $module->course_id;
        $uid       = (int) $user->id;

        log_message(
            'debug',
            'FINAL MODULE MARKED COMPLETE '
            . json_encode([
                'user_id'   => $uid,
                'module_id' => $mid,
                'course_id' => $course_id,
            ])
        );

        $this->assessment_service->invalidate_course_progress_aggregate_cache($uid, $course_id);
        usleep(100000);
        $modules = $this->course_model->get_modules($course_id, $uid);

        // Certificate gate: DB-only; pass prefetch so gate uses same snapshot as logs.
        $course_completed = $this->assessment_service->is_course_fully_completed($uid, $course_id, $modules);

        $incomplete_result = $this->db
            ->select('COUNT(*) AS c', false)
            ->from('module_progress mp')
            ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
            ->where('cm.course_id', $course_id)
            ->where('cm.archived', 0)
            ->where('mp.user_id', $uid)
            ->where('mp.status <>', 'completed')
            ->get();
        $incomplete_row = ($incomplete_result && $incomplete_result->num_rows() > 0)
            ? $incomplete_result->row()
            : null;
        $incomplete_count = $incomplete_row ? (int) $incomplete_row->c : 0;
        log_message('debug', 'CERT FINAL CHECK SQL incomplete_count=' . $incomplete_count);

        log_message('debug', 'MODULE COMPLETE CHECK: ' . json_encode([
            'user_id'   => $uid,
            'course_id' => $course_id,
            'completed' => $course_completed,
        ]));

        // UI / catalog only — same DB snapshot as completion gate (not used for certificate decision).
        $agg_ui = $this->assessment_service->get_course_progress_aggregate($uid, $course_id, $modules);
        $progress_pct = (int) ($agg_ui['course_progress_percent'] ?? 0);

        $certificate_url = null;
        $certificate = null;
        $certificate_generated_now = false;

        if ($course_completed) {
            log_message('debug', 'CERT CHECK PASS');

            $this->load->library('certificate_service');

            if ( ! class_exists('Certificate_service', false)) {
                log_message('error', 'Certificate_service NOT LOADED');
            }

            $this->load->model('Certificate_model', 'certificate_model');

            $existing = $this->certificate_model->get_by_user_course($uid, $course_id);
            if ($existing) {
                log_message('debug', 'CERT SKIPPED (already exists)');
                $certificate = $existing;
                $certificate_url = base_url(
                    'index.php/certificates/view/' . (int) $existing->id
                );
            } else {
                $certificate = $this->certificate_service->generate($uid, $course_id);

                if ($certificate) {
                    $certificate_generated_now = true;
                    log_message('debug', 'CERT GENERATED SUCCESS');
                    $certificate_url = base_url(
                        'index.php/certificates/view/' . (int) $certificate->id
                    );
                }
            }

            if ($certificate_generated_now && $certificate) {
                $this->load->library('event_dispatcher');
                $this->event_dispatcher->dispatch('course.completed', [
                    'user_id'        => (int) $uid,
                    'course_id'      => (int) $course_id,
                    'certificate_id' => (int) $certificate->id,
                ]);
            }
        }

        $payload = [
            'ok'               => true,
            'success'          => true,
            'module_completed' => true,
            'course_completed' => $course_completed,
            'certificate_url'  => $certificate_url,
            'message'          => 'Module marked complete.',
            // Legacy / toast only — derived from aggregate, not certificate gate.
            'progress_pct'     => $progress_pct,
            'course_done'      => $course_completed,
        ];

        if ($course_completed && $certificate_url === null) {
            $payload['certificate_ready']    = false;
            $payload['certificate_message'] = 'Course completed; certificate could not be issued automatically. Please contact support.';
        } elseif ($certificate_url !== null) {
            $payload['certificate_ready'] = true;
        }

        return $this->_complete_module_json($payload);
    }

    /**
     * JSON response for module completion (AJAX).
     *
     * @param array $payload
     */
    private function _complete_module_json(array $payload)
    {
        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        $payload_json = json_encode($payload, $flags);
        if ($payload_json === false) {
            log_message('error', 'complete_module_json: json_encode failed — ' . json_last_error_msg());
            $payload_json = '{"success":false,"message":"Server error."}';
        }

        $this->output->enable_profiler(false);

        return $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output($payload_json);
    }
}
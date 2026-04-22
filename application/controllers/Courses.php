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
 * @property Course_model         $course_model
 * @property Assessment_service $assessment_service
 * @property CI_Output               $output   Loaded by CI_Controller (JSON helpers use $this->output)
 */
class Courses extends CI_Controller {

    /** @var object Authenticated user row */
    private $user;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('User_model',   'user_model');
        $this->load->model('Course_model',             'course_model');
        $this->load->library('assessment_service');
        $this->load->helper('url');

        // ── Auth guard ────────────────────────────────────────
        $user_id = $this->session->userdata('user_id');
        if ( ! $user_id) {
            redirect('auth/login');
        }

        $user = $this->user_model->get_user($user_id);

        if ( ! $user) {
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        if ((int) $user->banned   === 1)       { $this->session->sess_destroy(); redirect('auth/login'); }
        if ($user->status        !== 'active') { $this->session->sess_destroy(); redirect('auth/login'); }
        if ((int) $user->DELETED  === 1)       { $this->session->sess_destroy(); redirect('auth/login'); }
        if ( ! empty($user->locked_until) && strtotime($user->locked_until) > time()) {
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

        // ── Attach per-user enrollment & progress to each course ──
        foreach ($courses as $course) {
            $course->total_modules  = (int) ($course->total_modules  ?? 0);
            $course->total_enrolled = (int) ($course->total_enrolled ?? 0);
            $cid                    = (int) $course->id;
            $course->enrollment_status = $status_map[$cid] ?? null;
            $course->is_enrolled    = in_array($cid, $enrolled_ids, true);

            // Only calculate progress for enrolled users with modules
            if ($course->is_enrolled && $course->total_modules > 0) {
                $course->my_progress = $this->course_model
                    ->get_progress_pct($user->id, $course->id);
            } else {
                $course->my_progress = 0;
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
        //          my_status (not_started/in_progress/completed),
        //          my_score, my_completed_at
        $modules = $this->course_model->get_modules($id, $user->id);

        // ── Enrollment row (or null) ──────────────────────────
        $enrollment         = $this->course_model->get_enrollment($user->id, $id);
        $enrollment_status  = $enrollment && isset($enrollment->status)
            ? (string) $enrollment->status
            : ($enrollment ? 'approved' : null);
        $is_enrolled        = $this->course_model->has_approved_enrollment($user->id, $id);

        // ── Progress stats ────────────────────────────────────
        $total_modules     = count($modules);
        $completed_modules = 0;
        foreach ($modules as $m) {
            if ($m->my_status === 'completed') $completed_modules++;
        }
        $progress_pct = $total_modules > 0
            ? (int) round(($completed_modules / $total_modules) * 100)
            : 0;

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

        // Only employees can self-enroll
        if ($user->role !== 'employee') {
            redirect('courses/view/' . $id);
        }

        // Course must exist and be published (archived = 0)
        $course = $this->course_model->get_course($id);
        if ( ! $course) show_404();

        $ok = $this->course_model->request_enrollment($user->id, $id);

        if ($ok) {
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

        if ($user->role !== 'employee') {
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
     * POST JSON — mark module complete (employee with approved enrollment).
     * URL: index.php/courses/complete_module/{module_id}
     */
    public function complete_module($module_id = null)
    {
        if ($this->input->method(true) !== 'post') {
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
                'message' => 'You must complete all video checkpoints before finishing this module.',
            ]);
        }

        $this->course_model->complete_module($user->id, $mid, null);

        $course_id    = (int) $module->course_id;
        $total_mods   = $this->course_model->count_modules($course_id);
        $done_mods    = $this->course_model->count_completed_modules($user->id, $course_id);
        $progress_pct = $total_mods > 0
            ? (int) round(($done_mods / $total_mods) * 100)
            : 0;
        $course_done = ($total_mods > 0 && $done_mods >= $total_mods);

        return $this->_complete_module_json([
            'success'       => true,
            'message'       => 'Already completed.',
            'progress_pct'  => $progress_pct,
            'course_done'   => $course_done,
        ]);
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

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output($payload_json);
    }
}
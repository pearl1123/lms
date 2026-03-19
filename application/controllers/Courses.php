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
 */
class Courses extends CI_Controller {

    /** @var object Authenticated user row */
    private $user;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('User_model',   'user_model');
        $this->load->model('Course_model', 'course_model');
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

        // ── Get this user's enrolled course IDs in one query ──
        $enrolled_ids = $this->course_model->get_enrolled_ids($user->id);

        // ── Attach per-user enrollment & progress to each course ──
        foreach ($courses as $course) {
            $course->total_modules  = (int) ($course->total_modules  ?? 0);
            $course->total_enrolled = (int) ($course->total_enrolled ?? 0);
            $course->is_enrolled    = in_array((int) $course->id, $enrolled_ids);

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

        $this->load->view('layouts/main', $data);
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
        $enrollment  = $this->course_model->get_enrollment($user->id, $id);
        $is_enrolled = (bool) $enrollment;

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
            'is_enrolled'       => $is_enrolled,
            'enrollment'        => $enrollment,
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

        $this->load->view('layouts/main', $data);
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

        // enroll() returns false if already enrolled, true on insert
        $enrolled = $this->course_model->enroll($user->id, $id);

        if ($enrolled) {
            $this->session->set_flashdata(
                'success',
                'You have successfully enrolled in <strong>'
                . htmlspecialchars($course->title) . '</strong>!'
            );
        } else {
            $this->session->set_flashdata(
                'info',
                'You are already enrolled in this course.'
            );
        }

        redirect('courses/view/' . $id);
    }
}
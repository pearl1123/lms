<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * My_courses Controller
 * URL: index.php/my_courses
 *
 * Routes:
 *   GET  index.php/my_courses          → role-based course list
 *
 * @property CI_DB_mysqli_driver  $db
 * @property CI_Session           $session
 * @property CI_Input             $input
 * @property User_model           $user_model
 */
class My_courses extends CI_Controller {

    /** @var object Authenticated user row */
    private $user;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('User_model', 'user_model');
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

        // Keep session user array fresh for sidebar
        $this->session->set_userdata('user', [
            'id'          => $user->id,
            'fullname'    => $user->fullname,
            'employee_id' => $user->employee_id,
            'role'        => $user->role,
            'status'      => $user->status,
        ]);
    }

    // =========================================================
    // index() — dispatches by role
    // URL: index.php/my_courses
    // =========================================================
    public function index()
    {
        switch ($this->user->role) {
            case 'admin':   $this->_admin();      break;
            case 'teacher': $this->_instructor(); break;
            default:        $this->_employee();   break;
        }
    }

    // =========================================================
    // ADMIN — all courses in the system
    // =========================================================
    private function _admin()
    {
        $user = $this->user;

        $courses_result = $this->db
            ->select('c.id, c.title, c.description, c.archived,
                      c.created_at, c.category_id,
                      cc.name AS category_name')
            ->from('courses c')
            ->join('course_categories cc', 'cc.id = c.category_id', 'left')
            ->order_by('c.created_at', 'DESC')
            ->get();

        $courses = [];
        if ($courses_result && $courses_result->num_rows() > 0) {
            foreach ($courses_result->result() as $course) {

                $course->module_count = $this->db
                    ->where('course_id', $course->id)
                    ->where('archived', 0)
                    ->count_all_results('course_modules');

                $course->enrolled_count = $this->db
                    ->where('course_id', $course->id)
                    ->count_all_results('enrollments');

                $total_possible = $course->enrolled_count * $course->module_count;
                if ($total_possible > 0) {
                    $done = $this->db
                        ->select('COUNT(*) AS cnt')
                        ->from('module_progress mp')
                        ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
                        ->where('cm.course_id', $course->id)
                        ->where('mp.status', 'completed')
                        ->get()->row();
                    $course->avg_progress = $done
                        ? round(((int) $done->cnt / $total_possible) * 100)
                        : 0;
                } else {
                    $course->avg_progress = 0;
                }

                $courses[] = $course;
            }
        }

        $categories = $this->db
            ->where('archived', 0)
            ->order_by('name', 'ASC')
            ->get('course_categories')
            ->result();

        $data = [
            'user'          => $user,
            'page_title'    => 'Course Management',
            'courses'       => $courses,
            'categories'    => $categories,
            'total_courses' => count($courses),
            'breadcrumbs'   => [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => 'Course Management'],
            ],
            'view'          => 'my_courses/admin',
        ];

        $this->load->view('layouts/main', $data);
    }

    // =========================================================
    // INSTRUCTOR / TEACHER — only their own courses
    // =========================================================
    private function _instructor()
    {
        $user = $this->user;

        $courses_result = $this->db
            ->select('c.id, c.title, c.description, c.archived,
                      c.created_at, c.category_id,
                      cc.name AS category_name')
            ->from('courses c')
            ->join('course_categories cc', 'cc.id = c.category_id', 'left')
            ->where('c.created_by', $user->id)
            ->order_by('c.created_at', 'DESC')
            ->get();

        $my_courses_list = [];
        if ($courses_result && $courses_result->num_rows() > 0) {
            foreach ($courses_result->result() as $course) {

                $course->module_count = $this->db
                    ->where('course_id', $course->id)
                    ->where('archived', 0)
                    ->count_all_results('course_modules');

                $course->enrolled_count = $this->db
                    ->where('course_id', $course->id)
                    ->count_all_results('enrollments');

                $total_possible = $course->enrolled_count * $course->module_count;
                if ($total_possible > 0) {
                    $done = $this->db
                        ->select('COUNT(*) AS cnt')
                        ->from('module_progress mp')
                        ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
                        ->where('cm.course_id', $course->id)
                        ->where('mp.status', 'completed')
                        ->get()->row();
                    $course->avg_progress = $done
                        ? round(((int) $done->cnt / $total_possible) * 100)
                        : 0;
                } else {
                    $course->avg_progress = 0;
                }

                $my_courses_list[] = $course;
            }
        }

        $categories = $this->db
            ->where('archived', 0)
            ->order_by('name', 'ASC')
            ->get('course_categories')
            ->result();

        $data = [
            'user'            => $user,
            'page_title'      => 'My Courses',
            'my_courses_list' => $my_courses_list,
            'categories'      => $categories,
            'breadcrumbs'     => [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => 'My Courses'],
            ],
            'view'            => 'my_courses/instructor',
        ];

        $this->load->view('layouts/main', $data);
    }

    // =========================================================
    // EMPLOYEE — enrolled + available courses
    // =========================================================
    private function _employee()
    {
        $user = $this->user;

        // ── Enrolled courses ──────────────────────────────────
        $enrolled_result = $this->db
            ->select('e.course_id, e.enrolled_at,
                      c.title, c.description, c.category_id,
                      cc.name AS category_name')
            ->from('enrollments e')
            ->join('courses c',           'c.id = e.course_id',     'left')
            ->join('course_categories cc', 'cc.id = c.category_id', 'left')
            ->where('e.user_id',  $user->id)
            ->where('c.archived', 0)
            ->order_by('e.enrolled_at', 'DESC')
            ->get();

        $enrolled_courses = [];
        if ($enrolled_result && $enrolled_result->num_rows() > 0) {
            foreach ($enrolled_result->result() as $ec) {

                $total_modules = $this->db
                    ->where('course_id', $ec->course_id)
                    ->where('archived', 0)
                    ->count_all_results('course_modules');

                $ec->module_count = $total_modules;

                if ($total_modules > 0) {
                    $done = $this->db
                        ->select('COUNT(*) AS cnt')
                        ->from('module_progress mp')
                        ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
                        ->where('cm.course_id', $ec->course_id)
                        ->where('mp.user_id',   $user->id)
                        ->where('mp.status',    'completed')
                        ->get()->row();
                    $done_count = $done ? (int) $done->cnt : 0;
                } else {
                    $done_count = 0;
                }

                $ec->modules_done = $done_count;
                $ec->progress_pct = $total_modules > 0
                    ? round(($done_count / $total_modules) * 100)
                    : 0;

                $enrolled_courses[] = $ec;
            }
        }

        // ── Available (not yet enrolled) courses ──────────────
        $enrolled_ids = array_map(fn($c) => $c->course_id, $enrolled_courses);

        $avail_query = $this->db
            ->select('c.id, c.title, c.description, c.category_id,
                      cc.name AS category_name')
            ->from('courses c')
            ->join('course_categories cc', 'cc.id = c.category_id', 'left')
            ->where('c.archived', 0);

        if ( ! empty($enrolled_ids)) {
            $avail_query->where_not_in('c.id', $enrolled_ids);
        }

        $avail_result     = $avail_query->order_by('c.created_at', 'DESC')->get();
        $available_courses = [];
        if ($avail_result && $avail_result->num_rows() > 0) {
            foreach ($avail_result->result() as $ac) {
                $ac->module_count = $this->db
                    ->where('course_id', $ac->id)
                    ->where('archived', 0)
                    ->count_all_results('course_modules');
                $available_courses[] = $ac;
            }
        }

        // ── Categories for filter dropdown ────────────────────
        $categories = $this->db
            ->where('archived', 0)
            ->order_by('name', 'ASC')
            ->get('course_categories')
            ->result();

        $data = [
            'user'              => $user,
            'page_title'        => 'My Learning',
            'enrolled_courses'  => $enrolled_courses,
            'available_courses' => $available_courses,
            'categories'        => $categories,
            'breadcrumbs'       => [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => 'My Learning'],
            ],
            'view'              => 'my_courses/employee',
        ];

        $this->load->view('layouts/main', $data);
    }
}
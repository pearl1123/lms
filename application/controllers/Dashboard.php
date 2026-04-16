<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * @property CI_DB_mysqli_driver      $db
 * @property CI_Session               $session
 * @property CI_Input                 $input
 * @property CI_Form_validation       $form_validation
 * @property User_model               $user_model
 */
class Dashboard extends CI_Controller {

    /** @var object Current authenticated user row */
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

        if ((int) $user->banned    === 1)       { $this->session->sess_destroy(); redirect('auth/login'); }
        if ($user->status         !== 'active') { $this->session->sess_destroy(); redirect('auth/login'); }
        if ((int) $user->DELETED   === 1)       { $this->session->sess_destroy(); redirect('auth/login'); }
        if ( ! empty($user->locked_until) && strtotime($user->locked_until) > time()) {
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        $this->user = $user;

        // ── Store user in session as array so all views/sidebar can read it ──
        $this->session->set_userdata('user', [
            'id'          => $user->id,
            'fullname'    => $user->fullname,
            'employee_id' => $user->employee_id,
            'role'        => $user->role,
            'status'      => $user->status,
        ]);
    }

    // =========================================================
    public function index()
    // =========================================================
    {
        switch ($this->user->role) {
            case 'admin':    $this->_admin_dashboard();    break;
            case 'teacher':  $this->_teacher_dashboard();  break;
            case 'employee':
            default:         $this->_employee_dashboard(); break;
        }
    }

    // =========================================================
    // ADMIN DASHBOARD
    // =========================================================
    private function _admin_dashboard()
    {
        $user = $this->user;

        $analytics = [
            'total_users'     => 0,
            'active_users'    => 0,
            'inactive_users'  => 0,
            'total_admins'    => 0,
            'total_teachers'  => 0,
            'total_employees' => 0,
            'total_courses'   => 0,
        ];

        $analytics['total_users'] = $this->db
            ->where('DELETED', 0)
            ->count_all_results('aauth_users');

        $analytics['active_users'] = $this->db
            ->where('status', 'active')
            ->where('DELETED', 0)
            ->count_all_results('aauth_users');

        $analytics['inactive_users'] = $this->db
            ->where('status', 'inactive')
            ->where('DELETED', 0)
            ->count_all_results('aauth_users');

        $analytics['total_admins'] = $this->db
            ->where('role', 'admin')
            ->where('DELETED', 0)
            ->count_all_results('aauth_users');

        $analytics['total_teachers'] = $this->db
            ->where('role', 'teacher')
            ->where('DELETED', 0)
            ->count_all_results('aauth_users');

        $analytics['total_employees'] = $this->db
            ->where('role', 'employee')
            ->where('DELETED', 0)
            ->count_all_results('aauth_users');

        $analytics['total_courses'] = $this->db
            ->where('archived', 0)
            ->count_all_results('courses');

        $latest_enrollments = $this->db
            ->select('e.id, e.enrolled_at,
                      u.fullname, u.employee_id, u.role,
                      c.title AS course_title')
            ->from('enrollments e')
            ->join('aauth_users u', 'u.id = e.user_id',  'left')
            ->join('courses c',     'c.id = e.course_id', 'left')
            ->where('e.status', 'approved')
            ->where('u.DELETED', 0)
            ->order_by('e.enrolled_at', 'DESC')
            ->limit(10)
            ->get()
            ->result();

        $data = [
            'user'               => $user,
            'page_title'         => 'Admin Dashboard',
            'analytics'          => $analytics,
            'latest_enrollments' => $latest_enrollments,
            'breadcrumbs'        => [['label' => 'Dashboard']],
            'view'               => 'dashboard/admin',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // TEACHER / INSTRUCTOR DASHBOARD
    // =========================================================
    private function _teacher_dashboard()
    {
        $user = $this->user;

        $analytics = [
            'my_courses'        => 0,
            'enrolled_students' => 0,
            'avg_completion'    => 0,
            'pending_reviews'   => 0,
        ];

        $analytics['my_courses'] = $this->db
            ->where('created_by', $user->id)
            ->where('archived', 0)
            ->count_all_results('courses');

        $course_result = $this->db
            ->select('id')
            ->where('created_by', $user->id)
            ->where('archived', 0)
            ->get('courses');

        $course_ids = [];
        if ($course_result && $course_result->num_rows() > 0) {
            foreach ($course_result->result_array() as $row) {
                $course_ids[] = (int) $row['id'];
            }
        }

        if ( ! empty($course_ids)) {
            $analytics['enrolled_students'] = $this->db
                ->where_in('course_id', $course_ids)
                ->where('status', 'approved')
                ->count_all_results('enrollments');

            $comp_result = $this->db
                ->select('COUNT(DISTINCT mp.user_id) AS cnt')
                ->from('module_progress mp')
                ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
                ->where_in('cm.course_id', $course_ids)
                ->where('mp.status', 'completed')
                ->get();

            $completed_count = ($comp_result && $comp_result->row())
                ? (int) $comp_result->row()->cnt : 0;

            $analytics['avg_completion'] = $analytics['enrolled_students'] > 0
                ? round(($completed_count / $analytics['enrolled_students']) * 100)
                : 0;
        }

        $my_courses_list = [];
        if ( ! empty($course_ids)) {
            $courses_result = $this->db
                ->select('c.id, c.title, c.archived, c.created_at')
                ->from('courses c')
                ->where('c.created_by', $user->id)
                ->where('c.archived', 0)
                ->order_by('c.created_at', 'DESC')
                ->get();

            if ($courses_result && $courses_result->num_rows() > 0) {
                foreach ($courses_result->result() as $course) {

                    $course->enrolled_count = $this->db
                        ->where('course_id', $course->id)
                        ->where('status', 'approved')
                        ->count_all_results('enrollments');

                    $course->module_count = $this->db
                        ->where('course_id', $course->id)
                        ->where('archived', 0)
                        ->count_all_results('course_modules');

                    $total_possible = $course->enrolled_count * $course->module_count;
                    if ($total_possible > 0) {
                        $done_result = $this->db
                            ->select('COUNT(*) AS cnt')
                            ->from('module_progress mp')
                            ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
                            ->where('cm.course_id', $course->id)
                            ->where('mp.status', 'completed')
                            ->get();
                        $done_cnt = ($done_result && $done_result->row())
                            ? (int) $done_result->row()->cnt : 0;
                        $course->avg_progress = round(($done_cnt / $total_possible) * 100);
                    } else {
                        $course->avg_progress = 0;
                    }

                    $course->status_label = 'Published';
                    $my_courses_list[] = $course;
                }
            }
        }

        $student_list = [];
        if ( ! empty($course_ids)) {
            $students_result = $this->db
                ->select('u.id, u.fullname, u.employee_id,
                          e.course_id, e.enrolled_at,
                          c.title AS course_title')
                ->from('enrollments e')
                ->join('aauth_users u', 'u.id = e.user_id',  'left')
                ->join('courses c',     'c.id = e.course_id', 'left')
                ->where_in('e.course_id', $course_ids)
                ->where('e.status', 'approved')
                ->where('u.DELETED', 0)
                ->order_by('e.enrolled_at', 'DESC')
                ->limit(10)
                ->get();

            if ($students_result && $students_result->num_rows() > 0) {
                foreach ($students_result->result() as $student) {
                    $total_modules = $this->db
                        ->where('course_id', $student->course_id)
                        ->where('archived', 0)
                        ->count_all_results('course_modules');

                    if ($total_modules > 0) {
                        $done_result = $this->db
                            ->select('COUNT(*) AS cnt')
                            ->from('module_progress mp')
                            ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
                            ->where('cm.course_id', $student->course_id)
                            ->where('mp.user_id',   $student->id)
                            ->where('mp.status',    'completed')
                            ->get();
                        $student->progress_pct = ($done_result && $done_result->row())
                            ? round(((int)$done_result->row()->cnt / $total_modules) * 100)
                            : 0;
                    } else {
                        $student->progress_pct = 0;
                    }

                    $student_list[] = $student;
                }
            }
        }

        $data = [
            'user'            => $user,
            'page_title'      => 'Instructor Dashboard',
            'analytics'       => $analytics,
            'my_courses_list' => $my_courses_list,
            'student_list'    => $student_list,
            'breadcrumbs'     => [['label' => 'Dashboard']],
            'view'            => 'dashboard/instructor',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // EMPLOYEE / STUDENT DASHBOARD
    // =========================================================
    private function _employee_dashboard()
    {
        $user = $this->user;

        $analytics = [
            'courses_enrolled'  => 0,
            'courses_completed' => 0,
            'learning_hours'    => '0h',
            'badges'            => 0,
        ];

        $enrolled_result = $this->db
            ->select('e.course_id, e.enrolled_at, c.title')
            ->from('enrollments e')
            ->join('courses c', 'c.id = e.course_id', 'left')
            ->where('e.user_id', $user->id)
            ->where('e.status', 'approved')
            ->where('c.archived', 0)
            ->order_by('e.enrolled_at', 'DESC')
            ->get();

        $enrolled_courses = [];
        if ($enrolled_result && $enrolled_result->num_rows() > 0) {
            $enrolled_courses = $enrolled_result->result();
        }

        $analytics['courses_enrolled'] = count($enrolled_courses);

        $completed_courses  = 0;
        $total_modules_done = 0;

        foreach ($enrolled_courses as $ec) {
            $total_modules = $this->db
                ->where('course_id', $ec->course_id)
                ->where('archived', 0)
                ->count_all_results('course_modules');

            if ($total_modules > 0) {
                $done_result = $this->db
                    ->select('COUNT(*) AS cnt')
                    ->from('module_progress mp')
                    ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
                    ->where('cm.course_id', $ec->course_id)
                    ->where('mp.user_id',   $user->id)
                    ->where('mp.status',    'completed')
                    ->get();

                $done_count = ($done_result && $done_result->row())
                    ? (int) $done_result->row()->cnt : 0;

                $total_modules_done += $done_count;
                $ec->progress_pct    = round(($done_count / $total_modules) * 100);
                $ec->module_count    = $total_modules;
                $ec->modules_done    = $done_count;

                if ($done_count >= $total_modules) $completed_courses++;
            } else {
                $ec->progress_pct = 0;
                $ec->module_count = 0;
                $ec->modules_done = 0;
            }
        }

        $analytics['courses_completed'] = $completed_courses;
        $total_minutes = $total_modules_done * 30;
        $analytics['learning_hours'] = floor($total_minutes / 60) . 'h '
                                     . ($total_minutes % 60) . 'm';

        $lessons        = [];
        $next_course_id = null;

        foreach ($enrolled_courses as $ec) {
            if ($ec->progress_pct > 0 && $ec->progress_pct < 100) {
                $next_course_id = $ec->course_id;
                break;
            }
        }
        if ( ! $next_course_id && ! empty($enrolled_courses)) {
            $next_course_id = $enrolled_courses[0]->course_id;
        }

        if ($next_course_id) {
            $module_result = $this->db
                ->select('cm.id, cm.title, mp.status')
                ->from('course_modules cm')
                ->join('module_progress mp',
                       'mp.module_id = cm.id AND mp.user_id = ' . (int) $user->id,
                       'left')
                ->where('cm.course_id', $next_course_id)
                ->where('cm.archived',  0)
                ->order_by('cm.module_order', 'ASC')
                ->limit(5)
                ->get();

            if ($module_result && $module_result->num_rows() > 0) {
                $lessons = array_column($module_result->result_array(), 'title');
            }
        }

        if (empty($lessons)) {
            $lessons = [
                'Introduction to Infection Control',
                'Hand Hygiene Protocols',
                'PPE Usage & Standards',
                'Isolation Precautions',
                'Final Assessment',
            ];
        }

        $top_result = $this->db
            ->select('u.fullname AS name, COUNT(mp.id) AS points')
            ->from('module_progress mp')
            ->join('aauth_users u', 'u.id = mp.user_id', 'left')
            ->where('mp.status', 'completed')
            ->where('u.DELETED', 0)
            ->where('u.role',    'employee')
            ->group_by('mp.user_id')
            ->order_by('points', 'DESC')
            ->limit(5)
            ->get();

        $top_learners = ($top_result && $top_result->num_rows() > 0)
            ? $top_result->result_array()
            : [
                ['name' => 'Alice Mendoza', 'points' => 980],
                ['name' => 'John Bautista', 'points' => 870],
                ['name' => 'Maria Garcia',  'points' => 850],
                ['name' => 'Carlos Reyes',  'points' => 820],
                ['name' => 'Ana Santos',    'points' => 790],
            ];

        $data = [
            'user'             => $user,
            'page_title'       => 'My Learning Dashboard',
            'analytics'        => $analytics,
            'enrolled_courses' => $enrolled_courses,
            'top_learners'     => $top_learners,
            'lessons'          => $lessons,
            'breadcrumbs'      => [['label' => 'Dashboard']],
            'view'             => 'dashboard/employee',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }
}
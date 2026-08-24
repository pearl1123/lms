<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * My_courses Controller
 * URL: index.php/my_courses
 *
 * Routes:
 *   GET  index.php/my_courses          → role-based course list
 *
 * @property My_courses_model     $my_courses_model
 * @property Course_model         $course_model
 * @property Course_phase2_model  $course_phase2
 * @property Assessment_service   $assessment_service
 */
class My_courses extends KA_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('My_courses_model', 'my_courses_model');
        $this->load->model('Course_model', 'course_model');
        $this->load->model('Course_phase2_model', 'course_phase2');
        $this->load->library('assessment_service');

        $this->require_permission('my_courses.view');
    }

    // =========================================================
    // index() — dispatches by role
    // URL: index.php/my_courses
    // =========================================================
    public function index()
    {
        switch (ka_user_effective_experience_role($this->auth_user->role ?? '')) {
            case 'admin':   $this->_admin();      break;
            case 'teacher':
            case 'instructor':
                if (ka_user_acting_as_learner($this->auth_user->role ?? '')) {
                    $this->_employee();
                } else {
                    $this->_instructor();
                }
                break;
            case 'employee':
            case 'student':
                $this->_employee();
                break;
            default:
                $this->_employee();
                break;
        }
    }

    // =========================================================
    // ADMIN — all courses in the system
    // =========================================================
    private function _admin()
    {
        $user = $this->auth_user;

        $courses    = $this->my_courses_model->get_admin_courses_with_stats();
        $categories = $this->my_courses_model->get_active_categories();

        $status_param = strtolower(trim((string) $this->input->get('status', true)));
        $filter_status = '';
        $kpi_active    = 'all';

        if ($status_param === 'published' || $status_param === '0') {
            $filter_status = '0';
            $kpi_active    = 'published';
        } elseif ($status_param === 'archived' || $status_param === '1') {
            $filter_status = '1';
            $kpi_active    = 'archived';
        } elseif ($status_param === 'enrollments') {
            $kpi_active = 'enrollments';
        }

        $data = [
            'user'          => $user,
            'page_title'    => 'Course Management',
            'courses'       => $courses,
            'categories'    => $categories,
            'total_courses' => count($courses),
            'keyword'       => trim((string) $this->input->get('q', true)),
            'filter_cat'    => $this->input->get('category', true) ?: '',
            'filter_status' => $filter_status,
            'kpi_active'    => $kpi_active,
            'breadcrumbs'   => [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => 'Course Management'],
            ],
            'view'          => 'my_courses/admin',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // INSTRUCTOR / TEACHER — only their own courses
    // =========================================================
    private function _instructor()
    {
        $user = $this->auth_user;

        $my_courses_list = $this->my_courses_model->get_instructor_courses_with_stats((int) $user->id);
        $categories      = $this->my_courses_model->get_active_categories();

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

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // EMPLOYEE — enrolled + available courses
    // =========================================================
    private function _employee()
    {
        $user = $this->auth_user;

        $pending_enrollments  = $this->my_courses_model->get_user_enrollments_by_status((int) $user->id, 'pending');
        $rejected_enrollments = $this->my_courses_model->get_user_enrollments_by_status((int) $user->id, 'rejected');

        $enrolled_rows = $this->my_courses_model->get_user_enrollments_by_status((int) $user->id, 'approved');
        $enrolled_courses = [];
        foreach ($enrolled_rows as $ec) {
            $full_course = $this->course_model->get_course((int) $ec->course_id);
            if ($full_course) {
                $ec->modality_name = (string) ($full_course->modality_name ?? '');
                $ec->show_f2f_notice = function_exists('etd_is_face_to_face_modality')
                    && etd_is_face_to_face_modality($ec->modality_name);
            }
            $agg = $this->assessment_service->get_course_progress_aggregate(
                (int) $user->id,
                (int) $ec->course_id
            );

            $ec->module_count            = (int) $agg['total_modules'];
            $ec->modules_done            = (int) $agg['completed_modules'];
            $ec->course_progress_percent = (int) $agg['course_progress_percent'];
            $ec->progress_pct            = $ec->course_progress_percent;
            $ec->course_cta              = get_course_cta((int) $ec->course_id, (int) $user->id, ka_lms_return_q('my_courses'));

            $enrolled_courses[] = $ec;
        }

        $blocked_ids     = $this->my_courses_model->get_blocked_enrollment_course_ids((int) $user->id);
        $invited_courses = $this->course_phase2->get_invited_courses_for_user((int) $user->id);
        $invited_ids     = array_map(static function ($row) {
            return (int) ($row->course_id ?? 0);
        }, $invited_courses);

        $GLOBALS['ka_catalog_viewer'] = $user;
        $avail_rows = $this->course_model->get_catalog('', 0);
        $available_courses = [];
        foreach ($avail_rows as $ac) {
            $cid = (int) $ac->id;
            if (in_array($cid, $blocked_ids, true) || in_array($cid, $invited_ids, true)) {
                continue;
            }
            $ac->module_count = (int) ($ac->total_modules ?? 0);
            $available_courses[] = $ac;
        }

        $categories = $this->my_courses_model->get_active_categories();

        $data = [
            'user'                 => $user,
            'page_title'           => 'My Learning',
            'enrolled_courses'     => $enrolled_courses,
            'invited_courses'      => $invited_courses,
            'pending_enrollments'  => $pending_enrollments,
            'rejected_enrollments' => $rejected_enrollments,
            'available_courses'    => $available_courses,
            'categories'           => $categories,
            'breadcrumbs'          => [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => 'My Learning'],
            ],
            'view'               => 'my_courses/employee',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }
}

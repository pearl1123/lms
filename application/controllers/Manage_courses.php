<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Manage_courses Controller
 *
 * Routes:
 *   GET  index.php/manage_courses              → course list (role-based)
 *   GET  index.php/manage_courses/create       → create form
 *   POST index.php/manage_courses/create       → save new course
 *   GET  index.php/manage_courses/edit/{id}    → edit course + module builder
 *   POST index.php/manage_courses/edit/{id}    → save course details
 *   GET  index.php/manage_courses/delete/{id}  → soft-delete course
 *   POST index.php/manage_courses/save_module  → AJAX: create/update module
 *   POST index.php/manage_courses/delete_module→ AJAX: soft-delete module
 *   POST index.php/manage_courses/reorder_modules → AJAX: save new order
 *   POST index.php/manage_courses/reassign     → admin reassigns course owner
 *
 * @property CI_DB_mysqli_driver  $db
 * @property CI_Session           $session
 * @property CI_Input             $input
 * @property CI_Form_validation   $form_validation
 * @property User_model           $user_model
 * @property Course_model         $course_model
 * @property Assessment_model     $assessment_model
 */
class Manage_courses extends CI_Controller {

    private $user;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->model('User_model',        'user_model');
        $this->load->model('Course_model',      'course_model');
        $this->load->model('Assessment_model',  'assessment_model');
        $this->load->helper(['url', 'form']);

        $user_id = $this->session->userdata('user_id');
        if ( ! $user_id) redirect('auth/login');

        $user = $this->user_model->get_user($user_id);
        if ( ! $user)                                          { $this->session->sess_destroy(); redirect('auth/login'); }
        if ((int) $user->banned  === 1)                       { $this->session->sess_destroy(); redirect('auth/login'); }
        if ($user->status       !== 'active')                 { $this->session->sess_destroy(); redirect('auth/login'); }
        if ((int) $user->DELETED === 1)                       { $this->session->sess_destroy(); redirect('auth/login'); }
        if ( ! empty($user->locked_until) && strtotime($user->locked_until) > time()) {
            $this->session->sess_destroy(); redirect('auth/login');
        }

        // Only admin and teacher can access this controller
        if ( ! in_array($user->role, ['admin', 'teacher'])) {
            $this->session->set_flashdata('error', 'You do not have permission to manage courses.');
            redirect('my_courses');
        }

        $this->user = $user;

        $this->session->set_userdata('user', [
            'id'          => $user->id,
            'fullname'    => $user->fullname,
            'employee_id' => $user->employee_id,
            'role'        => $user->role,
            'status'      => $user->status,
        ]);
    }

    // =========================================================
    // index() — Course management list
    // =========================================================
    public function index()
    {
        $user = $this->user;

        $courses = $user->role === 'admin'
            ? $this->course_model->get_all_courses(true)  // include archived
            : $this->course_model->get_courses_by_instructor($user->id, false);

        $data = [
            'user'        => $user,
            'page_title'  => 'Manage Courses',
            'courses'     => $courses,
            'breadcrumbs' => [
                ['label' => 'Dashboard',       'url' => 'dashboard'],
                ['label' => 'Manage Courses'],
            ],
            'view' => 'manage_courses/index',
        ];

        $this->load->view('layouts/main', $data);
    }

    // =========================================================
    // create() — GET: form  |  POST: save
    // =========================================================
    public function create()
    {
        $user = $this->user;

        if ($this->input->method() === 'post') {

            $this->form_validation
                ->set_rules('title',       'Course Title', 'required|max_length[255]')
                ->set_rules('category_id', 'Category',     'required|integer')
                ->set_rules('modality_id', 'Modality',     'required|integer');

            if ($this->form_validation->run()) {

                // Admin can assign to any teacher; teacher always creates for themselves
                $created_by = $user->role === 'admin' && $this->input->post('created_by')
                    ? (int) $this->input->post('created_by')
                    : (int) $user->id;

                $id = $this->course_model->create_course([
                    'title'          => $this->input->post('title'),
                    'description'    => $this->input->post('description'),
                    'category_id'    => $this->input->post('category_id'),
                    'modality_id'    => $this->input->post('modality_id'),
                    'access_type_id' => $this->input->post('access_type_id'),
                    'expiry_days'    => $this->input->post('expiry_days'),
                    'created_by'     => $created_by,
                ], $user->id);

                $this->session->set_flashdata('success', 'Course created! Now add your modules.');
                redirect('manage_courses/edit/' . $id);
            }
        }

        $data = [
            'user'         => $user,
            'page_title'   => 'Create Course',
            'categories'   => $this->course_model->get_categories(),
            'modalities'   => $this->course_model->get_modalities(),
            'access_types' => $this->course_model->get_access_types(),
            'teachers'     => $user->role === 'admin' ? $this->course_model->get_teachers() : [],
            'breadcrumbs'  => [
                ['label' => 'Dashboard',       'url' => 'dashboard'],
                ['label' => 'Manage Courses',  'url' => 'manage_courses'],
                ['label' => 'Create'],
            ],
            'view' => 'manage_courses/create',
        ];

        $this->load->view('layouts/main', $data);
    }

    // =========================================================
    // edit($id) — GET/POST: edit course details + module builder
    // =========================================================
    public function edit($id = null)
    {
        if ( ! $id) redirect('manage_courses');
        $id = (int) $id;

        $course = $this->course_model->get_course_any($id);
        if ( ! $course) show_404();
        $this->_check_ownership($course);

        if ($this->input->method() === 'post') {

            $this->form_validation
                ->set_rules('title',       'Course Title', 'required|max_length[255]')
                ->set_rules('category_id', 'Category',     'required|integer')
                ->set_rules('modality_id', 'Modality',     'required|integer');

            if ($this->form_validation->run()) {
                $this->course_model->update_course($id, [
                    'title'          => $this->input->post('title'),
                    'description'    => $this->input->post('description'),
                    'category_id'    => $this->input->post('category_id'),
                    'modality_id'    => $this->input->post('modality_id'),
                    'access_type_id' => $this->input->post('access_type_id'),
                    'expiry_days'    => $this->input->post('expiry_days'),
                ], $this->user->id);

                $this->session->set_flashdata('success', 'Course details updated.');
                redirect('manage_courses/edit/' . $id);
            }
        }

        $modules = $this->course_model->get_modules($id, 0);

        // Attach pre/post assessment counts to each module
        foreach ($modules as $mod) {
            $pre  = $this->assessment_model->get_assessments($mod->id, 'pre');
            $post = $this->assessment_model->get_assessments($mod->id, 'post');
            $mod->pre_count  = count($pre);
            $mod->post_count = count($post);
        }

        $data = [
            'user'         => $this->user,
            'page_title'   => 'Edit: ' . $course->title,
            'course'       => $course,
            'modules'      => $modules,
            'categories'   => $this->course_model->get_categories(),
            'modalities'   => $this->course_model->get_modalities(),
            'access_types' => $this->course_model->get_access_types(),
            'teachers'     => $this->user->role === 'admin'
                              ? $this->course_model->get_teachers() : [],
            'breadcrumbs'  => [
                ['label' => 'Dashboard',      'url' => 'dashboard'],
                ['label' => 'Manage Courses', 'url' => 'manage_courses'],
                ['label' => 'Edit: ' . $course->title],
            ],
            'view' => 'manage_courses/edit',
        ];

        $this->load->view('layouts/main', $data);
    }

    // =========================================================
    // delete($id) — Soft-delete a course
    // =========================================================
    public function delete($id = null)
    {
        if ( ! $id) redirect('manage_courses');
        $id = (int) $id;

        $course = $this->course_model->get_course_any($id);
        if ( ! $course) show_404();
        $this->_check_ownership($course);

        $this->course_model->delete_course($id, $this->user->id);
        $this->session->set_flashdata('success', '"' . $course->title . '" has been archived.');
        redirect('manage_courses');
    }

    // =========================================================
    // save_module() — AJAX POST: create or update a module
    // =========================================================
    public function save_module()
    {
        header('Content-Type: application/json');

        $course_id = (int) $this->input->post('course_id');
        $module_id = (int) $this->input->post('module_id'); // 0 = new

        // Ownership check
        $course = $this->course_model->get_course_any($course_id);
        if ( ! $course) {
            echo json_encode(['success' => false, 'message' => 'Course not found.']);
            return;
        }
        $this->_check_ownership($course, true);

        $title        = trim($this->input->post('title'));
        $content_type = $this->input->post('content_type');
        $valid_types  = ['pdf', 'slides', 'video', 'audio', 'zoom_recording'];

        if ($title === '') {
            echo json_encode(['success' => false, 'message' => 'Module title is required.']);
            return;
        }
        if ( ! in_array($content_type, $valid_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid content type.']);
            return;
        }

        $m_data = [
            'course_id'         => $course_id,
            'title'             => $title,
            'description'       => $this->input->post('description'),
            'content_type'      => $content_type,
            'content_path'      => $this->input->post('content_path'),
            'weight_percentage' => $this->input->post('weight_percentage'),
        ];

        if ($module_id > 0) {
            $this->course_model->update_module($module_id, $m_data, $this->user->id);
            $mid = $module_id;
            $msg = 'Module updated.';
        } else {
            $mid = $this->course_model->create_module($m_data, $this->user->id);
            $msg = 'Module added.';
        }

        $module = $this->course_model->get_module($mid);

        echo json_encode([
            'success'   => true,
            'message'   => $msg,
            'module_id' => $mid,
            'module'    => $module,
        ]);
    }

    // =========================================================
    // delete_module() — AJAX POST: soft-delete a module
    // =========================================================
    public function delete_module()
    {
        header('Content-Type: application/json');

        $module_id = (int) $this->input->post('module_id');
        $module    = $this->course_model->get_module($module_id);

        if ( ! $module) {
            echo json_encode(['success' => false, 'message' => 'Module not found.']);
            return;
        }

        $course = $this->course_model->get_course_any($module->course_id);
        $this->_check_ownership($course, true);

        $ok = $this->course_model->delete_module($module_id, $this->user->id);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Module deleted.' : 'Failed to delete module.',
        ]);
    }

    // =========================================================
    // reorder_modules() — AJAX POST: save drag-drop order
    // =========================================================
    public function reorder_modules()
    {
        header('Content-Type: application/json');

        $ids = $this->input->post('ids'); // array of module IDs in order
        if ( ! is_array($ids) || empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'No IDs provided.']);
            return;
        }

        $this->course_model->reorder_modules(array_map('intval', $ids));
        echo json_encode(['success' => true, 'message' => 'Order saved.']);
    }

    // =========================================================
    // reassign() — POST: admin reassigns course to a teacher
    // =========================================================
    public function reassign()
    {
        if ($this->user->role !== 'admin') {
            $this->session->set_flashdata('error', 'Only admins can reassign courses.');
            redirect('manage_courses');
        }

        $course_id   = (int) $this->input->post('course_id');
        $new_owner   = (int) $this->input->post('new_owner_id');
        $course      = $this->course_model->get_course_any($course_id);

        if ( ! $course) show_404();

        $this->course_model->reassign_course($course_id, $new_owner, $this->user->id);
        $this->session->set_flashdata('success', 'Course reassigned successfully.');
        redirect('manage_courses/edit/' . $course_id);
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    /**
     * Redirect if the current user doesn't own the course.
     * Admins always pass. Teachers must match created_by.
     *
     * @param object $course
     * @param bool   $json  If true, return JSON instead of redirect
     */
    private function _check_ownership($course, $json = false)
    {
        if ($this->user->role === 'admin') return;

        if ((int) $course->created_by !== (int) $this->user->id) {
            if ($json) {
                echo json_encode(['success' => false, 'message' => 'You can only manage your own courses.']);
                exit;
            }
            $this->session->set_flashdata('error', 'You can only manage your own courses.');
            redirect('manage_courses');
        }
    }
}
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
 *   GET  index.php/manage_courses/modules/{id} → module builder (course_id)
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
 * @property Course_phase2_model  $course_phase2
 * @property Notification_service $notification_service
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
        $this->load->model('Course_phase2_model', 'course_phase2');
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

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
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
                ->set_rules('modality_id', 'Modality',     'required|integer')
                ->set_rules('certificate_prefix', 'Certificate Prefix', 'required|alpha_numeric|max_length[12]')
                ->set_rules('signatory_name', 'Signatory Name', 'trim|max_length[120]')
                ->set_rules('signatory_title', 'Signatory Title', 'trim|max_length[120]');

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
                    'access_type'    => $this->input->post('access_type'),
                    'expiry_days'    => $this->input->post('expiry_days'),
                    'certificate_prefix' => $this->input->post('certificate_prefix'),
                    'signatory_name'     => $this->input->post('signatory_name'),
                    'signatory_title'    => $this->input->post('signatory_title'),
                    'created_by'     => $created_by,
                ], $user->id);

                if ($id > 0) {
                    $this->course_phase2->save_course_meta_from_post($id, $_POST, (int) $user->id);
                }

                $this->session->set_flashdata('success', 'Course created! Now add your modules.');
                redirect('manage_courses/edit/' . $id);
            }
        }

        $data = array_merge([
            'user'         => $user,
            'page_title'   => 'Create Course',
            'categories'   => $this->course_model->get_categories(),
            'modalities'   => $this->course_model->get_modalities(),
            'teachers'     => $user->role === 'admin' ? $this->course_model->get_teachers() : [],
            'breadcrumbs'  => [
                ['label' => 'Dashboard',       'url' => 'dashboard'],
                ['label' => 'Manage Courses',  'url' => 'manage_courses'],
                ['label' => 'Create'],
            ],
            'view' => 'manage_courses/create',
        ], $this->_phase2_view_data(0));

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
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

        $edit_rt_suffix = function () {
            $src = $this->input->post('return_url');
            if ($src === null || $src === '') {
                $src = $this->input->get('return_url');
            }
            $q = ka_lms_return_q(ka_lms_resolve_return_target($this->user, $src));

            return $q !== '' ? '?' . $q : '';
        };

        if ($this->input->method() === 'post') {
            if ($this->input->post('invite_submit')) {
                $this->_handle_course_invitations($id);
                redirect('manage_courses/edit/' . $id . $edit_rt_suffix());
            }

            $this->form_validation
                ->set_rules('title',       'Course Title', 'required|max_length[255]')
                ->set_rules('category_id', 'Category',     'required|integer')
                ->set_rules('modality_id', 'Modality',     'required|integer')
                ->set_rules('certificate_prefix', 'Certificate Prefix', 'required|alpha_numeric|max_length[12]')
                ->set_rules('signatory_name', 'Signatory Name', 'trim|max_length[120]')
                ->set_rules('signatory_title', 'Signatory Title', 'trim|max_length[120]');

            if ($this->form_validation->run()) {
                $total_weight = round((float) $this->course_model->sum_module_weights($id), 2);
                if (abs($total_weight - 100.0) > 0.01) {
                    $this->session->set_flashdata('error', 'Module weights must total exactly 100% before saving course details. Current total: ' . number_format($total_weight, 2) . '%.');
                    redirect('manage_courses/edit/' . $id . $edit_rt_suffix());
                }

                $this->course_model->update_course($id, [
                    'title'          => $this->input->post('title'),
                    'description'    => $this->input->post('description'),
                    'category_id'    => $this->input->post('category_id'),
                    'modality_id'    => $this->input->post('modality_id'),
                    'access_type'    => $this->input->post('access_type'),
                    'expiry_days'    => $this->input->post('expiry_days'),
                    'certificate_prefix' => $this->input->post('certificate_prefix'),
                    'signatory_name'     => $this->input->post('signatory_name'),
                    'signatory_title'    => $this->input->post('signatory_title'),
                ], $this->user->id);

                $this->course_phase2->save_course_meta_from_post($id, $_POST, (int) $this->user->id);

                $this->session->set_flashdata('success', 'Course details updated.');
                redirect('manage_courses/edit/' . $id . $edit_rt_suffix());
            }
        }

        $this->_render_course_edit_page($id, $course);
    }

    // =========================================================
    // modules($course_id) — GET: module builder (course ID, not module ID)
    // =========================================================
    public function modules($id = null)
    {
        if ( ! $id) {
            redirect('manage_courses');
        }
        $id = (int) $id;

        $course = $this->course_model->get_course_any($id);
        if ( ! $course) {
            log_message('debug', 'MODULE PAGE 404: ' . json_encode([
                'id'           => $id,
                'course_found' => false,
                'module_found' => false,
                'user_role'    => (string) ($this->user->role ?? ''),
            ]));
            show_404();
        }

        $this->_check_ownership($course);

        if ($this->input->method() === 'post') {
            redirect('manage_courses/edit/' . $id);
        }

        $this->_render_course_edit_page($id, $course, [
            'page_title'     => 'Modules: ' . $course->title,
            'breadcrumb_label' => 'Modules: ' . $course->title,
            'focus_modules'  => true,
        ]);
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

    public function publish($id = null)
    {
        $this->_set_publish_status_action($id, 'published');
    }

    public function unpublish($id = null)
    {
        $this->_set_publish_status_action($id, 'unpublished');
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

        // Multiple modules may share the same content_type; only total weight is restricted.

        $weight = (float) $this->input->post('weight_percentage');
        if ($weight < 0 || $weight > 100) {
            echo json_encode(['success' => false, 'message' => 'Module weight must be between 0 and 100%.']);

            return;
        }

        $total_weight = round($this->course_model->sum_module_weights($course_id, $module_id) + $weight, 2);
        if ($total_weight > 100.0) {
            echo json_encode([
                'success' => false,
                'message' => 'Module weights cannot exceed 100%. Current save would total ' . number_format($total_weight, 2) . '%.',
            ]);

            return;
        }

        $m_data = [
            'course_id'         => $course_id,
            'title'             => $title,
            'description'       => $this->input->post('description'),
            'content_type'      => $content_type,
            'content_path'      => $this->input->post('content_path'),
            'weight_percentage' => $weight,
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
        if ($module && ($module->content_type ?? '') === 'video') {
            $module->checkpoint_count = count($this->assessment_model->get_assessments($mid, 'checkpoint'));
        } elseif ($module) {
            $module->checkpoint_count = 0;
        }

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
     * Load manage_courses/edit view with modules for a course.
     *
     * @param int    $id
     * @param object $course
     * @param array  $overrides page_title, breadcrumb_label, focus_modules
     * @return void
     */
    private function _render_course_edit_page($id, $course, array $overrides = [])
    {
        $id = (int) $id;
        $modules = $this->course_model->get_modules($id, 0);

        foreach ($modules as $mod) {
            $pre  = $this->assessment_model->get_assessments($mod->id, 'pre');
            $post = $this->assessment_model->get_assessments($mod->id, 'post');
            $mod->pre_count  = count($pre);
            $mod->post_count = count($post);
            if (ka_module_is_video_content($mod)) {
                $mod->checkpoint_count = count($this->assessment_model->get_assessments($mod->id, 'checkpoint'));
            } else {
                $mod->checkpoint_count = 0;
            }
        }

        $crumb_label = $overrides['breadcrumb_label'] ?? ('Edit: ' . $course->title);

        $lms_rt = ka_lms_resolve_return_target($this->user, $this->input->get('return_url'));

        $data = array_merge([
            'user'         => $this->user,
            'page_title'   => $overrides['page_title'] ?? $crumb_label,
            'course'       => $course,
            'modules'      => $modules,
            'categories'   => $this->course_model->get_categories(),
            'modalities'   => $this->course_model->get_modalities(),
            'teachers'     => $this->user->role === 'admin'
                              ? $this->course_model->get_teachers() : [],
            'focus_modules' => ! empty($overrides['focus_modules']),
            'checkpoint_schema_ready' => $this->assessment_model->assessments_checkpoint_schema_ready(),
            'lms_return_target' => $lms_rt,
            'lms_return_q'      => ka_lms_return_q($lms_rt),
            'lms_edit_back_href'=> base_url('index.php/' . $lms_rt),
            'breadcrumbs'  => [
                ['label' => 'Dashboard',      'url' => 'dashboard'],
                ['label' => 'Manage Courses', 'url' => 'manage_courses'],
                ['label' => $crumb_label],
            ],
            'view' => 'manage_courses/edit',
        ], $this->_phase2_view_data($id));

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    /**
     * Phase 2 form variables for create/edit views.
     *
     * @param int $course_id 0 = create form defaults
     * @return array
     */
    private function _phase2_view_data($course_id = 0)
    {
        $user = $this->user;
        $cid  = (int) $course_id;

        $instructor_options = $user->role === 'admin'
            ? $this->course_model->get_teachers()
            : [(object) ['id' => (int) $user->id, 'fullname' => (string) $user->fullname]];

        $out = [
            'phase2_schema_ready'  => $this->course_phase2->schema_ready(),
            'hrmis_connection_ok'  => $this->course_phase2->hrmis_connection_ok(),
            'hrmis_ready'          => $this->course_phase2->hrmis_ready(),
            'departments'          => $this->course_phase2->get_departments(),
            'professions'          => $this->course_phase2->get_professions(),
            'instructor_options'   => $instructor_options,
            'is_edit_form'         => $cid > 0,
        ];

        if ($cid > 0) {
            $out['course_phase2'] = $this->course_phase2->load_course_phase2_form_data($cid);
            $out['invitable_users'] = $this->course_phase2->get_invitable_users($cid);
        } else {
            $out['course_phase2'] = (object) [
                'category_ids'   => [],
                'instructor_ids' => [(int) $user->id],
                'department_ids' => [],
                'profession_ids' => [],
                'access_type'    => 'approval_required',
                'publish_status' => 'draft',
                'invitations'    => [],
            ];
        }

        return $out;
    }

    private function _set_publish_status_action($id, $status)
    {
        $cid = (int) $id;
        if ($cid < 1) {
            redirect('manage_courses');
        }

        $course = $this->course_model->get_course_any($cid);
        if ( ! $course) {
            show_404();
        }
        $this->_check_ownership($course);

        $ok = $status === 'published'
            ? $this->course_model->publish_course($cid, (int) $this->user->id)
            : $this->course_model->unpublish_course($cid, (int) $this->user->id);

        $this->session->set_flashdata(
            $ok ? 'success' : 'error',
            $ok ? ('Course ' . ($status === 'published' ? 'published.' : 'unpublished.')) : 'Could not update publish status.'
        );

        redirect('manage_courses/edit/' . $cid);
    }

    private function _handle_course_invitations($course_id)
    {
        $cid = (int) $course_id;
        if ($cid < 1) {
            $this->session->set_flashdata('error', 'Invalid course.');
            return;
        }

        $user_ids = course_phase2_normalize_ids($this->input->post('invite_user_ids') ?? []);
        $dept_ids = course_phase2_normalize_ids($this->input->post('invite_department_ids') ?? []);
        if ( ! empty($dept_ids)) {
            foreach ($this->course_phase2->get_invitable_users($cid, $dept_ids) as $u) {
                $user_ids[] = (int) $u->id;
            }
        }
        $user_ids = array_values(array_unique(array_filter(array_map('intval', $user_ids))));

        $email = strtolower(trim((string) $this->input->post('invite_email')));
        $email_uid = 0;
        if ($email !== '') {
            if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->session->set_flashdata('error', 'Enter a valid invitation email.');
                return;
            }
            $match = $this->db
                ->select('id')
                ->where('DELETED', 0)
                ->where('LOWER(email) = ' . $this->db->escape($email), null, false)
                ->get('aauth_users', 1)
                ->row();
            if ($match) {
                $email_uid = (int) $match->id;
                $user_ids[] = $email_uid;
                $user_ids = array_values(array_unique($user_ids));
            }
        }

        if (empty($user_ids) && $email === '') {
            $this->session->set_flashdata('error', 'Select users, departments, or enter an email to invite.');
            return;
        }

        $this->load->library('notification_service');
        $sent = 0;
        $skipped = 0;
        foreach ($user_ids as $uid) {
            $res = $this->course_phase2->create_invitation($cid, (int) $this->user->id, '', (int) $uid);
            if ( ! empty($res['ok'])) {
                if (empty($res['duplicate']) && (int) ($res['id'] ?? 0) > 0) {
                    $sent++;
                    $this->notification_service->course_invitation_created((int) $uid, $cid, (int) $res['id']);
                } else {
                    $skipped++;
                }
            } else {
                $skipped++;
            }
        }

        if ($email !== '' && $email_uid < 1) {
            $res = $this->course_phase2->create_invitation($cid, (int) $this->user->id, $email, 0);
            ! empty($res['ok']) && empty($res['duplicate']) ? $sent++ : $skipped++;
        }

        $msg = $sent . ' invitation' . ($sent === 1 ? '' : 's') . ' sent.';
        if ($skipped > 0) {
            $msg .= ' ' . $skipped . ' skipped or already invited.';
        }
        $this->session->set_flashdata($sent > 0 ? 'success' : 'info', $msg);
    }

    /**
     * Redirect if the current user doesn't own the course.
     * Admins always pass. Teachers must be in course_instructors.
     *
     * @param object $course
     * @param bool   $json  If true, return JSON instead of redirect
     */
    private function _check_ownership($course, $json = false)
    {
        if ($this->user->role === 'admin') return;

        if ($this->course_phase2->user_manages_course((int) $this->user->id, (int) $course->id)) {
            return;
        }

        if ($json) {
            echo json_encode(['success' => false, 'message' => 'You are not assigned to manage this course.']);
            exit;
        }
        $this->session->set_flashdata('error', 'You are not assigned to manage this course.');
        redirect('manage_courses');
    }
}
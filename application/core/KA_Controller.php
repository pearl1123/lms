<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * KA_Controller — Base controller for kaBAGA Academy
 *
 * Provides:
 *   - Authenticated user guard (runs on every request)
 *   - Session user refresh (for sidebar)
 *   - Shared view rendering helper
 *   - JSON response helper
 *   - Flash message helpers
 *
 * All application controllers extend this instead of CI_Controller.
 * Place in: application/core/KA_Controller.php
 * CI3 autoloads files from application/core/ that match MY_* or a
 * custom subclass_prefix. Set $config['subclass_prefix'] = 'KA_';
 * in application/config/config.php
 *
 * @property CI_DB_mysqli_driver  $db
 * @property CI_Session           $session
 * @property CI_Input             $input
 * @property User_model           $user_model
 * @property Notification_model     $notification_model
 * @property Auth_model             $auth_model
 * 
 */
class KA_Controller extends CI_Controller {

    /** @var object Authenticated user stdClass */
    protected $auth_user;

    /** @var array<string, bool> Effective permission names for auth user */
    protected $user_permissions = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('User_model', 'user_model');
        $this->load->helper(['url', 'form', 'permission']);

        $this->_boot_auth();
        $this->_load_user_permissions();
    }

    // =========================================================
    // AUTH GUARD — runs on every request
    // =========================================================

    /**
     * Validate session and load the authenticated user.
     * Redirects to login on any failure.
     */
    /**
     * @return bool
     */
    protected function _is_ajax_request()
    {
        return strtolower((string) $this->input->server('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
    }

    /**
     * JSON auth/permission failure for AJAX endpoints (matches legacy Courses/Learning_notes behavior).
     *
     * @param int    $status
     * @param string $message
     * @param array  $extra
     */
    protected function _auth_json_exit($status, $message, array $extra = [])
    {
        $payload = array_merge([
            'success' => false,
            'message' => (string) $message,
            'auth'    => ((int) $status === 401),
        ], $extra);

        $encoded = json_encode($payload);
        $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json')
            ->set_output($encoded !== false ? $encoded : '{"success":false,"message":"Authentication required.","auth":true}');

        $this->output->_display();
        exit;
    }

    private function _boot_auth()
    {
        $user_id = $this->session->userdata('user_id');
        if ( ! $user_id) {
            if ($this->_is_ajax_request()) {
                $this->_auth_json_exit(401, 'Authentication required.');
            }
            redirect('auth/login');
        }

        $user = $this->user_model->get_user((int) $user_id);

        if ( ! $user) {
            if ($this->_is_ajax_request()) {
                $this->_auth_json_exit(401, 'Authentication required.');
            }
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        if ((int) $user->banned === 1) {
            if ($this->_is_ajax_request()) {
                $this->_auth_json_exit(403, 'Account is banned.');
            }
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        if ($user->status !== 'active') {
            if ($this->_is_ajax_request()) {
                $this->_auth_json_exit(403, 'Account is not active.');
            }
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        if ((int) $user->DELETED === 1) {
            if ($this->_is_ajax_request()) {
                $this->_auth_json_exit(403, 'Account is unavailable.');
            }
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        if ( ! empty($user->locked_until) && strtotime($user->locked_until) > time()) {
            if ($this->_is_ajax_request()) {
                $this->_auth_json_exit(423, 'Account is temporarily locked.');
            }
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        $this->auth_user = $user;

        // Keep session array fresh — sidebar reads this
        $this->session->set_userdata('user', [
            'id'          => $user->id,
            'fullname'    => $user->fullname,
            'employee_id' => $user->employee_id,
            'role'        => $user->role,
            'status'      => $user->status,
            'avatar_path' => $user->avatar_path ?? '',
        ]);
    }

    // =========================================================
    // ROLE GUARDS
    // =========================================================

    /**
     * Abort with 403 if current user is not one of the allowed roles.
     *
     * @param string|string[] $roles
     * @param string          $redirect_to
     */
    protected function require_role($roles, $redirect_to = 'dashboard')
    {
        $roles = (array) $roles;
        if ( ! in_array($this->auth_user->role, $roles)) {
            $this->flash('error', 'You do not have permission to access that page.');
            redirect($redirect_to);
        }
    }

    /**
     * Alias: only employees allowed.
     */
    protected function require_employee()
    {
        $this->require_role('employee', 'dashboard');
    }

    /**
     * Alias: only admin and instructor/teacher managers allowed (not while in learning mode).
     */
    protected function require_manager()
    {
        if (ka_user_acting_as_learner($this->auth_user->role ?? '')) {
            if ($this->_is_ajax_request()) {
                $this->_auth_json_exit(403, 'Switch to ' . ka_staff_mode_label_for_role($this->auth_user->role ?? '') . ' mode to access management tools.');
            }
            $this->flash('info', 'You are in learning mode. Switch back to ' . ka_staff_mode_label_for_role($this->auth_user->role ?? '') . ' mode to access management tools.');
            redirect('dashboard');
        }

        $this->require_role(['admin', 'teacher', 'instructor'], 'my_courses');
    }

    /**
     * True for employees/students or instructors in sidebar learning mode.
     */
    protected function is_learner_experience()
    {
        return ka_user_is_learner_experience($this->auth_user->role ?? '');
    }

    /**
     * Abort unless the user has at least one of the named permissions.
     * When the permission engine is not seeded, authenticated access is allowed (legacy mode).
     *
     * @param string|string[] $permissions
     * @param string          $redirect_to
     */
    protected function require_permission($permissions, $redirect_to = 'dashboard')
    {
        $permissions = array_values(array_filter(array_map('trim', (array) $permissions)));
        if ($permissions === []) {
            return;
        }

        $this->load->model('Permission_model', 'permission_model');

        if ( ! $this->permission_model->engine_is_active()) {
            return;
        }

        if ($this->permission_model->user_has_any((int) $this->auth_user->id, $permissions)) {
            return;
        }

        if ($this->_user_has_no_group_membership()) {
            return;
        }

        if (ka_learner_role_has_manifest_permission($permissions, (string) ($this->auth_user->role ?? ''))) {
            return;
        }

        if ($this->_is_ajax_request()) {
            $this->_auth_json_exit(403, 'You do not have permission to access that page.', ['auth' => true]);
        }

        $this->flash('error', 'You do not have permission to access that page.');

        if ($this->_permission_redirect_target_is_reachable($redirect_to)) {
            redirect($redirect_to);
        }

        show_error('You do not have permission to access this page.', 403);
    }

    /**
     * True when redirect target is a route the user may access (prevents redirect loops).
     *
     * @param string $redirect_to
     */
    private function _permission_redirect_target_is_reachable($redirect_to)
    {
        $redirect_to = trim((string) $redirect_to);
        if ($redirect_to === '') {
            return false;
        }

        $current_class = strtolower((string) $this->router->fetch_class());
        $segment       = strtolower(trim(explode('/', $redirect_to)[0]));

        if ($segment === $current_class) {
            return false;
        }

        $manifest = ka_permission_manifest();
        $nav_perm = trim((string) ($manifest['nav'][$segment] ?? ''));

        if ($nav_perm === '') {
            return true;
        }

        return $this->permission_model->user_has((int) $this->auth_user->id, $nav_perm);
    }

    /**
     * Users with no Aauth group rely on legacy role column until group assignment.
     */
    private function _user_has_no_group_membership()
    {
        return (int) $this->db
            ->where('user_id', (int) $this->auth_user->id)
            ->count_all_results('aauth_user_to_group') === 0;
    }

    /**
     * @return bool
     */
    protected function user_can($permission_name)
    {
        $permission_name = trim((string) $permission_name);
        if ($permission_name === '') {
            return true;
        }

        $this->load->model('Permission_model', 'permission_model');

        if ( ! $this->permission_model->engine_is_active()) {
            return $this->auth_user->role === 'admin';
        }

        if ($this->_user_has_no_group_membership()) {
            return in_array(strtolower((string) ($this->auth_user->role ?? '')), ['admin', 'employee', 'student'], true);
        }

        if ($this->permission_model->user_has((int) $this->auth_user->id, $permission_name)) {
            return true;
        }

        return ka_learner_role_has_manifest_permission($permission_name, (string) ($this->auth_user->role ?? ''));
    }

    private function _load_user_permissions()
    {
        $this->load->model('Permission_model', 'permission_model');
        $this->user_permissions = $this->permission_model->get_effective_map((int) $this->auth_user->id);
    }

    // =========================================================
    // VIEW RENDERING
    // =========================================================

    /**
     * Render a view through layouts/main.
     * Merges $data with sensible defaults (user, breadcrumbs).
     *
     * @param string $view       View path, e.g. 'courses/catalog'
     * @param array  $data       Variables passed to the view
     * @param array  $breadcrumbs
     */
    protected function render($view, array $data = [], array $breadcrumbs = [])
    {
        $data['user']        = $this->auth_user;
        $data['view']        = $view;
        $data['breadcrumbs'] = $breadcrumbs;

        $data = ka_merge_layout_vars($this, $data);

        $this->load->view('layouts/main', $data);
    }

    // =========================================================
    // JSON RESPONSES
    // =========================================================

    /**
     * Send a JSON response and stop execution.
     *
     * @param array $payload
     */
    protected function json(array $payload)
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    /**
     * Send a JSON success response.
     */
    protected function json_ok(array $extra = [])
    {
        $this->json(array_merge(['success' => true], $extra));
    }

    /**
     * Send a JSON error response.
     */
    protected function json_error($message, array $extra = [])
    {
        $this->json(array_merge(['success' => false, 'message' => $message], $extra));
    }

    // =========================================================
    // FLASH MESSAGES
    // =========================================================

    /**
     * Set a flash message.
     *
     * @param string $type    success | error | info | warning
     * @param string $message HTML allowed
     */
    protected function flash($type, $message)
    {
        $this->session->set_flashdata($type, $message);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Get a GET param as trimmed string, with default.
     */
    protected function get_param($key, $default = '')
    {
        $val = $this->input->get($key);
        return $val !== null ? trim($val) : $default;
    }

    /**
     * Get a GET param cast to int, with default.
     */
    protected function get_int($key, $default = 0)
    {
        return (int) ($this->input->get($key) ?? $default);
    }

    /**
     * Get a POST param, with default.
     */
    protected function post_param($key, $default = null)
    {
        $val = $this->input->post($key);
        return $val !== null ? $val : $default;
    }
}

// CI3 only auto-loads KA_Controller.php; secondary bases must be required explicitly.
require_once APPPATH . 'core/KA_Library_controller.php';
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
    private function _boot_auth()
    {
        $user_id = $this->session->userdata('user_id');
        if ( ! $user_id) {
            redirect('auth/login');
        }

        $user = $this->user_model->get_user((int) $user_id);

        if ( ! $user
            || (int) $user->banned  === 1
            || $user->status       !== 'active'
            || (int) $user->DELETED === 1
            || ( ! empty($user->locked_until) && strtotime($user->locked_until) > time())
        ) {
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
     * Alias: only admin and instructor/teacher managers allowed.
     */
    protected function require_manager()
    {
        $this->require_role(['admin', 'teacher', 'instructor'], 'my_courses');
    }

    /**
     * Abort unless the user has at least one of the named permissions.
     * Falls back to require_role('admin') when the permission engine has no seed data.
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
            if ($this->auth_user->role === 'admin') {
                return;
            }
            $this->require_role('admin', $redirect_to);

            return;
        }

        if ($this->permission_model->user_has_any((int) $this->auth_user->id, $permissions)) {
            return;
        }

        $this->flash('error', 'You do not have permission to access that page.');
        redirect($redirect_to);
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

        return $this->permission_model->user_has((int) $this->auth_user->id, $permission_name);
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
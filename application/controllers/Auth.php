<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_DB_mysqli_driver $db
 * @property CI_Session $session
 * @property CI_Input $input
 * @property CI_Form_validation $form_validation
 * @property User_model $user_model
 * @property Password_reset_service $password_reset_service
 */
class Auth extends CI_Controller {

    /** Max POSTs per window for check_employee (session-based). */
    private const RATE_CHECK_MAX = 15;

    /** Max registration POSTs per window. */
    private const RATE_REGISTER_MAX = 5;

    /** Max forgot-password POSTs per window. */
    private const RATE_FORGOT_MAX = 8;

    /** Rate-limit window (seconds). */
    private const RATE_WINDOW_SEC = 600;

    /** Remember-me session lifetime (14 days). */
    private const REMEMBER_LIFETIME_SEC = 1209600;

    /** Cookie flag set when user checks Remember me on login. */
    private const REMEMBER_COOKIE = 'lms_remember';

    public function __construct()
    {
        parent::__construct();

        $this->load->helper(['url', 'form', 'registration', 'ka_layout']);
        $this->load->library(['session', 'form_validation']);
        $this->load->model('User_model', 'user_model');
    }

    /** User-facing message when auth CSRF/session token fails. */
    private const AUTH_CSRF_EXPIRED_MSG = 'Session expired. Please refresh the page.';

    /**
     * Auth CSRF field name (config-driven; does not depend on global csrf_protection).
     */
    private function auth_csrf_token_name()
    {
        $name = config_item('csrf_token_name');

        return is_string($name) && $name !== '' ? $name : 'csrf_test_name';
    }

    /**
     * @return string 32-char hex token
     */
    private function generate_auth_csrf_hash()
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Exception $e) {
            return md5(uniqid((string) mt_rand(), true));
        }
    }

    /**
     * Persist a new auth CSRF token in session.
     *
     * @return array{csrf_field_name:string, csrf_hash:string}
     */
    private function rotate_auth_csrf()
    {
        $name = $this->auth_csrf_token_name();
        $hash = $this->generate_auth_csrf_hash();

        $this->session->set_userdata('auth_csrf', [
            'name' => $name,
            'hash' => $hash,
        ]);

        return [
            'csrf_field_name' => $name,
            'csrf_hash'       => $hash,
        ];
    }

    /**
     * Issue CSRF token for auth forms (session-backed; works when csrf_protection is FALSE).
     *
     * @param bool $force_new
     * @return array{csrf_field_name:string, csrf_hash:string}
     */
    private function auth_csrf_fields($force_new = false)
    {
        if ($force_new) {
            return $this->rotate_auth_csrf();
        }

        $stored = $this->get_auth_csrf_from_session();
        if (is_array($stored)
            && ! empty($stored['hash'])
            && is_string($stored['hash'])
            && preg_match('#^[0-9a-f]{32}$#i', $stored['hash']) === 1) {
            return [
                'csrf_field_name' => (string) ($stored['name'] ?? $this->auth_csrf_token_name()),
                'csrf_hash'       => (string) $stored['hash'],
            ];
        }

        return $this->rotate_auth_csrf();
    }

    /**
     * @return array{name:string, hash:string}|null
     */
    private function get_auth_csrf_from_session()
    {
        $stored = $this->session->userdata('auth_csrf');

        return is_array($stored) ? $stored : null;
    }

    /**
     * @param string $post_token
     * @return bool
     */
    private function auth_csrf_token_valid($post_token)
    {
        if ( ! is_string($post_token) || $post_token === '') {
            return false;
        }

        $stored = $this->get_auth_csrf_from_session();

        return is_array($stored)
            && ! empty($stored['hash'])
            && is_string($stored['hash'])
            && hash_equals((string) $stored['hash'], $post_token);
    }

    /**
     * @param string $redirect_on_fail
     * @param bool   $preserve_register_inputs
     */
    private function auth_csrf_fail($redirect_on_fail = 'auth/register', $preserve_register_inputs = false)
    {
        $this->session->set_flashdata('error', self::AUTH_CSRF_EXPIRED_MSG);
        $this->session->set_flashdata('auth_toast_error', self::AUTH_CSRF_EXPIRED_MSG);

        if ($preserve_register_inputs) {
            $this->session->set_flashdata(
                '_old_register_employee_id',
                normalize_employee_id($this->input->post('employee_id'))
            );
        }

        redirect($redirect_on_fail);
        exit;
    }

    /**
     * Enforce CSRF on auth POST endpoints (registration, login, check employee).
     *
     * @param string $redirect_on_fail
     * @param bool   $preserve_register_inputs
     * @return bool
     */
    private function verify_post_csrf($redirect_on_fail = 'auth/register', $preserve_register_inputs = false)
    {
        if (strtoupper($this->input->method()) !== 'POST') {
            return true;
        }

        $token_name = $this->auth_csrf_token_name();
        $post_token = $this->input->post($token_name);

        if ( ! $this->auth_csrf_token_valid($post_token)) {
            $this->auth_csrf_fail($redirect_on_fail, $preserve_register_inputs);

            return false;
        }

        return true;
    }

    /**
     * @param string $bucket
     * @param int    $max
     * @return bool true if rate limited (blocked)
     */
    private function rate_limit_exceeded($bucket, $max)
    {
        $key = 'auth_rl_' . $bucket;
        $now = time();
        $raw = $this->session->userdata($key);
        $data = is_array($raw) ? $raw : ['count' => 0, 'reset' => $now + self::RATE_WINDOW_SEC];

        if ($now > (int) $data['reset']) {
            $data = ['count' => 0, 'reset' => $now + self::RATE_WINDOW_SEC];
        }

        $data['count'] = (int) $data['count'] + 1;
        $this->session->set_userdata($key, $data);

        return $data['count'] > $max;
    }

    private function flash_old_login_inputs()
    {
        $emp = normalize_employee_id($this->input->post('employee_id'));
        $this->session->set_flashdata('_old_employee_id', $emp);
        $remember = $this->input->post('remember_me');
        $this->session->set_flashdata(
            '_old_remember_me',
            ($remember !== null && $remember !== '' && $remember !== '0' && $remember !== false) ? '1' : '0'
        );
    }

    /**
     * @return bool
     */
    private function remember_me_requested()
    {
        $remember = $this->input->post('remember_me');

        return $remember !== null && $remember !== '' && $remember !== '0' && $remember !== false;
    }

    /**
     * Persist longer session when Remember me is checked on login.
     */
    private function apply_remember_me_from_post()
    {
        if ( ! $this->remember_me_requested()) {
            $this->clear_remember_me_cookie();

            return;
        }

        $this->session->set_userdata('remember_me', 1);
        $this->config->set_item('sess_expiration', self::REMEMBER_LIFETIME_SEC);

        $path     = (string) $this->config->item('cookie_path');
        $domain   = (string) $this->config->item('cookie_domain');
        $secure   = (bool) $this->config->item('cookie_secure');
        $samesite = (string) ($this->config->item('sess_samesite') ?: 'Lax');

        if (is_php('7.3')) {
            setcookie(self::REMEMBER_COOKIE, '1', [
                'expires'  => time() + self::REMEMBER_LIFETIME_SEC,
                'path'     => $path !== '' ? $path : '/',
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => $samesite,
            ]);
        } else {
            setcookie(self::REMEMBER_COOKIE, '1', time() + self::REMEMBER_LIFETIME_SEC, $path, $domain, $secure, true);
        }

        $cookie_name = (string) $this->config->item('sess_cookie_name');
        if ($cookie_name === '') {
            $cookie_name = 'ci_session';
        }

        if (is_php('7.3')) {
            setcookie($cookie_name, session_id(), [
                'expires'  => time() + self::REMEMBER_LIFETIME_SEC,
                'path'     => $path !== '' ? $path : '/',
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => $samesite,
            ]);
        } else {
            setcookie($cookie_name, session_id(), time() + self::REMEMBER_LIFETIME_SEC, $path, $domain, $secure, true);
        }
    }

    private function clear_remember_me_cookie()
    {
        $path   = (string) $this->config->item('cookie_path');
        $domain = (string) $this->config->item('cookie_domain');
        $secure = (bool) $this->config->item('cookie_secure');

        if (is_php('7.3')) {
            setcookie(self::REMEMBER_COOKIE, '', [
                'expires'  => time() - 3600,
                'path'     => $path !== '' ? $path : '/',
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => (string) ($this->config->item('sess_samesite') ?: 'Lax'),
            ]);
        } else {
            setcookie(self::REMEMBER_COOKIE, '', time() - 3600, $path, $domain, $secure, true);
        }
    }

    public function index()
    {
        $this->login();
    }

    public function login()
    {
        if ($this->session->userdata('user_id')) {
            redirect('dashboard');
        }

        $old_emp = $this->session->flashdata('_old_employee_id');
        $employee_id_value = is_string($old_emp) ? $old_emp : (is_scalar($old_emp) ? (string) $old_emp : '');

        $old_rm = $this->session->flashdata('_old_remember_me');
        $remember_me_checked = ($old_rm === '1' || $old_rm === 1 || $old_rm === true);

        $this->load->view('auth/login', array_merge($this->auth_csrf_fields(), [
            'flash_messages'      => ka_collect_flash_messages($this, ['error', 'success']),
            'login_form_action'     => site_url('auth/login_process'),
            'employee_id_value'     => $employee_id_value,
            'remember_me_checked'   => $remember_me_checked,
            'forgot_password_url'   => site_url('auth/forgot-password'),
            'register_url'          => site_url('auth/register'),
            'alerts_partial_html'   => '',
        ]));
    }

    public function forgot_password()
    {
        if ($this->session->userdata('user_id')) {
            redirect('dashboard');
        }

        $old_emp = $this->session->flashdata('_old_forgot_employee_id');
        $employee_id_value = is_string($old_emp) ? $old_emp : (is_scalar($old_emp) ? (string) $old_emp : '');

        $dev_url = $this->session->flashdata('reset_dev_url');

        $this->load->view('auth/forgot_password', array_merge($this->auth_csrf_fields(), [
            'flash_messages'      => ka_collect_flash_messages($this, ['error', 'success']),
            'forgot_form_action'  => site_url('auth/forgot_password_process'),
            'login_url'           => site_url('auth/login'),
            'home_url'            => base_url(),
            'employee_id_value'   => $employee_id_value,
            'reset_dev_url'       => is_string($dev_url) ? $dev_url : '',
            'alerts_partial_html' => '',
        ]));
    }

    public function forgot_password_process()
    {
        $this->verify_post_csrf('auth/forgot-password');

        if ($this->rate_limit_exceeded('forgot_password', self::RATE_FORGOT_MAX)) {
            $this->session->set_flashdata('error', 'Too many reset attempts. Please wait a few minutes and try again.');
            redirect('auth/forgot-password');

            return;
        }

        $this->form_validation->set_rules('employee_id', 'Employee ID', 'required|trim');

        if ($this->form_validation->run() === false) {
            $this->session->set_flashdata('error', validation_errors());
            $this->session->set_flashdata('_old_forgot_employee_id', normalize_employee_id($this->input->post('employee_id')));
            redirect('auth/forgot-password');

            return;
        }

        $emp_id = normalize_employee_id($this->input->post('employee_id'));
        if ($emp_id === '') {
            $this->session->set_flashdata('error', 'Employee ID is required.');
            redirect('auth/forgot-password');

            return;
        }

        $this->load->library('password_reset_service');
        $result = $this->password_reset_service->request_reset($emp_id);

        $this->session->set_flashdata('success', $result['message']);
        if ( ! empty($result['dev_reset_url'])) {
            $this->session->set_flashdata('reset_dev_url', $result['dev_reset_url']);
        }

        $this->session->set_flashdata('_old_forgot_employee_id', $emp_id);
        redirect('auth/forgot-password');
    }

    public function reset_password($token = null)
    {
        if ($this->session->userdata('user_id')) {
            redirect('dashboard');
        }

        $token = trim((string) $token);
        if ($token === '') {
            $this->session->set_flashdata('error', 'Invalid password reset link.');
            redirect('auth/forgot-password');

            return;
        }

        $this->load->library('password_reset_service');
        $user = $this->password_reset_service->validate_reset_token($token);
        if ( ! $user) {
            $this->session->set_flashdata('error', 'This reset link is invalid or has expired. Please request a new one.');
            redirect('auth/forgot-password');

            return;
        }

        $this->load->view('auth/reset_password', array_merge($this->auth_csrf_fields(), [
            'flash_messages'        => ka_collect_flash_messages($this, ['error', 'success']),
            'reset_form_action'     => site_url('auth/reset_password_process'),
            'reset_token'           => $token,
            'login_url'             => site_url('auth/login'),
            'forgot_password_url'   => site_url('auth/forgot-password'),
            'alerts_partial_html'   => '',
        ]));
    }

    public function reset_password_process()
    {
        $this->verify_post_csrf('auth/forgot-password');

        if ($this->rate_limit_exceeded('reset_password', self::RATE_FORGOT_MAX)) {
            $this->session->set_flashdata('error', 'Too many attempts. Please wait a few minutes and try again.');
            redirect('auth/forgot-password');

            return;
        }

        $token = trim((string) $this->input->post('reset_token'));
        if ($token === '') {
            $this->session->set_flashdata('error', 'Invalid password reset request.');
            redirect('auth/forgot-password');

            return;
        }

        $this->form_validation->set_rules('password', 'New Password', 'required|min_length[8]');
        $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');

        if ($this->form_validation->run() === false) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('auth/reset-password/' . rawurlencode($token));

            return;
        }

        $this->load->library('password_reset_service');
        $result = $this->password_reset_service->complete_reset(
            $token,
            (string) $this->input->post('password')
        );

        if (empty($result['ok'])) {
            $this->session->set_flashdata('error', $result['message'] ?? 'Unable to reset password.');
            redirect(empty($this->password_reset_service->validate_reset_token($token))
                ? 'auth/forgot-password'
                : 'auth/reset-password/' . rawurlencode($token));

            return;
        }

        $this->session->set_flashdata('success', $result['message']);
        redirect('auth/login');
    }

    public function login_process()
    {
        $this->verify_post_csrf('auth/login');

        $this->form_validation->set_rules('employee_id', 'Employee ID', 'required|trim');
        $this->form_validation->set_rules('password', 'Password', 'required');

        if ($this->form_validation->run() === false) {
            $this->session->set_flashdata('error', validation_errors());
            $this->flash_old_login_inputs();
            redirect('auth/login');

            return;
        }

        $employee_id = normalize_employee_id($this->input->post('employee_id'));
        $password    = $this->input->post('password');

        $result = $this->user_model->login($employee_id, $password);
        if ($result === 'locked') {
            $this->session->set_flashdata('error', 'Account locked. Try again later.');
            $this->flash_old_login_inputs();
            redirect('auth/login');

            return;
        }

        if ($result) {
            $this->session->sess_regenerate(true);
            $this->session->set_userdata([
                'user_id'     => $result->id,
                'employee_id' => $result->employee_id,
                'name'        => $result->fullname,
                'role'        => $result->role,
            ]);
            $this->apply_remember_me_from_post();

            $this->load->library('audit_service');
            $this->audit_service->log('login', 'auth', (int) $result->id, (int) $result->id, [
                'employee_id' => (string) ($result->employee_id ?? ''),
            ]);

            redirect('dashboard');
        }

        $this->session->set_flashdata('error', 'Invalid Employee ID or Password.');
        $this->flash_old_login_inputs();
        redirect('auth/login');
    }

    public function register()
    {
        if ($this->session->userdata('user_id')) {
            redirect('dashboard');
        }

        $flash = ka_collect_flash_messages($this, ['error', 'success']);
        $old_emp = $this->session->flashdata('_old_register_employee_id');
        $employee_id_value = is_string($old_emp) ? $old_emp : (is_scalar($old_emp) ? (string) $old_emp : '');
        $toast_error = $this->session->flashdata('auth_toast_error');

        $this->load->view('auth/register_modal', array_merge($this->auth_csrf_fields(), [
            'flash_messages'     => $flash,
            'error'              => $flash['error'] ?? '',
            'success'            => $flash['success'] ?? '',
            'employee_id_value'  => $employee_id_value,
            'auth_toast_error'   => is_string($toast_error) ? $toast_error : '',
        ]));
    }

    /**
     * AJAX — HRMIS lookup (ACTIVE employees only). No manual fallback.
     */
    public function check_employee()
    {
        header('Content-Type: application/json');

        if ($this->rate_limit_exceeded('check_employee', self::RATE_CHECK_MAX)) {
            echo json_encode([
                'success' => false,
                'message' => 'Too many verification attempts. Please wait a few minutes and try again.',
            ]);

            return;
        }

        if ( ! $this->verify_post_csrf_ajax()) {
            echo json_encode([
                'success'    => false,
                'csrf_error' => true,
                'message'    => self::AUTH_CSRF_EXPIRED_MSG,
            ]);

            return;
        }

        $emp_id = normalize_employee_id($this->input->post('employee_id'));

        if ($emp_id === '') {
            echo json_encode([
                'exists'     => false,
                'registered' => false,
                'success'    => false,
                'message'    => 'Please enter an Employee ID.',
            ]);

            return;
        }

        if ( ! is_valid_lcp_employee_id($emp_id)) {
            echo json_encode([
                'exists'     => false,
                'registered' => false,
                'success'    => false,
                'message'    => 'Employee ID must be in the format LCP###### (e.g. LCP880201).',
            ]);

            return;
        }

        $validation = $this->user_model->validate_employee_for_registration($emp_id);
        if ( ! $validation['ok']) {
            echo json_encode([
                'exists'     => false,
                'registered' => false,
                'success'    => false,
                'message'    => $validation['message'],
            ]);

            return;
        }

        $hr = $validation['employee'];
        $registered = $this->user_model->is_registered($emp_id);

        $csrf = $this->rotate_auth_csrf();

        echo json_encode([
            'exists'          => true,
            'registered'      => $registered,
            'name'            => hrmis_employee_display_name($hr),
            'department'      => trim((string) ($hr->Department ?? '')),
            'position'        => trim((string) ($hr->Position ?? '')),
            'success'         => true,
            'csrf_field_name' => $csrf['csrf_field_name'],
            'csrf_hash'       => $csrf['csrf_hash'],
        ]);
    }

    /**
     * POST — employee registration (HRMIS-only; no POST identity fields).
     */
    public function register_process()
    {
        if ($this->rate_limit_exceeded('register_process', self::RATE_REGISTER_MAX)) {
            $this->session->set_flashdata('error', 'Too many registration attempts. Please wait a few minutes and try again.');
            redirect('auth/register');

            return;
        }

        $this->verify_post_csrf('auth/register', true);

        $this->form_validation->set_rules('employee_id', 'Employee ID', 'required|trim|callback_valid_lcp_employee_id');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[8]');
        $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');
        $this->form_validation->set_rules('agree_terms', 'Terms', 'required');

        if ($this->form_validation->run() === false) {
            $this->session->set_flashdata('error', validation_errors(' ', ' '));
            $this->session->set_flashdata(
                '_old_register_employee_id',
                normalize_employee_id($this->input->post('employee_id'))
            );
            redirect('auth/register');

            return;
        }

        $emp_id   = normalize_employee_id($this->input->post('employee_id'));
        $password = $this->input->post('password');

        if ($emp_id === '') {
            $this->session->set_flashdata('error', 'Employee ID is required.');
            redirect('auth/register');

            return;
        }

        if ($this->user_model->is_registered($emp_id)) {
            $this->session->set_flashdata('error', 'This Employee ID is already registered.');
            redirect('auth/login');

            return;
        }

        $validation = $this->user_model->validate_employee_for_registration($emp_id);
        if ( ! $validation['ok']) {
            $this->session->set_flashdata('error', $validation['message']);
            redirect('auth/register');

            return;
        }

        $hr  = $validation['employee'];
        $row = $this->user_model->build_registration_row_from_hrmis(
            $hr,
            password_hash($password, PASSWORD_DEFAULT)
        );

        if ( ! $this->user_model->register_user($row)) {
            $this->session->set_flashdata('error', 'Unable to complete registration. Please contact the LMS administrator.');
            redirect('auth/register');

            return;
        }

        $new_user = $this->db->where('employee_id', $emp_id)->get('aauth_users', 1)->row();
        if ($new_user) {
            $this->user_model->apply_hrmis_profile((int) $new_user->id, $emp_id);
        }

        $this->session->set_flashdata('success', 'Registration successful! You can now log in.');
        redirect('auth/login');
    }

    /**
     * Form validation callback — LCP###### format.
     *
     * @param string $str
     * @return bool
     */
    public function valid_lcp_employee_id($str)
    {
        if ( ! is_valid_lcp_employee_id($str)) {
            $this->form_validation->set_message(
                'valid_lcp_employee_id',
                'Employee ID must be in the format LCP###### (e.g. LCP880201).'
            );

            return false;
        }

        return true;
    }

    /**
     * CSRF check for JSON endpoints (no redirect).
     *
     * @return bool
     */
    private function verify_post_csrf_ajax()
    {
        $post_token = $this->input->post($this->auth_csrf_token_name());

        return $this->auth_csrf_token_valid($post_token);
    }

    public function logout()
    {
        $this->clear_remember_me_cookie();
        $this->session->sess_destroy();
        redirect('auth/login');
    }
}

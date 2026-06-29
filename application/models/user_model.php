<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    protected $table = 'aauth_users';
    protected $hrmis_db;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('registration');
        $this->hrmis_db = $this->load->database('hrmis', true);
    }

    /**
     * ACTIVE employee from HRMIS tblemployee (idno must match normalized LCP######).
     *
     * @param string $employee_id
     * @return object|null
     */
    public function get_hrmis_employee($employee_id)
    {
        $employee_id = normalize_employee_id($employee_id);
        if ( ! is_valid_lcp_employee_id($employee_id)) {
            return null;
        }

        if ( ! $this->hrmis_db) {
            return null;
        }

        return $this->hrmis_db
            ->where('idno', $employee_id)
            ->where('status', 'ACTIVE')
            ->get('tblemployee')
            ->row();
    }

    /**
     * Validate employee for registration (format + ACTIVE HRMIS).
     *
     * @param string $employee_id
     * @return array{ok:bool, message:string, employee:object|null}
     */
    public function validate_employee_for_registration($employee_id)
    {
        $employee_id = normalize_employee_id($employee_id);

        if ( ! is_valid_lcp_employee_id($employee_id)) {
            return [
                'ok'       => false,
                'message'  => 'Employee ID must be in the format LCP###### (e.g. LCP880201).',
                'employee' => null,
            ];
        }

        $hr = $this->get_hrmis_employee($employee_id);
        if ( ! $hr) {
            return [
                'ok'       => false,
                'message'  => HRMIS_REGISTRATION_BLOCK_MESSAGE,
                'employee' => null,
            ];
        }

        return [
            'ok'       => true,
            'message'  => '',
            'employee' => $hr,
        ];
    }

    /**
     * Build insert row from HRMIS only (never from POST identity fields).
     *
     * @param object $hr
     * @param string $password_hash
     * @return array
     */
    public function build_registration_row_from_hrmis($hr, $password_hash)
    {
        $employee_id = normalize_employee_id($hr->idno ?? '');
        $email       = trim((string) ($hr->emailadd ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = strtolower($employee_id) . '@lms.local';
        }

        return [
            'employee_id'      => $employee_id,
            'email'            => $email,
            'fullname'         => hrmis_employee_display_name($hr),
            'password'         => $password_hash,
            'role'             => 'employee',
            'office'           => trim((string) ($hr->Department ?? '')),
            'employment_type'  => trim((string) ($hr->Position ?? '')),
            'status'           => 'active',
            'created_at'       => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Sync LMS profile labels from HRMIS.
     *
     * @param int         $user_id
     * @param string|null $employee_id
     * @return bool
     */
    public function apply_hrmis_profile($user_id, $employee_id = null)
    {
        $uid = (int) $user_id;
        if ($uid < 1) {
            return false;
        }

        $user = $this->get_user($uid);
        if ( ! $user) {
            return false;
        }

        $emp_id = $employee_id !== null ? normalize_employee_id($employee_id) : normalize_employee_id($user->employee_id ?? '');
        if ($emp_id === '') {
            return false;
        }

        $hr = $this->get_hrmis_employee($emp_id);
        if ( ! $hr) {
            return false;
        }

        return (bool) $this->db->where('id', $uid)->update($this->table, [
            'office'          => trim((string) ($hr->Department ?? '')),
            'employment_type' => trim((string) ($hr->Position ?? '')),
            'fullname'        => hrmis_employee_display_name($hr),
        ]);
    }

    /**
     * @param string $employee_id
     */
    public function is_registered($employee_id)
    {
        $employee_id = normalize_employee_id($employee_id);
        if ($employee_id === '') {
            return false;
        }

        return (bool) $this->db
            ->where('employee_id', $employee_id)
            ->where('DELETED', 0)
            ->get($this->table, 1)
            ->row();
    }

    /**
     * @param array $data
     * @return bool
     */
    public function register_user($data)
    {
        return (bool) $this->db->insert($this->table, $data);
    }

    /**
     * @param string $employee_id
     * @param string $password
     * @return object|string|false
     */
    public function login($employee_id, $password)
    {
        $employee_id = normalize_employee_id($employee_id);
        if ($employee_id === '') {
            return false;
        }

        $user = $this->db
            ->where('employee_id', $employee_id)
            ->where('status', 'active')
            ->where('DELETED', 0)
            ->get($this->table)
            ->row();

        // Legacy accounts may have non-uppercase employee_id (pre-hardening).
        if ( ! $user) {
            $user = $this->db
                ->where('LOWER(employee_id)', strtolower($employee_id))
                ->where('status', 'active')
                ->where('DELETED', 0)
                ->get($this->table)
                ->row();
        }

        if ( ! $user) {
            return false;
        }

        if ($user->locked_until && strtotime($user->locked_until) > time()) {
            return 'locked';
        }

        if ( ! password_verify($password, $user->password)) {
            $attempts = (int) $user->failed_attempts + 1;
            $update   = ['failed_attempts' => $attempts];
            if ($attempts >= 5) {
                $update['locked_until'] = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            }
            $this->db->where('id', $user->id)->update($this->table, $update);

            return false;
        }

        $this->db->where('id', $user->id)->update($this->table, [
            'failed_attempts' => 0,
            'locked_until'    => null,
            'last_login'      => date('Y-m-d H:i:s'),
            'last_activity'   => date('Y-m-d H:i:s'),
        ]);

        return $user;
    }

    /**
     * Whether the account is in an active login lockout window.
     *
     * @param object|null $user aauth_users row
     */
    public function is_login_locked($user)
    {
        if ( ! $user || empty($user->locked_until)) {
            return false;
        }

        return strtotime((string) $user->locked_until) > time();
    }

    /**
     * Clear failed login counters so the user can sign in again.
     *
     * @param int $user_id
     */
    public function reset_login_lockout($user_id)
    {
        $uid = (int) $user_id;
        if ($uid < 1) {
            return false;
        }

        return (bool) $this->db
            ->where('id', $uid)
            ->where('DELETED', 0)
            ->update($this->table, [
                'failed_attempts' => 0,
                'locked_until'    => null,
            ]);
    }

    public function get_user($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    /**
     * Best deliverable email for outbound notifications.
     * Employees: HRMIS tblemployee.emailadd first, then LMS email (skip @lms.local placeholders).
     *
     * @param int $user_id
     * @return string Valid email or empty string
     */
    public function resolve_notification_email($user_id)
    {
        $uid = (int) $user_id;
        if ($uid < 1) {
            return '';
        }

        $user = $this->get_user($uid);
        if ( ! $user) {
            return '';
        }

        $emp_id = trim((string) ($user->employee_id ?? ''));
        if ($emp_id !== '') {
            $hr = $this->get_hrmis_employee($emp_id);
            if ($hr) {
                $hrmis_email = trim((string) ($hr->emailadd ?? ''));
                if ($hrmis_email !== '' && filter_var($hrmis_email, FILTER_VALIDATE_EMAIL)) {
                    return strtolower($hrmis_email);
                }
            }
        }

        $lms_email = trim((string) ($user->email ?? ''));
        if ($lms_email !== ''
            && filter_var($lms_email, FILTER_VALIDATE_EMAIL)
            && ! preg_match('/@lms\.local$/i', $lms_email)) {
            return strtolower($lms_email);
        }

        return '';
    }

    /**
     * Active, non-deleted LMS account by employee ID (password reset eligibility).
     *
     * @param  string $employee_id
     * @return object|null
     */
    public function get_active_user_by_employee_id($employee_id)
    {
        $employee_id = normalize_employee_id($employee_id);
        if ($employee_id === '') {
            return null;
        }

        $user = $this->db
            ->where('employee_id', $employee_id)
            ->where('status', 'active')
            ->where('DELETED', 0)
            ->get($this->table, 1)
            ->row();

        if ($user) {
            return $user;
        }

        return $this->db
            ->where('LOWER(employee_id)', strtolower($employee_id))
            ->where('status', 'active')
            ->where('DELETED', 0)
            ->get($this->table, 1)
            ->row();
    }

    /**
     * @param  int    $user_id
     * @param  string $token_hash  SHA-256 hex of plain token
     * @param  string $start       Y-m-d H:i:s
     * @param  string $end         Y-m-d H:i:s
     * @return bool
     */
    public function save_password_reset_token($user_id, $token_hash, $start, $end)
    {
        $uid = (int) $user_id;
        if ($uid < 1 || $token_hash === '') {
            return false;
        }

        return (bool) $this->db->where('id', $uid)->update($this->table, [
            'token'            => $token_hash,
            'token_date_start' => $start,
            'token_date_end'   => $end,
            'forgot_exp'       => $end,
        ]);
    }

    /**
     * @param  string $token_hash SHA-256 hex of plain token
     * @return object|null
     */
    public function get_user_by_password_reset_token($token_hash)
    {
        if ($token_hash === '') {
            return null;
        }

        $now = date('Y-m-d H:i:s');

        return $this->db
            ->where('token', $token_hash)
            ->where('token_date_end >=', $now)
            ->where('status', 'active')
            ->where('DELETED', 0)
            ->get($this->table, 1)
            ->row();
    }

    /**
     * @param  int    $user_id
     * @param  string $password_hash
     * @return bool
     */
    public function update_password_and_clear_reset_token($user_id, $password_hash)
    {
        $uid = (int) $user_id;
        if ($uid < 1 || $password_hash === '') {
            return false;
        }

        return (bool) $this->db->where('id', $uid)->update($this->table, [
            'password'         => $password_hash,
            'token'            => null,
            'token_date_start' => null,
            'token_date_end'   => null,
            'forgot_exp'       => null,
            'failed_attempts'  => 0,
            'locked_until'     => null,
        ]);
    }

    public function get_all_users($include_deleted = false)
    {
        if ( ! $include_deleted) {
            $this->db->where('DELETED', 0);
        }

        return $this->db
            ->select('id, employee_id, fullname, role, office, status, last_login, created_at')
            ->order_by('fullname', 'ASC')
            ->get($this->table)
            ->result();
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    protected $table = 'aauth_users';
    protected $hrmis_db;

    public function __construct()
    {
        parent::__construct();
        $this->hrmis_db = $this->load->database("hrmis", TRUE);
    }

    /* ===== GET HRMIS EMPLOYEE (ACTIVE ONLY) ===== */
    public function get_hrmis_employee($employee_id)
    {
        return $this->hrmis_db
            ->where('idno', $employee_id)
            ->where('status', 'ACTIVE')
            ->get('tblemployee')
            ->row();
    }

    /* ===== CHECK IF REGISTERED ===== */
    public function is_registered($employee_id)
    {
        $user = $this->db->get_where($this->table, ['employee_id' => $employee_id])->row();
        return (bool) $user;
    }

    /* ===== REGISTER USER ===== */
    public function register_user($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /* ===== LOGIN WITH LOCKOUT PROTECTION ===== */
    public function login($employee_id, $password)
    {
        $user = $this->db
            ->where('employee_id', $employee_id)
            ->where('status', 'active')
            ->where('DELETED', 0)
            ->get($this->table)
            ->row();

        if (!$user) return false;

        // Check lockout
        if ($user->locked_until && strtotime($user->locked_until) > time()) {
            return 'locked';
        }

        // Wrong password
        if (!password_verify($password, $user->password)) {
            $attempts = $user->failed_attempts + 1;
            $update   = ['failed_attempts' => $attempts];
            if ($attempts >= 5) {
                $update['locked_until'] = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            }
            $this->db->where('id', $user->id)->update($this->table, $update);
            return false;
        }

        // Success — reset lockout, record last login
        $this->db->where('id', $user->id)->update($this->table, [
            'failed_attempts' => 0,
            'locked_until'    => NULL,
            'last_login'      => date('Y-m-d H:i:s'),
            'last_activity'   => date('Y-m-d H:i:s'),
        ]);

        return $user;
    }

    /* ===== GET USER BY ID ===== */
    public function get_user($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    /* ===== GET ALL USERS ===== */
    public function get_all_users($include_deleted = false)
    {
        if (! $include_deleted) {
            $this->db->where('DELETED', 0);
        }

        return $this->db
            ->select('id, employee_id, fullname, role, office, status, last_login, created_at')
            ->order_by('fullname', 'ASC')
            ->get($this->table)
            ->result();
    }
}
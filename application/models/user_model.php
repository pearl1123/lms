<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    protected $table = 'aauth_users';

    public function __construct()
    {
        parent::__construct();
    }

    /* ===== GET HRMIS EMPLOYEE (ACTIVE ONLY) ===== */
    public function get_hrmis_employee($employee_id)
    {
        return $this->db
            ->where('employee_id', $employee_id)
            ->where('status', 'active')
            ->get('hrmis_employees')
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
}
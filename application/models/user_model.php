<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class user_model extends CI_Model {

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
        $user = $this->db->get_where('users', ['employee_id' => $employee_id])->row();
        return (bool) $user;
    }

    /* ===== REGISTER USER ===== */

    public function register_user($data)
    {
        return $this->db->insert('users', $data);
    }

    /* ===== LOGIN WITH LOCKOUT PROTECTION ===== */

    public function login($employee_id, $password)
    {
        $user = $this->db
            ->where('employee_id', $employee_id)
            ->get('users')
            ->row();

        if (!$user) {
            return false;
        }

        // Check if locked
        if ($user->locked_until && strtotime($user->locked_until) > time()) {
            return 'locked';
        }

        if (!password_verify($password, $user->password)) {

            $attempts = $user->failed_attempts + 1;

            $update = [
                'failed_attempts' => $attempts
            ];

            if ($attempts >= 5) {
                $update['locked_until'] = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            }

            $this->db->where('id', $user->id)->update('users', $update);

            return false;
        }

        // Successful login → reset attempts
        $this->db->where('id', $user->id)->update('users', [
            'failed_attempts' => 0,
            'locked_until' => NULL
        ]);

        return $user;
    }

    /* ===== GET USER ===== */

    public function get_user($id)
    {
        return $this->db->get_where('users', ['id' => $id])->row();
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_DB_mysqli_driver $db
 * @property CI_Session $session
 * @property CI_Input $input
 * @property CI_Form_validation $form_validation
 * @property User_model $user_model
 */

class Auth extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        $this->load->helper(['url', 'form']);
        $this->load->library(['session', 'form_validation']);
        $this->load->model('User_model', 'user_model');
    }

    public function index()
    {
        $this->login();
    }


    /* ================= LOGIN PAGE ================= */
    public function login()
    {
        if ($this->session->userdata('user_id')) {
            redirect('dashboard');
        }

        $this->load->view('auth/login');
    }

    /* ================= LOGIN PROCESS ================= */
   // Process login
    public function login_process()
    {
        $this->form_validation->set_rules('employee_id', 'Employee ID', 'required|trim');
        $this->form_validation->set_rules('password', 'Password', 'required');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('auth/login');
        }

        $employee_id = trim($this->input->post('employee_id'));
        $password    = $this->input->post('password');

        $result = $this->user_model->login($employee_id, $password);
        if ($result === 'locked') {
            $this->session->set_flashdata('error', 'Account locked. Try again later.');
            redirect('auth/login');
        }

        if ($result) {
            $this->session->sess_regenerate(TRUE);
            $this->session->set_userdata([
                'user_id'     => $result->id,
                'employee_id' => $result->employee_id,
                'name'        => $result->fullname,
                'role'        => $result->role,
            ]);

            redirect('dashboard');
        } else {
            $this->session->set_flashdata('error', 'Invalid Employee ID or Password.');
            redirect('auth/login');
        }
    }

    /* ================= REGISTER PAGE ================= */
    public function register()
    {
        if ($this->session->userdata('user_id')) {
            redirect('dashboard');
        }

        $this->load->view('auth/register');
    }

    /* ================= AJAX CHECK EMPLOYEE ================= */
    public function check_employee()
    {
        header('Content-Type: application/json');

        $emp_id = trim($this->input->post('employee_id'));

        if (!$emp_id) {
            echo json_encode(['exists' => false, 'registered' => false, 'name' => '']);
            return;
        }

        $hr = $this->user_model->get_hrmis_employee($emp_id);

        if ($hr) {
            $registered = $this->user_model->is_registered($emp_id);
            echo json_encode([
                'exists'     => true,
                'registered' => $registered,
                'name'       => $hr->name,
            ]);
        } else {
            echo json_encode([
                'exists'     => false,
                'registered' => false,
                'name'       => '',
            ]);
        }
    }

    /* ================= REGISTER PROCESS ================= */
    public function register_process()
    {
        $this->form_validation->set_rules('employee_id',       'Employee ID',       'required|trim');
        $this->form_validation->set_rules('password',          'Password',          'required|min_length[8]');
        $this->form_validation->set_rules('confirm_password',  'Confirm Password',  'required|matches[password]');
        $this->form_validation->set_rules('agree_terms',       'Terms',             'required');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('auth/login');
            return;
        }

        $emp_id   = trim($this->input->post('employee_id'));
        $password = $this->input->post('password');

        $hr = $this->user_model->get_hrmis_employee($emp_id);

        if (!$hr) {
            $this->session->set_flashdata('error', 'Employee ID not found or inactive in HRMIS.');
            redirect('auth/login');
            return;
        }

        if ($this->user_model->is_registered($emp_id)) {
            $this->session->set_flashdata('error', 'This Employee ID is already registered.');
            redirect('auth/login');
            return;
        }

        $this->user_model->register_user([
            'employee_id' => $emp_id,
            'fullname'    => $hr->name,
            'password'    => password_hash($password, PASSWORD_DEFAULT),
            'role'        => 'employee',
            'status'      => 'active',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->session->set_flashdata('success', 'Registration successful! You can now log in.');
        redirect('auth/login');
    }

    /* ================= LOGOUT ================= */
    public function logout()
    {
        $this->session->sess_destroy();
        redirect('auth/login');
    }
}
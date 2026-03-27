<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Users Controller
 *
 * Route: GET index.php/users -> user list (admin only)
 *
 * @property CI_DB_mysqli_driver $db
 * @property CI_Session          $session
 * @property CI_Input            $input
 * @property User_model          $user_model
 */
class Users extends CI_Controller {

    private $user;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->model('User_model', 'user_model');
        $this->load->helper('url');

        $user_id = $this->session->userdata('user_id');
        if ( ! $user_id) {
            redirect('auth/login');
        }

        $user = $this->user_model->get_user($user_id);
        if ( ! $user) {
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        if ((int) $user->banned === 1 || $user->status !== 'active' || (int) $user->DELETED === 1
            || (! empty($user->locked_until) && strtotime($user->locked_until) > time())) {
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        if ($user->role !== 'admin') {
            $this->session->set_flashdata('error', 'You do not have permission to access Users.');
            redirect('dashboard');
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

    public function index()
    {
        $users = $this->user_model->get_all_users();

        $data = [
            'user'        => $this->user,
            'page_title'  => 'Users & Enrollees',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => 'Users'],
            ],
            'view'        => 'administrator/users/list',
            'users'       => $users,
        ];

        $this->load->view('layouts/main', $data);
    }
}


<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * @property CI_DB_mysqli_driver $db
 * @property CI_Session $session
 * @property CI_Input $input
 * @property CI_Form_validation $form_validation
 * @property User_model $user_model
 */
class Dashboard extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('User_model', 'user_model');
        $this->load->helper('url');

        // Check login
        if (!$this->session->userdata('user_id')) {
            redirect('auth/login');
        }

        $user = $this->user_model->get_user($this->session->userdata('user_id'));
        if (!$user || $user->status !== 'active' || ($user->locked_until && strtotime($user->locked_until) > time())) {
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        $this->user = $user; // Store globally
    }

    // ────────────────────────────────
    // INDEX → DASHBOARD
    // ────────────────────────────────
    public function index()
    {
        $user = $this->user;

        // ── ADMIN DASHBOARD ──
        if ($user->role === 'admin') {
            $data = [
                'user' => $user,
                'page_title' => 'Admin Dashboard',
                'analytics' => [
                    'total_users' => $this->db->count_all('auth_users'),
                    'active_users' => $this->db->where('status','active')->count_all_results('auth_users'),
                    'inactive_users' => $this->db->where('status','inactive')->count_all_results('auth_users'),
                    'total_courses' => $this->db->count_all('courses'),
                ]
            ];
            $this->_render('dashboard/admin', $data);
            return;
        }

        // ── TEACHER DASHBOARD ──
        if ($user->role === 'teacher') {
            $data = [
                'user' => $user,
                'page_title' => 'Instructor Dashboard',
                'analytics' => [
                    'my_courses' => 5, // Example: replace with real query
                    'enrolled_students' => 20
                ]
            ];
            $this->_render('dashboard/teacher', $data);
            return;
        }

        // ── EMPLOYEE DASHBOARD ──
        $data = [
            'user' => $user,
            'page_title' => 'My Learning Dashboard',
            'analytics' => [
                'courses_enrolled' => 12,
                'courses_completed' => 7,
                'learning_hours' => '24h',
                'badges' => 5
            ],
            'lessons' => [
                'Lesson 1 - Introduction',
                'Lesson 2 - Fundamentals',
                'Lesson 3 - Practical Demo',
                'Lesson 4 - Project',
                'Lesson 5 - Final Exam'
            ],
            'notifications' => 3,
            'top_learners' => [
                ['name'=>'Alice','points'=>980],
                ['name'=>'John','points'=>870],
                ['name'=>'Maria','points'=>850]
            ]
        ];

        $this->_render('dashboard/employee', $data);
    }

    // ────────────────────────────────
    // PRIVATE RENDER FUNCTION
    // Wraps layouts (header, navbar, sidebar, footer)
    // ────────────────────────────────
    private function _render($view, $data = [])
    {
        $this->load->view('layouts/header', $data);
        $this->load->view('layouts/sidebar', $data);
        $this->load->view('layouts/navbar', $data);
        $this->load->view($view, $data);
        $this->load->view('layouts/footer', $data);
    }
}
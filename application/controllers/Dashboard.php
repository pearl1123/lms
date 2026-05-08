<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * @property CI_DB_mysqli_driver      $db
 * @property CI_Session               $session
 * @property CI_Input                 $input
 * @property CI_Form_validation       $form_validation
 * @property User_model               $user_model
 * @property dashboard_model          $dashboard_model
 * @property Course_model             $course_model
 * @property Notification_model       $notification_model
 * @property Certificate_model        $certificate_model
 * @property Assessment_service       $assessment_service
 */
class Dashboard extends CI_Controller {

    /** @var object Current authenticated user row */
    private $user;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('User_model', 'user_model');
        $this->load->model('dashboard_model');
        $this->load->model('Course_model', 'course_model');
        $this->load->model('Notification_model', 'notification_model');
        $this->load->model('Certificate_model', 'certificate_model');
        $this->load->library('assessment_service');
        $this->load->helper('url');

        // ── Auth guard ────────────────────────────────────────
        $user_id = $this->session->userdata('user_id');
        if ( ! $user_id) {
            redirect('auth/login');
        }

        $user = $this->user_model->get_user($user_id);

        if ( ! $user) {
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        if ((int) $user->banned    === 1)       { $this->session->sess_destroy(); redirect('auth/login'); }
        if ($user->status         !== 'active') { $this->session->sess_destroy(); redirect('auth/login'); }
        if ((int) $user->DELETED   === 1)       { $this->session->sess_destroy(); redirect('auth/login'); }
        if ( ! empty($user->locked_until) && strtotime($user->locked_until) > time()) {
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        $this->user = $user;

        // ── Store user in session as array so all views/sidebar can read it ──
        $this->session->set_userdata('user', [
            'id'          => $user->id,
            'fullname'    => $user->fullname,
            'employee_id' => $user->employee_id,
            'role'        => $user->role,
            'status'      => $user->status,
        ]);
    }

    // =========================================================
    public function index()
    // =========================================================
    {
        switch ($this->user->role) {
            case 'admin':    $this->_admin_dashboard();    break;
            case 'teacher':  $this->_teacher_dashboard();  break;
            case 'instructor': $this->_teacher_dashboard(); break;
            case 'student':
            case 'employee':
            default:         $this->_employee_dashboard(); break;
        }
    }

    // =========================================================
    // ADMIN DASHBOARD
    // =========================================================
    private function _admin_dashboard()
    {
        $user = $this->user;
        $dashboard_data = $this->dashboard_model->get_admin_dashboard_data((int) $user->id);

        $data = [
            'user'               => $user,
            'page_title'         => 'Admin Dashboard',
            'dashboard_data'     => $dashboard_data,
            'breadcrumbs'        => [['label' => 'Dashboard']],
            'view'               => 'dashboard/admin',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // TEACHER / INSTRUCTOR DASHBOARD
    // =========================================================
    private function _teacher_dashboard()
    {
        $user = $this->user;
        $dashboard_data = $this->dashboard_model->get_instructor_dashboard_data((int) $user->id);
        log_message('debug', 'INSTRUCTOR CHART DATA: ' . json_encode($dashboard_data['charts'] ?? []));

        $data = [
            'user'            => $user,
            'page_title'      => 'Instructor Dashboard',
            'dashboard_data'  => $dashboard_data,
            'breadcrumbs'     => [['label' => 'Dashboard']],
            'view'            => 'dashboard/instructor',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // EMPLOYEE / STUDENT DASHBOARD
    // =========================================================
    private function _employee_dashboard()
    {
        $user = $this->user;
        $dashboard_data = $this->dashboard_model->get_student_dashboard_data((int) $user->id);

        $data = [
            'user'             => $user,
            'page_title'       => 'My Learning Dashboard',
            'dashboard_data'   => $dashboard_data,
            'breadcrumbs'      => [['label' => 'Dashboard']],
            'view'             => 'dashboard/employee',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }
}
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * @property CI_DB_mysqli_driver $db
 * @property CI_Session $session
 * @property CI_Input $input
 * @property CI_Form_validation $form_validation
 * @property User_model $user_model
 */
class Courses extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        
        if (!$this->session->userdata('user_id')) {
            redirect('index.php/auth/login');
        }
        
        $this->load->model('User_model', 'user_model');
        $this->load->model('Course_model', 'course_model');
    }
    
    public function index()
    {
        $user = $this->user_model->get_user($this->session->userdata('user_id'));
        
        $data = [
            'user' => $user,
            'title' => 'Available Courses',
            'pretitle' => 'Browse and enroll',
            'content' => 'courses/index',
            'courses' => $this->course_model->get_all_courses()
        ];
        
        $this->load->view('layouts/main', $data);
    }
}
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Profile — authenticated user account workspace.
 *
 * Routes:
 *   GET  index.php/profile         → profile workspace
 *   POST index.php/profile         → update profile / password / avatar
 *
 * @property Profile_model     $profile_model
 */
class Profile extends KA_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model('Profile_model', 'profile_model');
        $this->load->helper(['url', 'form']);

        $this->require_permission('profile.view');
    }

    /**
     * Profile workspace (GET) or form handlers (POST).
     */
    public function index()
    {
        if ($this->input->method() === 'post') {
            if ($this->input->post('password_submit')) {
                $this->_handle_password_change();
            } elseif ($this->input->post('avatar_submit')) {
                $this->_handle_avatar_upload();
            } elseif ($this->input->post('profile_submit')) {
                $this->_handle_profile_update();
            } else {
                $this->session->set_flashdata('error', 'Unknown form action.');
            }
            redirect('profile');
        }

        $ctx = $this->profile_model->build_profile_context($this->auth_user);

        $data = array_merge([
            'user'       => $this->auth_user,
            'page_title' => 'My Profile',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => 'My Profile'],
            ],
            'view' => 'profile/index',
        ], $ctx);

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    private function _handle_profile_update()
    {
        $this->require_permission('profile.edit', 'profile');

        $this->form_validation->set_rules('email', 'Email', 'trim|valid_email');
        $this->form_validation->set_rules('contact_number', 'Contact number', 'trim|max_length[30]');
        $this->form_validation->set_rules('bio', 'Bio', 'trim|max_length[500]');

        if ( ! $this->form_validation->run()) {
            $this->session->set_flashdata('error', validation_errors());
            return;
        }

        $res = $this->profile_model->update_profile_from_post($this->auth_user, $_POST);
        $ok  = $res['ok'];
        $msg = $res['message'];

        if ( ! empty($_FILES['avatar']['name'])) {
            $av = $this->profile_model->save_avatar_upload((int) $this->auth_user->id, $_FILES['avatar']);
            if ($av['ok']) {
                $ok  = true;
                $msg = $res['ok'] ? 'Profile and photo updated.' : $av['message'];
            } elseif ( ! $res['ok']) {
                $msg = $res['message'] . ' ' . $av['message'];
            }
        }

        $this->session->set_flashdata($ok ? 'success' : 'error', $msg);

        if ($ok) {
            $this->auth_user = $this->user_model->get_user((int) $this->auth_user->id);
        }
    }

    private function _handle_password_change()
    {
        $this->require_permission('profile.edit', 'profile');

        $this->form_validation
            ->set_rules('current_password', 'Current password', 'required')
            ->set_rules('new_password', 'New password', 'required|min_length[8]')
            ->set_rules('confirm_password', 'Confirm password', 'required|matches[new_password]');

        if ( ! $this->form_validation->run()) {
            $this->session->set_flashdata('error', validation_errors());
            return;
        }

        $res = $this->profile_model->change_password(
            (int) $this->auth_user->id,
            (string) $this->input->post('current_password'),
            (string) $this->input->post('new_password')
        );
        $this->session->set_flashdata($res['ok'] ? 'success' : 'error', $res['message']);
    }

    private function _handle_avatar_upload()
    {
        $this->require_permission('profile.edit', 'profile');

        if (empty($_FILES['avatar'])) {
            $this->session->set_flashdata('error', 'Choose an image to upload.');
            return;
        }

        $res = $this->profile_model->save_avatar_upload((int) $this->auth_user->id, $_FILES['avatar']);
        $this->session->set_flashdata($res['ok'] ? 'success' : 'error', $res['message']);
    }
}

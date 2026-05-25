<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Settings — platform administration (admin only).
 *
 * @property User_model      $user_model
 * @property Settings_model  $settings_model
 */
class Settings extends CI_Controller {

    /** @var object */
    private $user;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->model('User_model', 'user_model');
        $this->load->model('Settings_model', 'settings_model');
        $this->load->helper(['url', 'form']);

        $user_id = $this->session->userdata('user_id');
        if ( ! $user_id) {
            redirect('auth/login');
        }

        $user = $this->user_model->get_user($user_id);
        if ( ! $user) {
            $this->session->sess_destroy();
            redirect('auth/login');
        }
        if ((int) $user->banned === 1 || $user->status !== 'active' || (int) $user->DELETED === 1) {
            $this->session->sess_destroy();
            redirect('auth/login');
        }
        if ( ! empty($user->locked_until) && strtotime($user->locked_until) > time()) {
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        if (strtolower((string) ($user->role ?? '')) !== 'admin') {
            $this->session->set_flashdata('error', 'Only administrators can access platform settings.');
            redirect('dashboard');
        }

        $this->user = $user;
    }

    public function index()
    {
        if ($this->input->method() === 'post' && $this->input->post('settings_save')) {
            $this->_handle_save();
            redirect('settings');
        }

        $settings = $this->settings_model->get_all_settings();
        $admin_ctx = $this->settings_model->build_admin_context();

        $data = array_merge([
            'user'       => $this->user,
            'page_title' => 'Settings',
            'settings'   => $settings,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => 'Administration'],
            ],
            'view' => 'settings/index',
        ], $admin_ctx);

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    private function _handle_save()
    {
        if ( ! $this->settings_model->table_ready()) {
            $this->session->set_flashdata('error', 'Run application/sql/migration_lms_settings.sql to enable saving platform settings.');
            return;
        }

        $sections = $this->input->post('settings');
        if ( ! is_array($sections)) {
            $this->session->set_flashdata('error', 'No settings data received.');
            return;
        }

        $current = $this->settings_model->get_all_settings();
        $actor   = (int) $this->user->id;

        if (isset($sections['notifications']['smtp_pass']) && trim((string) $sections['notifications']['smtp_pass']) === '') {
            unset($sections['notifications']['smtp_pass']);
            if (isset($current['notifications']['smtp_pass'])) {
                $sections['notifications']['smtp_pass'] = $current['notifications']['smtp_pass'];
            }
        }

        foreach (['logo_upload', 'favicon_upload', 'login_bg_upload'] as $upload_key) {
            if (empty($_FILES[$upload_key]['name'])) {
                continue;
            }
            $field_map = [
                'logo_upload'     => 'logo_path',
                'favicon_upload'  => 'favicon_path',
                'login_bg_upload' => 'login_bg_path',
            ];
            $res = $this->settings_model->save_branding_upload(
                'branding',
                $field_map[$upload_key],
                $_FILES[$upload_key],
                $current,
                $actor
            );
            if ($res['ok']) {
                $current = $this->settings_model->get_all_settings();
            }
        }

        $ok = $this->settings_model->save_sections($sections, $actor);
        $this->session->set_flashdata(
            $ok ? 'success' : 'error',
            $ok ? 'Platform settings saved.' : 'Could not save settings.'
        );
    }
}

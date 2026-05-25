<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Friendly fallback pages (404 / under construction).
 * Does not extend KA_Controller — must work for guests and broken routes.
 *
 * @property CI_Session $session
 * @property CI_Input   $input
 * @property User_model $user_model
 */
class Error_pages extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'form', 'ka_layout']);
    }

    /**
     * CI3 404_override — unknown routes and missing controller methods.
     */
    public function not_found()
    {
        $this->_render_fallback('not_found');
    }

    /**
     * Intentional placeholder for unfinished features.
     *
     * @param string $message Optional URI segment message (URL-encoded).
     */
    public function under_construction($message = '')
    {
        $this->_render_fallback('construction', $message);
    }

    /**
     * @param string $mode        not_found|construction
     * @param string $message_arg Optional custom copy
     */
    private function _render_fallback($mode, $message_arg = '')
    {
        $custom = trim((string) $message_arg);
        if ($custom === '') {
            $custom = trim((string) $this->input->get('msg', true));
        }

        if ($mode === 'not_found') {
            $this->output->set_status_header(404);
            $page_title = 'Page not found';
            $title      = 'Page not found';
            $subtitle   = 'This link may be incorrect, or the page may have been moved.';
            $hint       = 'We\'re actively improving this part of the LMS. Try the dashboard or go back to where you were.';
            $icon       = 'compass';
        } else {
            $this->output->set_status_header(200);
            $page_title = 'Coming soon';
            $title      = 'Feature in Development';
            $subtitle   = 'This section is currently being built and will be available soon.';
            $hint       = 'We\'re actively improving this part of the LMS.';
            $icon       = 'rocket';
        }

        if ($custom !== '') {
            $hint = $custom;
        }

        $user = $this->_resolve_session_user();

        $payload = [
            'page_title'       => $page_title,
            'ef_title'         => $title,
            'ef_subtitle'      => $subtitle,
            'ef_hint'          => $hint,
            'ef_icon'          => $icon,
            'ef_mode'          => $mode,
            'ef_dashboard_url' => $user ? site_url('dashboard') : site_url('auth/login'),
            'ef_primary_label' => $user ? 'Back to Dashboard' : 'Sign in',
            'ef_login_url'     => site_url('auth/login'),
            'embedded_in_app'  => false,
        ];

        if ($user) {
            $payload['embedded_in_app'] = true;
            $payload['user']            = $user;
            $payload['view']            = 'errors/under_construction';
            $payload['breadcrumbs']     = [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => $page_title],
            ];

            $this->load->view('layouts/main', ka_merge_layout_vars($this, $payload));
            return;
        }

        $this->load->view('errors/under_construction_standalone', $payload);
    }

    /**
     * @return object|null Active user for app shell, or null for guest standalone.
     */
    private function _resolve_session_user()
    {
        $user_id = $this->session->userdata('user_id');
        if ( ! $user_id) {
            return null;
        }

        $this->load->model('User_model', 'user_model');
        $user = $this->user_model->get_user((int) $user_id);

        if ( ! $user
            || (int) $user->banned === 1
            || $user->status !== 'active'
            || (int) $user->DELETED === 1
            || ( ! empty($user->locked_until) && strtotime($user->locked_until) > time())
        ) {
            return null;
        }

        return $user;
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('ka_user_initials')) {
    /**
     * @param string $full_name
     * @return string
     */
    function ka_user_initials($full_name)
    {
        $initials = '';
        foreach (explode(' ', trim((string) $full_name)) as $word) {
            if ($word !== '') {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        return substr($initials, 0, 2);
    }
}

if ( ! function_exists('ka_merge_layout_vars')) {
    /**
     * Merge variables required by layouts/main, sidebar, navbar, and alerts.
     *
     * @param CI_Controller $CI
     * @param array         $data Must include object `user` on authenticated pages
     * @return array
     */
    function ka_merge_layout_vars(CI_Controller $CI, array $data)
    {
        // CI_Security is a core class (system/core/Security.php), loaded at bootstrap
        // as $CI->security — do not use load->library('security'); that path does not exist.
        $user = $data['user'] ?? null;

        $full_name   = 'User';
        $employee_id = '';
        $role_raw    = 'employee';

        if (is_object($user)) {
            $full_name   = $user->fullname ?? 'User';
            $employee_id = $user->employee_id ?? '';
            $role_raw    = strtolower($user->role ?? 'employee');
        }

        $streak = null;
        if (is_object($user) && isset($user->streak) && $user->streak !== '' && $user->streak !== null) {
            $streak = $user->streak;
        }

        // Use core URI singleton (same instance as routing). Avoids $CI->uri, which is not
        // declared on CI_Controller and triggers "Undefined property '$uri'" in analyzers.
        $URI =& load_class('URI', 'core');

        $data['nav_context'] = [
            'segment_1'        => (string) ($URI->segment(1) ?: ''),
            'segment_2'        => (string) ($URI->segment(2) ?: ''),
            'user_role'        => $role_raw,
            'user_role_label'  => ucfirst($role_raw),
            'full_name'        => $full_name,
            'employee_id'      => $employee_id,
            'initials'         => ka_user_initials($full_name),
            'my_courses_label' => ($role_raw === 'employee') ? 'My Learning' : 'My Courses',
            'streak'           => $streak,
        ];

        $data['flash_messages'] = ka_collect_flash_messages($CI);
        if ( ! is_array($data['flash_messages'])) {
            $data['flash_messages'] = [];
        }

        $SEC =& load_class('Security', 'core');
        $data['csrf_field_name'] = (string) $SEC->get_csrf_token_name();
        $data['csrf_hash']       = (string) $SEC->get_csrf_hash();

        if ( ! isset($data['unread_notification_count']) && is_object($user) && isset($user->id)) {
            $CI->load->model('Notification_model', 'notification_model');
            $notification_model = $CI->{'notification_model'};
            $data['unread_notification_count'] = $notification_model
                ->count_unread((int) $user->id);
        }

        $data['notif_count'] = (int) ($data['unread_notification_count'] ?? 0);

        // Pre-render alerts so content views never call $this->load or get_instance().
        $data['alerts_partial_html'] = '';
        $CI->load->vars(['flash_messages' => $data['flash_messages']]);
        $alerts_out = $CI->load->view('layouts/alerts', [], true);
        $data['alerts_partial_html'] = is_string($alerts_out) ? $alerts_out : '';

        return $data;
    }
}

if ( ! function_exists('ka_collect_flash_messages')) {
    /**
     * Read flashdata once per key (consumes flash). Used by layouts and auth pages.
     *
     * @param CI_Controller $CI
     * @param string[]|null $keys default: layout alert keys
     * @return array<string,string>
     */
    function ka_collect_flash_messages(CI_Controller $CI, array $keys = null)
    {
        if ($keys === null) {
            $keys = [
                'success',
                'error',
                'warning',
                'info',
                'enrollment_notification',
            ];
        }
        $session         = $CI->{'session'};
        $flash_messages = [];
        foreach ($keys as $key) {
            $msg = $session->flashdata($key);
            if ($msg) {
                $flash_messages[$key] = $msg;
            }
        }

        return $flash_messages;
    }
}

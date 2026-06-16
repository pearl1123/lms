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

        $avatar_url = is_object($user) ? ka_user_avatar_url($user) : '';

        $perm_engine = function_exists('ka_permission_engine_active') && ka_permission_engine_active();
        $effective   = [];
        if ($perm_engine && is_object($user) && isset($user->id)) {
            $effective = ka_user_effective_permissions((int) $user->id);
        }

        $data['nav_context'] = [
            'segment_1'        => (string) ($URI->segment(1) ?: ''),
            'segment_2'        => (string) ($URI->segment(2) ?: ''),
            'user_role'        => $role_raw,
            'user_role_label'  => ucfirst($role_raw),
            'full_name'        => $full_name,
            'employee_id'      => $employee_id,
            'initials'         => ka_user_initials($full_name),
            'avatar_url'       => $avatar_url,
            'my_courses_label' => in_array($role_raw, ['employee', 'student'], true) ? 'My Learning' : 'My Courses',
            'streak'           => $streak,
            'perm_engine'      => $perm_engine,
            'effective_perms'=> $effective,
        ];

        $data['ka_branding'] = ka_branding_settings();

        $data['flash_messages'] = ka_collect_flash_messages($CI);
        if ( ! is_array($data['flash_messages'])) {
            $data['flash_messages'] = [];
        }

        $f2f_flash = $CI->{'session'}->flashdata('etd_f2f_advisory');
        $data['etd_f2f_advisory_html'] = is_string($f2f_flash) ? trim($f2f_flash) : '';

        $csrf_enabled = (bool) config_item('csrf_protection');
        if ($csrf_enabled) {
            $SEC =& load_class('Security', 'core');
            $data['csrf_field_name'] = (string) $SEC->get_csrf_token_name();
            $data['csrf_hash']       = (string) $SEC->get_csrf_hash();
        } else {
            $data['csrf_field_name'] = '';
            $data['csrf_hash']       = '';
        }

        $uid = (is_object($user) && isset($user->id)) ? (int) $user->id : 0;
        if ($uid > 0) {
            $CI->load->model('Notification_model', 'notification_model');
            $notification_model = $CI->{'notification_model'};
            if ( ! isset($data['unread_notification_count'])) {
                $data['unread_notification_count'] = $notification_model->count_unread($uid);
            }
            if ( ! isset($data['navbar_notifications'])) {
                $data['navbar_notifications'] = $notification_model->get_all($uid, 5, 0);
            }
        }
        if ( ! isset($data['navbar_notifications']) || ! is_array($data['navbar_notifications'])) {
            $data['navbar_notifications'] = [];
        }

        $data['notif_count'] = (int) ($data['unread_notification_count'] ?? 0);
        $data['notifications_context'] = [
            'unreadUrl'      => base_url('index.php/notifications/unread_count'),
            'latestUrl'      => base_url('index.php/notifications/latest'),
            'markReadBaseUrl'=> base_url('index.php/notifications/mark_read/'),
            'allUrl'         => base_url('index.php/notifications'),
            'csrfName'       => $data['csrf_field_name'],
            'csrfHash'       => $data['csrf_hash'],
        ];

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
                'reminder',
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

if ( ! function_exists('ka_lms_allowed_return_targets')) {
    /**
     * Whitelist for ?return_url=… on course / edit navigation (path keys, not full URLs).
     *
     * @return string[]
     */
    function ka_lms_allowed_return_targets()
    {
        return ['my_courses', 'manage_courses', 'courses'];
    }
}

if ( ! function_exists('ka_lms_normalize_return_target')) {
    /**
     * @param mixed $raw
     * @return string  one of allowed targets, or '' if invalid
     */
    function ka_lms_normalize_return_target($raw)
    {
        $v = strtolower(trim((string) $raw));

        return in_array($v, ka_lms_allowed_return_targets(), true) ? $v : '';
    }
}

if ( ! function_exists('ka_lms_default_return_target_for_role')) {
    /**
     * Default list landing when return_url is absent.
     *
     * @param object|null $user
     * @return string
     */
    function ka_lms_default_return_target_for_role($user)
    {
        $role = strtolower(is_object($user) ? ($user->role ?? '') : '');

        return in_array($role, ['admin', 'teacher'], true) ? 'manage_courses' : 'my_courses';
    }
}

if ( ! function_exists('ka_lms_resolve_return_target')) {
    /**
     * Resolve return_url from query/post with role safety, then role default.
     *
     * @param object|null $user
     * @param mixed       $raw
     * @return string
     */
    function ka_lms_resolve_return_target($user, $raw)
    {
        $n = ka_lms_normalize_return_target($raw);
        $role = strtolower(is_object($user) ? ($user->role ?? '') : '');

        if ($n === '') {
            return ka_lms_default_return_target_for_role($user);
        }

        if ($n === 'manage_courses' && ! in_array($role, ['admin', 'teacher'], true)) {
            return 'my_courses';
        }

        return $n;
    }
}

if ( ! function_exists('ka_lms_return_q')) {
    /**
     * Query fragment for links (no leading "?").
     *
     * @param string $target
     * @return string  e.g. return_url=my_courses, or '' if invalid
     */
    function ka_lms_return_q($target)
    {
        $t = ka_lms_normalize_return_target($target);
        if ($t === '') {
            return '';
        }

        return 'return_url=' . rawurlencode($t);
    }
}

if ( ! function_exists('ka_lms_append_return_to_url')) {
    /**
     * Append return_url to a URL that may already have a query string.
     *
     * @param string $url
     * @param string $target
     * @return string
     */
    function ka_lms_append_return_to_url($url, $target)
    {
        $q = ka_lms_return_q($target);
        if ($q === '') {
            return $url;
        }
        $sep = (strpos($url, '?') !== false) ? '&' : '?';

        return $url . $sep . $q;
    }
}

if ( ! function_exists('ka_assessment_create_back_href')) {
    /**
     * Back link from assessments/create when opened from a course module.
     *
     * @param object|null $user
     * @param int         $course_id
     * @param mixed       $return_raw return_url query value
     * @return string     path segment (no base_url), e.g. manage_courses/edit/21?return_url=my_courses#modules
     */
    function ka_assessment_create_back_href($user, $course_id, $return_raw = '')
    {
        $course_id = (int) $course_id;
        if ($course_id < 1) {
            return 'assessments';
        }

        $rt  = ka_lms_resolve_return_target($user, $return_raw);
        $q   = ka_lms_return_q($rt);
        $path = 'manage_courses/edit/' . $course_id;
        if ($q !== '') {
            $path .= '?' . $q;
        }

        return $path . '#modules';
    }
}

if ( ! function_exists('ka_module_is_video_content')) {
    /**
     * Whether a course module supports in-video checkpoints (manage UI + validation).
     *
     * @deprecated Prefer {@see course_phase3_effective_module_type()} === 'video' for the module contract.
     *             This helper still treats YouTube/file URLs as video when type was mis-saved; new code
     *             should rely on effective type only.
     *
     * Accepts canonical content_type "video" and legacy/alternate labels; also treats
     * YouTube or direct video file URLs in content_path as video when type was saved differently.
     *
     * @param object|array|null $module Row with content_type and optional content_path
     */
    function ka_module_is_video_content($module)
    {
        if ($module === null) {
            return false;
        }

        return course_phase3_effective_module_type_for_row($module, 'ka_module_is_video_content') === 'video';
    }
}

if ( ! function_exists('ka_under_construction_url')) {
    /**
     * URL for the “coming soon” placeholder (unfinished menu links / features).
     *
     * @param string $message Optional hint shown on the page
     * @return string
     */
    function ka_under_construction_url($message = '')
    {
        $url = site_url('error_pages/under_construction');
        $message = trim((string) $message);
        if ($message !== '') {
            $url .= '?msg=' . rawurlencode($message);
        }

        return $url;
    }
}

if ( ! function_exists('ka_redirect_under_construction')) {
    /**
     * Redirect to the under-construction workspace (logged-in users keep app shell).
     *
     * @param string $message
     * @return void
     */
    function ka_redirect_under_construction($message = '')
    {
        redirect(ka_under_construction_url($message));
    }
}

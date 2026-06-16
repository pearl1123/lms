<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('ka_permission_manifest')) {
    /**
     * @return array<string, mixed>
     */
    function ka_permission_manifest()
    {
        static $manifest = null;
        if ($manifest === null) {
            $manifest = require APPPATH . 'config/lms_permissions.php';
        }

        return $manifest;
    }
}

if ( ! function_exists('ka_permission_engine_active')) {
    function ka_permission_engine_active()
    {
        $CI = &get_instance();
        $CI->load->model('Permission_model', 'permission_model');

        return $CI->permission_model->engine_is_active();
    }
}

if ( ! function_exists('ka_user_can')) {
    /**
     * Check effective permission for the current session user.
     * When the engine has no seed data, returns false (caller should use role fallback).
     */
    function ka_user_can($permission_name)
    {
        $CI = &get_instance();
        $uid = (int) $CI->session->userdata('user_id');
        if ($uid < 1) {
            return false;
        }

        $CI->load->model('Permission_model', 'permission_model');

        return $CI->permission_model->user_has($uid, $permission_name);
    }
}

if ( ! function_exists('ka_nav_can')) {
    /**
     * Whether a sidebar segment is visible for the current user.
     *
     * @param string $segment URI segment 1
     * @param string $role    aauth_users.role
     */
    function ka_nav_can($segment, $role = 'employee')
    {
        $manifest = ka_permission_manifest();
        $nav      = $manifest['nav'] ?? [];
        $perm     = trim((string) ($nav[$segment] ?? ''));

        if ($perm === '') {
            return true;
        }

        if ( ! ka_permission_engine_active()) {
            if ($segment === 'users' || $segment === 'permissions') {
                return strtolower($role) === 'admin';
            }
            if (in_array($segment, ['manage_courses', 'categories', 'reports', 'settings', 'libraries'], true)) {
                return in_array(strtolower($role), ['admin', 'teacher'], true);
            }

            return true;
        }

        if (ka_user_can($perm)) {
            return true;
        }

        return ka_learner_role_has_manifest_permission($perm, $role);
    }
}

if ( ! function_exists('ka_learner_role_has_manifest_permission')) {
    /**
     * @param string|string[] $permissions
     * @param string|null     $role
     */
    function ka_learner_role_has_manifest_permission($permissions, $role = null)
    {
        $role = strtolower((string) ($role ?? ''));
        if ($role === '' && function_exists('get_instance')) {
            $CI = &get_instance();
            $role = strtolower((string) ($CI->session->userdata('role') ?? ''));
        }
        if ( ! in_array($role, ['employee', 'student'], true)) {
            return false;
        }

        $manifest = ka_permission_manifest();
        $allowed  = $manifest['groups']['Employee']['permissions'] ?? [];
        if ($allowed === []) {
            return false;
        }

        foreach ((array) $permissions as $permission) {
            $permission = trim((string) $permission);
            if ($permission !== '' && in_array($permission, $allowed, true)) {
                return true;
            }
        }

        return false;
    }
}

if ( ! function_exists('ka_gate_permission')) {
    /**
     * Enforce permission for controllers that do not extend KA_Controller.
     *
     * @param object $user aauth_users row
     * @param string|string[] $permissions
     * @param string $redirect
     * @return void
     */
    function ka_gate_permission($user, $permissions, $redirect = 'dashboard')
    {
        $permissions = array_values(array_filter(array_map('trim', (array) $permissions)));
        if ($permissions === []) {
            return;
        }

        $CI = &get_instance();
        $CI->load->model('Permission_model', 'permission_model');

        if ( ! $CI->permission_model->engine_is_active()) {
            if (is_object($user) && strtolower((string) ($user->role ?? '')) === 'admin') {
                return;
            }

            return;
        }

        $uid = is_object($user) ? (int) ($user->id ?? 0) : 0;
        if ($uid < 1 || ! $CI->permission_model->user_has_any($uid, $permissions)) {
            $CI->session->set_flashdata('error', 'You do not have permission to access that page.');
            redirect($redirect);
            exit;
        }
    }
}

if ( ! function_exists('ka_user_effective_permissions')) {
    /**
     * @return array<string, bool>
     */
    function ka_user_effective_permissions($user_id = null)
    {
        $CI = &get_instance();
        if ($user_id === null) {
            $user_id = (int) $CI->session->userdata('user_id');
        }

        $CI->load->model('Permission_model', 'permission_model');

        return $CI->permission_model->get_effective_map((int) $user_id);
    }
}

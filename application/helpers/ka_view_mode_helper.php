<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('ka_roles_with_learner_switch')) {
    /**
     * Account roles that may toggle instructor ↔ learner experience.
     *
     * @return string[]
     */
    function ka_roles_with_learner_switch()
    {
        return ['admin', 'teacher', 'instructor'];
    }
}

if ( ! function_exists('ka_user_can_switch_learner_mode')) {
    /**
     * @param string|null $account_role aauth_users.role
     */
    function ka_user_can_switch_learner_mode($account_role = null)
    {
        if ($account_role === null) {
            $CI = &get_instance();
            $account_role = strtolower((string) ($CI->session->userdata('user')['role'] ?? ''));
        }

        return in_array(strtolower(trim((string) $account_role)), ka_roles_with_learner_switch(), true);
    }
}

if ( ! function_exists('ka_user_acting_as_learner')) {
    /**
     * True when an instructor/teacher chose "learning mode" in the sidebar.
     *
     * @param string|null $account_role
     */
    function ka_user_acting_as_learner($account_role = null)
    {
        if ( ! ka_user_can_switch_learner_mode($account_role)) {
            return false;
        }

        $CI = &get_instance();

        return (int) $CI->session->userdata('lms_act_as_learner') === 1;
    }
}

if ( ! function_exists('ka_user_is_learner_experience')) {
    /**
     * True for employees/students, or instructors currently in learning mode.
     *
     * @param string|null $account_role
     */
    function ka_user_is_learner_experience($account_role = null)
    {
        if ($account_role === null) {
            $CI = &get_instance();
            $account_role = strtolower((string) ($CI->session->userdata('user')['role'] ?? ''));
        }

        $role = strtolower(trim((string) $account_role));
        if (in_array($role, ['employee', 'student'], true)) {
            return true;
        }

        return ka_user_acting_as_learner($role);
    }
}

if ( ! function_exists('ka_viewer_is_learner_experience')) {
    /**
     * @param object|null $viewer User row (typically auth_user)
     */
    function ka_viewer_is_learner_experience($viewer)
    {
        if ( ! is_object($viewer)) {
            return false;
        }

        return ka_user_is_learner_experience($viewer->role ?? '');
    }
}

if ( ! function_exists('ka_user_effective_experience_role')) {
    /**
     * Role used for learner-facing UI/routing (nav, dashboard, enrollments).
     *
     * @param string $account_role
     */
    function ka_user_effective_experience_role($account_role)
    {
        $account_role = strtolower(trim((string) $account_role));
        if (ka_user_acting_as_learner($account_role)) {
            return 'employee';
        }

        return $account_role;
    }
}

if ( ! function_exists('ka_user_experience_role_label')) {
    /**
     * @param string $account_role
     */
    function ka_user_experience_role_label($account_role)
    {
        $account_role = strtolower(trim((string) $account_role));
        if (ka_user_acting_as_learner($account_role)) {
            return 'Employee (learning)';
        }
        if (in_array($account_role, ['teacher', 'instructor'], true)) {
            return 'Instructor';
        }
        if ($account_role === 'admin') {
            return 'Administrator';
        }

        return ucfirst($account_role);
    }
}

if ( ! function_exists('ka_staff_mode_label_for_role')) {
    /**
     * Label for management/staff mode flash copy (admin vs instructor).
     *
     * @param string|null $account_role
     */
    function ka_staff_mode_label_for_role($account_role = null)
    {
        if ($account_role === null) {
            $CI = &get_instance();
            $account_role = strtolower((string) ($CI->session->userdata('user')['role'] ?? ''));
        }

        $account_role = strtolower(trim((string) $account_role));

        return $account_role === 'admin' ? 'Administrator' : 'Instructor';
    }
}

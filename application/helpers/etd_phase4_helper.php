<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('etd_phase4_settings')) {
    /**
     * Cached Phase 4 settings slice from lms_settings.
     *
     * @return array<string,mixed>
     */
    function etd_phase4_settings()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = [
            'f2f_notice_html'           => '',
            'managerial_category_slugs' => 'management,managerial,leadership',
            'retake_full_course_enabled'=> '1',
            'hyflex_label_map'          => 'hybrid:HyFlex',
        ];

        $CI =& get_instance();
        if ( ! isset($CI->db)) {
            return $cache;
        }

        $CI->load->model('Settings_model', 'settings_model');
        if ( ! $CI->settings_model->table_ready()) {
            return $cache;
        }

        $all = $CI->settings_model->get_all_settings();
        $etd = is_array($all['etd'] ?? null) ? $all['etd'] : [];
        $cache = array_merge($cache, $etd);

        return $cache;
    }
}

if ( ! function_exists('etd_modality_display_label')) {
    /**
     * UI label for modality (HyFlex rename; DB value unchanged).
     *
     * @param string|null $modality_desc
     * @return string
     */
    function etd_modality_display_label($modality_desc)
    {
        $raw = trim((string) $modality_desc);
        if ($raw === '') {
            return '';
        }

        $map = etd_phase4_settings()['hyflex_label_map'] ?? 'hybrid:HyFlex';
        foreach (explode(',', (string) $map) as $pair) {
            $pair = trim($pair);
            if ($pair === '' || strpos($pair, ':') === false) {
                continue;
            }
            list($from, $to) = array_map('trim', explode(':', $pair, 2));
            if (strcasecmp($raw, $from) === 0) {
                return $to;
            }
        }

        if (preg_match('/^hybrid$/i', $raw)) {
            return 'HyFlex';
        }

        return $raw;
    }
}

if ( ! function_exists('etd_f2f_notice_html')) {
    /**
     * Admin-configured Face-to-Face advisory HTML.
     *
     * @return string
     */
    function etd_f2f_notice_html()
    {
        return trim((string) (etd_phase4_settings()['f2f_notice_html'] ?? ''));
    }
}

if ( ! function_exists('etd_is_face_to_face_modality')) {
    /**
     * @param string|null $modality_desc
     */
    function etd_is_face_to_face_modality($modality_desc)
    {
        $d = strtolower(trim((string) $modality_desc));

        return $d !== '' && (
            strpos($d, 'face') !== false
            || strpos($d, 'f2f') !== false
            || strpos($d, 'in-person') !== false
            || strpos($d, 'in person') !== false
        );
    }
}

if ( ! function_exists('etd_managerial_category_slugs')) {
    /** @return string[] */
    function etd_managerial_category_slugs()
    {
        $raw = (string) (etd_phase4_settings()['managerial_category_slugs'] ?? 'management,managerial,leadership');
        $parts = array_filter(array_map(static function ($s) {
            return strtolower(trim($s));
        }, explode(',', $raw)));

        return $parts ?: ['management', 'managerial', 'leadership'];
    }
}

if ( ! function_exists('etd_course_is_managerial')) {
    /**
     * True when any mapped category name matches managerial slugs.
     *
     * @param object|null $course  expects category_name and/or categories[]
     * @param CI_Controller|null $CI optional for DB lookup by course id
     */
    function etd_course_is_managerial($course, $CI = null)
    {
        if ( ! $course) {
            return false;
        }

        $slugs = etd_managerial_category_slugs();
        $names = [];

        if ( ! empty($course->category_name)) {
            $names[] = strtolower((string) $course->category_name);
        }
        if ( ! empty($course->categories) && is_array($course->categories)) {
            foreach ($course->categories as $c) {
                if (is_object($c) && ! empty($c->name)) {
                    $names[] = strtolower((string) $c->name);
                }
            }
        }

        $cid = (int) ($course->id ?? $course->course_id ?? 0);
        if (empty($names) && $cid > 0 && $CI) {
            $CI->load->model('Course_phase2_model', 'course_phase2');
            $CI->load->model('Course_model', 'course_model');
            $ids = $CI->course_phase2->get_course_category_ids($cid);
            if ( ! empty($ids)) {
                foreach ($CI->course_model->get_categories() as $cat) {
                    if (in_array((int) $cat->id, $ids, true) && ! empty($cat->name)) {
                        $names[] = strtolower((string) $cat->name);
                    }
                }
            }
        }

        foreach ($names as $name) {
            foreach ($slugs as $slug) {
                if ($slug !== '' && strpos($name, $slug) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}

if ( ! function_exists('etd_retake_requires_full_course')) {
    /**
     * Non-managerial post-assessment failures require full course retake.
     *
     * @param object|null $course
     */
    function etd_retake_requires_full_course($course)
    {
        if ((string) (etd_phase4_settings()['retake_full_course_enabled'] ?? '1') !== '1') {
            return false;
        }

        return ! etd_course_is_managerial($course);
    }
}

if ( ! function_exists('ka_user_avatar_url')) {
    /**
     * Public URL for user avatar or empty string.
     *
     * @param object|array|null $user
     */
    function ka_user_avatar_url($user)
    {
        $path = '';
        if (is_object($user) && ! empty($user->avatar_path)) {
            $path = (string) $user->avatar_path;
        } elseif (is_array($user) && ! empty($user['avatar_path'])) {
            $path = (string) $user['avatar_path'];
        }

        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $full = FCPATH . ltrim(str_replace(['../', '..\\'], '', $path), '/\\');
        if ( ! is_file($full)) {
            return '';
        }

        return base_url(ltrim($path, '/'));
    }
}

if ( ! function_exists('ka_branding_settings')) {
    /**
     * @return array<string,string>
     */
    function ka_branding_settings()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = [
            'logo_url'     => '',
            'favicon_url'=> '',
            'accent_color' => '#6dabcf',
            'org_name'     => 'Lung Center of the Philippines',
            'lms_name'     => 'kaBAGA Academy',
        ];

        $CI =& get_instance();
        if ( ! isset($CI->db)) {
            return $cache;
        }

        $CI->load->model('Settings_model', 'settings_model');
        if ( ! $CI->settings_model->table_ready()) {
            return $cache;
        }

        $all = $CI->settings_model->get_all_settings();
        $b   = is_array($all['branding'] ?? null) ? $all['branding'] : [];
        $g   = is_array($all['general'] ?? null) ? $all['general'] : [];

        if ( ! empty($b['logo_path'])) {
            $cache['logo_url'] = base_url(ltrim((string) $b['logo_path'], '/'));
        }
        if ( ! empty($b['favicon_path'])) {
            $cache['favicon_url'] = base_url(ltrim((string) $b['favicon_path'], '/'));
        }
        if ( ! empty($b['accent_color'])) {
            $cache['accent_color'] = (string) $b['accent_color'];
        }
        if ( ! empty($g['organization_name'])) {
            $cache['org_name'] = (string) $g['organization_name'];
        }
        if ( ! empty($g['lms_name'])) {
            $cache['lms_name'] = (string) $g['lms_name'];
        }

        return $cache;
    }
}

if ( ! function_exists('etd_assessment_type_supports_randomize')) {
    /**
     * Only pre/post exams may shuffle question order for learners.
     *
     * @param object|null $assessment
     */
    function etd_assessment_type_supports_randomize($assessment)
    {
        if ( ! $assessment) {
            return false;
        }

        $type = strtolower(trim((string) ($assessment->type ?? '')));

        return in_array($type, ['pre', 'post'], true);
    }
}

if ( ! function_exists('etd_assessment_randomize_default_for_type')) {
    /**
     * Default randomize flag when creating assessments.
     *
     * @param string $type pre|post|checkpoint
     */
    function etd_assessment_randomize_default_for_type($type)
    {
        $type = strtolower(trim((string) $type));

        return in_array($type, ['pre', 'post'], true) ? 1 : 0;
    }
}

if ( ! function_exists('etd_assessment_randomize_enabled')) {
    /**
     * Learner take view: shuffle when type is pre/post and setting is enabled.
     *
     * @param object|null $assessment
     */
    function etd_assessment_randomize_enabled($assessment)
    {
        if ( ! etd_assessment_type_supports_randomize($assessment)) {
            return false;
        }

        return (int) ($assessment->randomize_questions ?? 0) === 1;
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('course_phase3_module_types')) {
    /**
     * Canonical module content types (Phase 3).
     *
     * @return string[]
     */
    function course_phase3_module_types()
    {
        return ['pdf', 'slides', 'video', 'audio', 'meeting_link', 'zoom_recording'];
    }
}

if ( ! function_exists('course_phase3_normalize_module_type')) {
    /**
     * Normalize legacy aliases for storage/rendering.
     *
     * @param string $type
     * @return string
     */
    function course_phase3_normalize_module_type($type)
    {
        $type = strtolower(trim((string) $type));
        if ($type === 'zoom_recording') {
            return 'meeting_link';
        }

        return in_array($type, course_phase3_module_types(), true) ? $type : '';
    }
}

if ( ! function_exists('course_phase3_content_path_is_youtube_video')) {
    /**
     * Whether content_path points at YouTube or a direct video file (checkpoint / player contract).
     *
     * @param string $path
     * @return bool
     */
    function course_phase3_content_path_is_youtube_video($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return false;
        }

        if (preg_match('#(?:youtube\.com|youtu\.be)#i', $path)) {
            return true;
        }

        return (bool) preg_match('#\.(?:mp4|webm|m4v|mov|ogv)(?:\?|$)#i', $path);
    }
}

if ( ! function_exists('course_phase3_effective_module_type')) {
    /**
     * Single source of truth for module content_type resolution (storage, admin UI, learner view, JSON).
     * Applies course_phase3_normalize_module_type(); unknown non-empty raw values → "video" + one debug log.
     * When $content_path is provided, meeting_link + YouTube/file URL resolves to "video" (mis-tagged modules).
     *
     * @param mixed       $raw
     * @param string      $context      Optional context for debug log (e.g. "module view id=12")
     * @param string|null $content_path Optional module content_path for path-aware resolution
     * @return string Resolved type (meeting_link, video, pdf, …) suitable for branching and CT_DATA keys
     */
    function course_phase3_effective_module_type($raw, $context = '', $content_path = null)
    {
        $raw_s = strtolower(trim((string) $raw));
        $n     = course_phase3_normalize_module_type($raw_s);
        if ($n !== '') {
            if ($n === 'meeting_link' && $content_path !== null && course_phase3_content_path_is_youtube_video($content_path)) {
                $suffix = $context !== '' ? ' (' . $context . ')' : '';
                log_message('debug', 'Phase3 module type: meeting_link + playable path → video' . $suffix . '.');
                return 'video';
            }

            return $n;
        }

        if ($raw_s !== '') {
            $suffix = $context !== '' ? ' (' . $context . ')' : '';
            log_message('debug', 'Phase3 module type: unknown "' . $raw_s . '"; using video' . $suffix . '.');
        }

        return 'video';
    }
}

if ( ! function_exists('course_phase3_effective_module_type_for_row')) {
    /**
     * Resolve effective type from a course_modules row (object or array).
     *
     * @param object|array|null $module
     * @param string            $context
     * @return string
     */
    function course_phase3_effective_module_type_for_row($module, $context = '')
    {
        if ($module === null) {
            return 'video';
        }

        $raw  = '';
        $path = null;
        if (is_object($module)) {
            $raw  = (string) ($module->content_type ?? '');
            $path = isset($module->content_path) ? (string) $module->content_path : null;
        } elseif (is_array($module)) {
            $raw  = (string) ($module['content_type'] ?? '');
            $path = isset($module['content_path']) ? (string) $module['content_path'] : null;
        }

        return course_phase3_effective_module_type($raw, $context, $path);
    }
}

if ( ! function_exists('course_phase3_resolved_module_type_contract_values')) {
    /**
     * Distinct values of course_phase3_effective_module_type() over declared module types.
     * Used to align JS with PHP without duplicating normalization rules (e.g. zoom → meeting_link).
     *
     * @return string[]
     */
    function course_phase3_resolved_module_type_contract_values()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $seen = [];
        foreach (course_phase3_module_types() as $t) {
            $e = course_phase3_effective_module_type($t, '');
            $seen[$e] = true;
        }
        $cache = array_keys($seen);
        sort($cache, SORT_STRING);

        return $cache;
    }
}

if ( ! function_exists('course_phase3_module_type_label')) {
    /**
     * Human-readable label. Prefer passing a value already from course_phase3_effective_module_type().
     *
     * @param string $type
     * @return string
     */
    function course_phase3_module_type_label($type)
    {
        $labels = [
            'pdf'           => 'PDF Document',
            'slides'        => 'Slides',
            'video'         => 'Video',
            'audio'         => 'Audio',
            'meeting_link'  => 'Meeting Link',
            'zoom_recording'=> 'Meeting Link',
        ];
        $key = course_phase3_normalize_module_type($type);

        return $labels[$key] ?? ucfirst(str_replace('_', ' ', $type));
    }
}

if ( ! function_exists('course_phase3_module_is_meeting_link')) {
    /**
     * @param object|array|null $module
     * @return bool
     */
    function course_phase3_module_is_meeting_link($module)
    {
        if ($module === null) {
            return false;
        }

        $raw = '';
        if (is_object($module)) {
            $raw = (string) ($module->content_type ?? '');
        } elseif (is_array($module)) {
            $raw = (string) ($module['content_type'] ?? '');
        }

        return course_phase3_effective_module_type($raw, 'course_phase3_module_is_meeting_link') === 'meeting_link';
    }
}

if ( ! function_exists('course_phase3_course_expiry_date')) {
    /**
     * @param object|null $course Row with created_at, expiry_days
     * @return string|null Y-m-d or null
     */
    function course_phase3_course_expiry_date($course)
    {
        if ( ! $course || empty($course->expiry_days) || (int) $course->expiry_days < 1) {
            return null;
        }
        $created = strtotime((string) ($course->created_at ?? ''));
        if ( ! $created) {
            return null;
        }

        return date('Y-m-d', strtotime('+' . (int) $course->expiry_days . ' days', $created));
    }
}

if ( ! function_exists('course_phase3_course_is_expired')) {
    /**
     * @param object|null $course
     * @return bool
     */
    function course_phase3_course_is_expired($course)
    {
        $exp = course_phase3_course_expiry_date($course);
        if ($exp === null) {
            return false;
        }

        return $exp < date('Y-m-d');
    }
}

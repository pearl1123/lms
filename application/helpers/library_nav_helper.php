<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Build grouped library navigation from library_registry config.
 *
 * @return array<int, array{label: string, items: array<int, array{key: string, title: string, url: string, custom: bool}>}>
 */
function ka_library_nav_groups()
{
    $CI = &get_instance();
    $CI->load->config('library_registry');
    $registry = $CI->config->item('library_registry');
    $groups   = $registry['groups'] ?? [];
    $modules  = $registry['modules'] ?? [];

    $bucket = [];
    foreach ($modules as $key => $mod) {
        $group = $mod['group'] ?? 'system';
        if ( ! isset($bucket[$group])) {
            $bucket[$group] = [];
        }
        $bucket[$group][] = [
            'key'    => $key,
            'title'  => $mod['title'] ?? $key,
            'url'    => site_url($mod['controller'] ?? ('libraries/' . $key)),
            'custom' => ! empty($mod['custom']),
        ];
    }

    uasort($groups, static function ($a, $b) {
        return ($a['order'] ?? 99) <=> ($b['order'] ?? 99);
    });

    $out = [];
    foreach ($groups as $gid => $gdef) {
        if (empty($bucket[$gid])) {
            continue;
        }
        usort($bucket[$gid], static function ($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });
        $out[] = [
            'label' => $gdef['label'] ?? ucfirst($gid),
            'items' => $bucket[$gid],
        ];
    }

    return $out;
}

/**
 * @param string $seg2 Current URI segment 2 (library key)
 */
function ka_library_nav_is_active($seg2)
{
    return in_array($seg2, [
        'assessment_choices',
        'course_categories',
        'lib_course_access_type',
        'lib_course_modality',
        'lib_assessment_questions',
        'lib_certificate_types',
        'lib_certificate_templates',
        'lib_notification_type',
        'lib_notification_channel',
        'lms_settings',
    ], true) || strpos((string) $seg2, 'lib_') === 0;
}

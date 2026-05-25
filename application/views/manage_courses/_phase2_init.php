<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$phase2 = $course_phase2 ?? null;
$is_edit = ! empty($is_edit_form);
$categories = $categories ?? [];
$sel_categories   = isset($sel_categories) ? $sel_categories : (isset($edit_category_ids) ? (array) $edit_category_ids : ($phase2 ? (array) $phase2->category_ids : []));
$sel_instructors  = isset($sel_instructors) ? $sel_instructors : ($phase2 ? (array) $phase2->instructor_ids : []);
$sel_departments  = isset($sel_departments) ? $sel_departments : ($phase2 ? (array) $phase2->department_ids : []);
$sel_professions  = isset($sel_professions) ? $sel_professions : ($phase2 ? (array) $phase2->profession_ids : []);
$access_type      = isset($access_type) ? $access_type : ($phase2 ? (string) $phase2->access_type : 'approval_required');
$phase2_ready     = ! empty($phase2_schema_ready);
$hrmis_connection_ok = ! empty($hrmis_connection_ok);
$hrmis_ready      = ! empty($hrmis_ready);
$departments      = is_array($departments ?? null) ? $departments : [];
$professions      = is_array($professions ?? null) ? $professions : [];
$dept_select_enabled = $hrmis_connection_ok && ! empty($departments);
$invitations      = ($phase2 && isset($phase2->invitations)) ? $phase2->invitations : [];
$instructor_options = $instructor_options ?? ($teachers ?? []);
$invitable_users = $invitable_users ?? [];
$publish_status = isset($edit_publish_status)
    ? (string) $edit_publish_status
    : ($phase2 ? (string) ($phase2->publish_status ?? 'draft') : 'draft');

if ( ! function_exists('crs_p2_person_initials')) {
    function crs_p2_person_initials($name)
    {
        $parts = preg_split('/\s+/', trim((string) $name));
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
        }

        return strtoupper(substr((string) $name, 0, 2));
    }
}

if ( ! function_exists('crs_p2_avatar_hue')) {
    function crs_p2_avatar_hue($seed)
    {
        $colors = ['#1a3a5c', '#2563eb', '#7c3aed', '#db2777', '#059669', '#d97706', '#0891b2', '#4f46e5'];
        $n = abs(crc32((string) $seed));

        return $colors[$n % count($colors)];
    }
}

if ( ! function_exists('crs_p2_invite_status_meta')) {
    function crs_p2_invite_status_meta($status)
    {
        $key = strtolower(trim((string) $status));
        $map = [
            'accepted' => ['class' => 'accepted', 'label' => 'Accepted', 'icon' => '✓'],
            'pending'  => ['class' => 'pending',  'label' => 'Pending',  'icon' => '◷'],
            'rejected' => ['class' => 'rejected', 'label' => 'Declined', 'icon' => '✕'],
        ];

        return $map[$key] ?? ['class' => 'default', 'label' => ucfirst($key ?: 'Unknown'), 'icon' => '•'];
    }
}

$access_options = [
    'open' => [
        'dot'   => 'open',
        'title' => 'Open Enrollment',
        'desc'  => 'Eligible learners can enroll right away — no approval step.',
    ],
    'approval_required' => [
        'dot'   => 'approval',
        'title' => 'Approval Required',
        'desc'  => 'Enrollment requests go to instructors for review before access is granted.',
    ],
    'invitation_only' => [
        'dot'   => 'invite',
        'title' => 'Invitation Only',
        'desc'  => 'Only learners you invite can discover and join this course.',
    ],
    'hidden' => [
        'dot'   => 'hidden',
        'title' => 'Hidden',
        'desc'  => 'Completely hidden from the catalog. Direct links and invitations still work.',
    ],
];

$publish_pill_class = 'crs-p2-publish-pill--' . preg_replace('/[^a-z_]/', '', strtolower($publish_status));
$publish_label = function_exists('course_phase2_publish_label')
    ? course_phase2_publish_label($publish_status)
    : ucfirst($publish_status);

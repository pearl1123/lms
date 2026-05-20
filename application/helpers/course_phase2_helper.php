<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('course_phase2_access_types')) {
    /** @return string[] */
    function course_phase2_access_types()
    {
        return ['open', 'approval_required', 'invitation_only', 'hidden'];
    }
}

if ( ! function_exists('course_phase2_publish_statuses')) {
    /** @return string[] */
    function course_phase2_publish_statuses()
    {
        return ['draft', 'published', 'unpublished'];
    }
}

if ( ! function_exists('course_phase2_access_label')) {
    function course_phase2_access_label($key)
    {
        $map = [
            'open'               => 'Open — immediate enrollment',
            'approval_required'  => 'Approval required',
            'invitation_only'    => 'Invitation only',
            'hidden'             => 'Hidden from catalog',
        ];

        return $map[$key] ?? ucfirst(str_replace('_', ' ', (string) $key));
    }
}

if ( ! function_exists('course_phase2_publish_label')) {
    function course_phase2_publish_label($key)
    {
        $map = [
            'draft'       => 'Draft',
            'published'   => 'Published',
            'unpublished' => 'Unpublished',
        ];

        return $map[$key] ?? ucfirst((string) $key);
    }
}

if ( ! function_exists('course_phase2_normalize_ids')) {
    /**
     * @param mixed $raw POST array or comma string
     * @return int[]
     */
    function course_phase2_normalize_ids($raw)
    {
        if (is_string($raw)) {
            $raw = $raw === '' ? [] : explode(',', $raw);
        }
        if ( ! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $v) {
            $id = (int) $v;
            if ($id > 0) {
                $out[$id] = $id;
            }
        }

        return array_values($out);
    }
}

if ( ! function_exists('course_phase2_hrmis_position_key')) {
    /**
     * Stable unsigned key for HRMIS tblemployee.Position (stored in course_professions.profession_id).
     *
     * @param string $position
     * @return int
     */
    function course_phase2_hrmis_position_key($position)
    {
        $norm = strtoupper(trim((string) $position));
        if ($norm === '') {
            return 0;
        }

        return (int) sprintf('%u', crc32($norm));
    }
}

if ( ! function_exists('course_phase2_audit_insert_row')) {
    /**
     * Standard LMS audit fields for INSERT.
     *
     * @param int $actor_id
     * @return array<string, mixed>
     */
    function course_phase2_audit_insert_row($actor_id = 0)
    {
        $now = date('Y-m-d H:i:s');
        $uid = (int) $actor_id;

        return [
            'date_encoded'       => $now,
            'encoded_by'         => $uid > 0 ? $uid : null,
            'date_last_modified' => $now,
            'modified_by'        => $uid > 0 ? $uid : null,
            'archived'           => 0,
        ];
    }
}

if ( ! function_exists('course_phase2_audit_update_row')) {
    /**
     * Standard LMS audit fields for UPDATE.
     *
     * @param int $actor_id
     * @return array<string, mixed>
     */
    function course_phase2_audit_update_row($actor_id = 0)
    {
        $uid = (int) $actor_id;

        return [
            'date_last_modified' => date('Y-m-d H:i:s'),
            'modified_by'        => $uid > 0 ? $uid : null,
        ];
    }
}

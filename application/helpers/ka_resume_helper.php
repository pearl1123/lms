<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('ka_resume_storage_key')) {
    /**
     * Client-side localStorage key for resume payload.
     */
    function ka_resume_storage_key($user_id, $module_id)
    {
        return 'ka_resume_u' . (int) $user_id . '_m' . (int) $module_id;
    }
}

if ( ! function_exists('ka_resume_normalize_state')) {
    /**
     * Sanitize resume payload from DB/POST.
     *
     * @param mixed $raw
     * @return array{type:string,position:float,meta:array}
     */
    function ka_resume_normalize_state($raw)
    {
        $empty = ['type' => '', 'position' => 0.0, 'meta' => []];

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw     = is_array($decoded) ? $decoded : [];
        }
        if ( ! is_array($raw)) {
            return $empty;
        }

        $type = strtolower(trim((string) ($raw['type'] ?? '')));
        $pos  = isset($raw['position']) ? (float) $raw['position'] : 0.0;
        if ($pos < 0) {
            $pos = 0.0;
        }

        $meta = is_array($raw['meta'] ?? null) ? $raw['meta'] : [];

        return [
            'type'     => $type,
            'position' => $pos,
            'meta'     => $meta,
        ];
    }
}

if ( ! function_exists('ka_resume_query_from_state')) {
    /**
     * Build query string for deep-linking into module player.
     *
     * @param array $state ka_resume_normalize_state output
     * @return string e.g. t=92 or page=18
     */
    function ka_resume_query_from_state(array $state)
    {
        $type = (string) ($state['type'] ?? '');
        $pos  = (float) ($state['position'] ?? 0);
        if ($pos <= 0) {
            return '';
        }

        switch ($type) {
            case 'video':
            case 'audio':
                return 't=' . (int) floor($pos);
            case 'pdf':
                return 'page=' . max(1, (int) floor($pos));
            case 'slides':
                return 'slide=' . max(1, (int) floor($pos));
            case 'scroll':
                return 'scroll=' . (int) floor($pos);
            default:
                return '';
        }
    }
}

if ( ! function_exists('ka_resume_module_url')) {
    /**
     * Module player URL with optional resume query.
     *
     * @param int        $module_id
     * @param array|null $state
     * @param string     $return_q  Optional return_url query fragment (no leading ?)
     */
    function ka_resume_module_url($module_id, $state = null, $return_q = '')
    {
        $url = site_url('courses/module/' . (int) $module_id);
        $qs  = [];

        if (is_array($state)) {
            $rq = ka_resume_query_from_state($state);
            if ($rq !== '') {
                parse_str($rq, $qs);
            }
        }

        if ($return_q !== '') {
            parse_str(ltrim($return_q, '?&'), $rq_extra);
            if (is_array($rq_extra)) {
                $qs = array_merge($qs, $rq_extra);
            }
        }

        if ( ! empty($qs)) {
            $url .= '?' . http_build_query($qs);
        }

        return $url;
    }
}

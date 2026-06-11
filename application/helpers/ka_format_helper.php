<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('ka_format_date_short')) {
    /**
     * @param string|null $datetime
     * @return string e.g. Jan 5, 2026
     */
    function ka_format_date_short($datetime)
    {
        if ($datetime === null || $datetime === '') {
            return '';
        }
        $ts = strtotime($datetime);

        return $ts ? date('M j, Y', $ts) : '';
    }
}

if ( ! function_exists('ka_format_month_year')) {
    /**
     * @param string|null $datetime
     * @return string e.g. Jan 2026
     */
    function ka_format_month_year($datetime)
    {
        if ($datetime === null || $datetime === '') {
            return '';
        }
        $ts = strtotime($datetime);

        return $ts ? date('M Y', $ts) : '';
    }
}

if ( ! function_exists('ka_assessment_pass_threshold')) {
    /**
     * Pass/fail threshold (%) — assessment override → course override → settings → 75%.
     *
     * @param int|null $course_id
     * @param int|null $assessment_id
     * @return float
     */
    function ka_assessment_pass_threshold($course_id = null, $assessment_id = null)
    {
        static $default = null;
        $fallback       = 75.0;

        if ($default === null) {
            $default = $fallback;
            if (function_exists('get_instance')) {
                $CI =& get_instance();
                if (isset($CI->db)) {
                    $CI->load->model('Settings_model', 'settings_model');
                    if ($CI->settings_model->table_ready()) {
                        $all = $CI->settings_model->get_all_settings();
                        $learning = is_array($all['learning'] ?? null) ? $all['learning'] : [];
                        $v = isset($learning['default_pass_threshold']) ? (float) $learning['default_pass_threshold'] : 0.0;
                        if ($v > 0 && $v <= 100) {
                            $default = $v;
                        }
                    }
                }
            }
        }

        $threshold = (float) $default;

        if ($assessment_id !== null && (int) $assessment_id > 0 && function_exists('get_instance')) {
            $CI =& get_instance();
            if (isset($CI->db) && $CI->db->field_exists('pass_threshold_pct', 'lib_assessments')) {
                $row = $CI->db->select('pass_threshold_pct, module_id')->from('lib_assessments')
                    ->where('id', (int) $assessment_id)->get()->row();
                if ($row && $row->pass_threshold_pct !== null && (float) $row->pass_threshold_pct > 0) {
                    return (float) $row->pass_threshold_pct;
                }
                if ($row && $course_id === null && ! empty($row->module_id)) {
                    $mod = $CI->db->select('course_id')->from('course_modules')
                        ->where('id', (int) $row->module_id)->get()->row();
                    if ($mod) {
                        $course_id = (int) $mod->course_id;
                    }
                }
            }
        }

        if ($course_id !== null && (int) $course_id > 0 && function_exists('get_instance')) {
            $CI =& get_instance();
            if (isset($CI->db) && $CI->db->field_exists('pass_threshold_pct', 'courses')) {
                $row = $CI->db->select('pass_threshold_pct')->from('courses')
                    ->where('id', (int) $course_id)->get()->row();
                if ($row && $row->pass_threshold_pct !== null && (float) $row->pass_threshold_pct > 0) {
                    return (float) $row->pass_threshold_pct;
                }
            }
        }

        return $threshold;
    }
}

if ( ! function_exists('ka_assessment_score_chip')) {
    /**
     * Build score chip class + label for assessment UIs (controller use).
     *
     * @param float $score
     * @param int   $pending
     * @param float $pass_threshold defaults to ka_assessment_pass_threshold()
     * @return array{class:string,text:string}
     */
    function ka_assessment_score_chip($score, $pending, $pass_threshold = null)
    {
        if ($pass_threshold === null) {
            $pass_threshold = ka_assessment_pass_threshold();
        }

        $pending = (int) $pending;
        if ($pending > 0) {
            return [
                'class' => 'asx-score-pending',
                'text'  => $pending . ' answer(s) pending review',
            ];
        }

        $score = (float) $score;

        return [
            'class' => ($score >= $pass_threshold) ? 'asx-score-pass' : 'asx-score-fail',
            'text'  => number_format($score, 1) . '% score',
        ];
    }
}

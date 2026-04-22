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
     * Single pass/fail threshold (%) for assessments — use everywhere (posts, certificates, UI).
     *
     * @return float
     */
    function ka_assessment_pass_threshold()
    {
        return 75.0;
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

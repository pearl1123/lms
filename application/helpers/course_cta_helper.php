<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('get_course_cta')) {
    /**
     * Learner course CTA — delegates to {@see Course_completion_service::get_course_cta()}.
     *
     * @param int    $course_id
     * @param int    $user_id
     * @param string $return_q Query string without leading ? (e.g. return_url=my_courses)
     * @return array{label:string,url:string,status:string,progress_percent:int,outline_url:string}
     */
    function get_course_cta($course_id, $user_id, $return_q = '')
    {
        $CI = &get_instance();
        $CI->load->library('course_completion_service');

        return $CI->course_completion_service->get_course_cta((int) $course_id, (int) $user_id, (string) $return_q);
    }
}

if ( ! function_exists('ka_course_cta_css_class')) {
    /**
     * Map CTA status to existing card/button CSS suffix (no UI redesign).
     *
     * @param string $status not_started|in_progress|completed
     * @param string $context catalog|learning_card|detail|dashboard
     */
    function ka_course_cta_css_class($status, $context = 'catalog')
    {
        $status = strtolower(trim((string) $status));

        if ($context === 'learning_card') {
            if ($status === 'completed') {
                return 'ec-cta-review';
            }
            if ($status === 'in_progress') {
                return 'ec-cta-continue';
            }

            return 'ec-cta-start';
        }

        if ($context === 'detail') {
            if ($status === 'completed') {
                return 'cd-enroll-btn-cert';
            }
            if ($status === 'in_progress') {
                return 'cd-enroll-btn-continue';
            }

            return 'cd-enroll-btn-continue';
        }

        if ($context === 'dashboard') {
            return 'db-stu-btn db-stu-btn--primary db-stu-btn--sm';
        }

        if ($status === 'completed') {
            return 'cat-cta-review';
        }
        if ($status === 'in_progress') {
            return 'cat-cta-continue';
        }

        return 'cat-cta-continue';
    }
}

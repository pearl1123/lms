<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Standard message when HRMIS rejects registration. */
define('HRMIS_REGISTRATION_BLOCK_MESSAGE', 'Employee ID not found or inactive in HRMIS. Please contact HRMIS Administrator.');

if ( ! function_exists('normalize_employee_id')) {
    /**
     * Trim and uppercase employee ID (LCP######).
     */
    function normalize_employee_id($employee_id)
    {
        return strtoupper(trim((string) $employee_id));
    }
}

if ( ! function_exists('is_valid_lcp_employee_id')) {
    /**
     * Server-side format: LCP + exactly 6 digits.
     */
    function is_valid_lcp_employee_id($employee_id)
    {
        $id = normalize_employee_id($employee_id);

        return $id !== '' && (bool) preg_match('/^LCP[0-9]{6}$/', $id);
    }
}

if ( ! function_exists('hrmis_employee_fullname')) {
    /**
     * @param object $hr HRMIS tblemployee row
     */
    function hrmis_employee_fullname($hr)
    {
        return trim(implode(' ', array_filter([
            trim((string) ($hr->lname ?? '')),
            trim((string) ($hr->fname ?? '')),
            trim((string) ($hr->mid ?? '')),
        ])));
    }
}

if ( ! function_exists('hrmis_employee_display_name')) {
    /**
     * @param object $hr HRMIS tblemployee row
     */
    function hrmis_employee_display_name($hr)
    {
        $lname = trim((string) ($hr->lname ?? ''));
        $rest  = trim((string) ($hr->fname ?? '') . ' ' . (string) ($hr->mid ?? ''));

        return $lname !== '' ? ($lname . ', ' . $rest) : trim($rest);
    }
}

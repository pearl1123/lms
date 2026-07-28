<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Standard message when HRMIS rejects registration. */
define('HRMIS_REGISTRATION_BLOCK_MESSAGE', 'Employee ID not found or inactive in HRMIS. Please contact HRMIS Administrator.');

if ( ! function_exists('normalize_employee_id')) {
    /**
     * Trim and uppercase employee ID (LCP + digits).
     */
    function normalize_employee_id($employee_id)
    {
        return strtoupper(trim((string) $employee_id));
    }
}

if ( ! function_exists('is_valid_lcp_employee_id')) {
    /**
     * Server-side format: LCP + 3 to 7 digits (matches HRMIS idno lengths in use).
     * Examples: LCP156, LCP10492, LCP880201.
     */
    function is_valid_lcp_employee_id($employee_id)
    {
        $id = normalize_employee_id($employee_id);

        return $id !== '' && (bool) preg_match('/^LCP[0-9]{3,7}$/', $id);
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

if ( ! function_exists('hrmis_employee_certificate_name')) {
    /**
     * Certificate recipient line: FirstName M. LastName (HRMIS fname / mid / lname).
     *
     * @param object $hr HRMIS tblemployee row
     */
    function hrmis_employee_certificate_name($hr)
    {
        $fname = trim((string) ($hr->fname ?? ''));
        $mid   = trim((string) ($hr->mid ?? ''));
        $lname = trim((string) ($hr->lname ?? ''));

        if ($fname === '' && $lname === '') {
            return '';
        }

        $mi = ($mid !== '') ? strtoupper(substr($mid, 0, 1)) . '.' : '';

        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([$fname, $mi, $lname], static function ($part) {
            return $part !== '';
        }))));
    }
}

if ( ! function_exists('certificate_recipient_from_stored_name')) {
    /**
     * Fallback when HRMIS is unavailable — parses LMS fullname (often "Lastname, Firstname Mid").
     *
     * @param string $stored
     */
    function certificate_recipient_from_stored_name($stored)
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return '';
        }

        if (strpos($stored, ',') !== false) {
            $parts = array_map('trim', explode(',', $stored, 2));
            $lname = $parts[0] ?? '';
            $rest  = $parts[1] ?? '';
            $bits  = preg_split('/\s+/', $rest);
            $fname = array_shift($bits) ?: '';
            $mid   = implode(' ', $bits);
        } else {
            $bits  = preg_split('/\s+/', $stored);
            if (count($bits) <= 1) {
                return $stored;
            }
            $fname = array_shift($bits);
            $lname = array_pop($bits);
            $mid   = implode(' ', $bits);
        }

        $mi = ($mid !== '') ? strtoupper(substr($mid, 0, 1)) . '.' : '';

        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([$fname, $mi, $lname], static function ($part) {
            return $part !== '';
        }))));
    }
}

if ( ! function_exists('certificate_recipient_display_name_for_cert')) {
    /**
     * Certificate PDF / detail recipient: FirstName M. LastName (HRMIS preferred).
     *
     * @param object $cert Certificate row with student_name, employee_id
     */
    function certificate_recipient_display_name_for_cert($cert)
    {
        $stored = is_object($cert) ? (string) ($cert->student_name ?? '') : '';
        $name   = certificate_recipient_from_stored_name($stored);
        $emp_id = is_object($cert) ? trim((string) ($cert->employee_id ?? '')) : '';

        if ($emp_id === '' || ! function_exists('get_instance')) {
            return $name;
        }

        $CI =& get_instance();
        $CI->load->model('User_model', 'user_model');
        $hr = $CI->user_model->get_hrmis_employee($emp_id);
        if ( ! $hr) {
            return $name;
        }

        $from_hr = hrmis_employee_certificate_name($hr);

        return $from_hr !== '' ? $from_hr : $name;
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

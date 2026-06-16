<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Learning notes export — TXT/PDF helpers.
 */

if ( ! function_exists('ln_export_resolve_format')) {
    function ln_export_resolve_format($format)
    {
        $f = strtolower(trim((string) $format));

        return in_array($f, ['txt', 'pdf'], true) ? $f : 'txt';
    }
}

if ( ! function_exists('ln_export_filename_slug')) {
    function ln_export_filename_slug($title, $fallback = 'note')
    {
        $slug = preg_replace('/[^a-z0-9_-]+/i', '_', trim((string) $title));

        return ($slug !== '' ? substr($slug, 0, 40) : $fallback);
    }
}

if ( ! function_exists('ln_export_branding')) {
    /**
     * @return array{lms_name:string,logo_src:string,org_name:string}
     */
    function ln_export_branding()
    {
        $lms_name = 'kaBAGA Academy';
        $org_name = 'Lung Center of the Philippines';
        $logo_src = '';

        if ( ! function_exists('ka_cert_platform_settings')) {
            $helper = APPPATH . 'helpers/certificate_pdf_helper.php';
            if (is_file($helper)) {
                require_once $helper;
            }
        }

        if (function_exists('ka_cert_platform_settings')) {
            $settings = ka_cert_platform_settings();
            $lms_name = trim((string) ($settings['general']['lms_name'] ?? $lms_name));
            $org_name = trim((string) ($settings['general']['org_name'] ?? $org_name));
            if ($lms_name === '') {
                $lms_name = 'kaBAGA Academy';
            }
        }

        if (function_exists('ka_cert_embed_src')) {
            if (function_exists('ka_cert_platform_settings')) {
                $lp = trim((string) (ka_cert_platform_settings()['branding']['logo_path'] ?? ''));
                if ($lp !== '') {
                    $logo_src = ka_cert_embed_src($lp);
                }
            }
            if ($logo_src === '') {
                $logo_src = ka_cert_embed_src('assets/img/LMS-LOGO.png')
                    ?: ka_cert_embed_src('assets/img/LMS-LOGO2.png');
            }
        }

        return [
            'lms_name' => $lms_name,
            'logo_src' => $logo_src,
            'org_name' => $org_name,
        ];
    }
}

if ( ! function_exists('ln_export_notes_pdf')) {
    /**
     * Stream learning note(s) as PDF (DOMPDF).
     *
     * @param object[] $notes
     */
    function ln_export_notes_pdf(array $notes, $filename, $owner_name)
    {
        $CI = get_instance();
        $CI->load->library('pdf');

        $brand = ln_export_branding();

        $html = $CI->load->view('learning_notes/export_pdf', [
            'notes'         => $notes,
            'owner_name'    => trim((string) $owner_name) ?: 'Learner',
            'exported_at'   => date('F j, Y g:i A'),
            'lms_name'      => $brand['lms_name'],
            'logo_src'      => $brand['logo_src'],
            'org_name'      => $brand['org_name'],
            'is_collection' => count($notes) > 1,
        ], true);

        $CI->pdf->load_html($html)->set_paper('A4', 'portrait')->render();
        $CI->pdf->stream((string) $filename, false);
    }
}

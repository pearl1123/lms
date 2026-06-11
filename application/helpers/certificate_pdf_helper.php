<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Certificate PDF helpers — DOMPDF-safe assets, template resolution, view data.
 */

if ( ! function_exists('ka_cert_allowed_templates')) {
    /**
     * @return array<string,string> slug => label
     */
    function ka_cert_allowed_templates()
    {
        return [
            'official_lcp_certificate' => 'Official LCP (sample design)',
            'premium_lcp_certificate' => 'Premium LCP (navy + gold)',
            'template_pdf'           => 'Enterprise SaaS Credential',
            'template_saas_preview'  => 'Enterprise SaaS Credential',
            'minimalist'             => 'Minimalist',
            'modern'                 => 'Modern',
        ];
    }
}

if ( ! function_exists('ka_cert_platform_settings')) {
    /**
     * Load merged platform settings (defaults + DB).
     *
     * @return array<string,mixed>
     */
    function ka_cert_platform_settings()
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        if ( ! function_exists('get_instance')) {
            $cache = [];

            return $cache;
        }

        get_instance();

        if ( ! class_exists('Settings_model', false)) {
            require_once APPPATH . 'models/Settings_model.php';
        }

        $settings_model = new Settings_model();
        $cache = $settings_model->get_all_settings();

        return $cache;
    }
}

if ( ! function_exists('ka_cert_resolve_template')) {
    /**
     * Resolve which view template to render.
     *
     * @param  string|null $override Explicit template slug
     * @param  object|null $cert     Certificate row (reserved for per-course overrides)
     * @return string
     */
    function ka_cert_resolve_template($override = null, $cert = null)
    {
        $allowed = array_keys(ka_cert_allowed_templates());

        if ($override !== null && $override !== '' && in_array($override, $allowed, true)) {
            return ka_cert_template_exists($override) ? $override : 'official_lcp_certificate';
        }

        $tpl = 'template_pdf';

        if (function_exists('get_instance')) {
            $settings = ka_cert_platform_settings();
            $tpl = trim((string) ($settings['certificates']['pdf_template'] ?? 'template_pdf'));
        }

        if ( ! in_array($tpl, $allowed, true) || ! ka_cert_template_exists($tpl)) {
            return ka_cert_template_exists('template_pdf')
                ? 'template_pdf'
                : (ka_cert_template_exists('premium_lcp_certificate')
                    ? 'premium_lcp_certificate'
                    : 'official_lcp_certificate');
        }

        return $tpl;
    }
}

if ( ! function_exists('ka_cert_template_exists')) {
    function ka_cert_template_exists($slug)
    {
        if ($slug === 'template_pdf') {
            return is_file(APPPATH . 'views/certificates/template_pdf.php');
        }

        return is_file(APPPATH . 'views/certificates/templates/' . $slug . '.php');
    }
}

if ( ! function_exists('ka_cert_template_view')) {
    /**
     * CI view path for a certificate template slug.
     */
    function ka_cert_template_view($slug)
    {
        return $slug === 'template_pdf'
            ? 'certificates/template_pdf'
            : 'certificates/templates/' . $slug;
    }
}

if ( ! function_exists('ka_cert_template_css_file')) {
    /**
     * Stylesheet path for a template slug.
     */
    function ka_cert_template_css_file($slug)
    {
        $map = [
            'official_lcp_certificate' => 'assets/css/certificate_official.css',
            'premium_lcp_certificate' => 'assets/css/certificate_premium.css',
            'minimalist'                 => 'assets/css/certificate_minimalist.css',
            'modern'                     => 'assets/css/certificate_modern.css',
        ];

        $rel = $map[$slug] ?? $map['official_lcp_certificate'];

        return FCPATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
    }
}

if ( ! function_exists('ka_cert_inline_css')) {
    /**
     * Read template CSS for DOMPDF (external stylesheets are unreliable).
     */
    function ka_cert_inline_css($template_slug)
    {
        $file = ka_cert_template_css_file($template_slug);
        if ( ! is_file($file)) {
            return '';
        }

        return (string) file_get_contents($file);
    }
}

if ( ! function_exists('ka_cert_embed_src')) {
    /**
     * Base64 data URI for DOMPDF (file:// paths are unreliable on Windows).
     */
    function ka_cert_embed_src($relative_path)
    {
        $full = FCPATH . ltrim(str_replace(['../', '..\\'], '', (string) $relative_path), '/\\');
        if ( ! is_file($full)) {
            return '';
        }

        $ext  = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $mime = ($ext === 'png') ? 'image/png' : (($ext === 'gif') ? 'image/gif' : 'image/jpeg');

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($full));
    }
}

if ( ! function_exists('ka_cert_qr_data_uri')) {
    /**
     * Optional verification QR (fetched at generation time, embedded as data URI).
     */
    function ka_cert_qr_data_uri($url, $size = 120)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        $api = 'https://api.qrserver.com/v1/create-qr-code/?size='
            . (int) $size . 'x' . (int) $size
            . '&data=' . rawurlencode($url);

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 4,
                'user_agent' => 'KABAGA-LMS-Certificate/1.0',
            ],
        ]);

        $bin = @file_get_contents($api, false, $ctx);
        if ($bin === false || strlen($bin) < 64) {
            return '';
        }

        return 'data:image/png;base64,' . base64_encode($bin);
    }
}

if ( ! function_exists('ka_cert_name_size_class')) {
    function ka_cert_name_size_class($name, $base = 'cert-name', $threshold = 28)
    {
        $len = function_exists('mb_strlen') ? mb_strlen((string) $name) : strlen((string) $name);
        if ($len > 42) {
            return $base . ' ' . $base . '--xs';
        }
        if ($len > $threshold) {
            return $base . ' ' . $base . '--sm';
        }

        return $base;
    }
}

if ( ! function_exists('ka_cert_completion_body_html')) {
    /**
     * Narrative paragraph modeled on the official sample certificate.
     */
    function ka_cert_completion_body_html(array $d)
    {
        $course = trim((string) ($d['course_title'] ?? ''));
        $prefix = trim((string) ($d['certificate_prefix'] ?? ''));
        if ($prefix !== '') {
            $course = $prefix . ' ' . $course;
        }

        $parts = [];
        if ($course !== '') {
            $parts[] = 'for successfully completing the <strong class="cert-em">' . htmlspecialchars($course) . '</strong>';
        } else {
            $parts[] = 'for successfully completing the required training';
        }

        $facilitator = trim((string) ($d['facilitator'] ?? ''));
        if ($facilitator !== '') {
            $parts[] = 'facilitated by ' . htmlspecialchars($facilitator);
        }

        $modality = trim((string) ($d['modality_name'] ?? ''));
        if ($modality !== '') {
            $parts[] = 'via ' . htmlspecialchars($modality);
        }

        $hours = trim((string) ($d['training_hours'] ?? ''));
        if ($hours !== '' && is_numeric($hours)) {
            $parts[] = 'with ' . htmlspecialchars($hours) . ' training hour' . ((float) $hours === 1.0 ? '' : 's');
        }

        $issued = trim((string) ($d['issued_at'] ?? ''));
        if ($issued !== '') {
            $parts[] = 'on ' . htmlspecialchars($issued);
        }

        $venue = trim((string) ($d['venue'] ?? 'the Lung Center of the Philippines'));
        if ($venue === '') {
            $venue = 'the Lung Center of the Philippines';
        }
        if (stripos($venue, 'at ') !== 0) {
            $venue = 'at ' . $venue;
        }
        if (substr($venue, -1) !== '.') {
            $venue .= '.';
        }
        $parts[] = htmlspecialchars($venue);

        return implode(' ', $parts);
    }
}

if ( ! function_exists('ka_cert_normalize_signatories')) {
    /**
     * @param  object[] $signatories
     * @param  string   $fallback_name
     * @param  string   $fallback_title
     * @return object[]
     */
    function ka_cert_normalize_signatories(array $signatories, $fallback_name = '', $fallback_title = '')
    {
        if ( ! empty($signatories)) {
            return $signatories;
        }

        $name = trim((string) $fallback_name);
        if ($name === '') {
            return [];
        }

        return [(object) [
            'name'  => $name,
            'title' => trim((string) $fallback_title),
        ]];
    }
}

if ( ! function_exists('ka_cert_build_view_data')) {
    /**
     * Assemble all variables for certificate template views.
     *
     * @param  object      $cert
     * @param  object[]    $signatories
     * @param  string|null $template_override
     * @return array<string,mixed>
     */
    function ka_cert_build_view_data($cert, array $signatories = [], $template_override = null)
    {
        $template = ka_cert_resolve_template($template_override, $cert);

        $code = (string) ($cert->certificate_code ?? '');
        $verify_url = '';
        if ($code !== '') {
            if (function_exists('get_instance')) {
                $CI =& get_instance();
                $CI->load->helper('url');
            }
            if (function_exists('base_url')) {
                $verify_url = base_url('index.php/certificates/verify/' . $code);
            }
        }

        $issued_raw = $cert->issued_at ?? '';
        $issued_at  = $issued_raw !== '' ? date('F j, Y', strtotime($issued_raw)) : '';

        $signatories = ka_cert_normalize_signatories(
            $signatories,
            $cert->signatory_name ?? '',
            $cert->signatory_title ?? ''
        );

        $org_name = 'LUNG CENTER OF THE PHILIPPINES';
        if (function_exists('get_instance')) {
            $settings = ka_cert_platform_settings();
            $org_name = strtoupper(trim((string) (
                $settings['general']['organization_name'] ?? $org_name
            )));
            if ($org_name === '') {
                $org_name = 'LUNG CENTER OF THE PHILIPPINES';
            }
        }

        $student_name = (string) ($cert->student_name ?? '');

        $data = [
            'pdf_template'       => $template,
            'cert_css'           => ka_cert_inline_css($template),
            'certificate_id'     => (int) ($cert->id ?? 0),
            'certificate_code'   => (string) ($cert->certificate_code ?? ''),
            'student_name'       => $student_name,
            'employee_id'        => (string) ($cert->employee_id ?? ''),
            'course_title'       => (string) ($cert->course_title ?? ''),
            'course_description' => (string) ($cert->course_description ?? ''),
            'category_name'      => (string) ($cert->category_name ?? ''),
            'modality_name'      => function_exists('etd_modality_display_label')
                ? etd_modality_display_label((string) ($cert->modality_name ?? ''))
                : (string) ($cert->modality_name ?? ''),
            'certificate_prefix' => (string) ($cert->certificate_prefix ?? ''),
            'training_hours'     => (string) ($cert->training_hours ?? ''),
            'facilitator'        => (string) ($cert->facilitator ?? ''),
            'venue'              => (string) ($cert->venue ?? 'the Lung Center of the Philippines'),
            'issued_at'          => $issued_at,
            'org_name'           => $org_name,
            'signatories'        => $signatories,
            'signatory_name'     => (string) ($cert->signatory_name ?? ''),
            'signatory_title'    => (string) ($cert->signatory_title ?? ''),
            'verify_url'         => $verify_url,
            'qr_src'             => ka_cert_qr_data_uri($verify_url, 100),
            'name_class'         => ka_cert_name_size_class($student_name),
            'completion_body'    => '',
            'assets'             => [
                'logo'        => ka_cert_embed_src('assets/img/certificate/lcp_cert_logo.jpeg')
                    ?: ka_cert_embed_src('assets/img/LMS-LOGO.png'),
                'deco_top'    => ka_cert_embed_src('assets/img/certificate/lcp_cert_deco_top_sm.jpeg')
                    ?: ka_cert_embed_src('assets/img/certificate/lcp_cert_deco_top.jpeg'),
                'deco_bottom' => ka_cert_embed_src('assets/img/certificate/lcp_cert_deco_bottom_sm.jpeg')
                    ?: ka_cert_embed_src('assets/img/certificate/lcp_cert_deco_bottom.jpeg'),
            ],
        ];

        $data['completion_body'] = ka_cert_completion_body_html($data);
        $data['sig_count']       = count($signatories);
        $data['sig_width']       = $data['sig_count'] > 0
            ? (int) floor(100 / min($data['sig_count'], 4))
            : 50;

        $data['learner_name']      = $student_name;
        $data['cert_serial']       = $data['certificate_code'];
        $data['date_range']        = $issued_at;
        $data['css_path']          = ka_cert_template_css_file($template);
        $data['qr_image_base64'] = '';
        if ( ! empty($data['qr_src'])) {
            $data['qr_image_base64'] = preg_replace('#^data:image/[^;]+;base64,#', '', (string) $data['qr_src']);
        }

        return $data;
    }
}

if ( ! function_exists('ka_cert_sig_image_src')) {
    function ka_cert_sig_image_src($signature_image_path)
    {
        $rel = ltrim(str_replace(['../', '..\\'], '', (string) $signature_image_path), '/\\');
        if ($rel === '') {
            return '';
        }

        return ka_cert_embed_src($rel);
    }
}

if ( ! function_exists('ka_cert_is_debug_mode')) {
    /**
     * DEV-only certificate layout debug (?debug_certificate=1).
     */
    function ka_cert_is_debug_mode()
    {
        if ( ! defined('ENVIRONMENT') || ENVIRONMENT === 'production') {
            return false;
        }

        if ( ! function_exists('get_instance')) {
            return false;
        }

        $CI =& get_instance();

        return $CI->input->get('debug_certificate') === '1';
    }
}

if ( ! function_exists('ka_cert_render_template_html')) {
    /**
     * Render certificate template HTML (single source for preview + PDF).
     *
     * @param  object      $cert
     * @param  object[]    $signatories
     * @param  array<string,mixed> $opts  template, cert_debug, for_pdf
     * @return string
     */
    function ka_cert_render_template_html($cert, array $signatories = [], array $opts = [])
    {
        $template  = ka_cert_resolve_template($opts['template'] ?? null, $cert);
        $view_data = ka_cert_build_view_data($cert, $signatories, $template);

        $for_pdf = ! empty($opts['for_pdf']);
        if ( ! $for_pdf && ( ! empty($opts['cert_debug']) || ka_cert_is_debug_mode())) {
            $view_data['cert_debug'] = true;
        }

        if ( ! function_exists('get_instance')) {
            ob_start();
            extract($view_data, EXTR_SKIP);
            include APPPATH . 'views/' . ka_cert_template_view($template) . '.php';

            return (string) ob_get_clean();
        }

        $CI =& get_instance();

        return $CI->load->view(ka_cert_template_view($template), $view_data, true);
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Certificate PDF generation and issuance helpers (DOMPDF).
 *
 * Loaded via {@see $this->load->library('certificate_service')}.
 *
 * @property Certificate_model $certificate_model
 * @property Course_completion_service $course_completion_service
 * @property Settings_model $settings_model
 */
class Certificate_service {

    public const PDF_DIR = 'uploads/certificates/';

    public const DEFAULT_TEMPLATE = 'premium_lcp_certificate';

    /**
     * @var \CI_Controller&object{
     *     certificate_model: \Certificate_model,
     *     course_completion_service: \Course_completion_service,
     *     settings_model: \Settings_model
     * }
     */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Certificate_model', 'certificate_model');
        $this->CI->load->model('Settings_model', 'settings_model');
        $this->CI->load->library('course_completion_service');
        $this->CI->load->helper('certificate_pdf');
    }

    /**
     * Ensure a certificate row exists for a completed course, generate PDF once, return full row or null.
     *
     * @param  int $user_id
     * @param  int $course_id
     * @return object|null  Row from {@see Certificate_model::get_by_id()}
     */
    public function generate($user_id, $course_id)
    {
        $user_id   = (int) $user_id;
        $course_id = (int) $course_id;
        $m         = $this->CI->certificate_model;

        $state = $this->CI->course_completion_service->evaluate_user_course_state($user_id, $course_id);
        if (empty($state['is_certificate_eligible'])) {
            log_message('debug', 'Certificate gate blocked by course completion service user=' . $user_id . ' course=' . $course_id);

            return null;
        }

        $existing = $m->get_by_user_course($user_id, $course_id);
        if ($existing) {
            return $this->_refresh_row_with_pdf((int) $existing->id);
        }

        $issued = $m->issue($user_id, $course_id, 0);
        if ( ! $issued['success']) {
            if (($issued['reason'] ?? '') === 'already_issued') {
                $row = $m->get_by_user_course($user_id, $course_id);
                if ($row) {
                    return $this->_refresh_row_with_pdf((int) $row->id);
                }
            }

            return null;
        }

        return $this->_refresh_row_with_pdf((int) $issued['certificate_id']);
    }

    /**
     * Render / cache PDF for a certificate row; returns relative path or null.
     *
     * @param  object $cert             Row from {@see Certificate_model::get_by_id()}
     * @param  bool   $force_regenerate Delete cached PDF and rebuild
     * @param  string|null $template    Override template slug
     * @return string|null Relative path under FCPATH
     */
    public function generate_pdf_for_row($cert, $force_regenerate = false, $template = null)
    {
        if (empty($cert->id)) {
            return null;
        }

        if ($force_regenerate && ! empty($cert->file_path)) {
            $this->_delete_cached_pdf($cert->file_path);
            $cert->file_path = null;
        }

        if ( ! $force_regenerate && ! empty($cert->file_path)) {
            $full = FCPATH . $cert->file_path;
            if (is_file($full) && filesize($full) > 0) {
                return $cert->file_path;
            }
        }

        $pdf_dir = FCPATH . self::PDF_DIR;
        if ( ! is_dir($pdf_dir)) {
            if ( ! @mkdir($pdf_dir, 0755, true) && ! is_dir($pdf_dir)) {
                log_message('error', 'Certificate PDF directory is not writable: ' . $pdf_dir);

                return null;
            }
        }

        try {
            if (ob_get_length()) {
                @ob_end_clean();
            }

            $m = $this->CI->certificate_model;

            if ( ! $this->_load_dompdf()) {
                return null;
            }

            $signatories = $m->resolve_signatories_for_pdf(
                (int) ($cert->course_id ?? 0),
                $cert->signatory_name ?? '',
                $cert->signatory_title ?? ''
            );

            $template  = ka_cert_resolve_template($template, $cert);
            $html      = ka_cert_render_template_html($cert, $signatories, [
                'template' => $template,
                'for_pdf'  => true,
            ]);

            if (trim($html) === '') {
                log_message('error', 'Certificate PDF template rendered empty HTML template=' . $template);

                return null;
            }

            $options = new Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('chroot', FCPATH);
            $options->set('dpi', 96);

            $dompdf = new Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $filename  = 'cert_' . preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $cert->certificate_code) . '.pdf';
            $full_path = $pdf_dir . $filename;
            $lock_path = $full_path . '.lock';
            $lock_fp   = @fopen($lock_path, 'c+');

            if ($lock_fp && ! flock($lock_fp, LOCK_EX)) {
                fclose($lock_fp);
                log_message('error', 'Certificate PDF lock failed: ' . $lock_path);

                return null;
            }

            if ( ! $force_regenerate && is_file($full_path) && filesize($full_path) > 0) {
                if ($lock_fp) {
                    flock($lock_fp, LOCK_UN);
                    fclose($lock_fp);
                }

                return self::PDF_DIR . $filename;
            }

            $written = file_put_contents($full_path, $dompdf->output());

            if ($lock_fp) {
                flock($lock_fp, LOCK_UN);
                fclose($lock_fp);
            }

            if ($written === false || ! is_file($full_path) || filesize($full_path) <= 0) {
                log_message('error', 'DOMPDF: file not written to ' . $full_path);

                return null;
            }

            return self::PDF_DIR . $filename;

        } catch (Throwable $e) {
            log_message('error', 'Certificate DOMPDF error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Force-regenerate PDF for a certificate row and persist path.
     *
     * @param  object $cert
     * @return string|null
     */
    public function regenerate_pdf_for_row($cert)
    {
        if (empty($cert->id)) {
            return null;
        }

        if ( ! empty($cert->file_path)) {
            $this->_delete_cached_pdf($cert->file_path);
        }

        $path = $this->generate_pdf_for_row($cert, true);
        if ($path) {
            $this->CI->certificate_model->save_file_path((int) $cert->id, $path);
        }

        return $path;
    }

    /**
     * @param  int $certificate_id
     * @return object|null
     */
    private function _refresh_row_with_pdf($certificate_id)
    {
        $m    = $this->CI->certificate_model;
        $cert = $m->get_by_id($certificate_id);
        if ( ! $cert) {
            return null;
        }

        $path = $this->generate_pdf_for_row($cert);
        if ($path) {
            $m->save_file_path($certificate_id, $path);
        }

        return $m->get_by_id($certificate_id);
    }

    private function _load_dompdf()
    {
        $autoload = FCPATH . 'vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;

            return true;
        }

        $manual = APPPATH . 'third_party/dompdf/autoload.inc.php';
        if (is_file($manual)) {
            require_once $manual;

            return true;
        }

        log_message('error', 'DOMPDF not found. Run: composer require dompdf/dompdf');

        return false;
    }

    private function _delete_cached_pdf($relative_path)
    {
        $full = FCPATH . ltrim(str_replace(['../', '..\\'], '', (string) $relative_path), '/\\');
        if (is_file($full)) {
            @unlink($full);
        }
    }

}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Certificate PDF generation and issuance helpers (DOMPDF).
 *
 * Loaded via {@see $this->load->library('certificate_service')}.
 *
 * @property Certificate_model $certificate_model
 */
class Certificate_service {

    public const PDF_DIR = 'uploads/certificates/';

    /** @var \CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Certificate_model', 'certificate_model');
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
     * @param  object $cert Row from {@see Certificate_model::get_by_id()}
     * @return string|null Relative path under FCPATH
     */
    public function generate_pdf_for_row($cert)
    {
        if (empty($cert->id)) {
            return null;
        }

        if ( ! empty($cert->file_path)) {
            $full = FCPATH . $cert->file_path;
            if (is_file($full) && filesize($full) > 0) {
                return $cert->file_path;
            }
        }

        $pdf_dir = FCPATH . self::PDF_DIR;
        if ( ! is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0755, true);
        }

        try {
            $autoload = FCPATH . 'vendor/autoload.php';
            if (is_file($autoload)) {
                require_once $autoload;
            } else {
                $manual = APPPATH . 'third_party/dompdf/autoload.inc.php';
                if ( ! is_file($manual)) {
                    log_message('error', 'DOMPDF not found. Run: composer require dompdf/dompdf');

                    return null;
                }
                require_once $manual;
            }

            $html = $this->CI->load->view('certificates/template_pdf', [
                'certificate_code' => $cert->certificate_code,
                'student_name'       => $cert->student_name,
                'employee_id'        => $cert->employee_id      ?? '',
                'course_title'       => $cert->course_title,
                'category_name'      => $cert->category_name    ?? '',
                'modality_name'      => $cert->modality_name    ?? '',
                'certificate_prefix' => $cert->certificate_prefix ?? '',
                'signatory_name'     => $cert->signatory_name   ?? '',
                'signatory_title'    => $cert->signatory_title  ?? '',
                'issued_at'          => date('F j, Y', strtotime($cert->issued_at)),
                'verify_url'         => base_url('index.php/certificates/verify/'
                                         . $cert->certificate_code),
            ], true);

            $options = new Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('chroot', FCPATH);

            $dompdf = new Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $filename  = 'cert_' . $cert->certificate_code . '.pdf';
            $full_path = $pdf_dir . $filename;

            file_put_contents($full_path, $dompdf->output());

            if (is_file($full_path) && filesize($full_path) > 0) {
                return self::PDF_DIR . $filename;
            }

            log_message('error', 'DOMPDF: file not written to ' . $full_path);

            return null;

        } catch (Exception $e) {
            log_message('error', 'Certificate DOMPDF error: ' . $e->getMessage());

            return null;
        }
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

}

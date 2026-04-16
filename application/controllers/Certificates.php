<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Certificates Controller
 *
 * Routes:
 *   GET  index.php/certificates                  → List (employee: mine / admin+teacher: all)
 *   GET  index.php/certificates/view/{id}        → Certificate detail + audit log
 *   GET  index.php/certificates/download/{id}    → Stream PDF to browser
 *   GET  index.php/certificates/verify/{code}    → Public verification page
 *   POST index.php/certificates/check/{course_id}→ Auto-issue if eligible
 *   POST index.php/certificates/revoke/{id}      → Admin/teacher revoke
 *
 * @property Certificate_model  $certificate_model
 * @property Course_model       $course_model
 * @property Notification_model $notification_model
 */
class Certificates extends KA_Controller {

    const PDF_DIR = 'uploads/certificates/';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Certificate_model', 'certificate_model');
        $this->load->model('Course_model',      'course_model');
        $this->load->model('Notification_model','notification_model');

        // Ensure PDF upload directory exists
        $pdf_dir = FCPATH . self::PDF_DIR;
        if ( ! is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0755, true);
        }
    }

    // =========================================================
    // index() — Certificate list
    // =========================================================
    public function index()
    {
        $user = $this->auth_user;

        switch ($user->role) {
            case 'admin':
                $certificates = $this->certificate_model->get_all_certificates([
                    'keyword'   => trim($this->input->get('q')      ?? ''),
                    'course_id' => (int) ($this->input->get('course') ?? 0),
                ]);
                $courses = $this->course_model->get_all_courses();
                break;

            case 'teacher':
                $certificates = $this->certificate_model
                    ->get_certificates_by_instructor($user->id);
                $courses = $this->course_model->get_courses_by_instructor($user->id);
                break;

            default: // employee
                $certificates = $this->certificate_model
                    ->get_user_certificates($user->id);
                $courses = [];
                break;
        }

        $is_manager = in_array($user->role, ['admin', 'teacher'], true);
        $is_admin   = ($user->role === 'admin');

        $certificate_stats = $this->_build_certificate_stats($certificates, $is_manager);

        foreach ($certificates as $cert) {
            $cert->issued_at_display = ka_format_date_short($cert->issued_at ?? null);
            if ($is_manager) {
                $cert->student_initials = ka_user_initials($cert->student_name ?? '');
            }
        }

        $this->render('certificates/index', [
            'page_title'        => 'Certificates',
            'certificates'      => $certificates,
            'courses'           => $courses,
            'is_manager'        => $is_manager,
            'is_admin'          => $is_admin,
            'certificate_stats' => $certificate_stats,
        ], [
            ['label' => 'Dashboard',    'url' => 'dashboard'],
            ['label' => 'Certificates'],
        ]);
    }

    /**
     * @param object[] $certificates
     * @param bool     $is_manager
     * @return array
     */
    private function _build_certificate_stats(array $certificates, $is_manager)
    {
        $total = count($certificates);
        $stats = [
            'total'                 => $total,
            'primary_label'         => $is_manager ? 'Total Issued' : 'Certificates Earned',
            'unique_courses'        => 0,
            'unique_recipients'     => 0,
            'ready_download'        => 0,
            'latest_earned_label'   => '—',
        ];

        if ($is_manager) {
            $course_ids = [];
            $names      = [];
            foreach ($certificates as $c) {
                if (isset($c->course_id)) {
                    $course_ids[(string) $c->course_id] = true;
                }
                $n = trim((string) ($c->student_name ?? ''));
                if ($n !== '') {
                    $names[$n] = true;
                }
            }
            $stats['unique_courses']    = count($course_ids);
            $stats['unique_recipients'] = count($names);
        } else {
            $latest_ts = null;
            foreach ($certificates as $c) {
                if ( ! empty($c->file_path)) {
                    $stats['ready_download']++;
                }
                if ( ! empty($c->issued_at)) {
                    $t = strtotime($c->issued_at);
                    if ($t && ($latest_ts === null || $t > $latest_ts)) {
                        $latest_ts = $t;
                    }
                }
            }
            if ($latest_ts !== null) {
                $stats['latest_earned_label'] = date('M Y', $latest_ts);
            }
        }

        return $stats;
    }

    // =========================================================
    // view($id) — Certificate detail page
    // =========================================================
    public function view($id = null)
    {
        if ( ! $id) redirect('certificates');
        $id = (int) $id;

        $cert = $this->certificate_model->get_by_id($id);
        if ( ! $cert) show_404();

        // Employees can only view their own certificates
        if ($this->auth_user->role === 'employee'
            && (int) $cert->user_id !== (int) $this->auth_user->id) {
            show_404();
        }

        // Log "printed" action once per session
        $viewed_key = 'cert_viewed_' . $id;
        if ($this->auth_user->role === 'employee'
            && ! $this->session->userdata($viewed_key)) {
            $this->_log_print($id);
            $this->session->set_userdata($viewed_key, true);
        }

        $this->render('certificates/view', [
            'page_title' => 'Certificate — ' . $cert->course_title,
            'cert'       => $cert,
            'logs'       => $this->certificate_model->get_logs($id),
        ], [
            ['label' => 'Dashboard',    'url' => 'dashboard'],
            ['label' => 'Certificates', 'url' => 'certificates'],
            ['label' => $cert->course_title],
        ]);
    }

    // =========================================================
    // download($id) — Stream PDF to browser
    // =========================================================
    public function download($id = null)
    {
        if ( ! $id) redirect('certificates');
        $id = (int) $id;

        $cert = $this->certificate_model->get_by_id($id);
        if ( ! $cert) show_404();

        if ($this->auth_user->role === 'employee'
            && (int) $cert->user_id !== (int) $this->auth_user->id) {
            show_404();
        }

        // Generate PDF if missing
        if (empty($cert->file_path) || ! file_exists(FCPATH . $cert->file_path)) {
            $file_path = $this->_generate_pdf($cert);
            if ($file_path) {
                $this->certificate_model->save_file_path($id, $file_path);
                $cert->file_path = $file_path;
            }
        }

        if (empty($cert->file_path) || ! file_exists(FCPATH . $cert->file_path)) {
            $this->flash('error', 'Certificate PDF could not be generated. Please try again.');
            redirect('certificates/view/' . $id);
        }

        $this->_log_print($id);

        $full_path = FCPATH . $cert->file_path;
        $filename  = 'Certificate_' . $cert->certificate_code . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($full_path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($full_path);
        exit;
    }

    // =========================================================
    // verify($code) — Public verification (requires login)
    // =========================================================
    public function verify($code = null)
    {
        $cert = null;
        if ($code) {
            $cert = $this->certificate_model
                ->get_by_code(strtoupper(trim($code)));
        }

        $this->render('certificates/verify', [
            'page_title' => 'Certificate Verification',
            'cert'       => $cert,
            'code'       => $code,
        ], [
            ['label' => 'Dashboard',    'url' => 'dashboard'],
            ['label' => 'Certificates', 'url' => 'certificates'],
            ['label' => 'Verify'],
        ]);
    }

    // =========================================================
    // check($course_id) — Auto-issue if eligible (employee only)
    // Called from course detail page after 100% completion
    // =========================================================
    public function check($course_id = null)
    {
        if ( ! $course_id || $this->auth_user->role !== 'employee') {
            redirect('certificates');
        }

        $course_id = (int) $course_id;
        $user      = $this->auth_user;

        $result = $this->certificate_model->check_eligibility($user->id, $course_id);

        if ($result['eligible']) {
            $issued = $this->certificate_model->issue($user->id, $course_id, 0);

            if ($issued['success']) {
                $cert = $this->certificate_model->get_by_id($issued['certificate_id']);

                // Generate PDF immediately
                $file_path = $this->_generate_pdf($cert);
                if ($file_path) {
                    $this->certificate_model->save_file_path(
                        $issued['certificate_id'], $file_path
                    );
                }

                // Notify the employee
                $course = $this->course_model->get_course($course_id);
                $this->notification_model->send([
                    'type_id'      => 1,
                    'title'        => 'Certificate Issued!',
                    'message'      => 'Congratulations! Your certificate for <strong>'
                                   . htmlspecialchars($course->title ?? '')
                                   . '</strong> has been issued. Certificate code: <strong>'
                                   . $issued['code'] . '</strong>.',
                    'reference_id' => $issued['certificate_id'],
                    'user_ids'     => [$user->id],
                    'encoded_by'   => 0,
                ]);

                $this->flash('success',
                    'Congratulations! Your certificate has been issued. '
                    . 'Certificate code: <strong>' . $issued['code'] . '</strong>'
                );
                redirect('certificates/view/' . $issued['certificate_id']);
            }
        }

        // Not eligible — show specific reason
        $messages = [
            'already_issued'          => 'You already have a certificate for this course.',
            'modules_incomplete'      => 'You need to complete all modules before receiving your certificate.',
            'post_assessment_missing' => 'You need to complete all post-assessments before receiving your certificate.',
            'post_assessment_failed'  => 'You need to score at least 75% on all post-assessments to earn your certificate. Please retake the assessment.',
            'post_assessment_pending' => 'Your essay answers are still being reviewed by your instructor. Check back after they have been graded.',
            'no_modules'              => 'This course has no modules.',
        ];

        $this->flash('info',
            $messages[$result['reason']] ?? 'Certificate requirements not yet met.'
        );
        redirect('courses/view/' . $course_id);
    }

    // =========================================================
    // revoke($id) — Admin/teacher revoke a certificate
    // =========================================================
    public function revoke($id = null)
    {
        if ( ! $id) redirect('certificates');

        $this->require_manager();

        $id   = (int) $id;
        $cert = $this->certificate_model->get_by_id($id);
        if ( ! $cert) show_404();

        $remarks = trim($this->input->post('remarks') ?? '');
        $ok      = $this->certificate_model->revoke($id, $this->auth_user->id, $remarks);

        if ($ok) {
            $this->notification_model->send([
                'type_id'      => 2,
                'title'        => 'Certificate Revoked',
                'message'      => 'Your certificate for <strong>'
                               . htmlspecialchars($cert->course_title)
                               . '</strong> (code: ' . $cert->certificate_code
                               . ') has been revoked.'
                               . ($remarks ? ' Reason: ' . htmlspecialchars($remarks) : ''),
                'reference_id' => $id,
                'user_ids'     => [$cert->user_id],
                'encoded_by'   => $this->auth_user->id,
            ]);
            $this->flash('success', 'Certificate revoked and student notified.');
        } else {
            $this->flash('error', 'Failed to revoke certificate.');
        }

        redirect('certificates');
    }

    // =========================================================
    // PRIVATE — PDF Generation (DOMPDF)
    // =========================================================

    /**
     * Generate a branded KABAGA Academy certificate PDF via DOMPDF.
     *
     * Install DOMPDF:
     *   Option A (Composer):    composer require dompdf/dompdf
     *   Option B (manual):      place dompdf in application/third_party/dompdf/
     *
     * @param  object      $cert  Row from get_by_id() with student_name, course_title etc.
     * @return string|null        Relative file path on success, null on failure
     */
    private function _generate_pdf($cert)
    {
        try {
            $autoload = FCPATH . 'vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            } else {
                $manual = APPPATH . 'third_party/dompdf/autoload.inc.php';
                if ( ! file_exists($manual)) {
                    log_message('error', 'DOMPDF not found. Run: composer require dompdf/dompdf');
                    return null;
                }
                require_once $manual;
            }

            $html = $this->load->view('certificates/template_pdf', [
                'certificate_code' => $cert->certificate_code,
                'student_name'     => $cert->student_name,
                'employee_id'      => $cert->employee_id      ?? '',
                'course_title'     => $cert->course_title,
                'category_name'    => $cert->category_name    ?? '',
                'modality_name'    => $cert->modality_name    ?? '',
                'issued_at'        => date('F j, Y', strtotime($cert->issued_at)),
                'verify_url'       => base_url('index.php/certificates/verify/'
                                     . $cert->certificate_code),
            ], true);

            $options = new Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled',      false);
            $options->set('defaultFont',          'DejaVu Sans');
            $options->set('chroot',               FCPATH);

            $dompdf = new Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $filename  = 'cert_' . $cert->certificate_code . '.pdf';
            $full_path = FCPATH . self::PDF_DIR . $filename;

            file_put_contents($full_path, $dompdf->output());

            if (file_exists($full_path) && filesize($full_path) > 0) {
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
     * Log a "printed" action for a certificate.
     */
    private function _log_print($certificate_id)
    {
        $this->certificate_model->log_action(
            $certificate_id,
            'printed',
            $this->auth_user->id,
            'Viewed/downloaded by ' . $this->auth_user->fullname
        );
    }
}
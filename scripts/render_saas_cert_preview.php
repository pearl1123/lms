<?php
/**
 * Render SaaS certificate preview HTML + PDF (does not touch production template_pdf.php).
 * Usage: php scripts/render_saas_cert_preview.php [cert_id]
 */
define('BASEPATH', true);
define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APPPATH', FCPATH . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'helpers/report_export_helper.php';
require APPPATH . 'helpers/certificate_pdf_helper.php';

putenv('COMPOSER_DISABLE_PLATFORM_CHECK=1');
if ( ! ka_load_vendor_autoload()) {
    echo "DOMPDF not available\n";
    exit(1);
}

if ( ! function_exists('base_url')) {
    function base_url($uri = '') {
        return 'http://localhost/lms/index.php/' . ltrim($uri, '/');
    }
}

$cert = (object) [
    'id'               => 11,
    'certificate_code' => 'KBGA-20260416-A1B2C3',
    'student_name'     => 'Juan Dela Cruz',
    'employee_id'      => 'LCP-2024-001',
    'course_title'     => 'Infection Control and Prevention',
    'category_name'    => 'Clinical Training',
    'modality_name'    => 'Asynchronous',
    'training_hours'   => '8',
    'issued_at'        => '2026-04-16 10:00:00',
    'course_id'        => 1,
    'signatory_name'   => '',
    'signatory_title'  => '',
];

$signatories = [
    (object) ['name' => 'Maria Santos', 'title' => "Training Officer\nkaBAGA Academy"],
    (object) ['name' => 'Dr. Ana Reyes', 'title' => "Program Director\nLung Center of the Philippines"],
];

$html = ka_cert_render_template_html($cert, $signatories, [
    'template' => 'template_saas_preview',
    'for_pdf'  => true,
]);

$out_dir = FCPATH . 'uploads/certificates/';
@mkdir($out_dir, 0755, true);

$html_path = $out_dir . '_saas_preview.html';
$pdf_path  = $out_dir . '_saas_preview.pdf';
file_put_contents($html_path, $html);

$tempDir = report_export_dompdf_temp_dir();
$options = new Dompdf\Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('chroot', FCPATH);
$options->set('tempDir', $tempDir);

$dompdf = new Dompdf\Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
file_put_contents($pdf_path, $dompdf->output());

echo "HTML: {$html_path}\n";
echo "PDF:  {$pdf_path} (" . filesize($pdf_path) . " bytes)\n";
echo "Browser preview: http://localhost/lms/index.php/certificates/preview_saas/11\n";

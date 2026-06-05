<?php
/**
 * QA: render premium_lcp_certificate via DOMPDF (no CI bootstrap).
 * Usage: php scripts/qa_cert_premium.php
 */
define('BASEPATH', true);
define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APPPATH', FCPATH . 'application' . DIRECTORY_SEPARATOR);

require FCPATH . 'vendor/autoload.php';
require APPPATH . 'helpers/certificate_pdf_helper.php';

if ( ! function_exists('base_url')) {
    function base_url($uri = '')
    {
        return 'http://localhost/lms/index.php/' . ltrim($uri, '/');
    }
}

$cert = (object) [
    'id'                 => 99,
    'certificate_code'   => 'LCP-2026-PREMIUM-QA',
    'student_name'       => 'Maria Clara Santos',
    'employee_id'        => 'LCP880201',
    'course_title'       => 'Supervisory Development Course — Phase IV',
    'course_description' => '',
    'category_name'      => 'Leadership',
    'modality_name'      => 'Blended Learning',
    'certificate_prefix' => '',
    'signatory_name'     => 'Dr. Gloanne C. Adolor',
    'signatory_title'    => 'Department Manager III',
    'issued_at'          => '2026-06-01 14:00:00',
    'facilitator'        => 'Education and Training Department',
    'venue'              => 'EMG Auditorium, Lung Center of the Philippines',
    'training_hours'     => '24',
    'course_id'          => 1,
];

$signatories = [
    (object) ['name' => 'Dr. Gloanne C. Adolor', 'title' => "Department Manager III\nEducation and Training Department"],
    (object) ['name' => 'Dr. Jubert P. Benedicto', 'title' => "OIC, Executive Director\nLung Center of the Philippines"],
];

$html = ka_cert_render_template_html($cert, $signatories, [
    'template' => 'premium_lcp_certificate',
    'for_pdf'  => true,
]);

$options = new Dompdf\Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('chroot', FCPATH);
$options->set('dpi', 96);

$dompdf = new Dompdf\Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$out = FCPATH . 'uploads/certificates/_qa_premium_saas.pdf';
@mkdir(dirname($out), 0755, true);
file_put_contents($out, $dompdf->output());
file_put_contents(FCPATH . 'uploads/certificates/_qa_premium_saas.html', $html);

$bytes = filesize($out);
echo $bytes > 1000 ? "PASS: PDF written ({$bytes} bytes)\n{$out}\n" : "FAIL: PDF too small\n";
echo 'HTML: ' . strlen($html) . " bytes\n";
exit($bytes > 1000 ? 0 : 1);

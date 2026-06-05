<?php
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
    'id'                 => 1,
    'certificate_code'   => 'LCP-2026-0001',
    'student_name'       => 'Mr. Dennis M. Soriano',
    'course_title'       => 'Supervisory Development Course',
    'course_description' => '',
    'category_name'      => '',
    'modality_name'      => 'Blended Learning',
    'certificate_prefix' => '',
    'signatory_name'     => '',
    'signatory_title'    => '',
    'issued_at'          => '2026-04-29 10:00:00',
    'facilitator'        => 'the Civil Service Commission - National Capital Region',
    'venue'              => 'EMG Auditorium, Lung Center of the Philippines',
    'course_id'          => 1,
];

$signatories = [
    (object) ['name' => 'Dr. Gloanne C. Adolor', 'title' => "Department Manager III\nEducation and Training Department"],
    (object) ['name' => 'Dr. Jubert P. Benedicto', 'title' => "OIC, Executive Director\nLung Center of the Philippines"],
];

$view_data = ka_cert_build_view_data($cert, $signatories, 'official_lcp_certificate');
extract($view_data, EXTR_SKIP);
ob_start();
include APPPATH . 'views/certificates/templates/official_lcp_certificate.php';
$html = ob_get_clean();

$options = new Dompdf\Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('chroot', FCPATH);

$dompdf = new Dompdf\Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$out = FCPATH . 'uploads/certificates/_qa_official_lcp.pdf';
@mkdir(dirname($out), 0755, true);
file_put_contents($out, $dompdf->output());
file_put_contents(FCPATH . 'uploads/certificates/_qa_official_lcp.html', $html);

echo 'PDF: ' . $out . ' (' . filesize($out) . " bytes)\n";
echo 'HTML: ' . strlen($html) . " bytes\n";

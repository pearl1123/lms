<?php
define('BASEPATH', true);
define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APPPATH', FCPATH . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'helpers/report_export_helper.php';
require APPPATH . 'helpers/certificate_pdf_helper.php';

putenv('COMPOSER_DISABLE_PLATFORM_CHECK=1');
$autoload = FCPATH . 'vendor/autoload.php';
echo 'Autoload path exists: ' . (is_file($autoload) ? 'yes' : 'NO - ' . $autoload) . "\n";
if (!ka_load_vendor_autoload()) {
    echo "FAIL: ka_load_vendor_autoload\n";
    if (is_file($autoload)) {
        require_once $autoload;
        echo 'After manual require, Dompdf class: ' . (class_exists('Dompdf\\Dompdf') ? 'yes' : 'no') . "\n";
    }
    exit(1);
}
echo "OK: DOMPDF PHP " . PHP_VERSION . "\n";

if (!function_exists('base_url')) {
    function base_url($uri = '') {
        return 'http://localhost/lms/index.php/' . ltrim($uri, '/');
    }
}

$templates = ['premium_lcp_certificate', 'template_pdf', 'official_lcp_certificate'];
$cert = (object) [
    'id'               => 11,
    'certificate_code' => 'KBGA-20250605-TEST11',
    'student_name'     => 'JUAN DELA CRUZ',
    'employee_id'      => 'LCP-2024-001',
    'course_title'     => 'Infection Control and Prevention',
    'category_name'    => 'Clinical Training',
    'modality_name'    => 'Asynchronous',
    'issued_at'        => '2024-12-01 10:00:00',
    'course_id'        => 1,
    'signatory_name'   => '',
    'signatory_title'  => '',
];

$pdf_dir = FCPATH . 'uploads/certificates/';
@mkdir($pdf_dir, 0755, true);
echo 'PDF dir writable: ' . (is_writable($pdf_dir) ? 'yes' : 'NO') . "\n";

$tmpdir = report_export_dompdf_temp_dir();
echo 'Temp dir: ' . $tmpdir . ' writable=' . (is_writable($tmpdir) ? 'yes' : 'NO') . "\n";

foreach ($templates as $tpl) {
    echo "\n--- Template: {$tpl} ---\n";
    try {
        $html = ka_cert_render_template_html($cert, [], ['template' => $tpl, 'for_pdf' => true]);
        echo 'HTML bytes: ' . strlen($html) . "\n";
        if (trim($html) === '') {
            echo "FAIL: empty HTML\n";
            continue;
        }

        $options = new Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', FCPATH);
        $options->set('tempDir', $tmpdir);
        $options->set('dpi', 96);

        $dompdf = new Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $out = $pdf_dir . '_debug_' . $tpl . '.pdf';
        $n = file_put_contents($out, $dompdf->output());
        echo "OK: wrote {$n} bytes -> {$out}\n";
    } catch (Throwable $e) {
        echo 'FAIL: ' . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
}

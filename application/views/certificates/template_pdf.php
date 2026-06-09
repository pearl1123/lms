<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Enterprise SaaS Certificate — DOMPDF · A4 landscape (841×595pt)
 * Coursera / Microsoft Learn style · kaBAGA Academy · Lung Center of the Philippines
 */

$student_name     = (string) ($student_name ?? '');
$course_title     = (string) ($course_title ?? '');
$certificate_code = (string) ($certificate_code ?? '');
$issued_at        = (string) ($issued_at ?? '');
$verify_url       = (string) ($verify_url ?? '');
$category_name    = trim((string) ($category_name ?? ''));
$modality_name    = trim((string) ($modality_name ?? ''));
$training_hours   = trim((string) ($training_hours ?? ''));
$employee_id      = trim((string) ($employee_id ?? ''));
$org_name         = (string) ($org_name ?? 'Lung Center of the Philippines');
$signatories      = isset($signatories) && is_array($signatories) ? $signatories : [];

$qr_src = (string) ($qr_src ?? '');
if ($qr_src === '' && ! empty($qr_image_base64)) {
    $qr_src = 'data:image/png;base64,' . (string) $qr_image_base64;
}

$duration_label = '';
if ($training_hours !== '' && is_numeric($training_hours)) {
    $h = (float) $training_hours;
    $duration_label = rtrim(rtrim(number_format($h, 1), '0'), '.') . ' hour' . ($h === 1.0 ? '' : 's');
} elseif ($training_hours !== '') {
    $duration_label = $training_hours;
} else {
    $duration_label = '—';
}

$course_type_parts = [];
if ($category_name !== '') {
    $course_type_parts[] = $category_name;
}
if ($modality_name !== '') {
    $course_type_parts[] = $modality_name;
}
$course_type_label = $course_type_parts !== [] ? implode(' · ', $course_type_parts) : 'Professional Development';

$kabaga_logo = '';
if (function_exists('ka_cert_embed_src')) {
    if (function_exists('get_instance')) {
        $settings = ka_cert_platform_settings();
        $lp = trim((string) ($settings['branding']['logo_path'] ?? ''));
        if ($lp !== '') {
            $kabaga_logo = ka_cert_embed_src($lp);
        }
    }
    if ($kabaga_logo === '') {
        $kabaga_logo = ka_cert_embed_src('assets/img/LMS-LOGO.png')
            ?: ka_cert_embed_src('assets/img/LMS-LOGO2.png');
    }
}

$lms_name = 'kaBAGA Academy';
if (function_exists('ka_cert_platform_settings')) {
    $lms_name = trim((string) (ka_cert_platform_settings()['general']['lms_name'] ?? $lms_name));
    if ($lms_name === '') {
        $lms_name = 'kaBAGA Academy';
    }
}

$name_len  = function_exists('mb_strlen') ? mb_strlen($student_name) : strlen($student_name);
$name_size = $name_len > 42 ? '26pt' : ($name_len > 32 ? '30pt' : '34pt');
$course_len = function_exists('mb_strlen') ? mb_strlen($course_title) : strlen($course_title);
$course_size = $course_len > 64 ? '14pt' : ($course_len > 48 ? '16pt' : '18pt');

$sig_rows = [];
foreach (array_slice($signatories, 0, 2) as $sig) {
    $title = trim((string) ($sig->title ?? ''));
    $lines = $title !== '' ? preg_split('/\r\n|\r|\n/', $title) : [];
    $sig_rows[] = [
        'name'  => trim((string) ($sig->name ?? '')),
        'role'  => (string) ($lines[0] ?? ''),
        'org'   => (string) ($lines[1] ?? ''),
    ];
}
if ($sig_rows === []) {
    $sig_rows = [
        ['name' => 'Training Officer', 'role' => 'Education & Training', 'org' => 'kaBAGA Academy'],
        ['name' => 'Program Director', 'role' => 'Executive Office', 'org' => $org_name],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
@page { size: A4 landscape; margin: 0; }

* { margin: 0; padding: 0; box-sizing: border-box; }

html, body {
    width: 841pt;
    height: 595pt;
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
    color: #0F172A;
    background: #ffffff;
}

.page {
    position: relative;
    width: 841pt;
    height: 595pt;
    background: #ffffff;
    overflow: hidden;
}

.hdr-table {
    width: 841pt;
    border-collapse: collapse;
}

.hdr-table td {
    vertical-align: middle;
    padding: 28pt 48pt 20pt 48pt;
}

.hdr-left { width: 55%; }

.hdr-brand-row {
    display: table;
}

.hdr-brand-cell {
    display: table-cell;
    vertical-align: middle;
}

.hdr-logo {
    width: 38pt;
    height: auto;
    vertical-align: middle;
}

.hdr-lms-name {
    font-size: 13pt;
    font-weight: bold;
    color: #0F172A;
    letter-spacing: 0.3pt;
    padding-left: 10pt;
    line-height: 1.2;
}

.hdr-lms-sub {
    font-size: 8pt;
    color: #64748B;
    letter-spacing: 0.8pt;
    text-transform: uppercase;
    padding-left: 10pt;
    padding-top: 2pt;
}

.hdr-right {
    text-align: right;
    width: 45%;
}

.hdr-org {
    font-size: 8pt;
    font-weight: bold;
    color: #64748B;
    letter-spacing: 1pt;
    text-transform: uppercase;
    line-height: 1.35;
}

.hdr-org-sub {
    font-size: 7pt;
    color: #94A3B8;
    padding-top: 3pt;
    line-height: 1.35;
}

.accent-bar {
    width: 841pt;
    height: 3pt;
    background: #2563EB;
}

.main-wrap {
    padding: 32pt 56pt 0 56pt;
}

.cred-label {
    font-size: 9pt;
    font-weight: bold;
    color: #2563EB;
    letter-spacing: 2pt;
    text-transform: uppercase;
    padding-bottom: 14pt;
}

.learner-name {
    font-weight: bold;
    color: #0F172A;
    line-height: 1.1;
    letter-spacing: -0.3pt;
    padding-bottom: 10pt;
}

.course-title {
    font-weight: bold;
    color: #0F172A;
    line-height: 1.3;
    padding-bottom: 10pt;
}

.completion-text {
    font-size: 10pt;
    color: #64748B;
    line-height: 1.5;
    max-width: 520pt;
}

.body-table {
    width: 729pt;
    margin-top: 28pt;
    border-collapse: collapse;
    table-layout: fixed;
}

.body-left {
    width: 58%;
    vertical-align: top;
    padding-right: 24pt;
}

.body-right {
    width: 42%;
    vertical-align: top;
}

.meta-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8pt;
}

.meta-table td {
    width: 50%;
    vertical-align: top;
    padding-right: 8pt;
}

.meta-box {
    background: #F8FAFC;
    border: 1pt solid #E2E8F0;
    border-radius: 6pt;
    padding: 10pt 12pt;
}

.meta-label {
    font-size: 7pt;
    font-weight: bold;
    color: #94A3B8;
    letter-spacing: 0.8pt;
    text-transform: uppercase;
    padding-bottom: 4pt;
}

.meta-value {
    font-size: 9pt;
    font-weight: bold;
    color: #0F172A;
    line-height: 1.3;
    word-break: break-word;
}

.meta-value-mono {
    font-family: DejaVu Sans Mono, Courier, monospace;
    font-size: 8pt;
    font-weight: bold;
    color: #0F172A;
    word-break: break-all;
}

.verify-box {
    background: #F8FAFC;
    border: 1pt solid #E2E8F0;
    border-radius: 8pt;
    padding: 14pt;
    margin-top: 4pt;
}

.verify-table {
    width: 100%;
    border-collapse: collapse;
}

.verify-table td {
    vertical-align: top;
}

.verify-qr-cell {
    width: 80pt;
    padding-right: 12pt;
}

.verify-qr {
    width: 72pt;
    height: 72pt;
    border: 1pt solid #E2E8F0;
    border-radius: 4pt;
    background: #ffffff;
}

.verify-badge {
    display: inline-block;
    background: #2563EB;
    color: #ffffff;
    font-size: 7pt;
    font-weight: bold;
    letter-spacing: 0.6pt;
    text-transform: uppercase;
    padding: 4pt 10pt;
    border-radius: 20pt;
    margin-bottom: 8pt;
}

.verify-heading {
    font-size: 9pt;
    font-weight: bold;
    color: #0F172A;
    padding-bottom: 4pt;
}

.verify-url {
    font-size: 7pt;
    color: #64748B;
    line-height: 1.4;
    word-break: break-all;
}

.verify-note {
    font-size: 7pt;
    color: #94A3B8;
    padding-top: 6pt;
    line-height: 1.35;
}

.sig-section {
    margin-top: 22pt;
    padding-top: 16pt;
    border-top: 1pt solid #E2E8F0;
}

.sig-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.sig-cell {
    width: 50%;
    vertical-align: top;
    padding-right: 16pt;
}

.sig-name {
    font-size: 9pt;
    font-weight: bold;
    color: #0F172A;
    line-height: 1.3;
}

.sig-role {
    font-size: 8pt;
    color: #64748B;
    line-height: 1.35;
    padding-top: 2pt;
}

.sig-org {
    font-size: 7pt;
    color: #94A3B8;
    padding-top: 1pt;
}

.footer-id {
    position: absolute;
    bottom: 20pt;
    left: 48pt;
    right: 48pt;
    text-align: center;
    font-size: 7pt;
    color: #CBD5E1;
    letter-spacing: 0.5pt;
}
</style>
</head>
<body>
<div class="page">

    <table class="hdr-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="hdr-left">
                <div class="hdr-brand-row">
                    <div class="hdr-brand-cell">
                        <?php if ($kabaga_logo !== ''): ?>
                        <img class="hdr-logo" src="<?= htmlspecialchars($kabaga_logo, ENT_QUOTES) ?>" alt="">
                        <?php endif; ?>
                    </div>
                    <div class="hdr-brand-cell">
                        <div class="hdr-lms-name"><?= htmlspecialchars($lms_name) ?></div>
                        <div class="hdr-lms-sub">Learning Management System</div>
                    </div>
                </div>
            </td>
            <td class="hdr-right">
                <div class="hdr-org"><?= htmlspecialchars($org_name) ?></div>
                <div class="hdr-org-sub">Official Training Credential</div>
            </td>
        </tr>
    </table>

    <div class="accent-bar"></div>

    <div class="main-wrap">

        <div class="cred-label">Certificate of Completion</div>

        <div class="learner-name" style="font-size:<?= htmlspecialchars($name_size, ENT_QUOTES) ?>;">
            <?= htmlspecialchars($student_name) ?>
        </div>

        <div class="course-title" style="font-size:<?= htmlspecialchars($course_size, ENT_QUOTES) ?>;">
            <?= htmlspecialchars($course_title) ?>
        </div>

        <div class="completion-text">
            has successfully completed this learning program and demonstrated the required competencies
            <?php if ($employee_id !== ''): ?>
            · ID <?= htmlspecialchars($employee_id) ?>
            <?php endif; ?>
        </div>

        <table class="body-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="body-left">
                    <table class="meta-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <div class="meta-box">
                                    <div class="meta-label">Completion Date</div>
                                    <div class="meta-value"><?= htmlspecialchars($issued_at !== '' ? $issued_at : '—') ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="meta-box">
                                    <div class="meta-label">Duration</div>
                                    <div class="meta-value"><?= htmlspecialchars($duration_label) ?></div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="meta-box">
                                    <div class="meta-label">Certificate ID</div>
                                    <div class="meta-value-mono"><?= htmlspecialchars($certificate_code !== '' ? $certificate_code : '—') ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="meta-box">
                                    <div class="meta-label">Course Type</div>
                                    <div class="meta-value"><?= htmlspecialchars($course_type_label) ?></div>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <div class="sig-section">
                        <table class="sig-table" cellpadding="0" cellspacing="0">
                            <tr>
                                <?php foreach ($sig_rows as $sig): ?>
                                <td class="sig-cell">
                                    <div class="sig-name"><?= htmlspecialchars($sig['name']) ?></div>
                                    <?php if ($sig['role'] !== ''): ?>
                                    <div class="sig-role"><?= htmlspecialchars($sig['role']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($sig['org'] !== ''): ?>
                                    <div class="sig-org"><?= htmlspecialchars($sig['org']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                        </table>
                    </div>
                </td>

                <td class="body-right">
                    <div class="verify-box">
                        <table class="verify-table" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="verify-qr-cell">
                                    <?php if ($qr_src !== ''): ?>
                                    <img class="verify-qr" src="<?= htmlspecialchars($qr_src, ENT_QUOTES) ?>" alt="">
                                    <?php else: ?>
                                    <div class="verify-qr" style="display:table;width:72pt;height:72pt;">
                                        <div style="display:table-cell;vertical-align:middle;text-align:center;font-size:7pt;color:#94A3B8;">QR</div>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="verify-badge">Verified Credential</div>
                                    <div class="verify-heading">Scan to verify authenticity</div>
                                    <?php if ($verify_url !== ''): ?>
                                    <div class="verify-url"><?= htmlspecialchars($verify_url) ?></div>
                                    <?php endif; ?>
                                    <div class="verify-note">This digital credential is issued by <?= htmlspecialchars($lms_name) ?> in partnership with <?= htmlspecialchars($org_name) ?>.</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

    </div>

    <?php if ($certificate_code !== ''): ?>
    <div class="footer-id"><?= htmlspecialchars($certificate_code) ?></div>
    <?php endif; ?>

</div>
</body>
</html>

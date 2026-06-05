<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Premium SaaS Certificate (navy + gold) — DOMPDF · A4 landscape
 * UI mirrors the on-screen certificate card. Data bindings unchanged.
 */

$learner_name      = (string) ($learner_name ?? ($student_name ?? ''));
$course_title      = (string) ($course_title ?? '');
$facilitator       = trim((string) ($facilitator ?? ''));
$date_range        = (string) ($date_range ?? ($issued_at ?? ''));
$venue             = trim((string) ($venue ?? ''));
$cert_serial       = (string) ($cert_serial ?? ($certificate_code ?? ''));
$verify_url        = (string) ($verify_url ?? '');
$qr_image_base64   = (string) ($qr_image_base64 ?? '');
$org_name          = (string) ($org_name ?? 'Lung Center of the Philippines');
$modality_name     = trim((string) ($modality_name ?? ''));
$training_hours    = trim((string) ($training_hours ?? ''));
$employee_id       = (string) ($employee_id ?? '');
$issue_date        = $date_range !== '' ? $date_range : (string) ($issued_at ?? '');

if ($qr_image_base64 === '' && ! empty($qr_src)) {
    $qr_image_base64 = preg_replace('#^data:image/[^;]+;base64,#', '', (string) $qr_src);
}

$signatory1_name  = (string) ($signatory1_name ?? '');
$signatory1_title = (string) ($signatory1_title ?? '');
$signatory1_dept  = (string) ($signatory1_dept ?? '');
$signatory1_img   = (string) ($signatory1_img ?? '');
$signatory2_name  = (string) ($signatory2_name ?? '');
$signatory2_title = (string) ($signatory2_title ?? '');
$signatory2_org   = (string) ($signatory2_org ?? '');
$signatory2_img   = (string) ($signatory2_img ?? '');

if (($signatory1_name === '' || $signatory2_name === '') && ! empty($signatories) && is_array($signatories)) {
    $s1 = $signatories[0] ?? null;
    $s2 = $signatories[1] ?? null;
    if ($s1) {
        $lines = preg_split('/\r\n|\r|\n/', trim((string) ($s1->title ?? '')));
        if ($signatory1_img === '' && ! empty($s1->signature_image_path) && function_exists('ka_cert_sig_image_src')) {
            $signatory1_img = ka_cert_sig_image_src($s1->signature_image_path);
        }
        if ($signatory1_name === '') {
            $signatory1_name = (string) ($s1->name ?? '');
        }
        if ($signatory1_title === '') {
            $signatory1_title = (string) ($lines[0] ?? '');
        }
        if ($signatory1_dept === '') {
            $signatory1_dept = (string) ($lines[1] ?? '');
        }
    }
    if ($s2) {
        $lines = preg_split('/\r\n|\r|\n/', trim((string) ($s2->title ?? '')));
        if ($signatory2_img === '' && ! empty($s2->signature_image_path) && function_exists('ka_cert_sig_image_src')) {
            $signatory2_img = ka_cert_sig_image_src($s2->signature_image_path);
        }
        if ($signatory2_name === '') {
            $signatory2_name = (string) ($s2->name ?? '');
        }
        if ($signatory2_title === '') {
            $signatory2_title = (string) ($lines[0] ?? '');
        }
        if ($signatory2_org === '') {
            $signatory2_org = (string) ($lines[1] ?? '');
        }
    }
}

if ($venue === '') {
    $venue = 'the Lung Center of the Philippines';
}

$name_len    = function_exists('mb_strlen') ? mb_strlen($learner_name) : strlen($learner_name);
$name_size   = $name_len > 48 ? '34pt' : ($name_len > 38 ? '40pt' : ($name_len > 28 ? '46pt' : '52pt'));
$course_len  = function_exists('mb_strlen') ? mb_strlen($course_title) : strlen($course_title);
$course_size = $course_len > 72 ? '15pt' : ($course_len > 50 ? '17pt' : '20pt');
$hours_label = $training_hours !== '' && is_numeric($training_hours)
    ? rtrim(rtrim(number_format((float) $training_hours, 1), '0'), '.') . ' hour' . ((float) $training_hours === 1.0 ? '' : 's')
    : ($training_hours !== '' ? $training_hours : '');
$modality_label = $modality_name !== '' ? $modality_name : '';

$logo_src = $assets['logo'] ?? '';
if ($logo_src === '' && function_exists('ka_cert_embed_src')) {
    $logo_src = ka_cert_embed_src('assets/img/LMS-LOGO.png')
        ?: ka_cert_embed_src('assets/img/certificate/lcp_cert_logo.jpeg');
}
if ($logo_src !== '' && strpos($logo_src, 'data:') !== 0 && function_exists('ka_cert_embed_src')) {
    $logo_path = preg_replace('#^(https?://[^/]+)?/?#', '', (string) $logo_src);
    $logo_embedded = ka_cert_embed_src($logo_path);
    if ($logo_embedded !== '') {
        $logo_src = $logo_embedded;
    }
}

if (empty($css_path)) {
    $css_path = FCPATH . 'assets/css/certificate_premium.css';
}

$cert_debug = ! empty($cert_debug);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
<?php if ( ! empty($cert_css)): ?>
<?= $cert_css ?>
<?php elseif (is_file($css_path)): ?>
<?= file_get_contents($css_path) ?>
<?php endif; ?>
<?php if ($cert_debug && is_file(__DIR__ . '/../certificate_debug.css.php')): ?>
<?php include __DIR__ . '/../certificate_debug.css.php'; ?>
<?php endif; ?>
</style>
</head>
<body>
<div class="cert-page<?= $cert_debug ? ' cert-debug' : '' ?>">

    <div class="deco deco-tr"></div>
    <div class="deco deco-bl"></div>
    <div class="frame"></div>

    <table class="cert-shell" cellpadding="0" cellspacing="0">

        <!-- Header -->
        <tr>
            <td class="hdr-left">
                <table class="brand-row" cellpadding="0" cellspacing="0">
                    <tr>
                        <?php if ($logo_src !== ''): ?>
                        <td class="brand-logo-cell">
                            <img class="brand-logo" src="<?= htmlspecialchars($logo_src, ENT_QUOTES) ?>" alt="">
                        </td>
                        <?php endif; ?>
                        <td class="brand-text-cell">
                            <div class="brand-name">kaBAGA Academy</div>
                            <div class="brand-sub"><?= htmlspecialchars($org_name) ?></div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="hdr-right">
                <span class="cred-badge">Verified Credential</span>
            </td>
        </tr>

        <!-- Hero -->
        <tr>
            <td colspan="2" class="hero">
                <div class="eyebrow">Certificate of Completion</div>
                <div class="hero-by">This certifies that</div>
                <div class="learner-name" style="font-size: <?= htmlspecialchars($name_size) ?>;">
                    <?= htmlspecialchars($learner_name) ?>
                </div>
                <?php if ($employee_id !== ''): ?>
                <div class="learner-id">Employee ID: <?= htmlspecialchars($employee_id) ?></div>
                <?php endif; ?>
                <div class="hero-completed">has successfully completed the course</div>
                <div class="course-title" style="font-size: <?= htmlspecialchars($course_size) ?>;">
                    <?= htmlspecialchars($course_title) ?>
                </div>
                <table class="gold-rule" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td></tr></table>
            </td>
        </tr>

        <!-- Meta row -->
        <tr>
            <td colspan="2" class="meta-wrap">
                <table class="meta-grid" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="meta-item">
                            <div class="meta-label">Date Issued</div>
                            <div class="meta-value"><?= htmlspecialchars($issue_date !== '' ? $issue_date : '—') ?></div>
                        </td>
                        <td class="meta-sep">&nbsp;</td>
                        <td class="meta-item">
                            <div class="meta-label">Credential ID</div>
                            <div class="meta-value meta-value--mono"><?= htmlspecialchars($cert_serial !== '' ? $cert_serial : '—') ?></div>
                        </td>
                        <?php if ($hours_label !== ''): ?>
                        <td class="meta-sep">&nbsp;</td>
                        <td class="meta-item">
                            <div class="meta-label">Duration</div>
                            <div class="meta-value"><?= htmlspecialchars($hours_label) ?></div>
                        </td>
                        <?php endif; ?>
                        <?php if ($modality_label !== ''): ?>
                        <td class="meta-sep">&nbsp;</td>
                        <td class="meta-item">
                            <div class="meta-label">Modality</div>
                            <div class="meta-value"><?= htmlspecialchars($modality_label) ?></div>
                        </td>
                        <?php endif; ?>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Footer: signatures + QR -->
        <tr>
            <td colspan="2" class="footer-wrap">
                <table class="footer-grid" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="sig-cell">
                            <?php if ($signatory1_img !== ''): ?>
                            <img class="sig-img" src="<?= htmlspecialchars($signatory1_img, ENT_QUOTES) ?>" alt="">
                            <?php endif; ?>
                            <table class="sig-line" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td></tr></table>
                            <div class="sig-name"><?= htmlspecialchars($signatory1_name !== '' ? $signatory1_name : "\xC2\xA0") ?></div>
                            <div class="sig-role"><?= htmlspecialchars($signatory1_title) ?></div>
                            <?php if ($signatory1_dept !== ''): ?>
                            <div class="sig-role"><?= htmlspecialchars($signatory1_dept) ?></div>
                            <?php endif; ?>
                        </td>

                        <td class="qr-cell">
                            <div class="qr-box">
                                <?php if ( ! empty($qr_image_base64)): ?>
                                <img class="qr-img" src="data:image/png;base64,<?= htmlspecialchars($qr_image_base64, ENT_QUOTES) ?>" alt="">
                                <?php else: ?>
                                <div class="qr-fallback">QR</div>
                                <?php endif; ?>
                            </div>
                            <div class="qr-label">Scan to verify</div>
                        </td>

                        <td class="sig-cell sig-cell--right">
                            <?php if ($signatory2_img !== ''): ?>
                            <img class="sig-img" src="<?= htmlspecialchars($signatory2_img, ENT_QUOTES) ?>" alt="">
                            <?php endif; ?>
                            <table class="sig-line" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td></tr></table>
                            <div class="sig-name"><?= htmlspecialchars($signatory2_name !== '' ? $signatory2_name : "\xC2\xA0") ?></div>
                            <div class="sig-role"><?= htmlspecialchars($signatory2_title) ?></div>
                            <?php if ($signatory2_org !== ''): ?>
                            <div class="sig-role"><?= htmlspecialchars($signatory2_org) ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <?php if ($verify_url !== ''): ?>
                <div class="verify-url">Verify at <?= htmlspecialchars($verify_url) ?></div>
                <?php endif; ?>
            </td>
        </tr>

    </table>

</div>
</body>
</html>

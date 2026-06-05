<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * LCP Official Certificate — replicates Sample Certificate for LMS.pptx
 * Variables from existing pipeline ({@see ka_cert_build_view_data()}).
 */

$signatories = isset($signatories) && is_array($signatories) ? $signatories : [];

$learner_name       = (string) ($student_name ?? '');
$course_title       = (string) ($course_title ?? '');
$date_range         = (string) ($issued_at ?? '');
$cert_serial        = (string) ($certificate_code ?? '');
$facilitator        = trim((string) ($facilitator ?? ''));
$venue              = trim((string) ($venue ?? 'EMG Auditorium, Lung Center of the Philippines'));

if ($facilitator === '') {
    $facilitator = 'the Civil Service Commission - National Capital Region';
}
$venue = preg_replace('/^at\s+/i', '', $venue);
if (stripos($venue, 'the ') !== 0) {
    $venue = 'the ' . $venue;
}

$name_len = function_exists('mb_strlen') ? mb_strlen($learner_name) : strlen($learner_name);
$name_pt  = '52pt';
if ($name_len > 35) {
    $name_pt = '38pt';
} elseif ($name_len > 28) {
    $name_pt = '44pt';
}

$sig_slots = array_slice($signatories, 0, 2);
$split_sig = static function ($sig) {
    if ( ! $sig) {
        return ['name' => '', 'title' => '', 'dept' => '', 'img' => ''];
    }
    $title = trim((string) ($sig->title ?? ''));
    $lines = $title !== '' ? preg_split('/\r\n|\r|\n/', $title) : [];
    $img   = '';
    if ( ! empty($sig->signature_image_path) && function_exists('ka_cert_sig_image_src')) {
        $img = ka_cert_sig_image_src($sig->signature_image_path);
    }

    return [
        'name'  => (string) ($sig->name ?? ''),
        'title' => (string) ($lines[0] ?? ''),
        'dept'  => (string) ($lines[1] ?? ''),
        'img'   => $img,
    ];
};

$s1 = $split_sig($sig_slots[0] ?? null);
$s2 = $split_sig($sig_slots[1] ?? null);

$signatory1_name  = $s1['name'] !== '' ? $s1['name'] : "\xC2\xA0";
$signatory1_title = $s1['title'];
$signatory1_dept  = $s1['dept'];
$signatory1_img   = $s1['img'];
$signatory2_name  = $s2['name'] !== '' ? $s2['name'] : "\xC2\xA0";
$signatory2_title = $s2['title'];
$signatory2_org   = $s2['dept'];
$signatory2_img   = $s2['img'];

$embed_cert_img = static function ($file) {
    $rel = 'assets/images/cert/' . $file;
    if (function_exists('ka_cert_embed_src')) {
        $src = ka_cert_embed_src($rel);
        if ($src !== '') {
            return $src;
        }
    }

    return '';
};

$img_wave_top    = $embed_cert_img('wave_lines.png');
$img_wave_bottom = $embed_cert_img('wave_bottom.png');
$img_logo        = $embed_cert_img('lcp_icon_only.png');
if ($img_logo === '') {
    $img_logo = $embed_cert_img('lcp_logo.png');
}

$embed_font = static function ($filename) {
    $path = FCPATH . 'assets/fonts/cert/' . $filename;
    if ( ! is_file($path)) {
        return '';
    }

    return 'data:font/truetype;base64,' . base64_encode((string) file_get_contents($path));
};

$font_hammersmith = $embed_font('HammersmithOne-Regular.ttf');
$font_archivo     = $embed_font('ArchivoBlack-Regular.ttf');
$font_parisienne  = $embed_font('Parisienne-Regular.ttf');
$font_montserrat  = $embed_font('Montserrat-Regular.ttf');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
<?= $cert_css ?? '' ?>
<?php if ($font_hammersmith !== ''): ?>
@font-face {
  font-family: 'Hammersmith One';
  src: url('<?= $font_hammersmith ?>') format('truetype');
  font-weight: normal;
  font-style: normal;
}
<?php endif; ?>
<?php if ($font_archivo !== ''): ?>
@font-face {
  font-family: 'Archivo Black';
  src: url('<?= $font_archivo ?>') format('truetype');
  font-weight: normal;
  font-style: normal;
}
<?php endif; ?>
<?php if ($font_parisienne !== ''): ?>
@font-face {
  font-family: 'Parisienne';
  src: url('<?= $font_parisienne ?>') format('truetype');
  font-weight: normal;
  font-style: normal;
}
<?php endif; ?>
<?php if ($font_montserrat !== ''): ?>
@font-face {
  font-family: 'Montserrat';
  src: url('<?= $font_montserrat ?>') format('truetype');
  font-weight: normal;
  font-style: normal;
}
<?php endif; ?>
</style>
</head>
<body>
<div class="cert-page">

  <?php if ($img_wave_top !== ''): ?>
  <div class="layer-wave-top">
    <img src="<?= htmlspecialchars($img_wave_top, ENT_QUOTES) ?>" alt="">
  </div>
  <?php endif; ?>

  <?php if ($img_logo !== ''): ?>
  <div class="layer-logo">
    <img class="logo-img" src="<?= htmlspecialchars($img_logo, ENT_QUOTES) ?>" alt="LCP Logo">
    <span class="org-name">LUNG CENTER OF THE PHILIPPINES</span>
  </div>
  <?php endif; ?>

  <div class="layer-title">CERTIFICATE</div>
  <div class="layer-subtitle">OF COMPLETION</div>

  <div class="layer-presented">This certificate is proudly presented to</div>

  <div class="layer-name" style="font-size: <?= htmlspecialchars($name_pt) ?>;">
    <?= htmlspecialchars($learner_name) ?>
  </div>

  <div class="layer-name-line"></div>

  <div class="layer-body">
    for successfully completing the <?= htmlspecialchars($course_title) ?> facilitated by
    <?= htmlspecialchars($facilitator) ?> last <?= htmlspecialchars($date_range) ?> at
    <?= htmlspecialchars($venue) ?>.
  </div>

  <?php if ($img_wave_bottom !== ''): ?>
  <div class="layer-wave-bottom">
    <img src="<?= htmlspecialchars($img_wave_bottom, ENT_QUOTES) ?>" alt="">
  </div>
  <?php endif; ?>

  <div class="layer-sig-left-name"><?= htmlspecialchars($signatory1_name) ?></div>
  <?php if ($signatory1_img !== ''): ?>
  <div class="layer-sig-left-signature">
    <img class="sig-image" src="<?= htmlspecialchars($signatory1_img, ENT_QUOTES) ?>" alt="">
  </div>
  <?php endif; ?>
  <div class="layer-sig-left-line"></div>
  <div class="layer-sig-left-titles">
    <?php if ($signatory1_title !== ''): ?><?= htmlspecialchars($signatory1_title) ?><br><?php endif; ?>
    <?= htmlspecialchars($signatory1_dept) ?>
  </div>

  <div class="layer-sig-right-name"><?= htmlspecialchars($signatory2_name) ?></div>
  <?php if ($signatory2_img !== ''): ?>
  <div class="layer-sig-right-signature">
    <img class="sig-image" src="<?= htmlspecialchars($signatory2_img, ENT_QUOTES) ?>" alt="">
  </div>
  <?php endif; ?>
  <div class="layer-sig-right-line"></div>
  <div class="layer-sig-right-titles">
    <?php if ($signatory2_title !== ''): ?><?= htmlspecialchars($signatory2_title) ?><br><?php endif; ?>
    <?= htmlspecialchars($signatory2_org) ?>
  </div>

  <?php if ($cert_serial !== ''): ?>
  <div class="layer-cert-serial">Certificate No: <?= htmlspecialchars($cert_serial) ?></div>
  <?php endif; ?>

  <?php if ( ! empty($verify_url)): ?>
  <div class="layer-cert-verify">Verify: <?= htmlspecialchars((string) $verify_url) ?></div>
  <?php endif; ?>

</div>
</body>
</html>

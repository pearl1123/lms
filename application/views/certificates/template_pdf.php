<?php
$student_name     = $student_name ?? '';
$course_title     = $course_title ?? '';
$employee_id      = $employee_id ?? '';
$category_name    = $category_name ?? '';
$modality_name    = $modality_name ?? '';
$certificate_prefix = $certificate_prefix ?? '';
$signatory_name   = $signatory_name ?? '';
$signatory_title  = $signatory_title ?? '';
$issued_at        = $issued_at ?? '';
$certificate_code = $certificate_code ?? '';
$verify_url       = $verify_url ?? '';

$logo_file = FCPATH . 'assets/img/LMS-LOGO.png';
$logo_src  = is_file($logo_file) ? ('file:///' . str_replace('\\', '/', $logo_file)) : '';
$safe_student_name = (string) $student_name;
$safe_course_title = (string) $course_title;
$tags = array_filter([$category_name ?? '', $modality_name ?? '']);
$name_len = function_exists('mb_strlen') ? mb_strlen($safe_student_name) : strlen($safe_student_name);
$name_class = ($name_len > 34) ? 'student-name student-name-sm' : 'student-name';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

  @page { size: A4 landscape; margin: 0; }

  body {
    width: 297mm;
    height: 210mm;
    font-family: DejaVu Sans, sans-serif;
    background: #f8f7f3;
    color: #1a3a5c;
    position: relative;
    overflow: hidden;
  }

  .frame-outer {
    position: absolute;
    top: 6mm; left: 6mm; right: 6mm; bottom: 6mm;
    border: 2pt solid #c9a84c;
    background: #ffffff;
  }

  .frame-inner {
    position: absolute;
    top: 9mm; left: 9mm; right: 9mm; bottom: 9mm;
    border: 0.6pt solid #d9be76;
  }

  .accent-top {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 20mm;
    background: #1a3a5c;
  }

  .accent-top-line {
    position: absolute;
    top: 20mm; left: 16mm; right: 16mm;
    height: 0.9pt;
    background: #d9be76;
  }

  .side-accent-left, .side-accent-right {
    position: absolute;
    top: 20mm; bottom: 12mm;
    width: 0.8pt;
    background: #e6d4a4;
  }
  .side-accent-left { left: 16mm; }
  .side-accent-right { right: 16mm; }

  .wm {
    position: absolute;
    top: 50%; left: 50%;
    width: 100mm;
    transform: translate(-50%, -42%);
    opacity: 0.045;
    text-align: center;
  }
  .wm img {
    width: 100mm;
    height: auto;
  }

  .header {
    position: absolute;
    top: 4mm; left: 18mm; right: 18mm;
    height: 16mm;
    color: #ffffff;
  }

  .header-table {
    width: 100%;
    border-collapse: collapse;
  }
  .header-table td {
    vertical-align: middle;
  }

  .header-logo-cell {
    width: 24mm;
  }
  .header-logo {
    width: 20mm;
    height: auto;
    display: block;
    margin-top: 0.8mm;
  }

  .header-org-wrap {
    text-align: left;
    padding-left: 2mm;
  }
  .header-org {
    font-size: 12pt;
    font-weight: bold;
    letter-spacing: 0.9pt;
    line-height: 1.1;
    text-transform: uppercase;
  }
  .header-sub {
    font-size: 7.4pt;
    letter-spacing: 0.2pt;
    margin-top: 1mm;
    color: #d2e3f0;
  }

  .main {
    position: absolute;
    top: 30mm; left: 24mm; right: 24mm; bottom: 32mm;
    text-align: center;
  }

  .cert-title {
    font-size: 8.8pt;
    letter-spacing: 2.1pt;
    text-transform: uppercase;
    color: #6b7f95;
    margin-top: 2mm;
  }

  .cert-main {
    font-family: DejaVu Serif, serif;
    font-size: 31pt;
    line-height: 1.12;
    color: #1a3a5c;
    letter-spacing: 0.2pt;
    margin-top: 2.2mm;
    font-weight: bold;
  }

  .title-line {
    width: 124mm;
    height: 1.3pt;
    background: #c9a84c;
    margin: 4.4mm auto 7.4mm;
  }

  .small-copy {
    font-size: 9.2pt;
    color: #5f6f82;
    margin-bottom: 2.4mm;
  }

  .student-name {
    font-family: DejaVu Serif, serif;
    font-size: 34pt;
    font-weight: bold;
    line-height: 1.12;
    letter-spacing: 0;
    color: #1a3a5c;
    margin: 0 auto 2.4mm;
    max-width: 235mm;
    word-wrap: break-word;
  }
  .student-name-sm {
    font-size: 27pt;
    line-height: 1.16;
  }

  .employee-id {
    font-size: 7.7pt;
    color: #8798ab;
    margin-bottom: 4.2mm;
  }

  .course-prefix {
    font-size: 9pt;
    color: #5f6f82;
    margin-bottom: 2mm;
  }

  .course-title {
    font-family: DejaVu Serif, serif;
    font-size: 19.5pt;
    font-weight: bold;
    color: #1a3a5c;
    line-height: 1.24;
    max-width: 228mm;
    margin: 0 auto 2.5mm;
    word-wrap: break-word;
  }

  .course-tags {
    font-size: 7.4pt;
    color: #8c9aad;
    margin-top: 0.5mm;
  }

  .detail-row {
    width: 100%;
    margin-top: 8.5mm;
    border-collapse: collapse;
  }
  .detail-row td {
    width: 33.33%;
    text-align: center;
    vertical-align: top;
    padding: 2.8mm 4mm;
    border: 0.6pt solid #e4dfcf;
    background: #fcfcfa;
  }
  .detail-label {
    font-size: 6.4pt;
    color: #8c9aad;
    text-transform: uppercase;
    letter-spacing: 0.8pt;
    margin-bottom: 1mm;
  }
  .detail-value {
    font-size: 10.4pt;
    color: #1a3a5c;
    font-weight: bold;
    line-height: 1.3;
    word-wrap: break-word;
  }

  .sig-row {
    position: absolute;
    left: 24mm; right: 24mm; bottom: 17mm;
    border-collapse: collapse;
    width: auto;
  }
  .sig-row td {
    width: 33.33%;
    text-align: center;
    vertical-align: bottom;
    padding: 0 6mm;
  }
  .sig-line {
    width: 52mm;
    margin: 0 auto 1.7mm;
    height: 0.9pt;
    background: #1a3a5c;
  }
  .sig-name {
    font-size: 8.2pt;
    color: #1a3a5c;
    font-weight: bold;
  }
  .sig-role {
    font-size: 6.4pt;
    color: #8c9aad;
    margin-top: 0.5mm;
    line-height: 1.3;
  }

  .footer {
    position: absolute;
    left: 14mm; right: 14mm; bottom: 2.2mm;
    border-collapse: collapse;
    width: auto;
    color: #435c79;
  }
  .footer td {
    width: 33.33%;
    vertical-align: middle;
    font-size: 6.2pt;
  }
  .footer-mid {
    text-align: center;
  }
  .footer-right {
    text-align: right;
  }
  .code {
    font-size: 7.2pt;
    font-weight: bold;
    color: #1a3a5c;
    letter-spacing: 0.7pt;
  }
  .verify {
    margin-top: 0.7mm;
    color: #7f90a6;
    font-size: 6pt;
    word-wrap: break-word;
  }
</style>
</head>
<body>
  <div class="frame-outer"></div>
  <div class="frame-inner"></div>
  <div class="accent-top"></div>
  <div class="accent-top-line"></div>
  <div class="side-accent-left"></div>
  <div class="side-accent-right"></div>

  <?php if ($logo_src !== ''): ?>
    <div class="wm"><img src="<?= htmlspecialchars($logo_src) ?>" alt=""></div>
  <?php endif; ?>

  <div class="header">
    <table class="header-table">
      <tr>
        <td class="header-logo-cell">
          <?php if ($logo_src !== ''): ?>
            <img class="header-logo" src="<?= htmlspecialchars($logo_src) ?>" alt="KABAGA Academy">
          <?php endif; ?>
        </td>
        <td class="header-org-wrap">
          <div class="header-org">KABAGA Academy</div>
          <div class="header-sub">Lung Center of the Philippines · Training &amp; Development Division</div>
        </td>
      </tr>
    </table>
  </div>

  <div class="main">
    <div class="cert-title">Certificate</div>
    <div class="cert-main">OF COMPLETION</div>
    <div class="title-line"></div>

    <div class="small-copy">This certifies that</div>
    <div class="<?= $name_class ?>"><?= htmlspecialchars($safe_student_name) ?></div>
    <?php if ( ! empty($employee_id)): ?>
      <div class="employee-id">Employee ID: <?= htmlspecialchars($employee_id) ?></div>
    <?php endif; ?>

    <div class="course-prefix">has successfully completed</div>
    <div class="course-title"><?= htmlspecialchars($safe_course_title) ?></div>
    <?php if ( ! empty($tags)): ?>
      <div class="course-tags"><?= htmlspecialchars(implode('  ·  ', $tags)) ?></div>
    <?php endif; ?>

    <table class="detail-row">
      <tr>
        <td>
          <div class="detail-label">Completion Date</div>
          <div class="detail-value"><?= htmlspecialchars($issued_at) ?></div>
        </td>
        <td>
          <div class="detail-label">Certificate Code</div>
          <div class="detail-value"><?= htmlspecialchars($certificate_code) ?></div>
        </td>
        <td>
          <div class="detail-label">Issued By</div>
          <div class="detail-value">KABAGA Academy</div>
        </td>
      </tr>
    </table>
  </div>

  <table class="sig-row">
    <tr>
      <td>
        <div class="sig-line"></div>
        <div class="sig-name">Training Officer</div>
        <div class="sig-role">Training &amp; Development Division</div>
      </td>
      <td>
        <div class="sig-line"></div>
        <div class="sig-name"><?= htmlspecialchars($signatory_name !== '' ? $signatory_name : 'Authorized By KABAGA') ?></div>
        <div class="sig-role"><?= htmlspecialchars($signatory_title !== '' ? $signatory_title : 'Lung Center of the Philippines') ?></div>
      </td>
      <td>
        <div class="sig-line"></div>
        <div class="sig-name">Medical Director</div>
        <div class="sig-role">Lung Center of the Philippines</div>
      </td>
    </tr>
  </table>

  <table class="footer">
    <tr>
      <td>KABAGA Academy · Lung Center of the Philippines</td>
      <td class="footer-mid">
        <div class="code">CERTIFICATE CODE: <?= htmlspecialchars($certificate_code) ?></div>
        <div class="verify">Verify: <?= htmlspecialchars($verify_url) ?></div>
      </td>
      <td class="footer-right">Official LMS Certificate</td>
    </tr>
  </table>
</body>
</html>
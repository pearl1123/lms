<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }

  @page {
    size: A4 landscape;
    margin: 0;
  }

  body {
    width: 297mm;
    height: 210mm;
    font-family: DejaVu Sans, sans-serif;
    background: #f1f5f9;
    position: relative;
    overflow: hidden;
  }

  /* ── Background ── */
  .bg {
    position: absolute;
    inset: 0;
    background: #f1f5f9;
  }

  /* ── Header band ── */
  .header-band {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 28mm;
    background: #1a3a5c;
  }

  /* ── Footer band ── */
  .footer-band {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 16mm;
    background: #1a3a5c;
  }

  /* ── Gold outer border ── */
  .border-outer {
    position: absolute;
    top: 7mm; left: 7mm;
    right: 7mm; bottom: 7mm;
    border: 2.5pt solid #c9a84c;
  }

  /* ── Gold inner border ── */
  .border-inner {
    position: absolute;
    top: 9mm; left: 9mm;
    right: 9mm; bottom: 9mm;
    border: 0.8pt solid #c9a84c;
  }

  /* ── Header content ── */
  .header-content {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 28mm;
    text-align: center;
    padding-top: 5mm;
  }

  .header-org {
    font-size: 14pt;
    font-weight: bold;
    color: #ffffff;
    letter-spacing: 2pt;
  }

  .header-sub {
    font-size: 8pt;
    color: #6dabcf;
    margin-top: 2mm;
  }

  .header-division {
    font-size: 7pt;
    color: rgba(255,255,255,0.5);
    margin-top: 1mm;
  }

  /* ── Main content ── */
  .main {
    position: absolute;
    top: 28mm;
    bottom: 16mm;
    left: 0; right: 0;
    text-align: center;
    padding: 0 20mm;
  }

  .cert-label {
    font-size: 9pt;
    font-weight: bold;
    color: #1a3a5c;
    letter-spacing: 4pt;
    text-transform: uppercase;
    margin-top: 8mm;
    margin-bottom: 2mm;
  }

  .gold-line {
    width: 100mm;
    height: 1.5pt;
    background: #c9a84c;
    margin: 0 auto 6mm;
  }

  .thin-line {
    width: 100mm;
    height: 0.5pt;
    background: #e8d5a3;
    margin: 0 auto;
  }

  .certify-text {
    font-size: 9pt;
    color: #64748b;
    margin-bottom: 3mm;
  }

  /* ── Seal ── */
  .seal-wrap {
    margin: 3mm auto 2mm;
    width: 22mm; height: 22mm;
    border-radius: 50%;
    border: 2pt solid #c9a84c;
    background: rgba(201,168,76,0.08);
    line-height: 1.3;
    display: table;
  }
  .seal-inner {
    display: table-cell;
    vertical-align: middle;
    text-align: center;
    font-size: 4.5pt;
    font-weight: bold;
    color: #c9a84c;
    letter-spacing: 0.5pt;
  }

  .student-name {
    font-size: 22pt;
    font-weight: bold;
    color: #1a3a5c;
    letter-spacing: -0.5pt;
    margin-bottom: 1mm;
    line-height: 1.1;
  }

  .employee-id {
    font-size: 7.5pt;
    color: #94a3b8;
    margin-bottom: 3mm;
  }

  .completed-text {
    font-size: 8.5pt;
    color: #64748b;
    margin-bottom: 2mm;
  }

  .course-title {
    font-size: 14pt;
    font-weight: bold;
    font-style: italic;
    color: #1a3a5c;
    margin-bottom: 1mm;
    line-height: 1.25;
  }

  .course-meta {
    font-size: 7pt;
    color: #94a3b8;
    margin-bottom: 4mm;
  }

  .issued-date {
    font-size: 8pt;
    color: #64748b;
    margin-bottom: 1mm;
  }

  /* ── Signature row ── */
  .sig-row {
    position: absolute;
    bottom: 22mm;
    left: 0; right: 0;
    display: table;
    width: 100%;
    padding: 0 35mm;
  }

  .sig-cell {
    display: table-cell;
    text-align: center;
    width: 33%;
    vertical-align: bottom;
  }

  .sig-line {
    width: 44mm;
    height: 0.6pt;
    background: #1a3a5c;
    margin: 0 auto 1.5mm;
  }

  .sig-name {
    font-size: 7pt;
    font-weight: bold;
    color: #1a3a5c;
  }

  .sig-title {
    font-size: 6pt;
    color: #94a3b8;
    margin-top: 0.5mm;
  }

  /* ── Footer ── */
  .footer-content {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 16mm;
    padding: 0 10mm;
    display: table;
    width: 100%;
  }

  .footer-left {
    display: table-cell;
    vertical-align: middle;
    font-size: 6.5pt;
    color: rgba(255,255,255,0.7);
    text-align: left;
  }

  .footer-center {
    display: table-cell;
    vertical-align: middle;
    text-align: center;
  }

  .cert-code-box {
    font-size: 7.5pt;
    font-weight: bold;
    color: #c9a84c;
    letter-spacing: 1pt;
  }

  .verify-url {
    font-size: 5.5pt;
    color: rgba(255,255,255,0.45);
    margin-top: 1mm;
  }

  .footer-right {
    display: table-cell;
    vertical-align: middle;
    font-size: 6.5pt;
    color: rgba(255,255,255,0.7);
    text-align: right;
  }

  /* ── Corner ornaments (pure CSS, no SVG) ── */
  .corner {
    position: absolute;
    width: 8mm; height: 8mm;
  }
  .corner-tl { top: 11mm; left: 11mm; border-top: 1.5pt solid #c9a84c; border-left: 1.5pt solid #c9a84c; }
  .corner-tr { top: 11mm; right: 11mm; border-top: 1.5pt solid #c9a84c; border-right: 1.5pt solid #c9a84c; }
  .corner-bl { bottom: 11mm; left: 11mm; border-bottom: 1.5pt solid #c9a84c; border-left: 1.5pt solid #c9a84c; }
  .corner-br { bottom: 11mm; right: 11mm; border-bottom: 1.5pt solid #c9a84c; border-right: 1.5pt solid #c9a84c; }
</style>
</head>
<body>

<div class="bg"></div>
<div class="header-band"></div>
<div class="footer-band"></div>
<div class="border-outer"></div>
<div class="border-inner"></div>
<div class="corner corner-tl"></div>
<div class="corner corner-tr"></div>
<div class="corner corner-bl"></div>
<div class="corner corner-br"></div>

<!-- Header -->
<div class="header-content">
  <div class="header-org">KABAGA ACADEMY</div>
  <div class="header-sub">Lung Center of the Philippines</div>
  <div class="header-division">Training &amp; Development Division</div>
</div>

<!-- Main body -->
<div class="main">
  <div class="cert-label">Certificate of Completion</div>
  <div class="gold-line"></div>

  <div class="certify-text">This is to certify that</div>

  <div class="seal-wrap">
    <div class="seal-inner">KABAGA<br>ACADEMY<br>LCP</div>
  </div>

  <div class="student-name"><?= strtoupper(htmlspecialchars($student_name)) ?></div>

  <?php if ( ! empty($employee_id)): ?>
  <div class="employee-id">Employee ID: <?= htmlspecialchars($employee_id) ?></div>
  <?php endif; ?>

  <div class="completed-text">has successfully completed the training course</div>

  <div class="course-title">&ldquo;<?= htmlspecialchars($course_title) ?>&rdquo;</div>

  <?php
  $tags = array_filter([$category_name ?? '', $modality_name ?? '']);
  if ( ! empty($tags)):
  ?>
  <div class="course-meta"><?= htmlspecialchars(implode('  ·  ', $tags)) ?></div>
  <?php endif; ?>

  <div class="thin-line"></div>

  <div class="issued-date" style="margin-top:3mm;">
    Issued on <strong><?= htmlspecialchars($issued_at) ?></strong>
  </div>
</div>

<!-- Signature row -->
<div class="sig-row">
  <div class="sig-cell">
    <div class="sig-line"></div>
    <div class="sig-name">Training Officer</div>
    <div class="sig-title">Training &amp; Development</div>
  </div>
  <div class="sig-cell"></div>
  <div class="sig-cell">
    <div class="sig-line"></div>
    <div class="sig-name">Medical Director</div>
    <div class="sig-title">Lung Center of the Philippines</div>
  </div>
</div>

<!-- Footer -->
<div class="footer-content">
  <div class="footer-left">KABAGA Academy &mdash; Lung Center of the Philippines</div>
  <div class="footer-center">
    <div class="cert-code-box">Certificate Code: <?= htmlspecialchars($certificate_code) ?></div>
    <div class="verify-url">Verify at: <?= htmlspecialchars($verify_url) ?></div>
  </div>
  <div class="footer-right">Training &amp; Development Division</div>
</div>

</body>
</html>
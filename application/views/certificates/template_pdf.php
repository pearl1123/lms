<?php
$student_name       = $student_name ?? '';
$course_title       = $course_title ?? '';
$employee_id        = $employee_id ?? '';
$category_name      = $category_name ?? '';
$modality_name      = $modality_name ?? '';
$issued_at          = $issued_at ?? '';
$certificate_code   = $certificate_code ?? '';
$verify_url         = $verify_url ?? '';
$signatories        = isset($signatories) && is_array($signatories) ? $signatories : [];

if (empty($signatories)) {
    $sn = trim((string) ($signatory_name ?? ''));
    $st = trim((string) ($signatory_title ?? ''));
    if ($sn !== '') {
        $signatories = [(object) ['name' => $sn, 'title' => $st]];
    }
}

$logo_file = FCPATH . 'assets/img/LMS-LOGO.png';
$logo_src  = is_file($logo_file) ? ('file:///' . str_replace('\\', '/', $logo_file)) : '';
$safe_student_name = (string) $student_name;
$safe_course_title = (string) $course_title;
$name_len = function_exists('mb_strlen') ? mb_strlen($safe_student_name) : strlen($safe_student_name);
$name_class = ($name_len > 34) ? 'student-name student-name-sm' : 'student-name';
$tags = array_filter([$category_name ?? '', $modality_name ?? '']);
$sig_count = count($signatories);
$sig_width = $sig_count > 0 ? (int) floor(100 / min($sig_count, 4)) : 33;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  @page { size: A4 landscape; margin: 12mm; }
  body {
    width: 273mm;
    height: 186mm;
    font-family: DejaVu Sans, sans-serif;
    background: #ffffff;
    color: #1e293b;
    position: relative;
  }
  .border-frame {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    border: 1pt solid #cbd5e1;
  }
  .accent-bar {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4mm;
    background: #1a3a5c;
  }
  .header {
    position: absolute;
    top: 10mm; left: 16mm; right: 16mm;
    text-align: center;
  }
  .header-logo {
    width: 14mm;
    height: auto;
    margin-bottom: 2mm;
  }
  .header-org {
    font-size: 9pt;
    font-weight: 600;
    color: #64748b;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }
  .main {
    position: absolute;
    top: 32mm; left: 20mm; right: 20mm;
    text-align: center;
  }
  .cert-title {
    font-size: 28pt;
    font-weight: 700;
    color: #1a3a5c;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 2mm;
  }
  .cert-subtitle {
    font-size: 11pt;
    color: #64748b;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    margin-bottom: 8mm;
  }
  .small-copy {
    font-size: 10pt;
    color: #64748b;
    margin-bottom: 3mm;
  }
  .student-name {
    font-size: 22pt;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 2mm;
    line-height: 1.2;
  }
  .student-name-sm { font-size: 17pt; }
  .employee-id {
    font-size: 9pt;
    color: #64748b;
    margin-bottom: 6mm;
  }
  .course-prefix {
    font-size: 10pt;
    color: #64748b;
    margin-bottom: 2mm;
  }
  .course-title {
    font-size: 14pt;
    font-weight: 600;
    color: #1a3a5c;
    max-width: 220mm;
    margin: 0 auto 2mm;
    line-height: 1.35;
  }
  .course-tags {
    font-size: 9pt;
    color: #94a3b8;
    margin-bottom: 6mm;
  }
  .meta-table {
    width: 70%;
    margin: 0 auto 4mm;
    border-collapse: collapse;
  }
  .meta-table td {
    width: 50%;
    padding: 2mm 4mm;
    vertical-align: top;
    text-align: center;
  }
  .meta-label {
    font-size: 7pt;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #94a3b8;
    margin-bottom: 1mm;
  }
  .meta-value {
    font-size: 9pt;
    font-weight: 600;
    color: #334155;
  }
  .sig-section {
    position: absolute;
    bottom: 22mm; left: 16mm; right: 16mm;
  }
  .sig-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
  }
  .sig-table td {
    vertical-align: top;
    text-align: center;
    padding: 0 3mm;
  }
  .sig-line {
    border-top: 0.6pt solid #334155;
    width: 80%;
    margin: 0 auto 2mm;
    height: 10mm;
  }
  .sig-name {
    font-size: 9pt;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.25;
  }
  .sig-role {
    font-size: 7.5pt;
    color: #64748b;
    line-height: 1.3;
  }
  .footer {
    position: absolute;
    bottom: 8mm; left: 16mm; right: 16mm;
    font-size: 7pt;
    color: #94a3b8;
    text-align: center;
    border-top: 0.4pt solid #e2e8f0;
    padding-top: 2mm;
  }
  .footer code {
    font-size: 7pt;
    color: #475569;
  }
</style>
</head>
<body>
  <div class="border-frame"></div>
  <div class="accent-bar"></div>

  <div class="header">
    <?php if ($logo_src !== ''): ?>
      <img class="header-logo" src="<?= htmlspecialchars($logo_src) ?>" alt="">
    <?php endif; ?>
    <div class="header-org">Lung Center of the Philippines · Learning Management</div>
  </div>

  <div class="main">
    <div class="cert-title">Certificate</div>
    <div class="cert-subtitle">of completion</div>

    <div class="small-copy">This is to certify that</div>
    <div class="<?= $name_class ?>"><?= htmlspecialchars($safe_student_name) ?></div>
    <?php if ( ! empty($employee_id)): ?>
      <div class="employee-id">Employee ID: <?= htmlspecialchars($employee_id) ?></div>
    <?php endif; ?>

    <div class="course-prefix">has successfully completed</div>
    <div class="course-title"><?= htmlspecialchars($safe_course_title) ?></div>
    <?php if ( ! empty($tags)): ?>
      <div class="course-tags"><?= htmlspecialchars(implode(' · ', $tags)) ?></div>
    <?php endif; ?>

    <table class="meta-table">
      <tr>
        <td>
          <div class="meta-label">Date issued</div>
          <div class="meta-value"><?= htmlspecialchars($issued_at) ?></div>
        </td>
        <td>
          <div class="meta-label">Certificate number</div>
          <div class="meta-value"><?= htmlspecialchars($certificate_code) ?></div>
        </td>
      </tr>
    </table>
  </div>

  <?php if ($sig_count > 0): ?>
  <div class="sig-section">
    <table class="sig-table">
      <tr>
        <?php foreach ($signatories as $sig): ?>
        <td style="width:<?= $sig_width ?>%;">
          <div class="sig-line"></div>
          <div class="sig-name"><?= htmlspecialchars((string) ($sig->name ?? '')) ?></div>
          <?php if ( ! empty($sig->title)): ?>
          <div class="sig-role"><?= htmlspecialchars((string) $sig->title) ?></div>
          <?php endif; ?>
        </td>
        <?php endforeach; ?>
      </tr>
    </table>
  </div>
  <?php endif; ?>

  <div class="footer">
    Verify at <code><?= htmlspecialchars($verify_url) ?></code>
  </div>
</body>
</html>

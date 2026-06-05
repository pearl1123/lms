<?php defined('BASEPATH') OR exit('No direct script access allowed');
$signatories = isset($signatories) && is_array($signatories) ? $signatories : [];
$sig_count   = (int) ($sig_count ?? count($signatories));
$sig_width   = (int) ($sig_width ?? ($sig_count > 0 ? (int) floor(100 / min($sig_count, 4)) : 50));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style><?= $cert_css ?? '' ?></style>
</head>
<body class="cert-modern">
  <div class="cert-modern__accent"></div>
  <div class="cert-modern__page">
    <div class="cert-modern__brand">kaBAGA Academy · <?= htmlspecialchars((string) ($org_name ?? 'Lung Center of the Philippines')) ?></div>
    <div class="cert-modern__title">Certificate</div>
    <div class="cert-modern__subtitle">of completion</div>
    <div class="cert-modern__presented">This certificate is proudly presented to</div>
    <div class="<?= htmlspecialchars((string) ($name_class ?? 'cert-name')) ?>"><?= htmlspecialchars((string) ($student_name ?? '')) ?></div>
    <div class="cert-modern__body"><?= $completion_body ?? '' ?></div>

    <?php if ($sig_count > 0): ?>
    <div class="cert-modern__sig-section">
      <table class="cert-modern__sig-table">
        <tr>
          <?php foreach ($signatories as $sig):
            $sig_img = ! empty($sig->signature_image_path) ? ka_cert_sig_image_src($sig->signature_image_path) : '';
            $role    = trim((string) ($sig->title ?? ''));
          ?>
          <td style="width:<?= $sig_width ?>%;">
            <div class="cert-modern__sig-name"><?= htmlspecialchars((string) ($sig->name ?? '')) ?></div>
            <?php if ($sig_img !== ''): ?>
            <img class="cert-modern__sig-img" src="<?= htmlspecialchars($sig_img, ENT_QUOTES) ?>" alt="">
            <?php else: ?>
            <div class="cert-modern__sig-line"></div>
            <?php endif; ?>
            <?php if ($role !== ''): ?>
            <div class="cert-modern__sig-role"><?= nl2br(htmlspecialchars($role), false) ?></div>
            <?php endif; ?>
          </td>
          <?php endforeach; ?>
        </tr>
      </table>
    </div>
    <?php endif; ?>

    <footer class="cert-modern__footer">
      Certificate No. <strong><?= htmlspecialchars((string) ($certificate_code ?? '')) ?></strong>
      <?php if ( ! empty($verify_url)): ?> · Verify at <code><?= htmlspecialchars((string) $verify_url) ?></code><?php endif; ?>
    </footer>
  </div>
</body>
</html>

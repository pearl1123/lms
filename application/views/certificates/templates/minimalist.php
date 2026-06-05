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
<body class="cert-minimal">
  <div class="cert-minimal__org"><?= htmlspecialchars((string) ($org_name ?? 'kaBAGA Academy')) ?></div>
  <div class="cert-minimal__title">Certificate of Completion</div>
  <div class="cert-minimal__presented">This certifies that</div>
  <div class="<?= htmlspecialchars((string) ($name_class ?? 'cert-name')) ?>"><?= htmlspecialchars((string) ($student_name ?? '')) ?></div>
  <div class="cert-minimal__body"><?= $completion_body ?? '' ?></div>

  <?php if ($sig_count > 0): ?>
  <div class="cert-minimal__sig-section">
    <table class="cert-minimal__sig-table">
      <tr>
        <?php foreach ($signatories as $sig):
          $sig_img = ! empty($sig->signature_image_path) ? ka_cert_sig_image_src($sig->signature_image_path) : '';
          $role    = trim((string) ($sig->title ?? ''));
        ?>
        <td style="width:<?= $sig_width ?>%;">
          <div class="cert-minimal__sig-name"><?= htmlspecialchars((string) ($sig->name ?? '')) ?></div>
          <?php if ($sig_img !== ''): ?>
          <img class="cert-minimal__sig-img" src="<?= htmlspecialchars($sig_img, ENT_QUOTES) ?>" alt="">
          <?php else: ?>
          <div class="cert-minimal__sig-line"></div>
          <?php endif; ?>
          <?php if ($role !== ''): ?>
          <div class="cert-minimal__sig-role"><?= nl2br(htmlspecialchars($role), false) ?></div>
          <?php endif; ?>
        </td>
        <?php endforeach; ?>
      </tr>
    </table>
  </div>
  <?php endif; ?>

  <footer class="cert-minimal__footer">
    <?= htmlspecialchars((string) ($certificate_code ?? '')) ?>
    <?php if ( ! empty($issued_at)): ?> · <?= htmlspecialchars((string) $issued_at) ?><?php endif; ?>
  </footer>
</body>
</html>

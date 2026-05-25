<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$p = $profile ?? null;
if ( ! $p) return;
$initials = ka_user_initials($p->fullname);
$avatar_url = '';
if ( ! empty($p->avatar_path) && is_file(FCPATH . $p->avatar_path)) {
    $avatar_url = base_url($p->avatar_path);
}
$role_label = ucfirst(str_replace('_', ' ', (string) $p->role));
$member_since = ! empty($p->created_at) ? date('M j, Y', strtotime((string) $p->created_at)) : '—';
$last_login = ! empty($p->last_login) ? date('M j, Y g:i A', strtotime((string) $p->last_login)) : '—';
$completeness = (int) ($completeness ?? 0);
?>
<div class="prf-hero animate__animated animate__fadeIn animate__fast">
  <div class="prf-hero-inner">
    <div class="prf-avatar-wrap">
      <div class="prf-avatar" id="prfAvatarPreview">
        <?php if ($avatar_url !== ''): ?>
        <img src="<?= htmlspecialchars($avatar_url, ENT_QUOTES, 'UTF-8') ?>" alt="">
        <?php else: ?>
        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="prf-hero-body">
      <h1 class="prf-hero-name"><?= htmlspecialchars($p->fullname, ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="prf-hero-meta">
        <?= htmlspecialchars($p->employee_id, ENT_QUOTES, 'UTF-8') ?>
        <?php if ($p->email !== ''): ?>
        · <?= htmlspecialchars($p->email, ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
      </p>
      <div class="prf-hero-chips">
        <span class="prf-chip"><?= htmlspecialchars($role_label, ENT_QUOTES, 'UTF-8') ?></span>
        <?php if ($p->office !== ''): ?>
        <span class="prf-chip"><?= htmlspecialchars($p->office, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <?php if ($p->profession !== ''): ?>
        <span class="prf-chip"><?= htmlspecialchars($p->profession, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
      </div>
      <div class="prf-completeness">
        <div class="prf-completeness-label">
          <span>Profile completeness</span>
          <span><?= $completeness ?>%</span>
        </div>
        <div class="prf-completeness-bar">
          <div class="prf-completeness-fill" style="width:<?= min(100, max(0, $completeness)) ?>%;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

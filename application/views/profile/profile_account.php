<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$p = $profile ?? null;
$editable = is_array($editable ?? null) ? $editable : [];
$schema = is_array($schema ?? null) ? $schema : [];
$hrmis_linked = ! empty($hrmis_linked);
if ( ! $p) return;
$member_since = ! empty($p->created_at) ? date('M j, Y', strtotime((string) $p->created_at)) : '—';
$last_login = ! empty($p->last_login) ? date('M j, Y g:i A', strtotime((string) $p->last_login)) : '—';
$can_edit_any = ! empty($editable['email']) || ! empty($editable['contact_number']) || ! empty($editable['bio']) || ! empty($editable['avatar']);
?>

<section class="prf-panel is-active" role="tabpanel" id="prf-panel-account" data-prf-tab="account" aria-labelledby="prf-nav-account">
  <div class="prf-card">
    <div class="prf-card-hdr">
      <h2 class="prf-card-title">Account overview</h2>
      <p class="prf-card-kicker">Your identity in kaBAGA Academy and how you appear across the LMS.</p>
    </div>
    <div class="prf-card-body">
      <dl class="prf-dl">
        <div>
          <dt>Employee ID</dt>
          <dd><?= htmlspecialchars($p->employee_id, ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
        <div>
          <dt>Full name</dt>
          <dd><?= htmlspecialchars($p->fullname, ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
        <div>
          <dt>Email</dt>
          <dd><?= $p->email !== '' ? htmlspecialchars($p->email, ENT_QUOTES, 'UTF-8') : '—' ?></dd>
        </div>
        <div>
          <dt>Department</dt>
          <dd><?= $p->office !== '' ? htmlspecialchars($p->office, ENT_QUOTES, 'UTF-8') : '—' ?></dd>
        </div>
        <div>
          <dt>Job title</dt>
          <dd><?= $p->profession !== '' ? htmlspecialchars($p->profession, ENT_QUOTES, 'UTF-8') : '—' ?></dd>
        </div>
        <div>
          <dt>Role</dt>
          <dd><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $p->role)), ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
        <div>
          <dt>Member since</dt>
          <dd><?= htmlspecialchars($member_since, ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
        <div>
          <dt>Last login</dt>
          <dd><?= htmlspecialchars($last_login, ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
      </dl>
    </div>
  </div>

  <?php if ($can_edit_any): ?>
  <div class="prf-card">
    <div class="prf-card-hdr">
      <h2 class="prf-card-title">Edit profile</h2>
      <p class="prf-card-kicker">Update contact details and your public bio. Identity fields synced from HRMIS stay read-only.</p>
    </div>
    <div class="prf-card-body">
      <form method="post" action="<?= base_url('profile') ?>" enctype="multipart/form-data">
        <input type="hidden" name="<?= html_escape($csrf_field_name ?? '') ?>" value="<?= html_escape($csrf_hash ?? '') ?>">
        <input type="hidden" name="profile_submit" value="1">

        <?php if ( ! empty($editable['avatar'])): ?>
        <div class="prf-field">
          <label class="prf-label">Profile photo</label>
          <input type="file" name="avatar" class="prf-input" accept="image/jpeg,image/png,image/webp">
          <p class="prf-help">JPG, PNG, or WebP. Max recommended 2 MB.</p>
        </div>
        <?php endif; ?>

        <div class="prf-row-2">
          <?php if ( ! empty($schema['email'])): ?>
          <div class="prf-field">
            <label class="prf-label" for="prf_email">Email</label>
            <input type="email" id="prf_email" name="email" class="prf-input"
                   value="<?= htmlspecialchars(set_value('email', $p->email), ENT_QUOTES, 'UTF-8') ?>"
                   <?= empty($editable['email']) ? 'disabled' : '' ?>>
            <?php if ($hrmis_linked && empty($editable['email'])): ?>
            <span class="prf-hrmis-note">Managed by HRMIS</span>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <?php if ( ! empty($schema['contact_number'])): ?>
          <div class="prf-field">
            <label class="prf-label" for="prf_contact">Contact number</label>
            <input type="text" id="prf_contact" name="contact_number" class="prf-input" maxlength="30"
                   value="<?= htmlspecialchars(set_value('contact_number', $p->contact_number), ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="e.g. 09XX XXX XXXX">
          </div>
          <?php endif; ?>
        </div>

        <?php if ($hrmis_linked): ?>
        <div class="prf-field">
          <label class="prf-label">Full name</label>
          <input type="text" class="prf-input" value="<?= htmlspecialchars($p->fullname, ENT_QUOTES, 'UTF-8') ?>" disabled>
          <span class="prf-hrmis-note">Managed by HRMIS</span>
        </div>
        <?php endif; ?>

        <?php if ( ! empty($schema['bio'])): ?>
        <div class="prf-field">
          <label class="prf-label" for="prf_bio">Short bio</label>
          <?php if (trim($p->bio) === ''): ?>
          <p class="prf-help" style="margin-bottom:.5rem;">No bio yet — tell colleagues a little about your learning goals.</p>
          <?php endif; ?>
          <textarea id="prf_bio" name="bio" class="prf-textarea" maxlength="500"
                    placeholder="Optional — e.g. clinical educator focused on infection prevention…"><?= htmlspecialchars(set_value('bio', $p->bio), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <?php endif; ?>

        <div class="prf-sticky-save">
          <p class="prf-help" style="margin:0;">Changes apply to your LMS account only.</p>
          <button type="submit" class="prf-btn-primary">Save profile</button>
        </div>
      </form>
    </div>
  </div>
  <?php else: ?>
  <div class="prf-card">
    <div class="prf-card-body">
      <?php $this->load->view('components/empty_state', [
          'emoji'       => '⚙',
          'title'       => 'Profile fields not enabled',
          'description' => 'Run <code>application/sql/migration_profile_user_fields.sql</code> to enable email, contact, bio, and avatar on user accounts.',
          'modifier'    => 'ka-empty--wide',
      ]); ?>
    </div>
  </div>
  <?php endif; ?>
</section>

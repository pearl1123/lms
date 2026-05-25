<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$is_edit = ! empty($is_edit);
$invitations = is_array($invitations ?? null) ? $invitations : [];
$invitable_users = is_array($invitable_users ?? null) ? $invitable_users : [];
$dept_select_enabled = ! empty($dept_select_enabled);
?>
<?php if ($is_edit): ?>
<section class="crs-p2-card" aria-labelledby="crs-p2-invite-title">
  <header class="crs-p2-card-hdr">
    <div class="crs-p2-card-icon crs-p2-card-icon--invite" aria-hidden="true">✉</div>
    <div>
      <h4 class="crs-p2-card-title" id="crs-p2-invite-title">Invitations</h4>
      <p class="crs-p2-card-sub"><span class="crs-p2-optional-badge">Optional</span> Invite learners directly or by department — ideal for invitation-only courses.</p>
    </div>
  </header>
  <div class="crs-p2-card-body">
    <div class="crs-p2-invite-compose">
      <div class="crs-p2-field">
        <label class="crs-p2-label">Invite learners</label>
        <select name="invite_user_ids[]" class="ka-select2" multiple data-select-kind="user" data-placeholder="Search learners by name…">
          <?php foreach ($invitable_users as $iu): ?>
          <?php
            $iuid = (int) $iu->id;
            $iuname = (string) ($iu->fullname ?? '');
            $iini = crs_p2_person_initials($iuname);
            $isub = ! empty($iu->employee_id) ? (string) $iu->employee_id : '';
          ?>
          <option value="<?= $iuid ?>"
                  data-name="<?= htmlspecialchars($iuname, ENT_QUOTES, 'UTF-8') ?>"
                  data-initials="<?= htmlspecialchars($iini, ENT_QUOTES, 'UTF-8') ?>"
                  data-sub="<?= htmlspecialchars($isub, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($iuname) ?><?= $isub !== '' ? ' (' . htmlspecialchars($isub) . ')' : '' ?>
          </option>
          <?php endforeach; ?>
        </select>
        <p class="crs-p2-help">Only learners eligible for this course appear in the list.</p>
      </div>
      <div class="crs-p2-field">
        <label class="crs-p2-label">Invite by department</label>
        <select name="invite_department_ids[]" class="ka-select2" multiple data-select-kind="dept" data-placeholder="Select departments…"<?= $dept_select_enabled ? '' : ' disabled' ?>>
          <?php foreach ($departments as $d): ?>
          <?php $dept_id = (int) (is_object($d) ? ($d->id ?? 0) : ($d['id'] ?? 0)); ?>
          <?php $dept_name = is_object($d) ? (string) ($d->name ?? '') : (string) ($d['name'] ?? ''); ?>
          <?php if ($dept_id < 1 || $dept_name === '') { continue; } ?>
          <option value="<?= $dept_id ?>"><?= htmlspecialchars($dept_name, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
        <p class="crs-p2-help">Sends invitations to every eligible learner in the selected departments.</p>
      </div>
    </div>

    <div class="crs-p2-invite-email-row">
      <div class="crs-p2-field" style="margin-bottom:0;">
        <label class="crs-p2-label" for="crs_p2_invite_email">Or invite by email</label>
        <input type="email" id="crs_p2_invite_email" name="invite_email" class="crs-input ef-input"
               placeholder="colleague@organization.gov.ph" style="width:100%;">
      </div>
      <button type="submit" name="invite_submit" value="1" class="crs-p2-invite-btn">Send invitations</button>
    </div>

    <div class="crs-p2-field crs-p2-invite-activity">
      <label class="crs-p2-label">Invitation activity</label>
      <?php if ( ! empty($invitations)): ?>
      <ul class="crs-p2-invite-list">
        <?php foreach ($invitations as $inv): ?>
        <?php
          $display = (string) ($inv->fullname ?? '');
          if ($display === '' && ! empty($inv->email)) {
              $display = (string) $inv->email;
          }
          if ($display === '') {
              $display = 'User #' . (int) ($inv->user_id ?? 0);
          }
          $email_line = ! empty($inv->email) ? (string) $inv->email : '';
          $st = crs_p2_invite_status_meta($inv->status ?? 'pending');
          $inv_date = ! empty($inv->created_at) ? date('M j, Y', strtotime((string) $inv->created_at)) : '';
          $seed = (int) ($inv->user_id ?? crc32($display));
        ?>
        <li class="crs-p2-invite-item">
          <div class="crs-p2-invite-person">
            <span class="crs-p2-avatar" style="background:<?= htmlspecialchars(crs_p2_avatar_hue($seed)) ?>"><?= htmlspecialchars(crs_p2_person_initials($display)) ?></span>
            <div class="crs-p2-invite-details">
              <div class="crs-p2-invite-name"><?= htmlspecialchars($display) ?></div>
              <?php if ($email_line !== '' && $email_line !== $display): ?>
              <div class="crs-p2-invite-email"><?= htmlspecialchars($email_line) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:.625rem;flex-shrink:0;">
            <?php if ($inv_date !== ''): ?>
            <span class="crs-p2-invite-date"><?= htmlspecialchars($inv_date) ?></span>
            <?php endif; ?>
            <span class="crs-p2-status crs-p2-status--<?= htmlspecialchars($st['class']) ?>">
              <span aria-hidden="true"><?= htmlspecialchars($st['icon']) ?></span>
              <?= htmlspecialchars($st['label']) ?>
            </span>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?>
      <div class="crs-p2-empty">
        <div class="crs-p2-empty-icon" aria-hidden="true">📭</div>
        <p class="crs-p2-empty-title">No invitations yet</p>
        <p class="crs-p2-empty-text">Select learners above or enter an email, then click <strong>Send invitations</strong>.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$phase2 = $course_phase2 ?? null;
$is_edit = ! empty($is_edit_form);
$categories = $categories ?? [];
$sel_categories   = isset($sel_categories) ? $sel_categories : ($phase2 ? (array) $phase2->category_ids : []);
$sel_instructors  = isset($sel_instructors) ? $sel_instructors : ($phase2 ? (array) $phase2->instructor_ids : []);
$sel_departments  = isset($sel_departments) ? $sel_departments : ($phase2 ? (array) $phase2->department_ids : []);
$sel_professions  = isset($sel_professions) ? $sel_professions : ($phase2 ? (array) $phase2->profession_ids : []);
$access_type      = isset($access_type) ? $access_type : ($phase2 ? (string) $phase2->access_type : 'approval_required');
$phase2_ready     = ! empty($phase2_schema_ready);
$hrmis_connection_ok = ! empty($hrmis_connection_ok);
$hrmis_ready      = ! empty($hrmis_ready);
$departments      = is_array($departments ?? null) ? $departments : [];
$professions      = is_array($professions ?? null) ? $professions : [];
$dept_select_enabled = $hrmis_connection_ok && ! empty($departments);
$invitations      = ($phase2 && isset($phase2->invitations)) ? $phase2->invitations : [];
$instructor_options = $instructor_options ?? ($teachers ?? []);
$invitable_users = $invitable_users ?? [];
$publish_status = isset($edit_publish_status)
    ? (string) $edit_publish_status
    : ($phase2 ? (string) ($phase2->publish_status ?? 'draft') : 'draft');

if ( ! function_exists('crs_p2_person_initials')) {
    function crs_p2_person_initials($name)
    {
        $parts = preg_split('/\s+/', trim((string) $name));
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
        }

        return strtoupper(substr((string) $name, 0, 2));
    }
}

if ( ! function_exists('crs_p2_avatar_hue')) {
    function crs_p2_avatar_hue($seed)
    {
        $colors = ['#1a3a5c', '#2563eb', '#7c3aed', '#db2777', '#059669', '#d97706', '#0891b2', '#4f46e5'];
        $n = abs(crc32((string) $seed));

        return $colors[$n % count($colors)];
    }
}

if ( ! function_exists('crs_p2_invite_status_meta')) {
    function crs_p2_invite_status_meta($status)
    {
        $key = strtolower(trim((string) $status));
        $map = [
            'accepted' => ['class' => 'accepted', 'label' => 'Accepted', 'icon' => '✓'],
            'pending'  => ['class' => 'pending',  'label' => 'Pending',  'icon' => '◷'],
            'rejected' => ['class' => 'rejected', 'label' => 'Declined', 'icon' => '✕'],
        ];

        return $map[$key] ?? ['class' => 'default', 'label' => ucfirst($key ?: 'Unknown'), 'icon' => '•'];
    }
}

$access_options = [
    'open' => [
        'dot'   => 'open',
        'title' => 'Open Enrollment',
        'desc'  => 'Eligible learners can enroll right away — no approval step.',
    ],
    'approval_required' => [
        'dot'   => 'approval',
        'title' => 'Approval Required',
        'desc'  => 'Enrollment requests go to instructors for review before access is granted.',
    ],
    'invitation_only' => [
        'dot'   => 'invite',
        'title' => 'Invitation Only',
        'desc'  => 'Only learners you invite can discover and join this course.',
    ],
    'hidden' => [
        'dot'   => 'hidden',
        'title' => 'Hidden',
        'desc'  => 'Completely hidden from the catalog. Direct links and invitations still work.',
    ],
];

$publish_pill_class = 'crs-p2-publish-pill--' . preg_replace('/[^a-z_]/', '', strtolower($publish_status));
$publish_label = function_exists('course_phase2_publish_label')
    ? course_phase2_publish_label($publish_status)
    : ucfirst($publish_status);
?>
<?php if ($phase2_ready): ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/manage_courses_phase2.css'); ?>">

<div class="crs-p2-stack">

  <?php if ( ! $hrmis_connection_ok): ?>
  <div class="crs-p2-alert crs-p2-alert--error">
    <span aria-hidden="true">⚠</span>
    <span>HRMIS Department data unavailable. You can still save this course — leave departments empty for organization-wide visibility.</span>
  </div>
  <?php elseif (empty($departments)): ?>
  <div class="crs-p2-alert crs-p2-alert--warn">
    <span aria-hidden="true">ℹ</span>
    <span>HRMIS connected but no departments were returned from <code>tbldepartment</code>. Leave empty for all employees.</span>
  </div>
  <?php endif; ?>

  <!-- SECTION A — Course Classification -->
  <section class="crs-p2-card" aria-labelledby="crs-p2-classification-title">
    <header class="crs-p2-card-hdr">
      <div class="crs-p2-card-icon" aria-hidden="true">🏷</div>
      <div>
        <h4 class="crs-p2-card-title" id="crs-p2-classification-title">Course Classification</h4>
        <p class="crs-p2-card-sub">Organize the course and define who can discover it across your organization.</p>
      </div>
    </header>
    <div class="crs-p2-card-body">
      <div class="crs-p2-field">
        <label class="crs-p2-label">Categories</label>
        <select name="category_ids[]" class="ka-select2" multiple data-select-kind="cat" data-placeholder="Add one or more categories…">
          <?php foreach ($categories as $cat): ?>
          <option value="<?= (int) $cat->id ?>" <?= in_array((int) $cat->id, $sel_categories, true) ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat->name) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <p class="crs-p2-help">Choose every topic this course belongs to. The first category is used as the primary catalog label.</p>
      </div>

      <div class="crs-p2-grid-2">
        <div class="crs-p2-field">
          <label class="crs-p2-label">Department visibility</label>
          <select name="department_ids[]" id="crs_p2_department_ids" class="ka-select2" multiple data-select-kind="dept" data-placeholder="Search departments…"<?= $dept_select_enabled ? '' : ' disabled' ?>>
            <?php foreach ($departments as $d): ?>
            <?php $dept_id = (int) (is_object($d) ? ($d->id ?? 0) : ($d['id'] ?? 0)); ?>
            <?php $dept_name = is_object($d) ? (string) ($d->name ?? '') : (string) ($d['name'] ?? ''); ?>
            <?php if ($dept_id < 1 || $dept_name === '') { continue; } ?>
            <option value="<?= $dept_id ?>" <?= in_array($dept_id, $sel_departments, true) ? 'selected' : '' ?>>
              <?= htmlspecialchars($dept_name, ENT_QUOTES, 'UTF-8') ?>
            </option>
            <?php endforeach; ?>
          </select>
          <p class="crs-p2-help">If no departments are selected, the course is visible to all employees.</p>
          <div id="crs_p2_dept_visibility_hint" class="crs-p2-dept-hint" aria-live="polite">
            <?php if (empty($sel_departments)): ?>
            <span class="crs-p2-dept-hint-all">🌐 Visible to all departments</span>
            <?php else: ?>
            <span class="crs-p2-dept-hint-restricted">Restricted to <?= count($sel_departments) ?> department<?= count($sel_departments) === 1 ? '' : 's' ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="crs-p2-field">
          <label class="crs-p2-label">Job title visibility</label>
          <select name="profession_ids[]" class="ka-select2" multiple data-select-kind="prof" data-placeholder="All job titles" <?= $hrmis_ready ? '' : 'disabled' ?>>
            <?php foreach ($professions as $p): ?>
            <option value="<?= (int) $p->id ?>" <?= in_array((int) $p->id, $sel_professions, true) ? 'selected' : '' ?>>
              <?= htmlspecialchars($p->name) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <p class="crs-p2-help">Restrict visibility by job title. Leave empty if every role should see this course.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION B — Teaching Team -->
  <section class="crs-p2-card" aria-labelledby="crs-p2-team-title">
    <header class="crs-p2-card-hdr">
      <div class="crs-p2-card-icon crs-p2-card-icon--team" aria-hidden="true">👥</div>
      <div>
        <h4 class="crs-p2-card-title" id="crs-p2-team-title">Teaching Team</h4>
        <p class="crs-p2-card-sub">Assign instructors who can manage content, review enrollments, and view analytics.</p>
      </div>
    </header>
    <div class="crs-p2-card-body">
      <div class="crs-p2-field">
        <label class="crs-p2-label">Instructors</label>
        <select name="instructor_ids[]" class="ka-select2" multiple data-select-kind="instructor" data-placeholder="Search instructors…">
          <?php foreach ($instructor_options as $t): ?>
          <?php
            $tid = (int) $t->id;
            $tname = (string) ($t->fullname ?? '');
            $tini = crs_p2_person_initials($tname);
            $tsub = ! empty($t->employee_id) ? (string) $t->employee_id : '';
          ?>
          <option value="<?= $tid ?>"
                  data-name="<?= htmlspecialchars($tname, ENT_QUOTES, 'UTF-8') ?>"
                  data-initials="<?= htmlspecialchars($tini, ENT_QUOTES, 'UTF-8') ?>"
                  data-sub="<?= htmlspecialchars($tsub, ENT_QUOTES, 'UTF-8') ?>"
                  <?= in_array($tid, $sel_instructors, true) ? 'selected' : '' ?>>
            <?= htmlspecialchars($tname) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <p class="crs-p2-help">The first instructor selected becomes the primary instructor for dashboards and notifications.</p>
        <div id="crs_p2_instructor_preview" class="crs-p2-instructor-preview" aria-live="polite"></div>
      </div>
    </div>
  </section>

  <!-- SECTION C — Access & Publishing -->
  <section class="crs-p2-card" aria-labelledby="crs-p2-access-title">
    <header class="crs-p2-card-hdr">
      <div class="crs-p2-card-icon crs-p2-card-icon--access" aria-hidden="true">🔐</div>
      <div>
        <h4 class="crs-p2-card-title" id="crs-p2-access-title">Access &amp; Publishing</h4>
        <p class="crs-p2-card-sub">Control how learners enroll and whether the course is live in the catalog.</p>
      </div>
    </header>
    <div class="crs-p2-card-body">
      <input type="hidden" name="access_type" id="crs_p2_access_type" value="<?= htmlspecialchars($access_type) ?>">

      <div class="crs-p2-field">
        <label class="crs-p2-label">Enrollment access</label>
        <div class="crs-p2-access-grid" role="radiogroup" aria-label="Enrollment access">
          <?php foreach (course_phase2_access_types() as $at): ?>
          <?php $meta = $access_options[$at] ?? ['dot' => 'hidden', 'title' => course_phase2_access_label($at), 'desc' => '']; ?>
          <label class="crs-p2-access-opt">
            <input type="radio" name="crs_p2_access_radio" value="<?= htmlspecialchars($at) ?>"
                   data-label="<?= htmlspecialchars($meta['title']) ?>"
                   <?= $access_type === $at ? 'checked' : '' ?>>
            <div class="crs-p2-access-card">
              <div class="crs-p2-access-head">
                <span class="crs-p2-access-dot crs-p2-access-dot--<?= htmlspecialchars($meta['dot']) ?>"></span>
                <span class="crs-p2-access-name"><?= htmlspecialchars($meta['title']) ?></span>
              </div>
              <p class="crs-p2-access-desc"><?= htmlspecialchars($meta['desc']) ?></p>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="crs-p2-field">
        <label class="crs-p2-label">Publish state</label>
        <?php if ( ! $is_edit): ?>
        <input type="hidden" name="publish_status" value="draft">
        <span class="crs-p2-publish-pill crs-p2-publish-pill--draft">Draft</span>
        <p class="crs-p2-help">New courses start as draft. Publish from the edit screen once modules and settings are ready.</p>
        <?php else: ?>
        <span class="crs-p2-publish-pill <?= htmlspecialchars($publish_pill_class) ?>"><?= htmlspecialchars($publish_label) ?></span>
        <p class="crs-p2-help">Use the <strong>Publish Workflow</strong> panel in the sidebar to publish or unpublish this course.</p>
        <?php endif; ?>
      </div>

      <div class="crs-p2-summary" aria-live="polite">
        <span class="crs-p2-summary-label">Visibility at a glance</span>
        <span class="crs-p2-summary-chip">Access: <strong data-summary-access><?= htmlspecialchars($access_options[$access_type]['title'] ?? $access_type) ?></strong></span>
        <span class="crs-p2-summary-chip crs-p2-summary-chip--publish">Status: <strong><?= htmlspecialchars($publish_label) ?></strong></span>
        <span class="crs-p2-summary-chip crs-p2-summary-chip--muted" data-summary-cat>No categories</span>
        <span class="crs-p2-summary-chip crs-p2-summary-chip--muted" data-summary-dept>All departments</span>
        <span class="crs-p2-summary-chip crs-p2-summary-chip--muted" data-summary-prof>All job titles</span>
      </div>
    </div>
  </section>

  <?php if ($is_edit): ?>
  <!-- SECTION D — Invitations -->
  <section class="crs-p2-card" aria-labelledby="crs-p2-invite-title">
    <header class="crs-p2-card-hdr">
      <div class="crs-p2-card-icon crs-p2-card-icon--invite" aria-hidden="true">✉</div>
      <div>
        <h4 class="crs-p2-card-title" id="crs-p2-invite-title">Invitations</h4>
        <p class="crs-p2-card-sub">Invite learners directly or by department — perfect for invitation-only courses.</p>
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

</div>

<script defer src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script defer src="<?= base_url('assets/js/manage_courses_phase2.js'); ?>"></script>
<?php else: ?>
<div class="crs-p2-alert crs-p2-alert--warn" style="margin-top:1rem;">
  Run <code>application/sql/migration_phase2_course_features.sql</code> to enable advanced course settings.
</div>
<?php endif; ?>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$sel_instructors = is_array($sel_instructors ?? null) ? $sel_instructors : [];
$instructor_options = is_array($instructor_options ?? null) ? $instructor_options : [];
?>
<section class="crs-p2-card" aria-labelledby="crs-p2-team-title">
  <header class="crs-p2-card-hdr">
    <div class="crs-p2-card-icon crs-p2-card-icon--team" aria-hidden="true">👥</div>
    <div>
      <h4 class="crs-p2-card-title" id="crs-p2-team-title">Teaching team</h4>
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

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$dashboard         = $dashboard ?? [];
$overviews         = $dashboard['overviews'] ?? [];
$most_missed       = $dashboard['most_missed'] ?? [];
$discrimination    = $dashboard['discrimination'] ?? [];
$filter_assessment = (int) ($filter_assessment ?? 0);
$generated_at      = htmlspecialchars($dashboard['generated_at'] ?? '', ENT_QUOTES, 'UTF-8');

$type_labels = ['pre' => 'Pre-Test', 'post' => 'Post-Test'];
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/assessments.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/assessment_integrity.css') ?>">

<div class="aint-workspace animate__animated animate__fadeIn animate__fast">
  <header class="aint-head">
    <div>
      <p class="aint-eyebrow">Assessments</p>
      <h1 class="aint-title">Integrity Analytics</h1>
      <p class="aint-subtitle">Question difficulty, discrimination, completion health, and version-aware attempt integrity for pre/post assessments.</p>
      <p class="aint-meta">Generated <?= $generated_at ?></p>
    </div>
    <form method="get" action="<?= site_url('assessments/integrity_analytics') ?>" class="aint-filter">
      <label class="visually-hidden" for="aintAssessmentFilter">Assessment</label>
      <select name="assessment_id" id="aintAssessmentFilter" class="aint-select" onchange="this.form.submit()">
        <option value="">All pre/post assessments</option>
        <?php foreach (($assessment_options ?? []) as $aid => $label): ?>
        <option value="<?= (int) $aid ?>" <?= $filter_assessment === (int) $aid ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      <a href="<?= site_url('assessments') ?>" class="aint-link-back">← Assessments</a>
    </form>
  </header>

  <?php if (empty($overviews)): ?>
  <div class="aint-empty">
    <p>No pre/post assessments with analytics data yet. Create assessments and collect learner attempts to populate this dashboard.</p>
  </div>
  <?php else: ?>

  <?php foreach ($overviews as $aid => $entry):
    $a       = $entry['assessment'];
    $m       = $entry['metrics'] ?? [];
    $health  = $entry['health'] ?? ['label' => '—', 'class' => '', 'score' => 0];
    $questions = $entry['questions'] ?? [];
    $tkey    = $a->type ?? '';
  ?>
  <section class="aint-card" id="assessment-<?= (int) $aid ?>">
    <div class="aint-card-head">
      <div>
        <h2 class="aint-card-title"><?= htmlspecialchars($a->title ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="aint-card-meta">
          <?= htmlspecialchars($type_labels[$tkey] ?? ucfirst($tkey), ENT_QUOTES, 'UTF-8') ?>
          · <?= htmlspecialchars($a->course_title ?? '', ENT_QUOTES, 'UTF-8') ?>
          <?php if ( ! empty($a->module_title)): ?>
          · <?= htmlspecialchars($a->module_title, ENT_QUOTES, 'UTF-8') ?>
          <?php endif; ?>
        </p>
      </div>
      <span class="aint-health <?= htmlspecialchars($health['class'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($health['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>
      </span>
    </div>

    <div class="aint-kpi-grid">
      <div class="aint-kpi">
        <span class="aint-kpi-lbl">Randomization</span>
        <span class="aint-kpi-val"><?= ! empty($m['randomize_enabled']) ? 'On' : 'Off' ?></span>
      </div>
      <div class="aint-kpi">
        <span class="aint-kpi-lbl">Content version</span>
        <span class="aint-kpi-val">v<?= (int) ($m['content_version'] ?? 1) ?></span>
      </div>
      <div class="aint-kpi">
        <span class="aint-kpi-lbl">Attempts started</span>
        <span class="aint-kpi-val"><?= (int) ($m['started'] ?? 0) ?></span>
      </div>
      <div class="aint-kpi">
        <span class="aint-kpi-lbl">Submitted</span>
        <span class="aint-kpi-val"><?= (int) ($m['submitted'] ?? 0) ?></span>
      </div>
      <div class="aint-kpi">
        <span class="aint-kpi-lbl">Retakes</span>
        <span class="aint-kpi-val"><?= (int) ($m['retakes'] ?? 0) ?></span>
      </div>
      <div class="aint-kpi">
        <span class="aint-kpi-lbl">Completion rate</span>
        <span class="aint-kpi-val"><?= number_format((float) ($m['completion_rate'] ?? 0), 1) ?>%</span>
      </div>
      <div class="aint-kpi">
        <span class="aint-kpi-lbl">Average score</span>
        <span class="aint-kpi-val"><?= number_format((float) ($m['average_score'] ?? 0), 1) ?>%</span>
      </div>
      <?php if ((int) ($m['legacy_attempts'] ?? 0) > 0): ?>
      <div class="aint-kpi aint-kpi--warn">
        <span class="aint-kpi-lbl">Legacy version attempts</span>
        <span class="aint-kpi-val"><?= (int) $m['legacy_attempts'] ?></span>
      </div>
      <?php endif; ?>
    </div>

    <?php if ( ! empty($questions)): ?>
    <h3 class="aint-section-title">Question analytics</h3>
    <div class="aint-table-wrap">
      <table class="aint-table">
        <thead>
          <tr>
            <th>Question</th>
            <th>Presented</th>
            <th>Correct</th>
            <th>Incorrect</th>
            <th>Difficulty</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($questions as $q): ?>
          <tr>
            <td class="aint-q-text"><?= htmlspecialchars(mb_strimwidth($q['question_text'] ?? '', 0, 120, '…'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int) ($q['times_presented'] ?? 0) ?></td>
            <td><?= number_format((float) ($q['correct_pct'] ?? 0), 1) ?>%</td>
            <td><?= number_format((float) ($q['incorrect_pct'] ?? 0), 1) ?>%</td>
            <td><span class="aint-diff aint-diff--<?= strtolower($q['difficulty'] ?? 'moderate') ?>"><?= htmlspecialchars($q['difficulty'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>
  <?php endforeach; ?>

  <?php if ( ! empty($most_missed)): ?>
  <section class="aint-card">
    <h2 class="aint-section-title">Most missed questions (top 10)</h2>
    <div class="aint-table-wrap">
      <table class="aint-table">
        <thead>
          <tr>
            <th>Question</th>
            <th>Assessment</th>
            <th>Failure rate</th>
            <th>Presented</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($most_missed as $row): ?>
          <tr>
            <td class="aint-q-text"><?= htmlspecialchars(mb_strimwidth($row['question_text'] ?? '', 0, 100, '…'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['assessment_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><strong><?= number_format((float) ($row['failure_pct'] ?? 0), 1) ?>%</strong></td>
            <td><?= (int) ($row['times_presented'] ?? 0) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( ! empty($discrimination)): ?>
  <section class="aint-card">
    <h2 class="aint-section-title">Question discrimination index</h2>
    <p class="aint-hint">High performers correct % minus low performers correct % (top/bottom 27% by assessment score).</p>
    <div class="aint-table-wrap">
      <table class="aint-table">
        <thead>
          <tr>
            <th>Question</th>
            <th>Assessment</th>
            <th>High %</th>
            <th>Low %</th>
            <th>Index</th>
            <th>Rating</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($discrimination, 0, 25) as $d): ?>
          <tr>
            <td class="aint-q-text"><?= htmlspecialchars(mb_strimwidth($d['question_text'] ?? '', 0, 90, '…'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($d['assessment_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= number_format((float) ($d['high_pct'] ?? 0), 1) ?>%</td>
            <td><?= number_format((float) ($d['low_pct'] ?? 0), 1) ?>%</td>
            <td><?= number_format((float) ($d['index'] ?? 0), 1) ?></td>
            <td><span class="aint-rating"><?= htmlspecialchars($d['rating'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>

  <?php endif; ?>
</div>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$ln = $report['learning_notes'] ?? [];
if (empty($ln['total_notes'])) {
    return;
}
?>
<section class="rpt-section" id="rpt-section-learning-notes" data-rpt-section="learning-notes" data-rpt-keywords="notes learning personal">
  <h2 class="rpt-section-title">Learning notes analytics</h2>
  <div class="rpt-two-col">
    <div>
      <h3 class="rpt-subtitle">Notes per course</h3>
      <ul class="rpt-simple-list">
        <?php foreach (($ln['notes_per_course'] ?? []) as $row): ?>
        <li>
          <span><?= htmlspecialchars($row->title ?? 'Course', ENT_QUOTES) ?></span>
          <strong><?= (int) ($row->note_count ?? 0) ?></strong>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h3 class="rpt-subtitle">Most active note-takers</h3>
      <ul class="rpt-simple-list">
        <?php foreach (($ln['top_note_takers'] ?? []) as $row): ?>
        <li>
          <span><?= htmlspecialchars($row->fullname ?? '', ENT_QUOTES) ?></span>
          <strong><?= (int) ($row->note_count ?? 0) ?> notes</strong>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$notes       = $notes ?? [];
$courses     = $courses ?? [];
$filter      = $filter ?? 'all';
$course_id   = (int) ($course_id ?? 0);
$search_q    = htmlspecialchars($search_q ?? '', ENT_QUOTES, 'UTF-8');
$table_ready = ! empty($table_ready);
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/learning_notes.css') ?>">

<div class="ln-dash animate__animated animate__fadeIn animate__fast">
  <header class="ln-dash-head">
    <div>
      <p class="ln-dash-eyebrow">Learning workspace</p>
      <h1 class="ln-dash-title">My Notes</h1>
      <p class="ln-dash-sub">Personal notes from your courses — private to you, synced across devices.</p>
    </div>
  </header>

  <?php if ( ! $table_ready): ?>
  <div class="ln-dash-alert">
    Learning notes storage is not ready. Ask your administrator to run <code>migration_learning_notes.sql</code>.
  </div>
  <?php endif; ?>

  <div class="ln-dash-filters">
    <form method="get" action="<?= site_url('learning_notes') ?>" class="ln-dash-filter-form">
      <input type="search" name="q" value="<?= $search_q ?>" class="ln-input" placeholder="Search title, content, tags…">
      <select name="filter" class="ln-select">
        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All notes</option>
        <option value="recent" <?= $filter === 'recent' ? 'selected' : '' ?>>Recent</option>
        <option value="favorites" <?= $filter === 'favorites' ? 'selected' : '' ?>>Favorites</option>
      </select>
      <select name="course_id" class="ln-select">
        <option value="0">All courses</option>
        <?php foreach ($courses as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $course_id === (int) $c['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['title'], ENT_QUOTES) ?> (<?= (int) $c['note_count'] ?>)
        </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="ln-btn ln-btn-primary">Apply</button>
    </form>
  </div>

  <?php if (empty($notes)): ?>
  <div class="ln-dash-empty">
    <p>No notes yet. Open a course module and use <strong>📝 My Notes</strong> on the right to capture learning insights.</p>
  </div>
  <?php else: ?>
  <div class="ln-dash-grid">
    <?php foreach ($notes as $n):
      $color = htmlspecialchars((string) ($n->color_label ?? ''), ENT_QUOTES);
      $card_class = 'ln-note-card';
      if ($color !== '') {
          $card_class .= ' ln-note-card--' . $color;
      }
    ?>
    <article class="<?= $card_class ?>">
      <div class="ln-note-card-head">
        <h2 class="ln-note-card-title"><?= htmlspecialchars($n->note_title ?: 'Untitled note', ENT_QUOTES) ?></h2>
        <div class="ln-note-card-badges">
          <?php if ((int) ($n->is_pinned ?? 0) === 1): ?><span class="ln-badge">Pinned</span><?php endif; ?>
          <?php if ((int) ($n->is_favorite ?? 0) === 1): ?><span class="ln-badge ln-badge--fav">★</span><?php endif; ?>
        </div>
      </div>
      <p class="ln-note-card-meta">
        <?= htmlspecialchars($n->course_title ?? '', ENT_QUOTES) ?>
        <?php if ( ! empty($n->module_title)): ?> · <?= htmlspecialchars($n->module_title, ENT_QUOTES) ?><?php endif; ?>
        <?php if ( ! empty($n->context_label)): ?> · <?= htmlspecialchars($n->context_label, ENT_QUOTES) ?><?php endif; ?>
      </p>
      <p class="ln-note-card-preview"><?= htmlspecialchars(mb_strimwidth(strip_tags((string) ($n->note_content ?? '')), 0, 200, '…'), ENT_QUOTES) ?></p>
      <p class="ln-note-card-date"><?= htmlspecialchars(date('M j, Y', strtotime($n->updated_at ?? $n->created_at ?? 'now')), ENT_QUOTES) ?></p>
      <?php if ( ! empty($n->return_url)): ?>
      <a href="<?= htmlspecialchars($n->return_url, ENT_QUOTES) ?>" class="ln-btn ln-btn-sm ln-btn-primary">Return to content</a>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

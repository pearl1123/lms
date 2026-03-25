<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$assessments = $assessments ?? [];
$user_role   = strtolower($user->role ?? 'employee');
$is_manager  = in_array($user_role, ['admin', 'teacher']);

$type_labels = ['pre' => 'Pre-Assessment', 'post' => 'Post-Assessment'];
$type_colors = ['pre' => '#3b82f6', 'post' => '#22c55e'];
?>
<?php $this->load->view('layouts/alerts'); ?>

<style>
.asx-topbar { display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem; }
.asx-topbar-left h2 { font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 2px;letter-spacing:-.02em; }
.asx-topbar-left p  { font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0; }
.asx-btn {
  display:inline-flex;align-items:center;gap:6px;
  padding:.5rem 1rem;border-radius:8px;font-size:.8125rem;
  font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:all .18s;
}
.asx-btn-primary { background:var(--ka-navy,#1a3a5c);color:#fff; }
.asx-btn-primary:hover { background:#254d75;color:#fff;transform:translateY(-1px); }

/* Stats */
.asx-stats { display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem; }
@media(max-width:767.98px){ .asx-stats { grid-template-columns:1fr; } }
.asx-stat {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:12px;padding:1rem 1.125rem;
  display:flex;align-items:center;gap:.875rem;
}
.asx-stat-icon { width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center; }
.asx-stat-icon svg { width:18px;height:18px; }
.asx-stat-val { font-size:1.5rem;font-weight:800;color:var(--ka-text,#1e293b);line-height:1; }
.asx-stat-lbl { font-size:.6875rem;color:var(--ka-text-muted,#64748b);font-weight:500;margin-top:2px; }

/* Filters */
.asx-filters {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:12px;padding:.875rem 1rem;
  display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem;
}
.asx-search-wrap { position:relative;flex:1;min-width:200px; }
.asx-search-icon { position:absolute;left:.75rem;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--ka-text-muted,#64748b);pointer-events:none; }
.asx-search { width:100%;height:36px;padding:0 .75rem 0 2.25rem;border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;font-size:.8125rem;background:var(--ka-bg,#f8fafc);outline:none;transition:all .2s; }
.asx-search:focus { border-color:var(--ka-primary,#6dabcf);background:#fff;box-shadow:0 0 0 3px rgba(109,171,207,.15); }
.asx-select { height:36px;padding:0 .75rem;border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;font-size:.8125rem;background:var(--ka-bg,#f8fafc);outline:none;cursor:pointer; }
.asx-select:focus { border-color:var(--ka-primary,#6dabcf); }

/* Cards */
.asx-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.125rem;margin-bottom:1.5rem; }
.asx-card {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;overflow:hidden;
  display:flex;flex-direction:column;
  transition:box-shadow .2s,transform .2s;
}
.asx-card:hover { box-shadow:0 8px 28px rgba(0,0,0,.09);transform:translateY(-3px); }
.asx-card-header {
  padding:1rem 1.125rem .75rem;
  border-bottom:1px solid var(--ka-border,#e2e8f0);
  display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;
}
.asx-card-type {
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 10px;border-radius:20px;font-size:.625rem;font-weight:700;
  text-transform:uppercase;letter-spacing:.06em;
}
.asx-card-actions { display:flex;gap:.375rem; }
.asx-card-action-btn {
  width:28px;height:28px;border-radius:7px;border:1.5px solid var(--ka-border,#e2e8f0);
  background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;
  color:var(--ka-text-muted,#64748b);transition:all .15s;text-decoration:none;
}
.asx-card-action-btn:hover { background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);border-color:var(--ka-primary,#6dabcf); }
.asx-card-action-btn.danger:hover { background:#fef2f2;color:#dc2626;border-color:#dc2626; }
.asx-card-action-btn svg { width:14px;height:14px; }
.asx-card-body { padding:1rem 1.125rem;flex:1; }
.asx-card-title { font-size:.9375rem;font-weight:700;color:var(--ka-text,#1e293b);margin-bottom:.375rem;line-height:1.3; }
.asx-card-course { font-size:.6875rem;color:var(--ka-primary,#6dabcf);font-weight:600;margin-bottom:.625rem; }
.asx-card-module { font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin-bottom:.875rem; }
.asx-card-meta { display:flex;align-items:center;gap:.875rem;flex-wrap:wrap; }
.asx-card-meta-item { display:flex;align-items:center;gap:4px;font-size:.6875rem;color:var(--ka-text-muted,#64748b); }
.asx-card-meta-item svg { width:13px;height:13px; }
.asx-card-footer {
  padding:.875rem 1.125rem;border-top:1px solid var(--ka-border,#e2e8f0);
  display:flex;align-items:center;gap:.5rem;
}
.asx-cta {
  flex:1;padding:.4375rem .5rem;border-radius:7px;text-align:center;
  font-size:.8125rem;font-weight:700;text-decoration:none;transition:all .15s;border:none;cursor:pointer;
}
.asx-cta-take     { background:var(--ka-navy,#1a3a5c);color:#fff; }
.asx-cta-take:hover { background:#254d75;color:#fff; }
.asx-cta-retake   { background:var(--ka-accent,#e8f4fd);color:var(--ka-primary-deep,#4a8eb0); }
.asx-cta-retake:hover { background:var(--ka-primary,#6dabcf);color:#fff; }
.asx-cta-review   { background:var(--ka-accent,#e8f4fd);color:var(--ka-primary-deep,#4a8eb0); }
.asx-cta-review:hover { background:var(--ka-primary,#6dabcf);color:#fff; }
.asx-cta-edit     { background:#fffbeb;color:#b45309; }
.asx-cta-edit:hover { background:#f59f00;color:#fff; }

/* Score chip */
.asx-score-chip {
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 10px;border-radius:20px;font-size:.6875rem;font-weight:700;
}
.asx-score-pass    { background:#ecfdf5;color:#065f46; }
.asx-score-fail    { background:#fef2f2;color:#991b1b; }
.asx-score-pending { background:#fffbeb;color:#92400e; }

/* Empty */
.asx-empty { text-align:center;padding:3rem 1rem;background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;grid-column:1/-1; }
.asx-empty svg { width:48px;height:48px;margin:0 auto .875rem;opacity:.2;display:block; }
.asx-empty h4 { font-size:1rem;font-weight:700;color:var(--ka-text,#1e293b);margin-bottom:.375rem; }
.asx-empty p  { font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin-bottom:1.25rem; }
</style>

<!-- ══ Top Bar ═══════════════════════════════════════════════ -->
<div class="asx-topbar animate__animated animate__fadeIn animate__fast">
  <div class="asx-topbar-left">
    <h2>Assessments</h2>
    <p><?= $is_manager ? 'Manage pre and post assessments for your course modules' : 'Your assigned assessments' ?></p>
  </div>
  <?php if ($is_manager): ?>
  <a href="<?= base_url('index.php/assessments/create') ?>" class="asx-btn asx-btn-primary">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Assessment
  </a>
  <?php endif; ?>
</div>

<!-- ══ Stats ════════════════════════════════════════════════ -->
<?php
$total    = count($assessments);
$pre_cnt  = count(array_filter($assessments, fn($a) => $a->type === 'pre'));
$post_cnt = count(array_filter($assessments, fn($a) => $a->type === 'post'));
?>
<div class="asx-stats animate__animated animate__fadeInUp animate__fast">
  <div class="asx-stat">
    <div class="asx-stat-icon" style="background:#eff6ff;color:#3b82f6;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    </div>
    <div><div class="asx-stat-val"><?= $total ?></div><div class="asx-stat-lbl">Total Assessments</div></div>
  </div>
  <div class="asx-stat">
    <div class="asx-stat-icon" style="background:#eff6ff;color:#3b82f6;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div><div class="asx-stat-val"><?= $pre_cnt ?></div><div class="asx-stat-lbl">Pre-Assessments</div></div>
  </div>
  <div class="asx-stat">
    <div class="asx-stat-icon" style="background:#ecfdf5;color:#22c55e;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
    </div>
    <div><div class="asx-stat-val"><?= $post_cnt ?></div><div class="asx-stat-lbl">Post-Assessments</div></div>
  </div>
</div>

<!-- ══ Filters ══════════════════════════════════════════════ -->
<div class="asx-filters animate__animated animate__fadeInUp animate__fast" style="animation-delay:.05s;">
  <div class="asx-search-wrap">
    <svg class="asx-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="asx-search" id="asxSearch" placeholder="Search assessments, courses, modules…">
  </div>
  <select class="asx-select" id="asxFilterType">
    <option value="">All Types</option>
    <option value="pre">Pre-Assessment</option>
    <option value="post">Post-Assessment</option>
  </select>
  <span style="font-size:.8125rem;font-weight:600;color:var(--ka-text-muted,#64748b);" id="asxCount"><?= $total ?> assessment<?= $total !== 1 ? 's' : '' ?></span>
</div>

<!-- ══ Assessment Grid ══════════════════════════════════════ -->
<div class="asx-grid animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s;" id="asxGrid">

  <?php if ( ! empty($assessments)): ?>
    <?php foreach ($assessments as $i => $a):
      $type_color = $type_colors[$a->type] ?? '#6dabcf';
      $type_label = $type_labels[$a->type] ?? ucfirst($a->type);
      $q_count    = (int)($a->question_count ?? 0);
    ?>
    <div class="asx-card animate__animated animate__fadeInUp"
         style="animation-delay:<?= ($i % 8) * 0.04 ?>s;"
         data-title="<?= htmlspecialchars(strtolower($a->title ?? '')) ?>"
         data-type="<?= $a->type ?>">

      <div class="asx-card-header">
        <span class="asx-card-type" style="background:<?= $type_color ?>22;color:<?= $type_color ?>;">
          <?= $type_label ?>
        </span>
        <?php if ($is_manager): ?>
        <div class="asx-card-actions">
          <a href="<?= base_url('index.php/assessments/edit/'.$a->id) ?>"
             class="asx-card-action-btn" title="Edit">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </a>
          <a href="<?= base_url('index.php/assessments/review/'.$a->id) ?>"
             class="asx-card-action-btn" title="Review Submissions">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </a>
          <button onclick="KA.deleteConfirm('<?= base_url('index.php/assessments/delete/'.$a->id) ?>', '<?= htmlspecialchars(addslashes($a->title ?? 'Assessment'), ENT_QUOTES) ?>')"
                  class="asx-card-action-btn danger" title="Delete">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
          </button>
        </div>
        <?php endif; ?>
      </div>

      <div class="asx-card-body">
        <div class="asx-card-title"><?= htmlspecialchars($a->title ?? 'Untitled Assessment') ?></div>
        <div class="asx-card-course"><?= htmlspecialchars($a->course_title ?? '—') ?></div>
        <div class="asx-card-module">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          <?= htmlspecialchars($a->module_title ?? '—') ?>
        </div>
        <div class="asx-card-meta">
          <div class="asx-card-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
            <?= $q_count ?> question<?= $q_count !== 1 ? 's' : '' ?>
          </div>
          <div class="asx-card-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <?= date('M j, Y', strtotime($a->created_at)) ?>
          </div>
        </div>

        <?php if ( ! $is_manager && isset($a->already_answered)): ?>
          <?php if ($a->already_answered && $a->result): ?>
            <?php
            $score   = $a->result['score'];
            $pending = $a->result['pending'];
            $chip_class = $pending > 0 ? 'asx-score-pending'
                        : ($score >= 75  ? 'asx-score-pass' : 'asx-score-fail');
            $chip_text  = $pending > 0
                        ? $pending . ' answer(s) pending review'
                        : number_format($score, 1) . '% score';
            ?>
            <div style="margin-top:.75rem;">
              <span class="asx-score-chip <?= $chip_class ?>">
                <?= $chip_text ?>
              </span>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <div class="asx-card-footer">
        <?php if ($is_manager): ?>
          <a href="<?= base_url('index.php/assessments/edit/'.$a->id) ?>"
             class="asx-cta asx-cta-edit">Edit Questions</a>
          <a href="<?= base_url('index.php/assessments/review/'.$a->id) ?>"
             class="asx-cta asx-cta-review">Review</a>
        <?php else: ?>
          <?php if (isset($a->already_answered) && $a->already_answered): ?>
            <a href="<?= base_url('index.php/assessments/result/'.$a->id) ?>"
               class="asx-cta asx-cta-retake">View Result</a>
          <?php else: ?>
            <a href="<?= base_url('index.php/assessments/take/'.$a->id) ?>"
               class="asx-cta asx-cta-take">Take Assessment</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

  <?php else: ?>
    <div class="asx-empty">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      <h4>No assessments yet</h4>
      <p><?= $is_manager ? 'Create your first assessment to get started.' : 'No assessments have been assigned to you yet.' ?></p>
      <?php if ($is_manager): ?>
        <a href="<?= base_url('index.php/assessments/create') ?>" class="asx-btn asx-btn-primary">Create Assessment</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const search  = document.getElementById('asxSearch');
  const typeEl  = document.getElementById('asxFilterType');
  const countEl = document.getElementById('asxCount');
  const cards   = document.querySelectorAll('.asx-card');

  function filter() {
    const kw   = search.value.toLowerCase().trim();
    const type = typeEl.value;
    let vis = 0;
    cards.forEach(function(c) {
      const title  = c.dataset.title  || '';
      const ctype  = c.dataset.type   || '';
      const show   = (!kw || title.includes(kw)) && (!type || ctype === type);
      c.style.display = show ? '' : 'none';
      if (show) vis++;
    });
    countEl.textContent = vis + ' assessment' + (vis !== 1 ? 's' : '');
  }

  search.addEventListener('input', filter);
  typeEl.addEventListener('change', filter);
});
</script>
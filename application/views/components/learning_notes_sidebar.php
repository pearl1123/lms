<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$ln_course_id  = (int) ($module->course_id ?? 0);
$ln_module_id  = (int) ($module->id ?? 0);
$ln_eff_type   = $eff_type ?? 'general';
$ln_map        = [
    'video'        => 'video',
    'pdf'          => 'pdf',
    'slides'       => 'slides',
    'audio'        => 'audio',
    'meeting_link' => 'general',
];
$ln_content_type = $ln_map[$ln_eff_type] ?? 'general';
?>
<?php if (empty($ln_notes_ready)) { return; } ?>
<div class="ln-notes-root" id="lnNotesRoot" aria-hidden="true">
  <div class="ln-notes-backdrop" id="lnNotesBackdrop"></div>
  <aside class="ln-notes-panel" id="lnNotesPanel" role="complementary" aria-label="My Notes">
    <header class="ln-notes-head">
      <h2 class="ln-notes-title">📝 My Notes</h2>
      <button type="button" class="ln-notes-close" id="lnNotesClose" aria-label="Close notes">&times;</button>
    </header>

    <div class="ln-notes-body">
      <section class="ln-notes-section">
        <h3 class="ln-notes-section-title">Quick Note</h3>
        <form id="lnNoteForm" class="ln-note-form" autocomplete="off">
          <input type="hidden" name="course_id" value="<?= $ln_course_id ?>">
          <input type="hidden" name="module_id" value="<?= $ln_module_id ?>">
          <input type="hidden" name="content_type" id="lnContentType" value="<?= htmlspecialchars($ln_content_type, ENT_QUOTES) ?>">
          <input type="hidden" name="timestamp_seconds" id="lnTimestampSeconds" value="">
          <input type="hidden" name="pdf_page" id="lnPdfPage" value="">
          <input type="hidden" name="slide_number" id="lnSlideNumber" value="">

          <label class="ln-label" for="lnNoteTitle">Title</label>
          <input type="text" class="ln-input" id="lnNoteTitle" name="note_title" maxlength="255" placeholder="Note title">

          <label class="ln-label" for="lnNoteContent">Note</label>
          <textarea class="ln-textarea" id="lnNoteContent" name="note_content" rows="4" placeholder="Write your learning note…" required></textarea>

          <p class="ln-context-hint" id="lnContextHint">Context will be captured when you save.</p>

          <label class="ln-label" for="lnNoteTags">Tags</label>
          <input type="text" class="ln-input" id="lnNoteTags" name="tags" placeholder="comma-separated">

          <div class="ln-color-row">
            <span class="ln-label">Label</span>
            <div class="ln-color-picks">
              <button type="button" class="ln-color-btn" data-color="" title="None">○</button>
              <button type="button" class="ln-color-btn ln-color-btn--blue" data-color="blue" title="Important">●</button>
              <button type="button" class="ln-color-btn ln-color-btn--yellow" data-color="yellow" title="Review later">●</button>
              <button type="button" class="ln-color-btn ln-color-btn--green" data-color="green" title="Key learning">●</button>
            </div>
            <input type="hidden" name="color_label" id="lnColorLabel" value="">
          </div>

          <div class="ln-form-actions">
            <button type="submit" class="ln-btn ln-btn-primary" id="lnSaveBtn">Save Note</button>
            <button type="button" class="ln-btn ln-btn-ghost" id="lnCancelBtn">Cancel</button>
          </div>
        </form>
      </section>

      <section class="ln-notes-section">
        <div class="ln-notes-toolbar">
          <h3 class="ln-notes-section-title">Saved Notes</h3>
          <input type="search" class="ln-search" id="lnSearch" placeholder="Search notes…" aria-label="Search notes">
        </div>
        <div class="ln-filter-row">
          <button type="button" class="ln-filter-chip is-active" data-filter="all">All</button>
          <button type="button" class="ln-filter-chip" data-filter="pinned">Pinned</button>
          <button type="button" class="ln-filter-chip" data-filter="favorites">Favorites</button>
        </div>
        <div class="ln-notes-list" id="lnNotesList">
          <p class="ln-empty">Loading notes…</p>
        </div>
      </section>
    </div>

    <footer class="ln-notes-foot">
      <a href="<?= site_url('learning_notes') ?>" class="ln-foot-link">Open My Notes workspace →</a>
    </footer>
  </aside>
</div>

<button type="button" class="ln-notes-tab" id="lnNotesTab" aria-expanded="false" aria-controls="lnNotesPanel">
  <span class="ln-notes-tab-icon">📝</span>
  <span class="ln-notes-tab-label">My Notes</span>
</button>

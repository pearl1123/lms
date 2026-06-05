<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$html = trim((string) ($notice_html ?? etd_f2f_notice_html()));
if ($html === '') {
    return;
}
$title = trim((string) ($notice_title ?? 'Face-to-Face training advisory'));
?>
<aside class="etd-f2f-notice" role="note" aria-labelledby="etd-f2f-notice-title">
  <div class="etd-f2f-notice-icon" aria-hidden="true">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
  </div>
  <div class="etd-f2f-notice-body">
    <h3 class="etd-f2f-notice-title" id="etd-f2f-notice-title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h3>
    <div class="etd-f2f-notice-content"><?= $html ?></div>
  </div>
</aside>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
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

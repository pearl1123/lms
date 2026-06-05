<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php include APPPATH . 'views/manage_courses/phase2_init.php'; ?>
<?php if ($phase2_ready): ?>
<?php $this->load->view('manage_courses/phase2_assets_links', get_defined_vars()); ?>

<div class="crs-p2-stack">

<?php $this->load->view('manage_courses/phase2_alerts_block', get_defined_vars()); ?>

<?php $this->load->view('manage_courses/phase2_block_categories', get_defined_vars()); ?>

<?php $this->load->view('manage_courses/phase2_block_visibility', get_defined_vars()); ?>

<?php $this->load->view('manage_courses/phase2_block_instructors', get_defined_vars()); ?>

<?php $this->load->view('manage_courses/phase2_block_access', get_defined_vars()); ?>

<?php $this->load->view('manage_courses/phase2_block_invitations', get_defined_vars()); ?>

</div>

<?php $this->load->view('manage_courses/phase2_scripts', get_defined_vars()); ?>
<?php else: ?>
<div class="crs-p2-alert crs-p2-alert--warn" style="margin-top:1rem;">
  Run <code>application/sql/migration_phase2_course_features.sql</code> to enable advanced course settings.
</div>
<?php endif; ?>

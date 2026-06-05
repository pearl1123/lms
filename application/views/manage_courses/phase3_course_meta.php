<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$phase3_ready          = ! empty($phase3_ready);
$phase3_batches_ready  = ! empty($phase3_batches_ready);
$certificate_signatories = is_array($certificate_signatories ?? null) ? $certificate_signatories : [];
$course_batches        = is_array($course_batches ?? null) ? $course_batches : [];
$is_edit_form          = ! empty($is_edit_form);
$show_phase3_panel     = $phase3_ready || ($is_edit_form && $phase3_batches_ready);
?>
<?php if ($show_phase3_panel): ?>
<?php $this->load->view('manage_courses/phase3_signatories_block', get_defined_vars()); ?>
<?php $this->load->view('manage_courses/phase3_batches_block', get_defined_vars()); ?>
<?php $this->load->view('manage_courses/phase3_clone_rows_script', get_defined_vars()); ?>
<?php endif; ?>

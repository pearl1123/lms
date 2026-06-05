<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/** @deprecated Loaded via assessments/edit.php + assessment_editor_shell */
$checkpoint_workspace = $checkpoint_workspace ?? null;
$assessment           = $assessment ?? null;
if ( ! $checkpoint_workspace || ! $assessment) return;
$this->load->view('assessments/checkpoint_workspace_content', get_defined_vars());

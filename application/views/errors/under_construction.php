<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/error_fallback.css'); ?>">
<?php $this->load->view('errors/fallback_card', get_defined_vars()); ?>

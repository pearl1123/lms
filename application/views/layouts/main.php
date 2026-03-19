<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| MAIN LAYOUT WRAPPER
|--------------------------------------------------------------------------
| This loads the global layout structure
| header → sidebar → navbar → page content → footer
|
| $view  = actual page view
| $data  = page data passed from controller
|
*/
?>

<?php $this->load->view('layouts/header'); ?>

<?php $this->load->view('layouts/sidebar'); ?>


    <?php $this->load->view('layouts/navbar'); ?>

    <!-- Page Content -->
    <main>
        <?php $this->load->view($view, isset($data) ? $data : []); ?>
    </main>

<?php $this->load->view('layouts/footer'); ?>
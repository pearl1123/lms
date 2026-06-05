<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Libraries hub — grouped admin modules (registry-driven).
 *
 * URL: index.php/libraries
 */
class Libraries_portal extends KA_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('library_nav');
        $this->require_permission('libraries.view', 'dashboard');
    }

    public function index()
    {
        $this->render('libraries/portal/index', [
            'page_title' => 'Libraries',
            'nav_groups' => ka_library_nav_groups(),
        ], [
            ['label' => 'Dashboard', 'url' => 'dashboard'],
            ['label' => 'Libraries'],
        ]);
    }
}

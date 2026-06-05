<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Extend CI session lifetime when the user chose "Remember me" on login.
 * Runs pre_system before Session library initializes.
 */
function ka_remember_session_lifetime()
{
    if (empty($_COOKIE['lms_remember']) || $_COOKIE['lms_remember'] !== '1') {
        return;
    }

    $cfg =& load_class('Config', 'core');
    $cfg->set_item('sess_expiration', 60 * 60 * 24 * 14);
}

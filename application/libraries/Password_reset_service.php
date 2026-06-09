<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loader shim — {@see Password_reset_service} in `application/services/Password_reset_service.php`.
 */
if ( ! class_exists('Password_reset_service', false)) {
    require_once APPPATH . 'services/Password_reset_service.php';
}

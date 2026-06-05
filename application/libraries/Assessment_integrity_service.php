<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loader shim — {@see Assessment_integrity_service} in application/services/.
 */
if ( ! class_exists('Assessment_integrity_service', false)) {
    require_once APPPATH . 'services/Assessment_integrity_service.php';
}

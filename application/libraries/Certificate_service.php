<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loader shim — {@see Certificate_service} in `application/services/Certificate_service.php`.
 */
if ( ! class_exists('Certificate_service', false)) {
    require_once APPPATH . 'services/Certificate_service.php';
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loader shim — {@see Audit_service} in `application/services/Audit_service.php`.
 */
if ( ! class_exists('Audit_service', false)) {
    require_once APPPATH . 'services/Audit_service.php';
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loader shim — real class: {@see Assessment_service} in `application/services/Assessment_service.php`.
 *
 * CodeIgniter only auto-loads libraries from this folder; the implementation lives under
 * `application/services/` next to controllers (domain layer, not HTTP controllers).
 */
if ( ! class_exists('Assessment_service', false)) {
    require_once APPPATH . 'services/Assessment_service.php';
}

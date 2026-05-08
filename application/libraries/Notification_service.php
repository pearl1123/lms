<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loader shim — {@see Notification_service} in `application/services/Notification_service.php`.
 */
if ( ! class_exists('Notification_service', false)) {
    require_once APPPATH . 'services/Notification_service.php';
}


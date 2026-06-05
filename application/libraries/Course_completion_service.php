<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loader shim — real class: {@see Course_completion_service} in
 * `application/services/Course_completion_service.php`.
 */
if ( ! class_exists('Course_completion_service', false)) {
    require_once APPPATH . 'services/Course_completion_service.php';
}

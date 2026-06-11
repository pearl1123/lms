<?php
/**
 * PHPUnit bootstrap for kaBAGA Academy LMS smoke tests.
 */

define('ENVIRONMENT', 'testing');
define('BASEPATH', realpath(__DIR__ . '/../system/') . DIRECTORY_SEPARATOR);
define('APPPATH', realpath(__DIR__ . '/../application/') . DIRECTORY_SEPARATOR);
define('FCPATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);
define('VIEWPATH', APPPATH . 'views/');

require_once BASEPATH . 'core/Common.php';

if (file_exists(APPPATH . 'config/constants.php')) {
    require APPPATH . 'config/constants.php';
}

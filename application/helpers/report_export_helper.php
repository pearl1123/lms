<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('report_export_log')) {
    /**
     * Append debug lines for report export troubleshooting (server + localhost).
     *
     * @param string               $message
     * @param array<string,mixed>  $context
     */
    function report_export_log($message, array $context = [])
    {
        $line = date('Y-m-d H:i:s') . ' [' . ($message) . ']';
        if ($context !== []) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $line .= PHP_EOL;

        $file = APPPATH . 'logs/report_export_debug.log';
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}

if ( ! function_exists('report_export_prepare_response')) {
    /**
     * Clear output buffers before streaming a download (prevents corrupt exports).
     */
    function report_export_prepare_response()
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
    }
}

if ( ! function_exists('ka_load_vendor_autoload')) {
    /**
     * Load Composer autoload on PHP 8.0 hosts without platform_check fatal errors.
     */
    function ka_load_vendor_autoload()
    {
        static $loaded = false;
        if ($loaded) {
            return true;
        }

        $autoload = FCPATH . 'vendor/autoload.php';
        if ( ! is_file($autoload)) {
            $manual = APPPATH . 'third_party/dompdf/autoload.inc.php';
            if (is_file($manual)) {
                require_once $manual;
                if ( ! class_exists('Dompdf\\Dompdf')) {
                    return false;
                }
                $loaded = true;

                return true;
            }

            return false;
        }

        if ( ! getenv('COMPOSER_DISABLE_PLATFORM_CHECK')) {
            putenv('COMPOSER_DISABLE_PLATFORM_CHECK=1');
            $_ENV['COMPOSER_DISABLE_PLATFORM_CHECK']    = '1';
            $_SERVER['COMPOSER_DISABLE_PLATFORM_CHECK'] = '1';
        }

        require_once $autoload;
        if ( ! class_exists('Dompdf\\Dompdf')) {
            return false;
        }
        $loaded = true;

        return true;
    }
}

if ( ! function_exists('report_export_dompdf_temp_dir')) {
    /**
     * Writable temp directory for DOMPDF (font/cache) on deployed hosts.
     */
    function report_export_dompdf_temp_dir()
    {
        $candidates = [
            APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'dompdf',
            APPPATH . 'cache',
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lms_dompdf',
        ];

        foreach ($candidates as $dir) {
            if ( ! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            if (is_dir($dir) && is_writable($dir)) {
                return $dir;
            }
        }

        return sys_get_temp_dir();
    }
}

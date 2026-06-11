<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI: php index.php cli/notification_emails/retry
 */
class Notification_emails extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        if ( ! is_cli()) {
            show_error('CLI only', 403);
        }
        $this->load->library('notification_service');
    }

    public function retry()
    {
        $sent = $this->notification_service->process_failed_email_retries(50);
        echo "Notification email retry complete. sent={$sent}\n";
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI: php index.php cli/backfill_cert_notification/run/{user_id}/{course_id}
 * Dispatches certificate.issued + course.completed for an existing certificate.
 */
class Backfill_cert_notification extends CI_Controller {

    public function run($user_id = 0, $course_id = 0)
    {
        if ( ! is_cli()) {
            show_404();
        }

        $uid = (int) $user_id;
        $cid = (int) $course_id;
        if ($uid < 1 || $cid < 1) {
            echo "Usage: php index.php cli/backfill_cert_notification/run/{user_id}/{course_id}\n";
            return;
        }

        $this->load->model('Certificate_model', 'certificate_model');
        $cert = $this->certificate_model->get_by_user_course($uid, $cid);
        if ( ! $cert) {
            echo "No active certificate for user={$uid} course={$cid}\n";
            return;
        }

        $this->load->library('event_dispatcher');
        $this->event_dispatcher->dispatch('certificate.issued', [
            'user_id'          => $uid,
            'course_id'        => $cid,
            'certificate_id'   => (int) $cert->id,
            'certificate_code' => (string) ($cert->certificate_code ?? ''),
        ]);
        $this->event_dispatcher->dispatch('course.completed', [
            'user_id'        => $uid,
            'course_id'      => $cid,
            'certificate_id' => (int) $cert->id,
        ]);

        echo "Dispatched notifications for cert id={$cert->id} code={$cert->certificate_code}\n";
    }
}

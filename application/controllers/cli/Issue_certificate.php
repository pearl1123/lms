<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI: php index.php cli/issue_certificate/run/{user_id}/{course_id}
 */
class Issue_certificate extends CI_Controller {

    public function run($user_id = 0, $course_id = 0)
    {
        if ( ! is_cli()) {
            show_404();
        }

        $uid = (int) $user_id;
        $cid = (int) $course_id;
        if ($uid < 1 || $cid < 1) {
            echo "Usage: php index.php cli/issue_certificate/run/{user_id}/{course_id}\n";
            return;
        }

        $this->load->library('certificate_service');
        $this->load->model('Certificate_model', 'certificate_model');

        $existing = $this->certificate_model->get_by_user_course($uid, $cid);
        if ($existing) {
            echo "Already issued id={$existing->id} code={$existing->certificate_code}\n";
            return;
        }

        $cert = $this->certificate_service->generate($uid, $cid);
        if ( ! $cert) {
            $check = $this->certificate_model->check_eligibility($uid, $cid);
            echo "Failed. eligible=" . (! empty($check['eligible']) ? '1' : '0')
                . " reason=" . ($check['reason'] ?? '') . "\n";
            echo json_encode($check['checks'] ?? [], JSON_PRETTY_PRINT) . "\n";
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

        echo "Issued id={$cert->id} code={$cert->certificate_code} file=" . ($cert->file_path ?? '') . "\n";
    }
}

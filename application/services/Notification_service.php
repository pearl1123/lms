<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'constants/Notification_types.php';

/**
 * Centralized notification event layer.
 *
 * Controllers should emit semantic events and avoid formatting payloads directly.
 */
class Notification_service {

    /**
     * @var CI_Controller&object{
     *   notification_model: Notification_model,
     *   course_model: Course_model,
     *   user_model: User_model,
     *   db: CI_DB_mysqli_driver
     * }
     */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Notification_model', 'notification_model');
        $this->CI->load->model('Course_model', 'course_model');
        $this->CI->load->model('User_model', 'user_model');
    }

    public function course_completed($user_id, $course_id, $certificate_id)
    {
        $uid = (int) $user_id;
        $cid = (int) $course_id;
        $cert_id = (int) $certificate_id;

        log_message('debug', 'NOTIFICATION EVENT: course_completed');

        if ($uid < 1 || $cert_id < 1) {
            return false;
        }

        return $this->send_database_once(
            [$uid],
            Notification_model::TYPE_SYSTEM,
            $cert_id,
            'Course completed — certificate ready',
            'You have successfully completed the course. Your certificate is ready.',
            0,
            Notification_types::CERTIFICATE,
            base_url('index.php/certificates/view/' . $cert_id)
        );
    }

    public function enrollment_requested($request_id, $user_id = 0, $course_id = 0)
    {
        $rid = (int) $request_id;
        $uid = (int) $user_id;
        $cid = (int) $course_id;
        log_message('debug', 'NOTIFICATION EVENT: enrollment_requested');
        if ($rid < 1) {
            return false;
        }

        $course_model = $this->CI->{'course_model'};
        $user_model   = $this->CI->{'user_model'};
        $db           = $this->CI->{'db'};
        $row = $course_model->get_enrollment_by_id($rid);
        if ( ! $row) {
            log_message('debug', 'NOTIFICATION SKIPPED: enrollment row not found ' . $rid);
            return false;
        }

        $uid = $uid > 0 ? $uid : (int) ($row->user_id ?? 0);
        $cid = $cid > 0 ? $cid : (int) ($row->course_id ?? 0);

        $course = $course_model->get_course_any($cid);
        $requester = $user_model->get_user($uid);
        $requester_name = ($requester && ! empty($requester->fullname)) ? (string) $requester->fullname : 'A learner';
        $course_title = ($course && ! empty($course->title)) ? (string) $course->title : 'a course';

        $recipient_map = [];
        $admin_result = $db
            ->select('id')
            ->from('aauth_users')
            ->where('DELETED', 0)
            ->where('status', 'active')
            ->where('role', 'admin')
            ->get();
        $mgr_rows = ($admin_result && $admin_result->num_rows() > 0) ? $admin_result->result() : [];
        foreach ($mgr_rows as $r) {
            $id = (int) ($r->id ?? 0);
            if ($id > 0) {
                $recipient_map[$id] = true;
            }
        }
        $this->CI->load->model('Course_phase2_model', 'course_phase2');
        if ($this->CI->course_phase2->schema_ready() && $cid > 0) {
            foreach ($this->CI->course_phase2->get_course_instructor_ids($cid) as $iid) {
                if ($iid > 0) {
                    $recipient_map[$iid] = true;
                }
            }
        }

        $recipient_ids = array_values(array_map('intval', array_keys($recipient_map)));
        if (empty($recipient_ids)) {
            log_message('debug', 'NOTIFICATION SKIPPED: no enrollment request recipients ' . json_encode([
                'request_id' => $rid,
                'user_id'    => $uid,
                'course_id'  => $cid,
            ]));
            return false;
        }

        return $this->send_database_once(
            $recipient_ids,
            Notification_model::TYPE_ENROLLMENT,
            $rid,
            'New Enrollment Request',
            $requester_name . ' has requested to enroll in the course: ' . $course_title . '.',
            $uid,
            Notification_types::REQUEST,
            base_url('index.php/enrollments/requests'),
            false
        );
    }

    /**
     * Notify instructors when a learner is auto-enrolled (open access).
     */
    public function enrollment_enrolled($enrollment_id, $user_id = 0, $course_id = 0)
    {
        $eid = (int) $enrollment_id;
        $uid = (int) $user_id;
        $cid = (int) $course_id;
        if ($eid < 1) {
            return false;
        }

        $course_model = $this->CI->{'course_model'};
        $user_model   = $this->CI->{'user_model'};
        $row = $course_model->get_enrollment_by_id($eid);
        if ( ! $row) {
            return false;
        }

        $uid = $uid > 0 ? $uid : (int) ($row->user_id ?? 0);
        $cid = $cid > 0 ? $cid : (int) ($row->course_id ?? 0);

        $course = $course_model->get_course_any($cid);
        $requester = $user_model->get_user($uid);
        $requester_name = ($requester && ! empty($requester->fullname)) ? (string) $requester->fullname : 'A learner';
        $course_title = ($course && ! empty($course->title)) ? (string) $course->title : 'a course';

        $recipient_map = [];
        $this->CI->load->model('Course_phase2_model', 'course_phase2');
        if ($this->CI->course_phase2->schema_ready() && $cid > 0) {
            foreach ($this->CI->course_phase2->get_course_instructor_ids($cid) as $iid) {
                if ($iid > 0) {
                    $recipient_map[$iid] = true;
                }
            }
        }

        $recipient_ids = array_values(array_map('intval', array_keys($recipient_map)));
        if (empty($recipient_ids)) {
            return false;
        }

        return $this->send_database_once(
            $recipient_ids,
            Notification_model::TYPE_ENROLLMENT,
            $eid,
            'New course enrollment',
            $requester_name . ' enrolled in ' . $course_title . '.',
            $uid,
            Notification_types::REQUEST,
            base_url('index.php/enrollments/requests'),
            false
        );
    }

    public function course_invitation_created($user_id, $course_id, $invitation_id = 0)
    {
        $uid = (int) $user_id;
        $cid = (int) $course_id;
        $iid = (int) $invitation_id;
        if ($uid < 1 || $cid < 1) {
            return false;
        }

        $course_model = $this->CI->{'course_model'};
        $course = $course_model->get_course_any($cid);
        $title = ($course && ! empty($course->title)) ? (string) $course->title : 'a course';
        $url = base_url('index.php/courses/accept_invitation/' . ($iid > 0 ? $iid : 0));
        if ($iid < 1) {
            $url = base_url('index.php/courses/view/' . $cid);
        }

        return $this->send_database_once(
            [$uid],
            Notification_model::TYPE_ENROLLMENT,
            $iid > 0 ? $iid : $cid,
            'Course invitation',
            'You are invited to join ' . $title . '.',
            0,
            Notification_types::REQUEST,
            $url,
            false
        );
    }

    public function enrollment_approved($user_id, $course_id)
    {
        $uid = (int) $user_id;
        $cid = (int) $course_id;
        log_message('debug', 'NOTIFICATION EVENT: enrollment_approved');
        if ($uid < 1 || $cid < 1) {
            return false;
        }

        $course_model = $this->CI->{'course_model'};
        $course = $course_model->get_course_any($cid);
        $title = (string) ($course->title ?? 'your course');

        return $this->send_database_once(
            [$uid],
            Notification_model::TYPE_ENROLLMENT,
            $cid,
            'Course Enrollment Approved',
            'You can now start your course: ' . $title,
            0,
            Notification_types::APPROVAL,
            base_url('index.php/my_courses')
        );
    }

    public function enrollment_rejected($user_id, $course_id)
    {
        $uid = (int) $user_id;
        $cid = (int) $course_id;
        log_message('debug', 'NOTIFICATION EVENT: enrollment_rejected');
        if ($uid < 1 || $cid < 1) {
            return false;
        }

        return $this->send_database_once(
            [$uid],
            Notification_model::TYPE_REMOVAL,
            $cid,
            'Course Enrollment Request Declined',
            'Your enrollment request was not approved.',
            0,
            Notification_types::REJECTION,
            base_url('index.php/my_courses')
        );
    }

    public function certificate_issued($user_id, $course_id, $certificate_id, $certificate_code = '')
    {
        $uid = (int) $user_id;
        $cid = (int) $course_id;
        $cert_id = (int) $certificate_id;
        log_message('debug', 'NOTIFICATION EVENT: certificate_issued');
        if ($uid < 1 || $cert_id < 1) {
            return false;
        }

        $course_model = $this->CI->{'course_model'};
        $course = $course_model->get_course_any($cid);
        $title = (string) ($course->title ?? '');
        $msg = 'Congratulations! Your certificate for <strong>'
            . htmlspecialchars($title)
            . '</strong> has been issued.';
        if ($certificate_code !== '') {
            $msg .= ' Certificate code: <strong>' . htmlspecialchars((string) $certificate_code) . '</strong>.';
        }

        return $this->send_database_once(
            [$uid],
            Notification_model::TYPE_SYSTEM,
            $cert_id,
            'Certificate Issued!',
            $msg,
            0,
            Notification_types::CERTIFICATE,
            base_url('index.php/certificates/view/' . $cert_id)
        );
    }

    public function certificate_revoked($user_id, $certificate_id, $course_title, $certificate_code, $remarks = '', $actor_id = 0)
    {
        $uid = (int) $user_id;
        $cert_id = (int) $certificate_id;
        log_message('debug', 'NOTIFICATION EVENT: certificate_revoked');
        if ($uid < 1 || $cert_id < 1) {
            return false;
        }

        $msg = 'Your certificate for <strong>'
            . htmlspecialchars((string) $course_title)
            . '</strong> (code: ' . htmlspecialchars((string) $certificate_code)
            . ') has been revoked.'
            . ($remarks ? ' Reason: ' . htmlspecialchars((string) $remarks) : '');

        return $this->send_database_once(
            [$uid],
            Notification_model::TYPE_REMOVAL,
            $cert_id,
            'Certificate Revoked',
            $msg,
            (int) $actor_id,
            Notification_types::SYSTEM,
            base_url('index.php/certificates/view/' . $cert_id)
        );
    }

    // Reserved extension points (future channels)
    // public function send_email(...) {}
    // public function send_push(...) {}
    // public function broadcast_websocket(...) {}

    private function send_database_once(array $user_ids, $type_id, $reference_id, $title, $message, $encoded_by, $type_key, $url, $dedupe = true)
    {
        $m = $this->CI->{'notification_model'};
        $sent_any = false;
        foreach ($user_ids as $uid) {
            $uid = (int) $uid;
            if ($uid < 1) continue;

            if ($dedupe) {
                $exists = $m->exists_for_user_reference_type($uid, (int) $reference_id, (int) $type_id);
                if ($exists) {
                    log_message('debug', 'NOTIFICATION SKIPPED (duplicate): ' . json_encode([
                        'user_id'      => $uid,
                        'reference_id' => (int) $reference_id,
                        'type_id'      => (int) $type_id,
                    ]));
                    continue;
                }
            }

            $id = $m->send([
                'type_id'      => (int) $type_id,
                'title'        => (string) $title,
                'message'      => (string) $message,
                'reference_id' => (int) $reference_id,
                'user_ids'     => [$uid],
                'encoded_by'   => (int) $encoded_by,
            ]);
            if ($id > 0) {
                $this->afterNotificationCreated([
                    'notification_id' => (int) $id,
                    'user_id'         => (int) $uid,
                    'type_id'         => (int) $type_id,
                    'type_key'        => (string) $type_key,
                    'reference_id'    => (int) $reference_id,
                    'url'             => (string) $url,
                ]);
                log_message('debug', 'NOTIFICATION SENT: ' . json_encode([
                    'notification_id' => (int) $id,
                    'user_id'         => (int) $uid,
                    'reference_id'    => (int) $reference_id,
                    'type_key'        => (string) $type_key,
                ]));
                $sent_any = true;
            }
        }

        return $sent_any;
    }

    /**
     * Realtime/channel extension point (reserved).
     *
     * @param array $notification
     * @return void
     */
    protected function afterNotificationCreated(array $notification)
    {
        // RESERVED FOR:
        // - websocket broadcast
        // - push notification
        // - email dispatch
    }
}


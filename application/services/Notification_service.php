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

        $sent = $this->send_database_once(
            [$uid],
            Notification_model::TYPE_ENROLLMENT,
            $cid,
            'Course Enrollment Approved',
            'You can now start your course: ' . $title,
            0,
            Notification_types::APPROVAL,
            base_url('index.php/my_courses')
        );

        if ($course && function_exists('etd_is_face_to_face_modality')
            && etd_is_face_to_face_modality($course->modality_name ?? '')) {
            $this->f2f_enrollment_confirmed($uid, $cid);
        }

        return $sent;
    }

    /**
     * Face-to-face enrollment confirmation with schedule/venue details.
     *
     * @param int $user_id
     * @param int $course_id
     */
    public function f2f_enrollment_confirmed($user_id, $course_id)
    {
        $uid = (int) $user_id;
        $cid = (int) $course_id;
        if ($uid < 1 || $cid < 1) {
            return false;
        }

        $course = $this->CI->course_model->get_course_any($cid);
        if ( ! $course) {
            return false;
        }

        $title = (string) ($course->title ?? 'your course');
        $parts = ['You are enrolled in the face-to-face course: ' . $title . '.'];
        if ( ! empty($course->schedule_date)) {
            $parts[] = 'Schedule: ' . (string) $course->schedule_date . '.';
        }
        if ( ! empty($course->venue)) {
            $parts[] = 'Venue: ' . (string) $course->venue . '.';
        }

        return $this->send_database_once(
            [$uid],
            Notification_model::TYPE_ENROLLMENT,
            $cid,
            'Face-to-face training enrollment',
            implode(' ', $parts),
            0,
            Notification_types::F2F,
            base_url('index.php/courses/view/' . $cid),
            false
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

    /**
     * Optional SMTP email (Settings → Notifications). Does not replace in-app notifications.
     *
     * @return array{ok:bool,message:string}
     */
    public function send_email($to, $subject, $html_body)
    {
        $this->CI->load->library('Email_service');
        if ( ! isset($this->CI->email_service)) {
            return ['ok' => false, 'message' => 'Email service unavailable.'];
        }

        return $this->CI->email_service->send($to, $subject, $html_body);
    }

    /**
     * @param string $template_key invite|approval|certificate|reminder|reassessment
     */
    public function send_email_template($to, $template_key, array $vars = [])
    {
        $this->CI->load->library('Email_service');
        if ( ! isset($this->CI->email_service)) {
            return ['ok' => false, 'message' => 'Email service unavailable.'];
        }

        return $this->CI->email_service->send_template($to, $template_key, $vars);
    }

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
                    'title'           => (string) $title,
                    'message'         => (string) $message,
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
        $this->dispatch_notification_email($notification);
    }

    /**
     * SMTP side-effect for in-app notifications (Settings toggles).
     *
     * @param array $notification
     */
    public function dispatch_notification_email(array $notification)
    {
        $uid = (int) ($notification['user_id'] ?? 0);
        if ($uid < 1) {
            return;
        }

        $map = $this->_email_template_for_notification($notification);
        if ($map === null) {
            return;
        }

        $this->CI->load->model('User_model', 'user_model');
        $this->CI->load->model('Settings_model', 'settings_model');
        $this->CI->load->model('Notification_email_log_model', 'notification_email_log_model');

        $toggle_key = $map['toggle'];
        if ($this->CI->settings_model->table_ready()) {
            $settings = $this->CI->settings_model->get_all_settings();
            $n        = is_array($settings['notifications'] ?? null) ? $settings['notifications'] : [];
            if (empty($n[$toggle_key]) || $n[$toggle_key] === '0') {
                $this->_log_email_attempt($notification, $map['template'], '', 'skipped', 'Toggle disabled: ' . $toggle_key);

                return;
            }
        }

        $to = $this->CI->user_model->resolve_notification_email($uid);
        if ($to === '') {
            $this->_log_email_attempt($notification, $map['template'], '', 'skipped', 'No deliverable email address');

            return;
        }

        $user = $this->CI->user_model->get_user($uid);
        $course_title = $map['course_title'];
        if ($course_title === '' && (int) ($notification['reference_id'] ?? 0) > 0) {
            $course_title = $this->_resolve_course_title_for_notification($notification);
        }

        $subject = (string) ($notification['title'] ?? 'kaBAGA Academy notification');
        $email_vars = [
            'subject'       => $subject,
            'learner_name'  => ($user && ! empty($user->fullname)) ? (string) $user->fullname : 'Learner',
            'course_title'  => $course_title !== '' ? $course_title : 'your course',
            'action_url'    => (string) ($notification['url'] ?? base_url()),
        ];

        if ($map['template'] === 'f2f' && (int) ($notification['reference_id'] ?? 0) > 0) {
            $f2f_course = $this->CI->course_model->get_course_any((int) $notification['reference_id']);
            if ($f2f_course) {
                $email_vars['schedule'] = (string) ($f2f_course->schedule_date ?? '');
                $email_vars['venue']    = (string) ($f2f_course->venue ?? '');
                if ($course_title === '') {
                    $email_vars['course_title'] = (string) ($f2f_course->title ?? 'your course');
                }
            }
        }

        $result = $this->send_email_template($to, $map['template'], $email_vars);

        $status = ! empty($result['ok']) ? 'sent' : 'failed';
        $this->_log_email_attempt(
            $notification,
            $map['template'],
            $to,
            $status,
            $status === 'sent' ? '' : (string) ($result['message'] ?? 'Send failed')
        );
    }

    /**
     * @param array $notification
     * @return array{template:string,toggle:string,course_title:string}|null
     */
    private function _email_template_for_notification(array $notification)
    {
        $type_key = (string) ($notification['type_key'] ?? '');
        $title    = strtolower((string) ($notification['title'] ?? ''));

        if ($type_key === Notification_types::CERTIFICATE) {
            return ['template' => 'certificate', 'toggle' => 'certificate_email', 'course_title' => ''];
        }

        if ($type_key === Notification_types::APPROVAL) {
            return ['template' => 'approval', 'toggle' => 'approval_email', 'course_title' => ''];
        }

        if ($type_key === Notification_types::REQUEST && strpos($title, 'invitation') !== false) {
            return ['template' => 'invite', 'toggle' => 'invite_email', 'course_title' => ''];
        }

        if ($type_key === Notification_types::F2F) {
            return ['template' => 'f2f', 'toggle' => 'approval_email', 'course_title' => ''];
        }

        if ($type_key === Notification_types::SYSTEM && strpos($title, 'completed') !== false) {
            return ['template' => 'certificate', 'toggle' => 'certificate_email', 'course_title' => ''];
        }

        return null;
    }

    /**
     * Retry failed notification emails (cron/CLI). Max 3 attempts per log row.
     *
     * @param int $limit
     * @return int Number of successfully resent emails
     */
    public function process_failed_email_retries($limit = 25)
    {
        $this->CI->load->model('Notification_email_log_model', 'notification_email_log_model');
        if ( ! $this->CI->notification_email_log_model->table_ready()) {
            return 0;
        }

        $this->CI->load->model('User_model', 'user_model');

        $rows = $this->CI->notification_email_log_model->get_failed_for_retry($limit);
        $sent = 0;

        foreach ($rows as $row) {
            $notification = [
                'notification_id' => (int) ($row->notification_id ?? 0),
                'user_id'         => (int) ($row->user_id ?? 0),
                'title'           => (string) ($row->subject ?? ''),
            ];

            $map = $this->_email_template_for_notification([
                'type_key' => $this->_infer_type_key_from_template((string) ($row->template_key ?? '')),
                'title'    => (string) ($row->subject ?? ''),
            ]);
            if ($map === null) {
                continue;
            }

            $to = (string) ($row->email_to ?? '');
            if ($to === '') {
                continue;
            }

            $user = $this->CI->user_model->get_user((int) ($row->user_id ?? 0));
            $result = $this->send_email_template($to, $map['template'], [
                'subject'      => (string) ($row->subject ?? 'kaBAGA Academy notification'),
                'learner_name' => ($user && ! empty($user->fullname)) ? (string) $user->fullname : 'Learner',
                'course_title' => $this->_resolve_course_title_for_notification($notification),
                'action_url'   => base_url('index.php/my_courses'),
            ]);

            $ok = ! empty($result['ok']);
            $this->CI->notification_email_log_model->mark_retry((int) $row->id, $ok, $ok ? '' : (string) ($result['message'] ?? ''));
            if ($ok) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @param string $template_key
     */
    private function _infer_type_key_from_template($template_key)
    {
        $map = [
            'invite'      => Notification_types::REQUEST,
            'approval'    => Notification_types::APPROVAL,
            'certificate' => Notification_types::CERTIFICATE,
            'f2f'         => Notification_types::F2F,
        ];

        return $map[$template_key] ?? Notification_types::SYSTEM;
    }

    /**
     * @param array $notification
     */
    private function _resolve_course_title_for_notification(array $notification)
    {
        $this->CI->load->model('Course_model', 'course_model');
        $ref = (int) ($notification['reference_id'] ?? 0);
        if ($ref < 1) {
            return '';
        }

        $course = $this->CI->course_model->get_course_any($ref);
        if ($course && ! empty($course->title)) {
            return (string) $course->title;
        }

        return '';
    }

    /**
     * @param array  $notification
     * @param string $template_key
     * @param string $email_to
     * @param string $status sent|failed|skipped
     * @param string $error
     */
    private function _log_email_attempt(array $notification, $template_key, $email_to, $status, $error = '')
    {
        if ( ! isset($this->CI->notification_email_log_model)) {
            $this->CI->load->model('Notification_email_log_model', 'notification_email_log_model');
        }

        if ( ! $this->CI->notification_email_log_model->table_ready()) {
            log_message('debug', 'EMAIL ' . strtoupper($status) . ': user=' . (int) ($notification['user_id'] ?? 0)
                . ' template=' . $template_key . ($error !== '' ? ' err=' . $error : ''));

            return;
        }

        $this->CI->notification_email_log_model->log([
            'notification_id' => (int) ($notification['notification_id'] ?? 0),
            'user_id'         => (int) ($notification['user_id'] ?? 0),
            'email_to'        => $email_to,
            'template_key'    => $template_key,
            'subject'         => (string) ($notification['title'] ?? ''),
            'status'          => $status,
            'error_message'   => $error !== '' ? $error : null,
        ]);
    }
}


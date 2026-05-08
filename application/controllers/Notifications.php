<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'constants/Notification_types.php';

/**
 * JSON notification endpoints for navbar badge/dropdown.
 */
class Notifications extends KA_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Notification_model', 'notification_model');
    }

    /** GET /notifications/unread_count */
    public function unread_count()
    {
        $uid = (int) $this->auth_user->id;
        $this->json([
            'count' => (int) $this->notification_model->get_unread_count($uid),
        ]);
    }

    /** GET /notifications/latest */
    public function latest()
    {
        $uid = (int) $this->auth_user->id;
        $rows = $this->notification_model->get_latest_for_user($uid, 10);

        $items = [];
        foreach ($rows as $n) {
            $type_key = $this->_type_key($n);
            $items[] = [
                'id'         => (int) ($n->notification_id ?? 0),
                'title'      => (string) ($n->title ?? ''),
                'message'    => (string) ($n->message ?? ''),
                'read'       => ((int) ($n->is_read ?? 0) === 1),
                'is_read'    => ((int) ($n->is_read ?? 0) === 1),
                'created_at' => (string) ($n->date_encoded ?? ''),
                'url'        => $this->_notification_url($n, $type_key),
                'type_key'   => $type_key,
            ];
        }

        $this->json(['items' => $items]);
    }

    /** POST /notifications/mark_read/{id} */
    public function mark_read($id = 0)
    {
        if (strtolower($this->input->method(true)) !== 'post') {
            return $this->json(['success' => false, 'message' => 'Invalid request method.']);
        }

        $nid = (int) $id;
        if ($nid < 1) {
            return $this->json(['success' => false, 'message' => 'Invalid notification.']);
        }

        $uid = (int) $this->auth_user->id;
        $ok = $this->notification_model->mark_read_by_notification($uid, $nid);

        $this->json([
            'success' => (bool) $ok,
            'count'   => (int) $this->notification_model->get_unread_count($uid),
        ]);
    }

    /**
     * Resolve click URL for dropdown items.
     *
     * @param object $n Row from notification_model::get_all()
     * @return string
     */
    private function _notification_url($n, $type_key = Notification_types::SYSTEM)
    {
        $ref = (int) ($n->reference_id ?? 0);

        if ($type_key === Notification_types::CERTIFICATE && $ref > 0) {
            return base_url('index.php/certificates/view/' . $ref);
        }
        if ($type_key === Notification_types::REQUEST) {
            return base_url('index.php/enrollments/requests');
        }
        if (($type_key === Notification_types::APPROVAL || $type_key === Notification_types::REJECTION)) {
            return base_url('index.php/my_courses');
        }
        if ($ref > 0) {
            return base_url('index.php/courses/view/' . $ref);
        }

        return base_url('index.php/announcements');
    }

    /**
     * Deterministic type key for frontend mapping.
     *
     * @param object $n
     * @return string certificate|approval|rejection|request|system
     */
    private function _type_key($n)
    {
        $title = strtolower((string) ($n->title ?? ''));
        $type_id = (int) ($n->type_id ?? 0);
        if ($type_id === (int) Notification_model::TYPE_ENROLLMENT) {
            return Notification_types::APPROVAL;
        }
        if ($type_id === (int) Notification_model::TYPE_COURSE_UPDATE && strpos($title, 'request') !== false) {
            return Notification_types::REQUEST;
        }
        if ($type_id === (int) Notification_model::TYPE_REMOVAL
            && (strpos($title, 'declined') !== false || strpos($title, 'rejected') !== false)) {
            return Notification_types::REJECTION;
        }
        if (strpos($title, 'certificate') !== false) {
            return Notification_types::CERTIFICATE;
        }
        if (strpos($title, 'approved') !== false) {
            return Notification_types::APPROVAL;
        }
        if (strpos($title, 'declined') !== false || strpos($title, 'rejected') !== false) {
            return Notification_types::REJECTION;
        }
        if (strpos($title, 'request') !== false) {
            return Notification_types::REQUEST;
        }

        return Notification_types::SYSTEM;
    }
}


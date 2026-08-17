<?php
defined('BASEPATH') OR exit('No direct script access allowed');
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
            $type_key = $this->notification_model->type_key_for_row($n);
            $items[] = [
                'id'         => (int) ($n->notification_id ?? 0),
                'title'      => (string) ($n->title ?? ''),
                'message'    => (string) ($n->message ?? ''),
                'read'       => ((int) ($n->is_read ?? 0) === 1),
                'is_read'    => ((int) ($n->is_read ?? 0) === 1),
                'created_at' => (string) ($n->date_encoded ?? ''),
                'url'        => $this->notification_model->action_url_for_row($n),
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

}


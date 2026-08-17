<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Announcements
 *
 * Lists in–app notifications (announcements) for the logged‑in user
 * using the lib_notification / lib_user_notification tables.
 *
 * @property Notification_model $notification_model
 */
class Announcements extends KA_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Notification_model', 'notification_model');

        $this->require_permission('announcements.view');
    }

    /**
     * Show all notifications for the current user.
     */
    public function index()
    {
        $page   = max(1, (int) $this->input->get('page'));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $user_id = (int) $this->auth_user->id;

        $notifications = $this->notification_model->get_all($user_id, $limit, $offset);
        $notifications = $this->notification_model->enrich_list_action_meta($notifications);
        $unread_count = $this->notification_model->count_unread($user_id);

        $data = [
            'user'           => $this->auth_user,
            'page_title'     => 'Announcements',
            'notifications'  => $notifications,
            'unread_count'   => $unread_count,
            'current_page'   => $page,
            'per_page'       => $limit,
            'breadcrumbs'    => [['label' => 'Announcements']],
            'view'           => 'announcements/index',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    /**
     * Mark a single user_notification row as read.
     *
     * URI: /announcements/mark_read/{user_notification_id}
     */
    public function mark_read($user_notification_id = 0)
    {
        $user_notification_id = (int) $user_notification_id;
        $user_id = (int) $this->auth_user->id;

        if ($user_notification_id > 0 && $user_id > 0) {
            $this->notification_model->mark_read($user_notification_id, $user_id);
        }

        redirect('announcements');
    }

    /**
     * Mark as read, then go to the notification destination (enrollment queue, course, etc.).
     *
     * URI: /announcements/open/{user_notification_id}
     */
    public function open($user_notification_id = 0)
    {
        $user_notification_id = (int) $user_notification_id;
        $user_id = (int) $this->auth_user->id;
        $fallback = base_url('index.php/announcements');

        if ($user_notification_id < 1 || $user_id < 1) {
            redirect('announcements');

            return;
        }

        $row = $this->notification_model->get_user_notification($user_notification_id, $user_id);
        if ( ! $row) {
            redirect('announcements');

            return;
        }

        $this->notification_model->mark_read($user_notification_id, $user_id);
        if ( ! $this->notification_model->is_action_available_for_row($row)) {
            redirect('announcements');

            return;
        }

        $url = $this->notification_model->action_url_for_row($row);
        if ($url === '' || $url === $fallback) {
            redirect('announcements');

            return;
        }

        redirect($url);
    }

    /**
     * Mark all unread notifications as read for the current user.
     */
    public function mark_all_read()
    {
        $user_id = (int) $this->auth_user->id;
        if ($user_id > 0) {
            $this->notification_model->mark_all_read($user_id);
        }
        redirect('announcements');
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Announcements
 *
 * Lists in–app notifications (announcements) for the logged‑in user
 * using the lib_notification / lib_user_notification tables.
 *
 * @property CI_DB_mysqli_driver $db
 * @property CI_Session          $session
 * @property CI_Input            $input
 * @property CI_Pagination       $pagination
 * @property notification_model  $notification_model
 */
class Announcements extends CI_Controller
{
    /** @var object|null */
    private $user;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->helper(['url']);
        $this->load->model('Notification_model', 'notification_model');

        // Require login (same pattern as Dashboard)
        $user_id = $this->session->userdata('user_id');
        if (! $user_id) {
            redirect('auth/login');
        }

        $this->user = $this->session->userdata('user') ?: null;
        if (! $this->user) {
            // Fallback to a simple user object if session('user') is not set
            $this->user = (object)[
                'id'       => $user_id,
                'fullname' => '',
                'role'     => 'employee',
            ];
        }
    }

    /**
     * Show all notifications for the current user.
     */
    public function index()
    {
        $page   = max(1, (int) $this->input->get('page'));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $user_id = (int) ($this->user->id ?? $this->session->userdata('user_id'));

        // Fetch notifications from lib_user_notification + lib_notification
        $notifications = $this->notification_model->get_all($user_id, $limit, $offset);

        // Simple unread count for the badge / header
        $unread_count = $this->notification_model->count_unread($user_id);

        $data = [
            'user'           => $this->user,
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
        $user_id = (int) ($this->user->id ?? $this->session->userdata('user_id'));

        if ($user_notification_id > 0 && $user_id > 0) {
            $this->notification_model->mark_read($user_notification_id, $user_id);
        }

        redirect('announcements');
    }

    /**
     * Mark all unread notifications as read for the current user.
     */
    public function mark_all_read()
    {
        $user_id = (int) ($this->user->id ?? $this->session->userdata('user_id'));
        if ($user_id > 0) {
            $this->notification_model->mark_all_read($user_id);
        }
        redirect('announcements');
    }
}


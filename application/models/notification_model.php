<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * notification_model
 *
 * Uses your existing notification tables:
 * ─────────────────────────────────────────────────────────────
 * lib_notification_type
 *   notification_type_id, notification_type_desc, archived
 *
 * lib_notification
 *   notification_id, notification_type_id, notification_title,
 *   notification_message, reference_id (= course_id),
 *   date_encoded, encoded_by, archived
 *
 * lib_user_notification
 *   user_notification_id, notification_id, user_id,
 *   is_read, date_read, date_encoded, archived
 *
 * lib_notification_channel
 *   channel_id, channel_name, channel_desc, is_active
 *
 * lib_notification_channel_link
 *   notification_channel_link_id, notification_id,
 *   channel_id, status, date_sent
 *
 * Notification Types (seed these in lib_notification_type):
 *   1 = Enrollment        (user enrolled in a course)
 *   2 = Removal           (user removed from a course)
 *   3 = System            (general system notification)
 *   4 = Deadline Warning  (enrollment deadline approaching)
 *   5 = Course Update     (course details changed)
 *
 * @property CI_DB_mysqli_driver $db
 */
class notification_model extends CI_Model {

    // ── Notification type IDs ─────────────────────────────────
    // Match these to your lib_notification_type table values.
    // Update if your IDs differ.
    const TYPE_ENROLLMENT       = 1;
    const TYPE_REMOVAL          = 2;
    const TYPE_SYSTEM           = 3;
    const TYPE_DEADLINE_WARNING = 4;
    const TYPE_COURSE_UPDATE    = 5;

    // ── In-App channel ID ─────────────────────────────────────
    // The channel_id in lib_notification_channel for "In-App".
    const CHANNEL_IN_APP = 1;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // =========================================================
    // SEND NOTIFICATIONS
    // =========================================================

    /**
     * Send a notification to one or more users.
     *
     * @param  array  $data
     *   - type_id       int     (use class constants above)
     *   - title         string
     *   - message       string  (may contain HTML)
     *   - reference_id  int     optional — course_id or other entity ID
     *   - user_ids      int[]   recipient user IDs
     *   - encoded_by    int     sender/system user ID
     *   - channels      int[]   channel IDs (default: [CHANNEL_IN_APP])
     * @return int  notification_id created
     */
    public function send($data)
    {
        $now = date('Y-m-d H:i:s');

        // ── 1. Insert into lib_notification ───────────────────
        $this->db->insert('lib_notification', [
            'notification_type_id'  => (int) $data['type_id'],
            'notification_title'    => trim($data['title']),
            'notification_message'  => $data['message'],
            'reference_id'          => ! empty($data['reference_id']) ? (int) $data['reference_id'] : null,
            'date_encoded'          => $now,
            'encoded_by'            => (int) ($data['encoded_by'] ?? 0),
            'archived'              => 0,
        ]);
        $notification_id = (int) $this->db->insert_id();

        if ($notification_id === 0) return 0;

        // ── 2. Link to users via lib_user_notification ────────
        $user_ids = (array) ($data['user_ids'] ?? []);
        foreach ($user_ids as $uid) {
            $this->db->insert('lib_user_notification', [
                'notification_id' => $notification_id,
                'user_id'         => (int) $uid,
                'is_read'         => 0,
                'date_encoded'    => $now,
                'encoded_by'      => (int) ($data['encoded_by'] ?? 0),
                'archived'        => 0,
            ]);
        }

        // ── 3. Link to channels via lib_notification_channel_link
        $channels = ! empty($data['channels'])
            ? (array) $data['channels']
            : [self::CHANNEL_IN_APP];

        foreach ($channels as $channel_id) {
            $this->db->insert('lib_notification_channel_link', [
                'notification_id' => $notification_id,
                'channel_id'      => (int) $channel_id,
                'status'          => 'sent',
                'date_sent'       => $now,
            ]);
        }

        return $notification_id;
    }

    // =========================================================
    // LMS-SPECIFIC HELPERS
    // =========================================================

    /**
     * Notify a user that they were removed from a course.
     *
     * @param  int    $user_id     The removed student
     * @param  int    $course_id
     * @param  string $course_title
     * @param  int    $removed_by  Admin/teacher user ID
     * @param  string $reason      Optional reason
     * @return int    notification_id
     */
    public function notify_removal($user_id, $course_id, $course_title, $removed_by, $reason = '')
    {
        $msg = 'You have been removed from the course <strong>'
             . htmlspecialchars($course_title) . '</strong>.';

        if ($reason !== '') {
            $msg .= ' <em>Reason: ' . htmlspecialchars($reason) . '</em>';
        }

        return $this->send([
            'type_id'      => self::TYPE_REMOVAL,
            'title'        => 'Removed from Course',
            'message'      => $msg,
            'reference_id' => $course_id,
            'user_ids'     => [$user_id],
            'encoded_by'   => $removed_by,
        ]);
    }

    /**
     * Notify a user that they successfully enrolled in a course.
     *
     * @param  int    $user_id
     * @param  int    $course_id
     * @param  string $course_title
     * @return int
     */
    public function notify_enrollment($user_id, $course_id, $course_title)
    {
        return $this->send([
            'type_id'      => self::TYPE_ENROLLMENT,
            'title'        => 'Enrolled Successfully',
            'message'      => 'You have been enrolled in <strong>'
                            . htmlspecialchars($course_title) . '</strong>. Good luck!',
            'reference_id' => $course_id,
            'user_ids'     => [$user_id],
            'encoded_by'   => $user_id,
        ]);
    }

    /**
     * Send a deadline warning to all non-enrolled users for a course.
     * Called when X days remain before enrollment_deadline.
     *
     * @param  int    $course_id
     * @param  string $course_title
     * @param  int    $days_left
     * @param  int[]  $user_ids    Users to warn
     * @return int
     */
    public function notify_deadline_warning($course_id, $course_title, $days_left, $user_ids)
    {
        if (empty($user_ids)) return 0;

        $day_str = $days_left === 1 ? '1 day' : "{$days_left} days";

        return $this->send([
            'type_id'      => self::TYPE_DEADLINE_WARNING,
            'title'        => 'Enrollment Closing Soon',
            'message'      => "Enrollment for <strong>" . htmlspecialchars($course_title)
                            . "</strong> closes in <strong>{$day_str}</strong>. Enroll now before it's too late!",
            'reference_id' => $course_id,
            'user_ids'     => $user_ids,
            'encoded_by'   => 0,
        ]);
    }

    // =========================================================
    // FETCH NOTIFICATIONS (for a user)
    // =========================================================

    /**
     * Get all unread notifications for a user.
     *
     * @param  int $user_id
     * @return object[]
     */
    public function get_unread($user_id)
    {
        $r = $this->db
            ->select('
                un.user_notification_id,
                un.is_read,
                un.date_encoded,
                n.notification_id,
                n.notification_title   AS title,
                n.notification_message AS message,
                n.reference_id,
                n.notification_type_id AS type_id,
                nt.notification_type_desc AS type_name,
                c.id AS course_id
            ', false)
            ->from('lib_user_notification un')
            ->join('lib_notification n',
                   'n.notification_id = un.notification_id', 'left')
            ->join('lib_notification_type nt',
                   'nt.notification_type_id = n.notification_type_id', 'left')
            ->join('courses c', 'c.id = n.reference_id AND c.archived = 0', 'left')
            ->where('un.user_id',  (int) $user_id)
            ->where('un.is_read',  0)
            ->where('un.archived', 0)
            ->where('n.archived',  0)
            ->order_by('un.date_encoded', 'DESC')
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * Get all notifications for a user (read + unread), paginated.
     *
     * @param  int $user_id
     * @param  int $limit
     * @param  int $offset
     * @return object[]
     */
    public function get_all($user_id, $limit = 20, $offset = 0)
    {
        $r = $this->db
            ->select('
                un.user_notification_id,
                un.is_read,
                un.date_read,
                un.date_encoded,
                n.notification_id,
                n.notification_title   AS title,
                n.notification_message AS message,
                n.reference_id,
                n.notification_type_id AS type_id,
                nt.notification_type_desc AS type_name,
                c.id AS course_id
            ', false)
            ->from('lib_user_notification un')
            ->join('lib_notification n',
                   'n.notification_id = un.notification_id', 'left')
            ->join('lib_notification_type nt',
                   'nt.notification_type_id = n.notification_type_id', 'left')
            ->join('courses c', 'c.id = n.reference_id AND c.archived = 0', 'left')
            ->where('un.user_id',  (int) $user_id)
            ->where('un.archived', 0)
            ->where('n.archived',  0)
            ->order_by('un.date_encoded', 'DESC')
            ->limit((int) $limit, (int) $offset)
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * Count unread notifications for a user.
     * Used for the navbar badge.
     *
     * @param  int $user_id
     * @return int
     */
    public function count_unread($user_id)
    {
        return (int) $this->db
            ->from('lib_user_notification un')
            ->join('lib_notification n',
                   'n.notification_id = un.notification_id', 'left')
            ->where('un.user_id',  (int) $user_id)
            ->where('un.is_read',  0)
            ->where('un.archived', 0)
            ->where('n.archived',  0)
            ->count_all_results();
    }

    // =========================================================
    // MARK AS READ
    // =========================================================

    /**
     * Mark a single notification as read.
     *
     * @param  int $user_notification_id
     * @param  int $user_id  Safety check
     * @return bool
     */
    public function mark_read($user_notification_id, $user_id)
    {
        return (bool) $this->db
            ->where('user_notification_id', (int) $user_notification_id)
            ->where('user_id', (int) $user_id)
            ->update('lib_user_notification', [
                'is_read'            => 1,
                'date_read'          => date('Y-m-d H:i:s'),
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $user_id,
            ]);
    }

    /**
     * Mark ALL unread notifications as read for a user.
     *
     * @param  int $user_id
     * @return bool
     */
    public function mark_all_read($user_id)
    {
        return (bool) $this->db
            ->where('user_id', (int) $user_id)
            ->where('is_read', 0)
            ->update('lib_user_notification', [
                'is_read'            => 1,
                'date_read'          => date('Y-m-d H:i:s'),
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $user_id,
            ]);
    }

    /**
     * Fetch unread notifications AND mark them read in one call.
     * Used on the dashboard login check.
     *
     * @param  int $user_id
     * @return object[]
     */
    public function get_and_mark_read($user_id)
    {
        $notifications = $this->get_unread($user_id);
        if ( ! empty($notifications)) {
            $this->mark_all_read($user_id);
        }
        return $notifications;
    }

    // =========================================================
    // NOTIFICATION TYPES & CHANNELS (for dropdowns/config)
    // =========================================================

    /** Get all active notification types. */
    public function get_types()
    {
        $r = $this->db
            ->where('archived', 0)
            ->order_by('notification_type_id', 'ASC')
            ->get('lib_notification_type');

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /** Get all active channels. */
    public function get_channels()
    {
        $r = $this->db
            ->where('is_active', 1)
            ->order_by('channel_id', 'ASC')
            ->get('lib_notification_channel');

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }
}
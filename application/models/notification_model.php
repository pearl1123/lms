<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'constants/Notification_types.php';
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

    /**
     * Check if a user already has a notification for a reference + type.
     * Used for idempotent one-time events (e.g., certificate issued).
     *
     * @param int $user_id
     * @param int $reference_id
     * @param int $type_id
     * @return bool
     */
    public function exists_for_user_reference_type($user_id, $reference_id, $type_id)
    {
        $q = $this->db
            ->from('lib_user_notification un')
            ->join('lib_notification n', 'n.notification_id = un.notification_id', 'inner')
            ->where('un.user_id', (int) $user_id)
            ->where('un.archived', 0)
            ->where('n.archived', 0)
            ->where('n.reference_id', (int) $reference_id)
            ->where('n.notification_type_id', (int) $type_id)
            ->limit(1)
            ->get();

        return ($q && $q->num_rows() > 0);
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

    /** Backward-compatible alias for API endpoints. */
    public function get_unread_count($user_id)
    {
        return $this->count_unread((int) $user_id);
    }

    /**
     * Latest notifications for dropdown/panel.
     *
     * @param int $user_id
     * @param int $limit
     * @return object[]
     */
    public function get_latest_for_user($user_id, $limit = 10)
    {
        return $this->get_all((int) $user_id, (int) $limit, 0);
    }

    /**
     * One notification row owned by this user (same columns as get_all).
     *
     * @param int $user_notification_id
     * @param int $user_id
     * @return object|null
     */
    public function get_user_notification($user_notification_id, $user_id)
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
            ->where('un.user_notification_id', (int) $user_notification_id)
            ->where('un.user_id', (int) $user_id)
            ->where('un.archived', 0)
            ->where('n.archived', 0)
            ->limit(1)
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->row() : null;
    }

    /**
     * UI type key: certificate|approval|rejection|request|system
     *
     * @param object $n
     * @return string
     */
    public function type_key_for_row($n)
    {
        $title = strtolower((string) ($n->title ?? ''));
        $type_id = (int) ($n->type_id ?? 0);
        if (strpos($title, 'request') !== false || strpos($title, 'invitation') !== false) {
            return Notification_types::REQUEST;
        }
        if (strpos($title, 'approved') !== false) {
            return Notification_types::APPROVAL;
        }
        if (strpos($title, 'declined') !== false || strpos($title, 'rejected') !== false) {
            return Notification_types::REJECTION;
        }
        if ($type_id === (int) self::TYPE_ENROLLMENT) {
            return Notification_types::APPROVAL;
        }
        if ($type_id === (int) self::TYPE_REMOVAL
            && (strpos($title, 'declined') !== false || strpos($title, 'rejected') !== false)) {
            return Notification_types::REJECTION;
        }
        if (strpos($title, 'certificate') !== false) {
            return Notification_types::CERTIFICATE;
        }

        return Notification_types::SYSTEM;
    }

    /**
     * Destination URL when the user opens a notification.
     *
     * @param object $n
     * @return string
     */
    public function action_url_for_row($n)
    {
        $type_key = $this->type_key_for_row($n);
        $ref = (int) ($n->reference_id ?? 0);
        $course_id = (int) ($n->course_id ?? 0);

        if ($type_key === Notification_types::CERTIFICATE && $ref > 0) {
            return base_url('index.php/certificates/view/' . $ref);
        }
        if ($type_key === Notification_types::REQUEST) {
            $title = strtolower((string) ($n->title ?? ''));
            if (strpos($title, 'invitation') !== false) {
                if ($ref > 0) {
                    return base_url('index.php/courses/accept_invitation/' . $ref);
                }
                if ($course_id > 0) {
                    return base_url('index.php/courses/view/' . $course_id);
                }
            }

            if ($ref > 0) {
                return base_url('index.php/enrollments/requests?request_id=' . $ref);
            }

            return base_url('index.php/enrollments/requests');
        }
        if ($type_key === Notification_types::APPROVAL || $type_key === Notification_types::REJECTION) {
            return base_url('index.php/my_courses');
        }
        if ($course_id > 0) {
            return base_url('index.php/courses/view/' . $course_id);
        }
        if ($ref > 0) {
            return base_url('index.php/courses/view/' . $ref);
        }

        return base_url('index.php/announcements');
    }

    /**
     * Short CTA label for the announcements list.
     *
     * @param object $n
     * @return string
     */
    public function action_label_for_row($n)
    {
        $type_key = $this->type_key_for_row($n);
        if ($type_key === Notification_types::REQUEST) {
            $title = strtolower((string) ($n->title ?? ''));
            if (strpos($title, 'invitation') !== false) {
                return 'View invitation';
            }

            return 'Review request';
        }
        if ($type_key === Notification_types::CERTIFICATE) {
            return 'View certificate';
        }
        if ($type_key === Notification_types::APPROVAL || $type_key === Notification_types::REJECTION) {
            return 'Open My Courses';
        }
        if ((int) ($n->course_id ?? 0) > 0) {
            return 'Open course';
        }

        return 'Open';
    }

    /**
     * Whether the primary action button / deep link still applies.
     *
     * @param object     $n
     * @param array|null $enrollment_statuses  enrollment_id => status (optional batch cache)
     * @return bool
     */
    public function is_action_available_for_row($n, array $enrollment_statuses = null)
    {
        $type_key = $this->type_key_for_row($n);
        if ($type_key !== Notification_types::REQUEST) {
            return true;
        }

        $title = strtolower((string) ($n->title ?? ''));
        if (strpos($title, 'invitation') !== false) {
            return true;
        }

        $ref = (int) ($n->reference_id ?? 0);
        if ($ref < 1) {
            return true;
        }

        if (is_array($enrollment_statuses) && array_key_exists($ref, $enrollment_statuses)) {
            return $enrollment_statuses[$ref] === 'pending';
        }

        $row = $this->db
            ->select('status')
            ->where('id', $ref)
            ->get('enrollments', 1)
            ->row();

        if ( ! $row) {
            return false;
        }

        return (string) ($row->status ?? '') === 'pending';
    }

    /**
     * Attach action_label and action_available to notification rows for list UIs.
     *
     * @param object[] $notifications
     * @return object[]
     */
    public function enrich_list_action_meta(array $notifications)
    {
        $enrollment_ids = [];
        foreach ($notifications as $n) {
            if ($this->type_key_for_row($n) !== Notification_types::REQUEST) {
                continue;
            }
            $title = strtolower((string) ($n->title ?? ''));
            if (strpos($title, 'invitation') !== false) {
                continue;
            }
            $ref = (int) ($n->reference_id ?? 0);
            if ($ref > 0) {
                $enrollment_ids[$ref] = true;
            }
        }

        $statuses = [];
        if ( ! empty($enrollment_ids)) {
            $r = $this->db
                ->select('id, status')
                ->where_in('id', array_keys($enrollment_ids))
                ->get('enrollments');
            if ($r) {
                foreach ($r->result() as $row) {
                    $statuses[(int) $row->id] = (string) ($row->status ?? '');
                }
            }
        }

        foreach ($notifications as $n) {
            $n->action_label = $this->action_label_for_row($n);
            $n->action_available = $this->is_action_available_for_row($n, $statuses);
        }

        return $notifications;
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
     * Mark notification as read by notification_id (ownership enforced).
     * Returns true when a matching row exists and update succeeds.
     *
     * @param int $user_id
     * @param int $notification_id
     * @return bool
     */
    public function mark_read_by_notification($user_id, $notification_id)
    {
        $row = $this->db
            ->select('user_notification_id')
            ->from('lib_user_notification')
            ->where('user_id', (int) $user_id)
            ->where('notification_id', (int) $notification_id)
            ->where('archived', 0)
            ->limit(1)
            ->get()
            ->row();

        if ( ! $row) {
            return false;
        }

        return $this->mark_read((int) $row->user_notification_id, (int) $user_id);
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
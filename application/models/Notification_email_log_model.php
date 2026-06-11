<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Outbound notification email audit log (notification_email_log).
 */
class Notification_email_log_model extends CI_Model {

    protected $table = 'notification_email_log';

    public function table_ready()
    {
        return $this->db->table_exists($this->table);
    }

    /**
     * @param array $row
     * @return int Insert id or 0
     */
    public function log(array $row)
    {
        if ( ! $this->table_ready()) {
            return 0;
        }

        $payload = [
            'notification_id' => isset($row['notification_id']) ? (int) $row['notification_id'] : null,
            'user_id'         => isset($row['user_id']) ? (int) $row['user_id'] : null,
            'email_to'        => (string) ($row['email_to'] ?? ''),
            'template_key'    => (string) ($row['template_key'] ?? ''),
            'subject'         => (string) ($row['subject'] ?? ''),
            'status'          => in_array(($row['status'] ?? ''), ['sent', 'failed', 'skipped'], true)
                ? $row['status'] : 'skipped',
            'error_message'   => isset($row['error_message']) ? (string) $row['error_message'] : null,
            'retry_count'     => (int) ($row['retry_count'] ?? 0),
            'created_at'      => date('Y-m-d H:i:s'),
        ];

        if ($payload['email_to'] === '') {
            return 0;
        }

        return $this->db->insert($this->table, $payload) ? (int) $this->db->insert_id() : 0;
    }

    /**
     * @param int $limit
     * @return array<int,object>
     */
    public function get_failed_for_retry($limit = 25)
    {
        if ( ! $this->table_ready()) {
            return [];
        }

        $limit = max(1, min(100, (int) $limit));

        $r = $this->db
            ->where('status', 'failed')
            ->where('retry_count <', 3)
            ->where('email_to !=', '')
            ->order_by('created_at', 'ASC')
            ->limit($limit)
            ->get($this->table);

        return $r ? $r->result() : [];
    }

    /**
     * @param int    $log_id
     * @param bool   $success
     * @param string $error
     */
    public function mark_retry($log_id, $success, $error = '')
    {
        if ( ! $this->table_ready()) {
            return false;
        }

        $id = (int) $log_id;
        if ($id < 1) {
            return false;
        }

        $row = $this->db->where('id', $id)->get($this->table, 1)->row();
        if ( ! $row) {
            return false;
        }

        $payload = [
            'retry_count'   => (int) ($row->retry_count ?? 0) + 1,
            'status'        => $success ? 'sent' : 'failed',
            'error_message' => $success ? null : ($error !== '' ? $error : $row->error_message),
        ];

        return (bool) $this->db->where('id', $id)->update($this->table, $payload);
    }
}

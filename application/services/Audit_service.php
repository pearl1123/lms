<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Central platform audit writer (activity_logs).
 */
class Audit_service {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * @param string      $action
     * @param string      $module  e.g. auth, enrollment, assessment
     * @param int|null    $user_id Actor; defaults to session user
     * @param int|null    $reference_id
     * @param array|string|null $details
     */
    public function log($action, $module = 'system', $user_id = null, $reference_id = null, $details = null)
    {
        if ( ! $this->CI->db->table_exists('activity_logs')) {
            return false;
        }

        if ($user_id === null) {
            $user_id = (int) $this->CI->session->userdata('user_id');
        }

        $row = [
            'user_id'      => $user_id > 0 ? $user_id : null,
            'action'       => substr((string) $action, 0, 255),
            'module'       => substr((string) $module, 0, 64),
            'reference_id' => $reference_id !== null ? (int) $reference_id : null,
            'details'      => is_array($details) ? json_encode($details) : (is_string($details) ? $details : null),
            'ip_address'   => (string) $this->CI->input->ip_address(),
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        if ( ! $this->CI->db->field_exists('module', 'activity_logs')) {
            unset($row['module'], $row['reference_id'], $row['details']);
        }

        return (bool) $this->CI->db->insert('activity_logs', $row);
    }
}

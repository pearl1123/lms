<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Generic CRUD for registry-driven admin libraries.
 *
 * @property CI_DB_mysqli_driver $db
 */
class Library_crud_model extends CI_Model {

    /** @var array */
    private $config = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * @param array $config Registry module config
     */
    public function set_config(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $filters q, include_archived
     * @return object[]
     */
    public function get_all(array $filters = [])
    {
        $table = $this->config['table'];
        $pk    = $this->config['primary_key'];

        $this->db->select($table . '.*', false);
        $this->_apply_fk_list_selects();

        if ($this->_has_soft_archive() && empty($filters['include_archived'])) {
            $this->_apply_active_filter();
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '' && ! empty($this->config['searchable'])) {
            $this->db->group_start();
            $first = true;
            foreach ($this->config['searchable'] as $col) {
                if ($first) {
                    $this->db->like($table . '.' . $col, $q);
                    $first = false;
                } else {
                    $this->db->or_like($table . '.' . $col, $q);
                }
            }
            $this->db->group_end();
        }

        $order = $this->config['order_by'] ?? ($pk . ' DESC');
        foreach (preg_split('/\s*,\s*/', $order) as $clause) {
            $parts = preg_split('/\s+/', trim($clause), 2);
            if (count($parts) === 2) {
                $this->db->order_by($table . '.' . $parts[0], $parts[1]);
            }
        }

        $r = $this->db->get($table);

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * @param mixed $id
     * @return object|null
     */
    public function get_by_id($id)
    {
        $r = $this->db
            ->where($this->config['primary_key'], $id)
            ->get($this->config['table'], 1);

        return ($r && $r->num_rows() > 0) ? $r->row() : null;
    }

    /**
     * @param array $data
     * @return int|string insert id
     */
    public function insert(array $data)
    {
        $row = $this->_filter_writable($data, true);
        if (empty($row)) {
            return 0;
        }

        $this->_stamp_audit($row, true);

        if ( ! $this->db->insert($this->config['table'], $row)) {
            return 0;
        }

        return $this->db->insert_id();
    }

    /**
     * @param mixed $id
     * @param array $data
     */
    public function update($id, array $data)
    {
        $row = $this->_filter_writable($data, false);
        if (empty($row)) {
            return true;
        }

        $this->_stamp_audit($row, false);

        return (bool) $this->db
            ->where($this->config['primary_key'], $id)
            ->update($this->config['table'], $row);
    }

    public function soft_delete($id)
    {
        $col = $this->config['soft_delete'] ?? 'archived';
        if ($col === false || $col === null || $col === '') {
            return false;
        }

        if ( ! empty($this->config['soft_delete_invert'])) {
            return (bool) $this->db
                ->where($this->config['primary_key'], $id)
                ->update($this->config['table'], [$col => 0]);
        }

        return (bool) $this->db
            ->where($this->config['primary_key'], $id)
            ->update($this->config['table'], [$col => 1]);
    }

    public function restore($id)
    {
        $col = $this->config['soft_delete'] ?? 'archived';
        if ($col === false || $col === null || $col === '') {
            return false;
        }

        if ( ! empty($this->config['soft_delete_invert'])) {
            return (bool) $this->db
                ->where($this->config['primary_key'], $id)
                ->update($this->config['table'], [$col => 1]);
        }

        return (bool) $this->db
            ->where($this->config['primary_key'], $id)
            ->update($this->config['table'], [$col => 0]);
    }

    /**
     * FK dropdown options for form fields.
     *
     * @return array<string, array<int, string>>
     */
    public function get_form_select_options()
    {
        $out = [];
        foreach ($this->config['form_fields'] ?? [] as $field) {
            if (($field['type'] ?? '') !== 'select' || empty($field['fk'])) {
                continue;
            }
            $fk   = $field['fk'];
            $opts = [];
            $this->db->select($fk['key'] . ' AS k', false);
            $label = $fk['label'] ?? 'name';
            $this->db->select($label . ' AS lbl', false);
            $this->db->from($fk['table']);
            if ( ! empty($fk['where'])) {
                $this->db->where($fk['where']);
            }
            if ( ! empty($fk['order'])) {
                foreach (preg_split('/\s*,\s*/', $fk['order']) as $clause) {
                    $parts = preg_split('/\s+/', trim($clause), 2);
                    if (count($parts) === 2) {
                        $this->db->order_by($parts[0], $parts[1]);
                    }
                }
            }
            $r = $this->db->get();
            if ($r) {
                foreach ($r->result() as $row) {
                    $opts[(int) $row->k] = (string) $row->lbl;
                }
            }
            $out[$field['field']] = $opts;
        }

        return $out;
    }

    /**
     * @return bool
     */
    public function is_archived_row($row)
    {
        $col = $this->config['soft_delete'] ?? 'archived';
        if ($col === false || $col === null) {
            return false;
        }
        if ( ! empty($this->config['soft_delete_invert'])) {
            return (int) ($row->{$col} ?? 1) !== 1;
        }

        return (int) ($row->{$col} ?? 0) === 1;
    }

    private function _filter_writable(array $data, $is_insert)
    {
        $allowed = [];
        foreach ($this->config['form_fields'] ?? [] as $field) {
            $allowed[] = $field['field'];
        }

        $row = [];
        foreach ($allowed as $col) {
            if ( ! array_key_exists($col, $data)) {
                continue;
            }
            $val = $data[$col];
            $def = $this->_field_def($col);
            $type = $def['type'] ?? 'text';

            if ($type === 'checkbox') {
                $row[$col] = ($val === true || $val === 1 || $val === '1') ? 1 : 0;
            } elseif ($type === 'number') {
                $row[$col] = ($val === '' || $val === null) ? null : (int) $val;
            } else {
                $row[$col] = is_string($val) ? trim($val) : $val;
            }
        }

        if ($is_insert && ! empty($this->config['insert_defaults'])) {
            foreach ($this->config['insert_defaults'] as $col => $val) {
                if ($val === 'NOW' && ! isset($row[$col])) {
                    $row[$col] = date('Y-m-d H:i:s');
                } elseif ( ! isset($row[$col])) {
                    $row[$col] = $val;
                }
            }
        }

        if ($is_insert && $this->_has_soft_archive() && empty($this->config['soft_delete_invert'])) {
            $col = $this->config['soft_delete'];
            if ($col && $col !== false) {
                $row[$col] = 0;
            }
        }

        return $row;
    }

    private function _stamp_audit(array &$row, $is_insert)
    {
        $CI  = &get_instance();
        $uid = (int) ($CI->session->userdata('user_id') ?? 0);
        $now = date('Y-m-d H:i:s');
        if ($is_insert) {
            if ($this->db->field_exists('date_encoded', $this->config['table'])) {
                $row['date_encoded'] = $now;
            }
            if ($uid && $this->db->field_exists('encoded_by', $this->config['table'])) {
                $row['encoded_by'] = $uid;
            }
        } else {
            if ($this->db->field_exists('date_last_modified', $this->config['table'])) {
                $row['date_last_modified'] = $now;
            }
            if ($uid && $this->db->field_exists('modified_by', $this->config['table'])) {
                $row['modified_by'] = $uid;
            }
        }
    }

    private function _field_def($col)
    {
        foreach ($this->config['form_fields'] ?? [] as $field) {
            if ($field['field'] === $col) {
                return $field;
            }
        }

        return [];
    }

    private function _has_soft_archive()
    {
        $col = $this->config['soft_delete'] ?? 'archived';

        return $col !== false && $col !== null && $col !== '';
    }

    private function _apply_active_filter()
    {
        $table = $this->config['table'];
        $col   = $this->config['soft_delete'];
        if ( ! empty($this->config['soft_delete_invert'])) {
            $this->db->where($table . '.' . $col, 1);
        } else {
            $this->db->where($table . '.' . $col, 0);
        }
    }

    private function _apply_fk_list_selects()
    {
        foreach ($this->config['list_columns'] ?? [] as $col) {
            if (($col['type'] ?? '') !== 'fk' || empty($col['fk'])) {
                continue;
            }
            $fkField = $col['fk'];
            $def     = $this->_field_def($fkField);
            if (empty($def['fk'])) {
                continue;
            }
            $fk = $def['fk'];
            $alias = 'fk_' . $fkField;
            $this->db->select($alias . '.' . $fk['label'] . ' AS fk_' . $fkField . '_label', false);
            $this->db->join(
                $fk['table'] . ' AS ' . $alias,
                $alias . '.' . $fk['key'] . ' = ' . $this->config['table'] . '.' . $fkField,
                'left'
            );
        }
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_DB_mysqli_driver $db
 */
class Learning_notes_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function table_ready()
    {
        static $ready = null;
        if ($ready === null) {
            $ready = $this->db->table_exists('learning_notes');
        }

        return $ready;
    }

    /**
     * @param int   $user_id
     * @param array $filters course_id, module_id, q, favorite, recent
     * @return object[]
     */
    public function get_notes_for_user($user_id, array $filters = [])
    {
        if ( ! $this->table_ready()) {
            return [];
        }

        $this->db
            ->select('ln.*, c.title AS course_title, cm.title AS module_title', false)
            ->from('learning_notes ln')
            ->join('courses c', 'c.id = ln.course_id', 'left')
            ->join('course_modules cm', 'cm.id = ln.module_id', 'left')
            ->where('ln.user_id', (int) $user_id)
            ->where('ln.archived', 0);

        if ( ! empty($filters['course_id'])) {
            $this->db->where('ln.course_id', (int) $filters['course_id']);
        }
        if ( ! empty($filters['module_id'])) {
            $this->db->where('ln.module_id', (int) $filters['module_id']);
        }
        if ( ! empty($filters['favorite'])) {
            $this->db->where('ln.is_favorite', 1);
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $this->db->group_start();
            $this->db->like('ln.note_title', $q);
            $this->db->or_like('ln.note_content', $q);
            $this->db->or_like('ln.tags_json', $q);
            $this->db->group_end();
        }

        $this->db->order_by('ln.is_pinned', 'DESC');
        $this->db->order_by('ln.is_favorite', 'DESC');
        $this->db->order_by('ln.updated_at', 'DESC');

        if ( ! empty($filters['recent'])) {
            $this->db->limit(max(1, (int) ($filters['limit'] ?? 50)));
        }

        $r = $this->db->get();

        return ($r && $r->num_rows() > 0) ? $this->_hydrate_notes($r->result()) : [];
    }

    /**
     * @return object|null
     */
    public function get_note($note_id, $user_id)
    {
        if ( ! $this->table_ready()) {
            return null;
        }

        $r = $this->db
            ->select('ln.*, c.title AS course_title, cm.title AS module_title', false)
            ->from('learning_notes ln')
            ->join('courses c', 'c.id = ln.course_id', 'left')
            ->join('course_modules cm', 'cm.id = ln.module_id', 'left')
            ->where('ln.id', (int) $note_id)
            ->where('ln.user_id', (int) $user_id)
            ->where('ln.archived', 0)
            ->get();

        if ( ! $r || $r->num_rows() === 0) {
            return null;
        }

        $rows = $this->_hydrate_notes($r->result());

        return $rows[0] ?? null;
    }

    /**
     * @param array $data
     * @return int note id or 0
     */
    public function create_note($user_id, array $data)
    {
        if ( ! $this->table_ready()) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $tags = $this->_encode_tags($data['tags'] ?? []);

        $ok = $this->db->insert('learning_notes', [
            'user_id'           => (int) $user_id,
            'course_id'         => (int) $data['course_id'],
            'module_id'         => ! empty($data['module_id']) ? (int) $data['module_id'] : null,
            'note_title'        => trim((string) ($data['note_title'] ?? '')),
            'note_content'      => trim((string) ($data['note_content'] ?? '')),
            'content_type'      => $this->_sanitize_content_type($data['content_type'] ?? 'general'),
            'timestamp_seconds' => $this->_nullable_int($data['timestamp_seconds'] ?? null),
            'pdf_page'          => $this->_nullable_int($data['pdf_page'] ?? null),
            'slide_number'      => $this->_nullable_int($data['slide_number'] ?? null),
            'tags_json'         => $tags,
            'is_pinned'         => ! empty($data['is_pinned']) ? 1 : 0,
            'is_favorite'       => ! empty($data['is_favorite']) ? 1 : 0,
            'color_label'       => $this->_sanitize_color($data['color_label'] ?? null),
            'created_at'        => $now,
            'updated_at'        => $now,
            'archived'          => 0,
        ]);

        return $ok ? (int) $this->db->insert_id() : 0;
    }

    public function update_note($note_id, $user_id, array $data)
    {
        if ( ! $this->table_ready()) {
            return false;
        }

        $row = $this->get_note($note_id, $user_id);
        if ( ! $row) {
            return false;
        }

        $update = ['updated_at' => date('Y-m-d H:i:s')];

        if (array_key_exists('note_title', $data)) {
            $update['note_title'] = trim((string) $data['note_title']);
        }
        if (array_key_exists('note_content', $data)) {
            $update['note_content'] = trim((string) $data['note_content']);
        }
        if (array_key_exists('tags', $data)) {
            $update['tags_json'] = $this->_encode_tags($data['tags']);
        }
        if (array_key_exists('is_pinned', $data)) {
            $update['is_pinned'] = ! empty($data['is_pinned']) ? 1 : 0;
        }
        if (array_key_exists('is_favorite', $data)) {
            $update['is_favorite'] = ! empty($data['is_favorite']) ? 1 : 0;
        }
        if (array_key_exists('color_label', $data)) {
            $update['color_label'] = $this->_sanitize_color($data['color_label']);
        }

        return (bool) $this->db
            ->where('id', (int) $note_id)
            ->where('user_id', (int) $user_id)
            ->update('learning_notes', $update);
    }

    public function archive_note($note_id, $user_id)
    {
        if ( ! $this->table_ready()) {
            return false;
        }

        return (bool) $this->db
            ->where('id', (int) $note_id)
            ->where('user_id', (int) $user_id)
            ->update('learning_notes', [
                'archived'   => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function toggle_pin($note_id, $user_id)
    {
        $row = $this->get_note($note_id, $user_id);
        if ( ! $row) {
            return false;
        }

        return $this->update_note($note_id, $user_id, ['is_pinned' => ! (int) $row->is_pinned]);
    }

    public function toggle_favorite($note_id, $user_id)
    {
        $row = $this->get_note($note_id, $user_id);
        if ( ! $row) {
            return false;
        }

        return $this->update_note($note_id, $user_id, ['is_favorite' => ! (int) $row->is_favorite]);
    }

    /**
     * @return array<int,array{id:int,title:string}>
     */
    public function get_courses_with_notes($user_id)
    {
        if ( ! $this->table_ready()) {
            return [];
        }

        $r = $this->db
            ->select('ln.course_id, c.title, COUNT(*) AS note_count', false)
            ->from('learning_notes ln')
            ->join('courses c', 'c.id = ln.course_id', 'left')
            ->where('ln.user_id', (int) $user_id)
            ->where('ln.archived', 0)
            ->group_by('ln.course_id, c.title')
            ->order_by('c.title', 'ASC')
            ->get();

        if ( ! $r || $r->num_rows() === 0) {
            return [];
        }

        $out = [];
        foreach ($r->result() as $row) {
            $out[] = [
                'id'          => (int) $row->course_id,
                'title'       => (string) ($row->title ?? 'Course'),
                'note_count'  => (int) $row->note_count,
            ];
        }

        return $out;
    }

    /**
     * Admin analytics snapshot.
     *
     * @return array{total_notes:int,notes_per_course:array,top_note_takers:array}
     */
    public function get_admin_analytics($limit = 10)
    {
        if ( ! $this->table_ready()) {
            return [
                'total_notes'      => 0,
                'notes_per_course' => [],
                'top_note_takers'  => [],
            ];
        }

        $total = (int) $this->db
            ->where('archived', 0)
            ->count_all_results('learning_notes');

        $per_course = $this->db
            ->select('c.id, c.title, COUNT(ln.id) AS note_count', false)
            ->from('learning_notes ln')
            ->join('courses c', 'c.id = ln.course_id', 'left')
            ->where('ln.archived', 0)
            ->group_by('c.id, c.title')
            ->order_by('note_count', 'DESC')
            ->limit(8)
            ->get()
            ->result();

        $top = $this->db
            ->select('u.id, u.fullname, u.employee_id, COUNT(ln.id) AS note_count', false)
            ->from('learning_notes ln')
            ->join('aauth_users u', 'u.id = ln.user_id', 'left')
            ->where('ln.archived', 0)
            ->group_by('u.id, u.fullname, u.employee_id')
            ->order_by('note_count', 'DESC')
            ->limit(max(1, (int) $limit))
            ->get()
            ->result();

        return [
            'total_notes'       => $total,
            'notes_per_course'  => $per_course,
            'top_note_takers'   => $top,
        ];
    }

    /**
     * @param object[] $rows
     * @return object[]
     */
    private function _hydrate_notes(array $rows)
    {
        foreach ($rows as $row) {
            $row->tags = $this->_decode_tags($row->tags_json ?? '');
            $row->context_label = $this->format_context_label($row);
            $row->return_url = $this->build_return_url($row);
        }

        return $rows;
    }

    public function format_context_label($note)
    {
        $type = (string) ($note->content_type ?? 'general');
        if ($type === 'video' || $type === 'audio') {
            $sec = (int) ($note->timestamp_seconds ?? 0);
            if ($sec > 0) {
                return $this->format_timestamp($sec);
            }
        }
        if ($type === 'pdf') {
            $p = (int) ($note->pdf_page ?? 0);
            if ($p > 0) {
                return 'Page ' . $p;
            }
        }
        if ($type === 'slides') {
            $s = (int) ($note->slide_number ?? 0);
            if ($s > 0) {
                return 'Slide ' . $s;
            }
        }

        return '';
    }

    public function format_timestamp($seconds)
    {
        $sec = max(0, (int) $seconds);
        $h   = (int) floor($sec / 3600);
        $m   = (int) floor(($sec % 3600) / 60);
        $s   = $sec % 60;

        if ($h > 0) {
            return sprintf('%d:%02d:%02d', $h, $m, $s);
        }

        return sprintf('%02d:%02d', $m, $s);
    }

    /**
     * @param object $note
     */
    public function build_return_url($note)
    {
        $mid = (int) ($note->module_id ?? 0);
        if ($mid < 1) {
            return base_url('index.php/courses/view/' . (int) ($note->course_id ?? 0));
        }

        $url = base_url('index.php/courses/module/' . $mid);
        $type = (string) ($note->content_type ?? 'general');

        if (($type === 'video' || $type === 'audio') && (int) ($note->timestamp_seconds ?? 0) > 0) {
            return $url . '?t=' . (int) $note->timestamp_seconds;
        }
        if ($type === 'pdf' && (int) ($note->pdf_page ?? 0) > 0) {
            return $url . '?page=' . (int) $note->pdf_page;
        }
        if ($type === 'slides' && (int) ($note->slide_number ?? 0) > 0) {
            return $url . '?slide=' . (int) $note->slide_number;
        }

        return $url;
    }

    private function _sanitize_content_type($type)
    {
        $type = strtolower(trim((string) $type));
        $allowed = ['video', 'pdf', 'slides', 'audio', 'general'];

        return in_array($type, $allowed, true) ? $type : 'general';
    }

    private function _sanitize_color($color)
    {
        $color = strtolower(trim((string) $color));
        $allowed = ['blue', 'yellow', 'green', ''];

        return in_array($color, $allowed, true) && $color !== '' ? $color : null;
    }

    private function _nullable_int($val)
    {
        if ($val === null || $val === '') {
            return null;
        }

        $n = (int) $val;

        return $n > 0 ? $n : null;
    }

    /**
     * @param string|array $tags
     */
    private function _encode_tags($tags)
    {
        if (is_string($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }
        if ( ! is_array($tags)) {
            return null;
        }
        $clean = [];
        foreach ($tags as $t) {
            $t = trim((string) $t);
            if ($t !== '') {
                $clean[] = $t;
            }
        }

        return empty($clean) ? null : json_encode(array_values($clean), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return string[]
     */
    private function _decode_tags($json)
    {
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode((string) $json, true);

        return is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [];
    }
}

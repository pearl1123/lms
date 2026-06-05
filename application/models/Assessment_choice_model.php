<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin library CRUD for lib_assessment_choices (schema: db_lms.sql).
 *
 * @property CI_DB_mysqli_driver $db
 */
class Assessment_choice_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * @param array $filters question_id, q, include_archived
     * @return object[]
     */
    public function get_all(array $filters = [])
    {
        $this->db
            ->select('lac.id, lac.question_id, lac.choice_text, lac.is_correct, lac.choice_order, lac.archived', false)
            ->select('laq.question_text, laq.question_type, laq.assessment_id', false)
            ->select('la.title AS assessment_title', false)
            ->from('lib_assessment_choices lac')
            ->join('lib_assessment_questions laq', 'laq.id = lac.question_id', 'left')
            ->join('lib_assessments la', 'la.id = laq.assessment_id', 'left');

        if (empty($filters['include_archived'])) {
            $this->db->where('lac.archived', 0);
        }

        if ( ! empty($filters['question_id'])) {
            $this->db->where('lac.question_id', (int) $filters['question_id']);
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $this->db->group_start();
            $this->db->like('lac.choice_text', $q);
            $this->db->or_like('laq.question_text', $q);
            $this->db->group_end();
        }

        $r = $this->db
            ->order_by('lac.question_id', 'ASC')
            ->order_by('lac.choice_order', 'ASC')
            ->order_by('lac.id', 'ASC')
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * @return object|null
     */
    public function get_by_id($id)
    {
        $r = $this->db
            ->where('id', (int) $id)
            ->get('lib_assessment_choices', 1);

        return ($r && $r->num_rows() > 0) ? $r->row() : null;
    }

    /**
     * @param int $question_id
     * @param bool $include_archived
     * @return object[]
     */
    public function get_by_question($question_id, $include_archived = false)
    {
        $this->db
            ->where('question_id', (int) $question_id)
            ->order_by('choice_order', 'ASC')
            ->order_by('id', 'ASC');

        if ( ! $include_archived) {
            $this->db->where('archived', 0);
        }

        $r = $this->db->get('lib_assessment_choices');

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * @param array $data question_id, choice_text, is_correct, choice_order
     * @return int insert id or 0
     */
    public function insert(array $data)
    {
        if ( ! $this->question_exists((int) $data['question_id'])) {
            return 0;
        }

        $order = isset($data['choice_order']) ? (int) $data['choice_order'] : 0;
        if ($order < 1) {
            $order = $this->next_choice_order((int) $data['question_id']);
        }

        $ok = $this->db->insert('lib_assessment_choices', [
            'question_id'  => (int) $data['question_id'],
            'choice_text'  => mb_substr(trim((string) $data['choice_text']), 0, 500),
            'is_correct'   => $this->_parse_is_correct($data['is_correct'] ?? 0),
            'choice_order' => $order,
            'archived'     => 0,
        ]);

        return $ok ? (int) $this->db->insert_id() : 0;
    }

    /**
     * @param int   $id
     * @param array $data
     */
    public function update($id, array $data)
    {
        $row = $this->get_by_id($id);
        if ( ! $row) {
            return false;
        }

        $update = [];

        if (array_key_exists('question_id', $data)) {
            $qid = (int) $data['question_id'];
            if ( ! $this->question_exists($qid)) {
                return false;
            }
            $update['question_id'] = $qid;
        }

        if (array_key_exists('choice_text', $data)) {
            $update['choice_text'] = mb_substr(trim((string) $data['choice_text']), 0, 500);
        }

        if (array_key_exists('is_correct', $data)) {
            $update['is_correct'] = $this->_parse_is_correct($data['is_correct']);
        }

        if (array_key_exists('choice_order', $data)) {
            $update['choice_order'] = max(1, (int) $data['choice_order']);
        }

        if (empty($update)) {
            return true;
        }

        return (bool) $this->db
            ->where('id', (int) $id)
            ->update('lib_assessment_choices', $update);
    }

    public function soft_delete($id)
    {
        return (bool) $this->db
            ->where('id', (int) $id)
            ->update('lib_assessment_choices', ['archived' => 1]);
    }

    public function restore($id)
    {
        $row = $this->get_by_id($id);
        if ( ! $row || ! $this->question_exists((int) $row->question_id)) {
            return false;
        }

        return (bool) $this->db
            ->where('id', (int) $id)
            ->update('lib_assessment_choices', ['archived' => 0]);
    }

    /**
     * MCQ questions for dropdowns (non-archived).
     *
     * @return object[]
     */
    public function get_mcq_questions_for_dropdown()
    {
        $r = $this->db
            ->select('laq.id, laq.question_text, laq.assessment_id, la.title AS assessment_title', false)
            ->from('lib_assessment_questions laq')
            ->join('lib_assessments la', 'la.id = laq.assessment_id', 'left')
            ->where('laq.archived', 0)
            ->where('laq.question_type', 'multiple_choice')
            ->order_by('la.title', 'ASC')
            ->order_by('laq.id', 'ASC')
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    public function question_exists($question_id)
    {
        if ((int) $question_id < 1) {
            return false;
        }

        return (bool) $this->db
            ->where('id', (int) $question_id)
            ->where('archived', 0)
            ->count_all_results('lib_assessment_questions');
    }

    private function next_choice_order($question_id)
    {
        $row = $this->db
            ->select_max('choice_order', 'max_order')
            ->where('question_id', (int) $question_id)
            ->where('archived', 0)
            ->get('lib_assessment_choices')
            ->row();

        return $row ? ((int) $row->max_order + 1) : 1;
    }

    private function _parse_is_correct($value)
    {
        return ($value === true || $value === 1 || $value === '1') ? 1 : 0;
    }
}

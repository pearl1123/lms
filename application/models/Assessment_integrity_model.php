<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Data layer for Assessment Integrity Analytics dashboard.
 *
 * @property CI_DB_mysqli_driver $db
 */
class Assessment_integrity_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('Assessment_model', 'assessment_model');
        $this->load->model('Assessment_attempt_order_model', 'attempt_order_model');
    }

    /**
     * Pre/post assessments visible to managers (optional id filter).
     *
     * @param int[] $assessment_ids
     * @return object[]
     */
    public function get_assessment_overview_rows(array $assessment_ids = [])
    {
        $this->db
            ->select('
                la.id, la.module_id, la.type, la.title, la.created_at,
                la.randomize_questions,
                cm.title AS module_title,
                c.id AS course_id,
                c.title AS course_title
            ', false)
            ->from('lib_assessments la')
            ->join('course_modules cm', 'cm.id = la.module_id', 'left')
            ->join('courses c', 'c.id = cm.course_id', 'left')
            ->where('la.archived', 0)
            ->where_in('la.type', ['pre', 'post'])
            ->order_by('c.title', 'ASC')
            ->order_by('la.title', 'ASC');

        if ($this->assessment_model->assessments_content_version_ready()) {
            $this->db->select('la.content_version', false);
        }

        if ( ! empty($assessment_ids)) {
            $this->db->where_in('la.id', array_map('intval', $assessment_ids));
        }

        $CI =& get_instance();
        $CI->load->model('Course_phase2_model', 'course_phase2');
        $user = $CI->session->userdata('user');
        $role = is_array($user) ? ($user['role'] ?? '') : '';
        if ($role === 'teacher' || $role === 'instructor') {
            $uid = is_array($user) ? (int) ($user['id'] ?? 0) : 0;
            if ($uid > 0) {
                $CI->course_phase2->restrict_query_to_instructor_courses($uid);
            }
        }

        $r = $this->db->get();
        if ( ! $r || $r->num_rows() === 0) {
            return [];
        }

        $rows = $r->result();
        foreach ($rows as $row) {
            $aid = (int) $row->id;
            $row->content_version       = max(1, (int) ($row->content_version ?? 1));
            $row->started_count         = $this->_count_started($aid);
            $row->submitted_count       = $this->_count_submitted_learners($aid);
            $row->retake_count          = $this->_count_retakes($aid);
            $row->legacy_attempt_count  = $this->_count_legacy_attempts($aid);
            $row->average_score         = $this->_average_score_for_assessment($aid);
        }

        return $rows;
    }

    private function _count_started($assessment_id)
    {
        if ($this->attempt_order_model->table_ready()) {
            return $this->attempt_order_model->count_started_for_assessment($assessment_id);
        }

        return $this->_count_distinct_answerers($assessment_id);
    }

    private function _count_retakes($assessment_id)
    {
        if ($this->attempt_order_model->table_ready()) {
            return $this->attempt_order_model->count_retakes_for_assessment($assessment_id);
        }

        return 0;
    }

    private function _count_legacy_attempts($assessment_id)
    {
        if ( ! $this->attempt_order_model->table_ready()) {
            return 0;
        }

        return (int) $this->db
            ->where('assessment_id', (int) $assessment_id)
            ->where('is_legacy_version', 1)
            ->count_all_results('assessment_attempt_orders');
    }

    private function _count_submitted_learners($assessment_id)
    {
        $q_ids = $this->assessment_model->get_question_ids_for_assessment($assessment_id);
        if (empty($q_ids)) {
            return 0;
        }

        $row = $this->db
            ->select('COUNT(DISTINCT aa.user_id) AS c', false)
            ->from('assessment_answers aa')
            ->where_in('aa.question_id', $q_ids)
            ->where('aa.archived', 0)
            ->get()
            ->row();

        return $row ? (int) $row->c : 0;
    }

    private function _count_distinct_answerers($assessment_id)
    {
        return $this->_count_submitted_learners($assessment_id);
    }

    private function _average_score_for_assessment($assessment_id)
    {
        $q_ids = $this->assessment_model->get_question_ids_for_assessment($assessment_id);
        if (empty($q_ids)) {
            return 0.0;
        }

        $r = $this->db
            ->select('aa.user_id, AVG(aa.score) AS user_avg', false)
            ->from('assessment_answers aa')
            ->where_in('aa.question_id', $q_ids)
            ->where('aa.archived', 0)
            ->where('aa.score IS NOT NULL', null, false)
            ->group_by('aa.user_id')
            ->get();

        if ( ! $r || $r->num_rows() === 0) {
            return 0.0;
        }

        $sum = 0;
        $n   = 0;
        foreach ($r->result() as $row) {
            $sum += (float) $row->user_avg;
            $n++;
        }

        return $n > 0 ? round($sum / $n, 2) : 0.0;
    }

    /**
     * @return object[]
     */
    public function get_question_stats_for_assessment($assessment_id)
    {
        $q_ids = $this->assessment_model->get_question_ids_for_assessment($assessment_id);
        if (empty($q_ids)) {
            return [];
        }

        $r = $this->db
            ->select('
                laq.id AS question_id,
                laq.question_text,
                laq.question_type,
                COUNT(aa.id) AS times_presented,
                SUM(CASE WHEN aa.score >= 99.99 THEN 1 ELSE 0 END) AS correct_count
            ', false)
            ->from('lib_assessment_questions laq')
            ->join(
                'assessment_answers aa',
                'aa.question_id = laq.id AND aa.archived = 0',
                'left'
            )
            ->where_in('laq.id', $q_ids)
            ->where('laq.archived', 0)
            ->group_by('laq.id')
            ->order_by('laq.id', 'ASC')
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * @param int   $limit
     * @param int[] $assessment_ids
     * @return array<int,array>
     */
    public function get_most_missed_questions($limit = 10, array $assessment_ids = [])
    {
        $this->db
            ->select('
                laq.id AS question_id,
                laq.question_text,
                la.id AS assessment_id,
                la.title AS assessment_title,
                COUNT(aa.id) AS times_presented,
                SUM(CASE WHEN aa.score IS NOT NULL AND aa.score < 99.99 THEN 1 ELSE 0 END) AS incorrect_count
            ', false)
            ->from('lib_assessment_questions laq')
            ->join('lib_assessments la', 'la.id = laq.assessment_id', 'inner')
            ->join('assessment_answers aa', 'aa.question_id = laq.id AND aa.archived = 0', 'left')
            ->where('laq.archived', 0)
            ->where('la.archived', 0)
            ->where_in('la.type', ['pre', 'post'])
            ->group_by('laq.id')
            ->having('times_presented >', 0);

        if ( ! empty($assessment_ids)) {
            $this->db->where_in('la.id', array_map('intval', $assessment_ids));
        }

        $r = $this->db->get();
        if ( ! $r || $r->num_rows() === 0) {
            return [];
        }

        $candidates = [];
        foreach ($r->result() as $row) {
            $presented = (int) $row->times_presented;
            $incorrect = (int) $row->incorrect_count;
            $fail_pct  = $presented > 0 ? round(($incorrect / $presented) * 100, 1) : 0.0;
            $candidates[] = [
                'question_id'     => (int) $row->question_id,
                'question_text'   => (string) $row->question_text,
                'assessment_id'   => (int) $row->assessment_id,
                'assessment_name' => (string) $row->assessment_title,
                'times_presented' => $presented,
                'failure_pct'     => $fail_pct,
            ];
        }

        usort($candidates, static function ($a, $b) {
            return ($b['failure_pct'] <=> $a['failure_pct']);
        });

        return array_slice($candidates, 0, max(1, (int) $limit));
    }

    /**
     * Raw rows for discrimination computation in service.
     *
     * @param int[] $assessment_ids
     * @return object[]
     */
    public function get_discrimination_raw(array $assessment_ids = [])
    {
        $assessments = $this->get_assessment_overview_rows($assessment_ids);
        $out         = [];

        foreach ($assessments as $a) {
            $aid = (int) $a->id;
            foreach ($this->_discrimination_for_assessment($aid) as $row) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * @return object[]
     */
    private function _discrimination_for_assessment($assessment_id)
    {
        $q_ids = $this->assessment_model->get_question_ids_for_assessment($assessment_id);
        if (empty($q_ids)) {
            return [];
        }

        $user_scores = $this->_user_overall_scores($assessment_id, $q_ids);
        if (count($user_scores) < 4) {
            return [];
        }

        usort($user_scores, static function ($a, $b) {
            return ($b['score'] <=> $a['score']);
        });

        $n       = count($user_scores);
        $slice   = max(1, (int) ceil($n * 0.27));
        $high_ids = array_column(array_slice($user_scores, 0, $slice), 'user_id');
        $low_ids  = array_column(array_slice($user_scores, -$slice), 'user_id');

        $assessment = $this->assessment_model->get_assessment($assessment_id);
        $title      = $assessment ? (string) ($assessment->title ?? '') : '';

        $questions = $this->db
            ->select('id, question_text')
            ->where_in('id', $q_ids)
            ->where('archived', 0)
            ->get('lib_assessment_questions')
            ->result();

        $rows = [];
        foreach ($questions as $q) {
            $qid = (int) $q->id;
            $rows[] = (object) [
                'question_id'      => $qid,
                'question_text'    => (string) $q->question_text,
                'assessment_id'    => (int) $assessment_id,
                'assessment_title' => $title,
                'high_correct_pct' => $this->_pct_correct_for_users($qid, $high_ids),
                'low_correct_pct'  => $this->_pct_correct_for_users($qid, $low_ids),
            ];
        }

        return $rows;
    }

    /**
     * @param int[] $q_ids
     * @return array<int,array{user_id:int,score:float}>
     */
    private function _user_overall_scores($assessment_id, array $q_ids)
    {
        $out = [];

        $r = $this->db
            ->select('DISTINCT user_id', false)
            ->from('assessment_answers')
            ->where_in('question_id', $q_ids)
            ->where('archived', 0)
            ->get();

        if ( ! $r || $r->num_rows() === 0) {
            return [];
        }

        foreach ($r->result() as $row) {
            $uid    = (int) $row->user_id;
            $result = $this->assessment_model->get_result($uid, (int) $assessment_id);
            $out[]  = [
                'user_id' => $uid,
                'score'   => (float) ($result['score'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @param int[] $user_ids
     */
    private function _pct_correct_for_users($question_id, array $user_ids)
    {
        if (empty($user_ids)) {
            return 0.0;
        }

        $r = $this->db
            ->select('
                COUNT(*) AS total,
                SUM(CASE WHEN score >= 99.99 THEN 1 ELSE 0 END) AS correct
            ', false)
            ->from('assessment_answers')
            ->where('question_id', (int) $question_id)
            ->where_in('user_id', array_map('intval', $user_ids))
            ->where('archived', 0)
            ->where('score IS NOT NULL', null, false)
            ->get()
            ->row();

        if ( ! $r || (int) $r->total < 1) {
            return 0.0;
        }

        return round(((int) $r->correct / (int) $r->total) * 100, 2);
    }
}

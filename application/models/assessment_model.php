<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Assessment_model
 *
 * Tables used (exact schema):
 * ─────────────────────────────────────────────────────────────
 * lib_assessments        id, module_id, type (pre|post), title, created_at, archived
 * lib_assessment_questions  id, assessment_id, question_text, question_type, is_required, min_words, archived
 * lib_assessment_choices    id, question_id, choice_text, is_correct, choice_order, archived  ← NEW
 * assessment_answers     id, question_id, user_id, answer_text, score, checked_by, checked_at, archived
 *
 * ─────────────────────────────────────────────────────────────
 * RUN THIS SQL ONCE IN YOUR DATABASE BEFORE USING THIS MODEL:
 * ─────────────────────────────────────────────────────────────
 * CREATE TABLE `lib_assessment_choices` (
 *   `id`           int(11)      NOT NULL AUTO_INCREMENT,
 *   `question_id`  int(11)      NOT NULL,
 *   `choice_text`  varchar(500) NOT NULL,
 *   `is_correct`   tinyint(1)   NOT NULL DEFAULT 0,
 *   `choice_order` int(11)      NOT NULL DEFAULT 1,
 *   `archived`     tinyint(1)   NOT NULL DEFAULT 0,
 *   PRIMARY KEY (`id`),
 *   KEY `question_id` (`question_id`),
 *   CONSTRAINT `fk_choice_question`
 *     FOREIGN KEY (`question_id`)
 *     REFERENCES `lib_assessment_questions` (`id`)
 *     ON DELETE CASCADE
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 *
 * @property CI_DB_mysqli_driver $db
 */
class assessment_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // =========================================================
    // ASSESSMENTS
    // =========================================================

    /**
     * Get assessments, optionally filtered by module_id and/or type.
     * Includes question count, module title, course title.
     */
    public function get_assessments($module_id = 0, $type = '')
    {
        $this->db
            ->select('
                la.id, la.module_id, la.type, la.title, la.created_at,
                cm.title AS module_title,
                c.id     AS course_id,
                c.title  AS course_title,
                COUNT(DISTINCT laq.id) AS question_count
            ', false)
            ->from('lib_assessments la')
            ->join('course_modules cm',
                   'cm.id = la.module_id', 'left')
            ->join('courses c',
                   'c.id = cm.course_id',  'left')
            ->join('lib_assessment_questions laq',
                   'laq.assessment_id = la.id AND laq.archived = 0', 'left')
            ->where('la.archived', 0)
            ->group_by('la.id');

        if ((int) $module_id > 0) $this->db->where('la.module_id', (int) $module_id);
        if ($type !== '')         $this->db->where('la.type',       $type);

        $r = $this->db->order_by('la.created_at', 'DESC')->get();
        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * Get all assessments for courses owned by a specific instructor.
     */
    public function get_assessments_by_instructor($user_id)
    {
        $r = $this->db
            ->select('
                la.id, la.module_id, la.type, la.title, la.created_at,
                cm.title AS module_title,
                c.id     AS course_id,
                c.title  AS course_title,
                COUNT(DISTINCT laq.id) AS question_count
            ', false)
            ->from('lib_assessments la')
            ->join('course_modules cm',
                   'cm.id = la.module_id', 'left')
            ->join('courses c',
                   'c.id = cm.course_id',  'left')
            ->join('lib_assessment_questions laq',
                   'laq.assessment_id = la.id AND laq.archived = 0', 'left')
            ->where('c.created_by', (int) $user_id)
            ->where('la.archived',  0)
            ->group_by('la.id')
            ->order_by('la.created_at', 'DESC')
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * Get a single assessment row with module + course info.
     */
    public function get_assessment($assessment_id)
    {
        $r = $this->db
            ->select('
                la.id, la.module_id, la.type, la.title, la.created_at,
                cm.title    AS module_title,
                cm.course_id,
                c.title     AS course_title,
                c.created_by AS course_owner
            ', false)
            ->from('lib_assessments la')
            ->join('course_modules cm', 'cm.id = la.module_id', 'left')
            ->join('courses c',         'c.id = cm.course_id',  'left')
            ->where('la.id',       (int) $assessment_id)
            ->where('la.archived', 0)
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->row() : null;
    }

    /** Create a new assessment. Returns new ID. */
    public function create_assessment($data)
    {
        $this->db->insert('lib_assessments', [
            'module_id'    => (int) $data['module_id'],
            'type'         => $data['type'],
            'title'        => trim($data['title']),
            'created_at'   => date('Y-m-d H:i:s'),
            'date_encoded' => date('Y-m-d H:i:s'),
            'encoded_by'   => (int) $data['encoded_by'],
        ]);
        return (int) $this->db->insert_id();
    }

    /** Update assessment title/type. */
    public function update_assessment($assessment_id, $data)
    {
        return (bool) $this->db
            ->where('id', (int) $assessment_id)
            ->update('lib_assessments', [
                'type'               => $data['type'],
                'title'              => trim($data['title']),
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $data['modified_by'],
            ]);
    }

    /** Soft-delete an assessment. */
    public function delete_assessment($assessment_id)
    {
        return (bool) $this->db
            ->where('id', (int) $assessment_id)
            ->update('lib_assessments', ['archived' => 1]);
    }

    // =========================================================
    // QUESTIONS
    // =========================================================

    /**
     * Get all non-archived questions for an assessment,
     * with choices already attached as $q->choices[].
     */
    public function get_questions($assessment_id)
    {
        $r = $this->db
            ->where('assessment_id', (int) $assessment_id)
            ->where('archived',      0)
            ->order_by('id', 'ASC')
            ->get('lib_assessment_questions');

        if ( ! $r || $r->num_rows() === 0) return [];

        $questions = $r->result();
        foreach ($questions as $q) {
            $q->choices = $this->get_choices($q->id);
        }
        return $questions;
    }

    /** Get a single question with its choices. */
    public function get_question($question_id)
    {
        $r = $this->db
            ->where('id',       (int) $question_id)
            ->where('archived', 0)
            ->get('lib_assessment_questions');

        if ( ! $r || $r->num_rows() === 0) return null;

        $q          = $r->row();
        $q->choices = $this->get_choices($q->id);
        return $q;
    }

    /** Create a question. Returns new question ID. */
    public function create_question($data)
    {
        $this->db->insert('lib_assessment_questions', [
            'assessment_id' => (int) $data['assessment_id'],
            'question_text' => trim($data['question_text']),
            'question_type' => $data['question_type'],
            'is_required'   => empty($data['is_required']) ? 0 : 1,
            'min_words'     => ( ! empty($data['min_words']) && (int)$data['min_words'] > 0)
                               ? (int) $data['min_words'] : null,
            'date_encoded'  => date('Y-m-d H:i:s'),
            'encoded_by'    => (int) $data['encoded_by'],
        ]);
        return (int) $this->db->insert_id();
    }

    /** Update a question's text/type/settings. */
    public function update_question($question_id, $data)
    {
        return (bool) $this->db
            ->where('id', (int) $question_id)
            ->update('lib_assessment_questions', [
                'question_text'      => trim($data['question_text']),
                'question_type'      => $data['question_type'],
                'is_required'        => empty($data['is_required']) ? 0 : 1,
                'min_words'          => ( ! empty($data['min_words']) && (int)$data['min_words'] > 0)
                                        ? (int) $data['min_words'] : null,
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $data['modified_by'],
            ]);
    }

    /** Soft-delete question and its choices. */
    public function delete_question($question_id)
    {
        $this->db->where('question_id', (int) $question_id)
                 ->update('lib_assessment_choices', ['archived' => 1]);

        return (bool) $this->db
            ->where('id', (int) $question_id)
            ->update('lib_assessment_questions', ['archived' => 1]);
    }

    // =========================================================
    // CHOICES (lib_assessment_choices)
    // =========================================================

    /** Get non-archived choices for a question ordered by choice_order. */
    public function get_choices($question_id)
    {
        $r = $this->db
            ->where('question_id', (int) $question_id)
            ->where('archived',    0)
            ->order_by('choice_order', 'ASC')
            ->get('lib_assessment_choices');

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * Replace all choices for a question.
     * Soft-deletes old ones, inserts new set.
     *
     * @param int   $question_id
     * @param array $choices  [['text'=>'...','is_correct'=>0|1], ...]
     */
    public function save_choices($question_id, $choices)
    {
        $this->db->where('question_id', (int) $question_id)
                 ->update('lib_assessment_choices', ['archived' => 1]);

        if (empty($choices)) return;

        $order = 1;
        foreach ($choices as $c) {
            $text = trim($c['text'] ?? '');
            if ($text === '') continue;
            $this->db->insert('lib_assessment_choices', [
                'question_id'  => (int) $question_id,
                'choice_text'  => $text,
                'is_correct'   => empty($c['is_correct']) ? 0 : 1,
                'choice_order' => $order++,
                'archived'     => 0,
            ]);
        }
    }

    // =========================================================
    // ANSWERS (assessment_answers)
    // =========================================================

    /**
     * Has this user already submitted answers for this assessment?
     */
    public function has_answered($user_id, $assessment_id)
    {
        $q_ids = $this->_get_question_ids($assessment_id);
        if (empty($q_ids)) return false;

        return (bool) $this->db
            ->where_in('question_id', $q_ids)
            ->where('user_id',  (int) $user_id)
            ->where('archived', 0)
            ->count_all_results('assessment_answers');
    }

    /**
     * Get user's answers for an assessment, keyed by question_id.
     * Returns [question_id => answer_object].
     */
    public function get_user_answers($user_id, $assessment_id)
    {
        $q_ids = $this->_get_question_ids($assessment_id);
        if (empty($q_ids)) return [];

        $r = $this->db
            ->where_in('question_id', $q_ids)
            ->where('user_id',  (int) $user_id)
            ->where('archived', 0)
            ->get('assessment_answers');

        if ( ! $r || $r->num_rows() === 0) return [];

        $map = [];
        foreach ($r->result() as $row) {
            $map[$row->question_id] = $row;
        }
        return $map;
    }

    /**
     * Submit answers for an assessment.
     * Auto-scores multiple_choice and fill_blank.
     * Essay / likert left as null (pending manual review).
     *
     * @return array ['submitted'=>int, 'auto_scored'=>int, 'pending_review'=>int]
     */
    public function submit_answers($user_id, $assessment_id, $answers)
    {
        $questions   = $this->get_questions($assessment_id);
        $submitted   = 0;
        $auto_scored = 0;
        $pending     = 0;
        $now         = date('Y-m-d H:i:s');

        foreach ($questions as $q) {
            $answer_text = trim($answers[$q->id] ?? '');

            if ($answer_text === '' && ! $q->is_required) continue;

            // ── Auto-score ────────────────────────────────────
            $score = null;

            if ($q->question_type === 'multiple_choice') {
                $score = 0.00;
                foreach ($q->choices as $choice) {
                    if ((int) $choice->is_correct === 1
                        && (string) $choice->id === $answer_text) {
                        $score = 100.00;
                        break;
                    }
                }
                $auto_scored++;

            } elseif ($q->question_type === 'fill_blank') {
                $correct = '';
                foreach ($q->choices as $choice) {
                    if ((int) $choice->is_correct === 1) {
                        $correct = strtolower(trim($choice->choice_text));
                        break;
                    }
                }
                $score = (strtolower($answer_text) === $correct) ? 100.00 : 0.00;
                $auto_scored++;

            } else {
                // essay / likert — manual review needed
                $pending++;
            }

            // ── Upsert ────────────────────────────────────────
            $existing = $this->db
                ->where('question_id', $q->id)
                ->where('user_id',     (int) $user_id)
                ->where('archived',    0)
                ->get('assessment_answers')
                ->row();

            if ($existing) {
                $this->db->where('id', $existing->id)
                         ->update('assessment_answers', [
                             'answer_text'        => $answer_text,
                             'score'              => $score,
                             'checked_by'         => null,
                             'checked_at'         => null,
                             'date_last_modified' => $now,
                             'modified_by'        => (int) $user_id,
                         ]);
            } else {
                $this->db->insert('assessment_answers', [
                    'question_id'  => $q->id,
                    'user_id'      => (int) $user_id,
                    'answer_text'  => $answer_text,
                    'score'        => $score,
                    'date_encoded' => $now,
                    'encoded_by'   => (int) $user_id,
                ]);
            }
            $submitted++;
        }

        return [
            'submitted'      => $submitted,
            'auto_scored'    => $auto_scored,
            'pending_review' => $pending,
        ];
    }

    /**
     * Grade a single answer (instructor).
     *
     * @param int   $answer_id
     * @param float $score       0–100
     * @param int   $checker_id
     */
    public function score_answer($answer_id, $score, $checker_id)
    {
        return (bool) $this->db
            ->where('id', (int) $answer_id)
            ->update('assessment_answers', [
                'score'              => round((float) $score, 2),
                'checked_by'         => (int) $checker_id,
                'checked_at'         => date('Y-m-d H:i:s'),
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $checker_id,
            ]);
    }

    /**
     * Calculate overall score for a user on an assessment.
     *
     * @return array ['score'=>float, 'scored'=>int, 'total'=>int, 'pending'=>int]
     */
    public function get_result($user_id, $assessment_id)
    {
        $q_ids = $this->_get_question_ids($assessment_id);
        $total = count($q_ids);

        if ($total === 0) {
            return ['score' => 0, 'scored' => 0, 'total' => 0, 'pending' => 0];
        }

        $r = $this->db
            ->select('score')
            ->where_in('question_id', $q_ids)
            ->where('user_id',  (int) $user_id)
            ->where('archived', 0)
            ->get('assessment_answers');

        $scored = $pending = 0;
        $sum    = 0;

        if ($r && $r->num_rows() > 0) {
            foreach ($r->result() as $row) {
                if ($row->score !== null) {
                    $sum += (float) $row->score;
                    $scored++;
                } else {
                    $pending++;
                }
            }
        }

        return [
            'score'   => $scored > 0 ? round($sum / $scored, 2) : 0,
            'scored'  => $scored,
            'total'   => $total,
            'pending' => $pending,
        ];
    }

    /**
     * Summary of all students who attempted an assessment.
     * Used on instructor review page.
     */
    public function get_attempt_summary($assessment_id)
    {
        $q_ids = $this->_get_question_ids($assessment_id);
        if (empty($q_ids)) return [];

        $r = $this->db
            ->select('
                aa.user_id,
                u.fullname    AS student_name,
                u.employee_id,
                COUNT(aa.id)  AS answered,
                SUM(CASE WHEN aa.score IS NOT NULL THEN 1 ELSE 0 END) AS scored,
                SUM(CASE WHEN aa.score IS NULL     THEN 1 ELSE 0 END) AS pending,
                AVG(aa.score) AS avg_score,
                MAX(aa.date_encoded) AS submitted_at
            ', false)
            ->from('assessment_answers aa')
            ->join('aauth_users u', 'u.id = aa.user_id', 'left')
            ->where_in('aa.question_id', $q_ids)
            ->where('aa.archived', 0)
            ->group_by('aa.user_id')
            ->order_by('submitted_at', 'DESC')
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * Get all answers for a specific student on an assessment.
     * Used on instructor's detailed review per student.
     */
    public function get_student_answers($user_id, $assessment_id)
    {
        $q_ids = $this->_get_question_ids($assessment_id);
        if (empty($q_ids)) return [];

        $r = $this->db
            ->select('
                aa.id, aa.question_id, aa.answer_text, aa.score,
                aa.checked_by, aa.checked_at, aa.date_encoded,
                laq.question_text, laq.question_type, laq.min_words,
                checker.fullname AS checker_name
            ', false)
            ->from('assessment_answers aa')
            ->join('lib_assessment_questions laq',
                   'laq.id = aa.question_id', 'left')
            ->join('aauth_users checker',
                   'checker.id = aa.checked_by', 'left')
            ->where_in('aa.question_id', $q_ids)
            ->where('aa.user_id',  (int) $user_id)
            ->where('aa.archived', 0)
            ->order_by('aa.question_id', 'ASC')
            ->get();

        if ( ! $r || $r->num_rows() === 0) return [];

        $answers = $r->result();
        foreach ($answers as $a) {
            $a->choices = $this->get_choices($a->question_id);
        }
        return $answers;
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    private function _get_question_ids($assessment_id)
    {
        $r = $this->db
            ->select('id')
            ->where('assessment_id', (int) $assessment_id)
            ->where('archived',      0)
            ->get('lib_assessment_questions');

        if ( ! $r || $r->num_rows() === 0) return [];

        return array_map('intval',
            array_column($r->result_array(), 'id')
        );
    }
}
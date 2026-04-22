<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Video checkpoint delivery for course modules (part of the assessment domain).
 *
 * Data lives in lib_assessments (type=checkpoint, context=video),
 * lib_assessment_questions, lib_assessment_choices, assessment_answers.
 * Legacy fallback: course_module_youtube_quizzes + user_youtube_quiz_passes when
 * unified schema/data is not present for a module.
 *
 * choices column on legacy table is LONGTEXT (JSON string).
 *
 * @property CI_DB_mysqli_driver   $db
 * @property assessment_model      $assessment_model
 */
class Module_video_checkpoint_model extends CI_Model {

    /** @var bool|null */
    private $_table_legacy_checkpoint;

    /** @var bool|null */
    private $_table_passes;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('assessment_model');
    }

    // =========================================================
    // SCHEMA
    // =========================================================

    /**
     * Unified checkpoint rows exist for this module (post-migration data).
     */
    private function _module_has_unified_checkpoints($module_id)
    {
        if ( ! $this->assessment_model->assessments_checkpoint_schema_ready()) {
            return false;
        }

        return (int) $this->db
            ->where('module_id', (int) $module_id)
            ->where('type', 'checkpoint')
            ->where('context', 'video')
            ->where('archived', 0)
            ->count_all_results('lib_assessments') > 0;
    }

    private function _module_has_legacy_checkpoint_rows($module_id)
    {
        if ( ! $this->_has_legacy_checkpoint_table()) {
            return false;
        }

        return (int) $this->db
            ->where('module_id', (int) $module_id)
            ->where('archived', 0)
            ->count_all_results('course_module_youtube_quizzes') > 0;
    }

    /**
     * Prefer lib_assessments when this module has migrated checkpoints; otherwise use legacy
     * if data still lives there (schema may exist before rows are migrated).
     */
    private function _use_unified_data_for_module($module_id)
    {
        if ( ! $this->assessment_model->assessments_checkpoint_schema_ready()) {
            return false;
        }
        if ($this->_module_has_unified_checkpoints($module_id)) {
            return true;
        }
        if ($this->_module_has_legacy_checkpoint_rows($module_id)) {
            return false;
        }

        return true;
    }

    private function _has_legacy_checkpoint_table()
    {
        if ($this->_table_legacy_checkpoint === null) {
            $this->_table_legacy_checkpoint = (bool) $this->db->table_exists('course_module_youtube_quizzes');
        }

        return $this->_table_legacy_checkpoint;
    }

    private function _has_passes_table()
    {
        if ($this->_table_passes === null) {
            $this->_table_passes = (bool) $this->db->table_exists('user_youtube_quiz_passes');
        }

        return $this->_table_passes;
    }

    /**
     * Decode choices from LONGTEXT / legacy JSON column / accidental array.
     *
     * @param mixed $raw
     * @return string[]
     */
    public function decode_choices($raw)
    {
        if (is_array($raw)) {
            $out = [];
            foreach ($raw as $v) {
                if (is_string($v)) {
                    $out[] = $v;
                } elseif (is_numeric($v)) {
                    $out[] = (string) $v;
                }
            }

            return $out;
        }

        if ( ! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->decode_choices($decoded);
        }

        $decoded = json_decode(stripslashes($raw), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->decode_choices($decoded);
        }

        return [];
    }

    /**
     * @param string[] $choices
     * @return string JSON for LONGTEXT storage
     */
    public function encode_choices(array $choices)
    {
        $clean = [];
        foreach ($choices as $c) {
            $clean[] = is_string($c) ? $c : (string) $c;
        }

        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Extract 11-char YouTube video id from URL, embed snippet, or content_path.
     */
    public static function extract_youtube_video_id($content_path)
    {
        $s = trim((string) $content_path);
        if ($s === '') {
            return null;
        }
        if (preg_match(
            '#(?:youtube\.com/(?:embed/|watch\?v=|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#',
            $s,
            $m
        )) {
            return $m[1];
        }
        if (preg_match('#src=["\'][^"\']*youtube\.com/embed/([A-Za-z0-9_-]{11})#i', $s, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Whether this module is a video type with detectable YouTube id.
     */
    public function is_youtube_module($module)
    {
        if ( ! $module || ($module->content_type ?? '') !== 'video') {
            return false;
        }

        return self::extract_youtube_video_id($module->content_path ?? '') !== null;
    }

    /**
     * Checkpoint rows for this module (unified lib_assessments or legacy table).
     *
     * @param int $module_id
     * @return object[]
     */
    public function get_checkpoints_by_module($module_id)
    {
        if ($this->_use_unified_data_for_module($module_id)) {
            $r = $this->db
                ->where('module_id', (int) $module_id)
                ->where('type', 'checkpoint')
                ->where('context', 'video')
                ->where('archived', 0)
                ->order_by('sort_order', 'ASC')
                ->order_by('id', 'ASC')
                ->get('lib_assessments');

            return ($r && $r->num_rows() > 0) ? $r->result() : [];
        }

        if ( ! $this->_has_legacy_checkpoint_table()) {
            return [];
        }

        $r = $this->db
            ->where('module_id', (int) $module_id)
            ->where('archived', 0)
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get('course_module_youtube_quizzes');

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * Public payload (no correct answer) for client.
     *
     * @param int $module_id
     * @return array<int,array<string,mixed>>
     */
    public function get_public_checkpoints_payload($module_id)
    {
        $out = [];

        if ($this->_use_unified_data_for_module($module_id)) {
            foreach ($this->get_checkpoints_by_module($module_id) as $la) {
                $q = $this->db
                    ->where('assessment_id', (int) $la->id)
                    ->where('archived', 0)
                    ->order_by('id', 'ASC')
                    ->get('lib_assessment_questions', 1)
                    ->row();

                $choice_labels = [];
                if ($q) {
                    foreach ($this->assessment_model->get_choices((int) $q->id) as $c) {
                        $choice_labels[] = (string) $c->choice_text;
                    }
                }

                $tt = $la->trigger_type ?? 'manual';
                $tv = isset($la->trigger_value) ? (float) $la->trigger_value : null;

                $out[] = [
                    'id'              => (int) $la->id,
                    'question'        => $q ? (string) $q->question_text : '',
                    'choices'         => $choice_labels,
                    'trigger_percent' => ($tt === 'percent') ? $tv : null,
                    'trigger_seconds' => ($tt === 'seconds') ? (int) round((float) $tv) : 0,
                    'is_required'     => (int) ($la->is_required ?? 1) === 1,
                    'sort_order'      => (int) ($la->sort_order ?? 0),
                ];
            }

            return $out;
        }

        foreach ($this->get_checkpoints_by_module($module_id) as $row) {
            $choices = $this->decode_choices($row->choices ?? '');
            $out[]   = [
                'id'              => (int) $row->id,
                'question'        => (string) $row->question,
                'choices'         => $choices,
                'trigger_percent'  => $row->trigger_percent !== null
                    ? (float) $row->trigger_percent
                    : null,
                'trigger_seconds'  => (int) ($row->trigger_seconds ?? 0),
                'is_required'      => (int) ($row->is_required ?? 1) === 1,
                'sort_order'       => (int) ($row->sort_order ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @param int $assessment_id lib_assessments.id (unified) or legacy course_module_youtube_quizzes.id
     * @return object|null
     */
    public function get_checkpoint_assessment($assessment_id)
    {
        $aid = (int) $assessment_id;

        if ($this->assessment_model->assessments_checkpoint_schema_ready()) {
            $r = $this->db
                ->where('id', $aid)
                ->where('type', 'checkpoint')
                ->where('context', 'video')
                ->where('archived', 0)
                ->get('lib_assessments', 1);

            if ($r && $r->num_rows() > 0) {
                return $r->row();
            }
        }

        if ( ! $this->_has_legacy_checkpoint_table()) {
            return null;
        }

        $r = $this->db
            ->where('id', $aid)
            ->where('archived', 0)
            ->get('course_module_youtube_quizzes', 1);

        return ($r && $r->num_rows() > 0) ? $r->row() : null;
    }

    /**
     * @param int $module_id
     * @return int
     */
    public function count_required_checkpoints($module_id)
    {
        if ($this->_use_unified_data_for_module($module_id)) {
            return (int) $this->db
                ->where('module_id', (int) $module_id)
                ->where('type', 'checkpoint')
                ->where('context', 'video')
                ->where('is_required', 1)
                ->where('archived', 0)
                ->count_all_results('lib_assessments');
        }

        if ( ! $this->_has_legacy_checkpoint_table()) {
            return 0;
        }

        return (int) $this->db
            ->where('module_id', (int) $module_id)
            ->where('is_required', 1)
            ->where('archived', 0)
            ->count_all_results('course_module_youtube_quizzes');
    }

    /**
     * @param int $module_id
     */
    public function has_required_checkpoints($module_id)
    {
        return $this->count_required_checkpoints($module_id) > 0;
    }

    /**
     * @param int $user_id
     * @param int $module_id
     * @return int[] assessment ids the user has passed
     */
    public function get_passed_checkpoint_assessment_ids($user_id, $module_id)
    {
        if ($this->_use_unified_data_for_module($module_id)) {
            $uid = (int) $user_id;
            $mid = (int) $module_id;

            $sql = '
                SELECT DISTINCT la.id AS aid
                FROM lib_assessments la
                INNER JOIN lib_assessment_questions q
                    ON q.assessment_id = la.id AND q.archived = 0
                INNER JOIN assessment_answers aa
                    ON aa.question_id = q.id
                    AND aa.user_id = ?
                    AND aa.archived = 0
                WHERE la.module_id = ?
                  AND la.type = ?
                  AND la.context = ?
                  AND la.archived = 0
                  AND aa.score IS NOT NULL
                  AND aa.score >= 100
            ';

            $r = $this->db->query($sql, [$uid, $mid, 'checkpoint', 'video']);
            if ( ! $r || $r->num_rows() === 0) {
                return [];
            }

            return array_map('intval', array_column($r->result_array(), 'aid'));
        }

        if ( ! $this->_has_legacy_checkpoint_table() || ! $this->_has_passes_table()) {
            return [];
        }

        $r = $this->db
            ->select('p.quiz_id')
            ->from('user_youtube_quiz_passes p')
            ->join('course_module_youtube_quizzes q', 'q.id = p.quiz_id', 'inner')
            ->where('p.user_id', (int) $user_id)
            ->where('p.archived', 0)
            ->where('q.module_id', (int) $module_id)
            ->where('q.archived', 0)
            ->get();

        if ( ! $r || $r->num_rows() === 0) {
            return [];
        }

        return array_map('intval', array_column($r->result_array(), 'quiz_id'));
    }

    /**
     * @param int $user_id
     * @param int $module_id
     * @return bool true if nothing to enforce or all required checkpoints passed
     */
    public function user_passed_all_required($user_id, $module_id)
    {
        if ($this->_use_unified_data_for_module($module_id)) {
            $required = $this->count_required_checkpoints($module_id);
            if ($required < 1) {
                return true;
            }

            $sql = '
                SELECT COUNT(DISTINCT la.id) AS cnt
                FROM lib_assessments la
                INNER JOIN lib_assessment_questions q
                    ON q.assessment_id = la.id AND q.archived = 0
                INNER JOIN assessment_answers aa
                    ON aa.question_id = q.id
                    AND aa.user_id = ?
                    AND aa.archived = 0
                WHERE la.module_id = ?
                  AND la.type = ?
                  AND la.context = ?
                  AND la.is_required = 1
                  AND la.archived = 0
                  AND aa.score IS NOT NULL
                  AND aa.score >= 100
            ';

            $r   = $this->db->query($sql, [(int) $user_id, (int) $module_id, 'checkpoint', 'video']);
            $cnt = $r && $r->num_rows() > 0 ? (int) $r->row()->cnt : 0;

            return $cnt >= $required;
        }

        if ( ! $this->_has_legacy_checkpoint_table()) {
            return true;
        }

        $required = $this->count_required_checkpoints($module_id);
        if ($required < 1) {
            return true;
        }

        if ( ! $this->_has_passes_table()) {
            return false;
        }

        $cnt = (int) $this->db
            ->from('user_youtube_quiz_passes p')
            ->join('course_module_youtube_quizzes q', 'q.id = p.quiz_id', 'inner')
            ->where('p.user_id', (int) $user_id)
            ->where('p.archived', 0)
            ->where('q.module_id', (int) $module_id)
            ->where('q.archived', 0)
            ->where('q.is_required', 1)
            ->count_all_results();

        return $cnt >= $required;
    }

    /**
     * @param int    $user_id
     * @param int    $assessment_id unified: lib_assessments.id or legacy row id
     * @param int    $choice_index
     * @param int    $expected_module_id
     * @return array{ok:bool,message:string}
     */
    public function submit_checkpoint_answer($user_id, $assessment_id, $choice_index, $expected_module_id)
    {
        $checkpoint = $this->get_checkpoint_assessment($assessment_id);
        if ( ! $checkpoint) {
            return ['ok' => false, 'message' => 'Checkpoint not found.'];
        }

        if (isset($checkpoint->type, $checkpoint->context)
            && $checkpoint->type === 'checkpoint'
            && $checkpoint->context === 'video'
        ) {
            return $this->assessment_model->save_checkpoint_pass(
                (int) $user_id,
                (int) $assessment_id,
                (int) $choice_index,
                (int) $expected_module_id
            );
        }

        if ( ! $this->_has_legacy_checkpoint_table() || ! $this->_has_passes_table()) {
            return ['ok' => false, 'message' => 'Checkpoints are not available.'];
        }

        if ((int) $checkpoint->module_id !== (int) $expected_module_id) {
            return ['ok' => false, 'message' => 'Invalid checkpoint for this module.'];
        }

        $choices = $this->decode_choices($checkpoint->choices ?? '');
        if (empty($choices)) {
            return ['ok' => false, 'message' => 'Invalid checkpoint configuration.'];
        }

        $idx = (int) $choice_index;
        if ($idx < 0 || $idx >= count($choices)) {
            return ['ok' => false, 'message' => 'Invalid answer.'];
        }

        if ($idx !== (int) $checkpoint->correct_choice_index) {
            return ['ok' => false, 'message' => 'Incorrect. Review the material and try again.'];
        }

        $now = date('Y-m-d H:i:s');
        $uid = (int) $user_id;
        $ok  = $this->db->replace('user_youtube_quiz_passes', [
            'user_id'            => $uid,
            'quiz_id'            => (int) $assessment_id,
            'passed_at'          => $now,
            'date_encoded'       => $now,
            'encoded_by'         => $uid,
            'date_last_modified' => $now,
            'modified_by'        => $uid,
            'archived'           => 0,
        ]);

        if ( ! $ok) {
            $dbErr = $this->db->error();
            log_message('error', 'user_youtube_quiz_passes replace failed: ' . json_encode($dbErr));

            return ['ok' => false, 'message' => 'Could not save your answer. Please try again.'];
        }

        return ['ok' => true, 'message' => 'Correct!'];
    }
}

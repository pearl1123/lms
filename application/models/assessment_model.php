<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Assessment_model
 *
 * Tables used (exact schema):
 * ─────────────────────────────────────────────────────────────
 * lib_assessments        id, module_id, type (pre|post|checkpoint), context, trigger_*,
 *                        title, created_at, archived (+ legacy_checkpoint_id after migration)
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
     *
     * @param int    $module_id
     * @param string $type                  pre | post | checkpoint | ''
     * @param bool   $include_checkpoints   When $type is empty, include type=checkpoint (manager listing).
     */
    public function get_assessments($module_id = 0, $type = '', $include_checkpoints = false)
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

        if ((int) $module_id > 0) {
            $this->db->where('la.module_id', (int) $module_id);
        }
        if ($type !== '') {
            $this->db->where('la.type', $type);
        } elseif ( ! $include_checkpoints) {
            $this->db->where('la.type !=', 'checkpoint');
        }

        $r = $this->db->order_by('la.created_at', 'DESC')->get();
        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * Get all assessments for courses owned by a specific instructor.
     *
     * @param bool $include_checkpoints Include video checkpoint rows in the list
     */
    public function get_assessments_by_instructor($user_id, $include_checkpoints = false)
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
            ->where('la.archived',  0)
            ->group_by('la.id')
            ->order_by('la.created_at', 'DESC');

        $CI =& get_instance();
        $CI->load->model('Course_phase2_model', 'course_phase2');
        $CI->course_phase2->restrict_query_to_instructor_courses((int) $user_id);

        if ( ! $include_checkpoints) {
            $this->db->where('la.type !=', 'checkpoint');
        }

        $r = $this->db->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * True after lib_assessments.context (and related) columns exist — unified video checkpoints.
     */
    public function assessments_checkpoint_schema_ready()
    {
        static $ready = null;
        if ($ready === null) {
            $ready = (bool) $this->db->field_exists('context', 'lib_assessments');
        }

        return $ready;
    }

    /**
     * Physical FK column on lib_assessments for migrated-from-legacy checkpoint rows.
     *
     * @deprecated Supports legacy_youtube_quiz_id until migration_rename_quiz_to_video_checkpoint_UP.sql is applied everywhere.
     * @return string|null legacy_checkpoint_id, legacy_youtube_quiz_id, or null
     */
    public function legacy_checkpoint_link_column()
    {
        static $memo = '__unset__';
        if ($memo === '__unset__') {
            if ($this->db->field_exists('legacy_checkpoint_id', 'lib_assessments')) {
                $memo = 'legacy_checkpoint_id';
            } elseif ($this->db->field_exists('legacy_youtube_quiz_id', 'lib_assessments')) {
                $memo = 'legacy_youtube_quiz_id';
            } else {
                $memo = null;
            }
        }

        return $memo;
    }

    /**
     * Get a single assessment row with module + course info.
     */
    public function get_assessment($assessment_id)
    {
        $extra_cols = '';
        if ($this->assessments_randomize_column_ready()) {
            $extra_cols .= ', la.randomize_questions';
        }
        if ($this->assessments_content_version_ready()) {
            $extra_cols .= ', la.content_version';
        }

        if ($this->assessments_checkpoint_schema_ready()) {
            $extra_cols = ($extra_cols ?? '') . ',
                la.context, la.trigger_type, la.trigger_value, la.is_required, la.sort_order';
            $link = $this->legacy_checkpoint_link_column();
            if ($link === 'legacy_checkpoint_id') {
                $extra_cols .= ', la.legacy_checkpoint_id';
            } elseif ($link === 'legacy_youtube_quiz_id') {
                $extra_cols .= ', la.legacy_youtube_quiz_id AS legacy_checkpoint_id';
            }
        }

        $r = $this->db
            ->select('
                la.id, la.module_id, la.type, la.title, la.created_at
                '.$extra_cols.',
                cm.title    AS module_title,
                cm.course_id,
                c.title     AS course_title
            ', false)
            ->from('lib_assessments la')
            ->join('course_modules cm', 'cm.id = la.module_id', 'left')
            ->join('courses c',         'c.id = cm.course_id',  'left')
            ->where('la.id',       (int) $assessment_id)
            ->where('la.archived', 0)
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->row() : null;
    }

    /**
     * Find the active assessment for a module/type that has questions.
     * Used to recover from duplicate stale assessment rows that have no questions.
     *
     * @param int    $module_id
     * @param string $type pre|post
     * @return object|null
     */
    public function get_assessment_with_questions_for_module($module_id, $type)
    {
        $r = $this->db
            ->select('la.id, la.module_id, la.type, la.title, COUNT(DISTINCT laq.id) AS question_count', false)
            ->from('lib_assessments la')
            ->join(
                'lib_assessment_questions laq',
                'laq.assessment_id = la.id AND laq.archived = 0',
                'inner'
            )
            ->where('la.module_id', (int) $module_id)
            ->where('la.type', (string) $type)
            ->where('la.archived', 0)
            ->group_by('la.id')
            ->order_by('question_count', 'DESC')
            ->order_by('la.created_at', 'DESC')
            ->order_by('la.id', 'DESC')
            ->limit(1)
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->row() : null;
    }

    /** Create a new assessment. Returns new ID, or 0 on blocked video checkpoint (limit / duplicate second). */
    public function create_assessment($data)
    {
        $now = date('Y-m-d H:i:s');
        $uid = (int) $data['encoded_by'];
        $mid = (int) $data['module_id'];
        $row = [
            'module_id'    => $mid,
            'type'         => $data['type'],
            'title'        => trim($data['title']),
            'created_at'   => $now,
            'date_encoded' => $now,
            'encoded_by'   => $uid,
        ];

        if (($data['type'] ?? '') === 'checkpoint' && $this->assessments_checkpoint_schema_ready()) {
            $this->load->model('Module_video_checkpoint_model', '_vchklim');
            $vlim = $this->_vchklim;
            if ($vlim->count_lib_video_checkpoints_for_module($mid) >= Module_video_checkpoint_model::MAX_VIDEO_CHECKPOINTS_PER_MODULE) {
                log_message('warning', 'create_assessment: video checkpoint cap reached for module ' . $mid);

                return 0;
            }

            $secs = isset($data['trigger_seconds']) ? (int) $data['trigger_seconds'] : 0;
            $vd   = isset($data['video_duration_seconds']) ? (int) $data['video_duration_seconds'] : 0;
            if ($secs > 0 && $vd > 0 && $secs > $vd) {
                log_message(
                    'warning',
                    'create_assessment: video checkpoint trigger_seconds exceeds video duration (' . $secs . ' > ' . $vd . ') module ' . $mid
                );

                return 0;
            }
            if ($secs > 0 && $vlim->lib_video_checkpoint_trigger_seconds_is_taken($mid, $secs, 0)) {
                log_message('warning', 'create_assessment: duplicate video checkpoint trigger_seconds ' . $secs . ' module ' . $mid);

                return 0;
            }
            if ($secs > 0) {
                $row['trigger_type']  = 'seconds';
                $row['trigger_value'] = (float) $secs;
            } else {
                $row['trigger_type']  = 'percent';
                $row['trigger_value'] = isset($data['trigger_percent'])
                    ? (float) $data['trigger_percent']
                    : 0.0;
            }
            $row['context']     = 'video';
            $row['is_required']  = ! empty($data['is_required']) ? 1 : 0;
            $row['sort_order']   = (int) ($data['sort_order'] ?? 0);
        }

        if ($this->assessments_randomize_column_ready()) {
            $this->load->helper('etd_phase4');
            $atype = strtolower(trim((string) ($data['type'] ?? '')));
            if ($atype === 'checkpoint') {
                $row['randomize_questions'] = 0;
            } elseif (in_array($atype, ['pre', 'post'], true)) {
                $row['randomize_questions'] = array_key_exists('randomize_questions', $data)
                    ? (! empty($data['randomize_questions']) ? 1 : 0)
                    : etd_assessment_randomize_default_for_type($atype);
            }
        }

        $this->db->insert('lib_assessments', $row);

        return (int) $this->db->insert_id();
    }

    /** Update assessment title/type/module and optional checkpoint fields. False if duplicate checkpoint second. */
    public function update_assessment($assessment_id, $data)
    {
        $aid = (int) $assessment_id;
        $update = [
            'type'               => $data['type'],
            'title'              => trim($data['title']),
            'date_last_modified' => date('Y-m-d H:i:s'),
            'modified_by'        => (int) $data['modified_by'],
        ];

        if (isset($data['module_id'])) {
            $update['module_id'] = (int) $data['module_id'];
        }

        if (($data['type'] ?? '') === 'checkpoint' && $this->assessments_checkpoint_schema_ready()) {
            $secs = isset($data['trigger_seconds']) ? (int) $data['trigger_seconds'] : 0;
            $mod  = (int) ($update['module_id'] ?? $data['module_id'] ?? 0);
            if ($mod < 1) {
                $ex = $this->get_assessment($aid);
                $mod = $ex ? (int) $ex->module_id : 0;
            }
            $vd = isset($data['video_duration_seconds']) ? (int) $data['video_duration_seconds'] : 0;
            if ($secs > 0 && $vd > 0 && $secs > $vd) {
                log_message(
                    'warning',
                    'update_assessment: video checkpoint trigger_seconds exceeds video duration (' . $secs . ' > ' . $vd . ') assessment ' . $aid
                );

                return false;
            }
            if ($secs > 0 && $mod > 0) {
                $this->load->model('Module_video_checkpoint_model', '_vchkdup');
                if ($this->_vchkdup->lib_video_checkpoint_trigger_seconds_is_taken($mod, $secs, $aid)) {
                    log_message('warning', 'update_assessment: duplicate checkpoint trigger_seconds ' . $secs . ' module ' . $mod);

                    return false;
                }
            }
            if ($secs > 0) {
                $update['trigger_type']  = 'seconds';
                $update['trigger_value'] = (float) $secs;
            } else {
                $update['trigger_type']  = 'percent';
                $update['trigger_value'] = isset($data['trigger_percent'])
                    ? (float) $data['trigger_percent']
                    : 0.0;
            }
            $update['context']    = 'video';
            $update['is_required'] = ! empty($data['is_required']) ? 1 : 0;
            if (array_key_exists('sort_order', $data)) {
                $update['sort_order'] = (int) $data['sort_order'];
            }
        }

        if ($this->assessments_randomize_column_ready()) {
            $this->load->helper('etd_phase4');
            $atype = strtolower(trim((string) ($data['type'] ?? '')));
            if ($atype === 'checkpoint') {
                $update['randomize_questions'] = 0;
            } elseif (in_array($atype, ['pre', 'post'], true) && array_key_exists('randomize_questions', $data)) {
                $update['randomize_questions'] = ! empty($data['randomize_questions']) ? 1 : 0;
            }
        }

        return (bool) $this->db
            ->where('id', (int) $assessment_id)
            ->update('lib_assessments', $update);
    }

    /**
     * Max seconds-based trigger_value for video checkpoints on a module (for duration inference).
     *
     * @param int $module_id
     * @return int
     */
    public function get_max_video_checkpoint_trigger_seconds_for_module($module_id)
    {
        if ($module_id < 1 || ! $this->assessments_checkpoint_schema_ready()) {
            return 0;
        }

        $row = $this->db
            ->select_max('trigger_value', 'max_tv')
            ->where('module_id', (int) $module_id)
            ->where('type', 'checkpoint')
            ->where('context', 'video')
            ->where('trigger_type', 'seconds')
            ->where('archived', 0)
            ->get('lib_assessments')
            ->row();

        return $row ? max(0, (int) round((float) ($row->max_tv ?? 0))) : 0;
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

    /** Whether lib_assessments.randomize_questions exists. */
    public function assessments_randomize_column_ready()
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        $ready = $this->db->field_exists('randomize_questions', 'lib_assessments');

        return $ready;
    }

    /** Whether lib_assessments.content_version exists. */
    public function assessments_content_version_ready()
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        $ready = $this->db->field_exists('content_version', 'lib_assessments');

        return $ready;
    }

    /**
     * Current content version for an assessment (defaults to 1).
     */
    public function get_assessment_content_version($assessment_id)
    {
        if ( ! $this->assessments_content_version_ready()) {
            return 1;
        }

        $row = $this->db
            ->select('content_version')
            ->where('id', (int) $assessment_id)
            ->get('lib_assessments', 1)
            ->row();

        return $row ? max(1, (int) $row->content_version) : 1;
    }

    /**
     * Bump content_version after question/choice structural edits.
     */
    public function bump_assessment_content_version($assessment_id)
    {
        if ( ! $this->assessments_content_version_ready()) {
            return false;
        }

        $aid = (int) $assessment_id;
        if ($aid < 1) {
            return false;
        }

        $this->db->set('content_version', 'content_version + 1', false);
        $this->db->where('id', $aid);

        return (bool) $this->db->update('lib_assessments');
    }

    /**
     * @return Assessment_attempt_order_model
     */
    private function _attempt_order_model()
    {
        $CI =& get_instance();
        $CI->load->model('Assessment_attempt_order_model', 'assessment_attempt_order_model');

        return $CI->assessment_attempt_order_model;
    }

    /**
     * Questions for learner take (pre/post): DB-backed order + optional shuffle.
     *
     * @param int      $assessment_id
     * @param object   $assessment
     * @param int      $user_id
     * @param int|null $enrollment_id
     * @return array
     */
    public function get_questions_for_attempt($assessment_id, $assessment, $user_id, $enrollment_id = null)
    {
        $questions = $this->get_questions($assessment_id);
        if (empty($questions)) {
            return [];
        }

        $atype = strtolower(trim((string) ($assessment->type ?? '')));
        if ( ! in_array($atype, ['pre', 'post'], true)) {
            return $questions;
        }

        $order_model = $this->_attempt_order_model();
        if ( ! $order_model->table_ready()) {
            return $this->_get_questions_for_attempt_session_fallback(
                $assessment_id,
                $assessment,
                $user_id,
                $questions
            );
        }

        $stored      = $this->_resolve_persistent_attempt_order(
            $assessment_id,
            $assessment,
            $user_id,
            $enrollment_id,
            $questions,
            $order_model
        );
        $created_new = ! empty($stored['_created_new']);

        unset($stored['_created_new']);
        $this->_cache_attempt_order_session($user_id, $assessment_id, $stored);
        $this->_log_assessment_randomization_attempt(
            (int) $assessment_id,
            (int) $user_id,
            $stored,
            $created_new,
            'db'
        );

        return $this->_apply_attempt_order_to_questions($questions, $stored);
    }

    /**
     * Mark active DB attempt order submitted after successful POST.
     */
    public function finalize_attempt_order_on_submit($user_id, $assessment_id)
    {
        $order_model = $this->_attempt_order_model();
        if ($order_model->table_ready()) {
            $order_model->mark_submitted($user_id, $assessment_id);
        }
        $this->clear_assessment_attempt_order_session($user_id, $assessment_id);
    }

    /**
     * @param array $questions canonical from get_questions()
     * @return array{question_ids:int[],choice_orders:array<int,int[]>,_created_new?:bool}
     */
    private function _resolve_persistent_attempt_order(
        $assessment_id,
        $assessment,
        $user_id,
        $enrollment_id,
        array $questions,
        Assessment_attempt_order_model $order_model
    ) {
        $aid     = (int) $assessment_id;
        $uid     = (int) $user_id;
        $version = $this->get_assessment_content_version($aid);
        $row     = $order_model->get_active($uid, $aid);
        $shuffle = etd_assessment_randomize_enabled($assessment);

        if ($row) {
            $stored = $order_model->decode_order_payload($row);
            if ((int) $row->assessment_version < $version && (int) $row->is_legacy_version !== 1) {
                $order_model->mark_legacy_version((int) $row->id);
            }

            return $stored;
        }

        $legacy_session = $this->_read_attempt_order_session($uid, $aid);
        if ( ! empty($legacy_session['question_ids'])) {
            $encoded = $order_model->encode_order_payload($legacy_session);
            $order_model->create([
                'assessment_id'       => $aid,
                'user_id'             => $uid,
                'enrollment_id'       => $enrollment_id,
                'assessment_version'  => $version,
                'question_order_json' => $encoded['question_order_json'],
                'choice_order_json'   => $encoded['choice_order_json'],
            ]);
            $legacy_session['_created_new'] = false;

            return $legacy_session;
        }

        $stored = $this->_build_new_attempt_order($questions, $shuffle);
        $encoded = $order_model->encode_order_payload($stored);
        $order_model->create([
            'assessment_id'       => $aid,
            'user_id'             => $uid,
            'enrollment_id'       => $enrollment_id,
            'assessment_version'  => $version,
            'question_order_json' => $encoded['question_order_json'],
            'choice_order_json'   => $encoded['choice_order_json'],
        ]);
        $stored['_created_new'] = true;

        return $stored;
    }

    /**
     * @param array $questions
     * @return array{question_ids:int[],choice_orders:array<int,int[]>}
     */
    private function _build_new_attempt_order(array $questions, $shuffle)
    {
        $q_ids = array_map(static function ($q) {
            return (int) $q->id;
        }, $questions);

        if ($shuffle) {
            shuffle($q_ids);
        }

        $choice_orders = [];
        foreach ($questions as $q) {
            $qid = (int) $q->id;
            if (($q->question_type ?? '') !== 'multiple_choice' || empty($q->choices)) {
                continue;
            }
            $c_ids = array_map(static function ($c) {
                return (int) $c->id;
            }, $q->choices);
            if ($shuffle) {
                shuffle($c_ids);
            }
            $choice_orders[$qid] = $c_ids;
        }

        return [
            'question_ids'  => $q_ids,
            'choice_orders' => $choice_orders,
        ];
    }

    /**
     * @param array{question_ids:int[],choice_orders:array<int,int[]>} $stored
     */
    private function _apply_attempt_order_to_questions(array $questions, array $stored)
    {
        $by_id = [];
        foreach ($questions as $q) {
            $by_id[(int) $q->id] = $q;
        }

        $ordered = [];
        foreach ((array) ($stored['question_ids'] ?? []) as $qid) {
            $qid = (int) $qid;
            if ( ! isset($by_id[$qid])) {
                continue;
            }
            $q = $by_id[$qid];
            if (($q->question_type ?? '') === 'multiple_choice'
                && ! empty($stored['choice_orders'][$qid])) {
                $choice_map = [];
                foreach ($q->choices as $c) {
                    $choice_map[(int) $c->id] = $c;
                }
                $shuffled = [];
                foreach ($stored['choice_orders'][$qid] as $cid) {
                    if (isset($choice_map[$cid])) {
                        $shuffled[] = $choice_map[$cid];
                    }
                }
                if (count($shuffled) === count($q->choices)) {
                    $q->choices = $shuffled;
                }
            }
            $ordered[] = $q;
        }

        if (empty($ordered)) {
            return $questions;
        }

        return $ordered;
    }

    /**
     * Read session cache (legacy pre-migration attempts).
     *
     * @return array{question_ids:int[],choice_orders:array<int,int[]>}
     */
    private function _read_attempt_order_session($user_id, $assessment_id)
    {
        if ( ! function_exists('get_instance')) {
            return ['question_ids' => [], 'choice_orders' => []];
        }
        $CI =& get_instance();
        if ( ! isset($CI->session)) {
            return ['question_ids' => [], 'choice_orders' => []];
        }
        $stored = $CI->session->userdata(
            $this->assessment_attempt_order_session_key($user_id, $assessment_id)
        );
        if ( ! is_array($stored) || empty($stored['question_ids'])) {
            return ['question_ids' => [], 'choice_orders' => []];
        }

        return [
            'question_ids'  => array_values(array_map('intval', (array) $stored['question_ids'])),
            'choice_orders' => is_array($stored['choice_orders'] ?? null) ? $stored['choice_orders'] : [],
        ];
    }

    /**
     * Session cache only (not source of truth when DB table exists).
     *
     * @param array{question_ids:int[],choice_orders:array<int,int[]>} $stored
     */
    private function _cache_attempt_order_session($user_id, $assessment_id, array $stored)
    {
        if ( ! function_exists('get_instance')) {
            return;
        }
        $CI =& get_instance();
        if ( ! isset($CI->session)) {
            return;
        }
        $CI->session->set_userdata(
            $this->assessment_attempt_order_session_key($user_id, $assessment_id),
            [
                'question_ids'  => $stored['question_ids'] ?? [],
                'choice_orders' => $stored['choice_orders'] ?? [],
            ]
        );
    }

    /**
     * Backward compat when migration not applied yet.
     */
    private function _get_questions_for_attempt_session_fallback(
        $assessment_id,
        $assessment,
        $user_id,
        array $questions
    ) {
        if ( ! etd_assessment_randomize_enabled($assessment)) {
            return $questions;
        }

        $session_key = $this->assessment_attempt_order_session_key($user_id, $assessment_id);
        $CI          =& get_instance();
        $stored      = $CI->session->userdata($session_key);
        $created_new = false;

        if ( ! is_array($stored) || empty($stored['question_ids'])) {
            $created_new = true;
            $stored      = $this->_build_new_attempt_order($questions, true);
            $CI->session->set_userdata($session_key, $stored);
        }

        $this->_log_assessment_randomization_attempt(
            (int) $assessment_id,
            (int) $user_id,
            $stored,
            $created_new,
            'session'
        );

        return $this->_apply_attempt_order_to_questions($questions, $stored);
    }

    /**
     * @param array{question_ids:int[],choice_orders:array<int,int[]>} $stored
     */
    private function _log_assessment_randomization_attempt(
        $assessment_id,
        $user_id,
        array $stored,
        $created_new,
        $source = 'db'
    ) {
        $choice_log = [];
        foreach ((array) ($stored['choice_orders'] ?? []) as $qid => $cids) {
            $choice_log[(int) $qid] = array_values(array_map('intval', (array) $cids));
        }

        log_message('debug', 'ASSESSMENT_RANDOMIZE: ' . json_encode([
            'assessment_id' => (int) $assessment_id,
            'user_id'       => (int) $user_id,
            'event'         => $created_new ? 'created' : 'restored',
            'source'        => (string) $source,
            'question_ids'  => array_values(array_map('intval', (array) ($stored['question_ids'] ?? []))),
            'choice_orders' => $choice_log,
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Session key for optional per-request cache (take view only).
     */
    public function assessment_attempt_order_session_key($user_id, $assessment_id)
    {
        return 'assessment_attempt_order_' . (int) $user_id . '_' . (int) $assessment_id;
    }

    /**
     * Clear session cache for attempt order.
     */
    public function clear_assessment_attempt_order_session($user_id, $assessment_id)
    {
        if ( ! function_exists('get_instance')) {
            return;
        }
        $CI =& get_instance();
        if ( ! isset($CI->session)) {
            return;
        }
        $key = $this->assessment_attempt_order_session_key($user_id, $assessment_id);
        $CI->session->unset_userdata($key);
        log_message('debug', 'ASSESSMENT_RANDOMIZE: ' . json_encode([
            'assessment_id' => (int) $assessment_id,
            'user_id'       => (int) $user_id,
            'event'         => 'cleared',
        ], JSON_UNESCAPED_UNICODE));
    }

    public function clear_user_assessment_attempt($user_id, $assessment_id)
    {
        $order_model = $this->_attempt_order_model();
        if ($order_model->table_ready()) {
            $order_model->mark_retaken($user_id, $assessment_id);
        }
        $this->clear_assessment_attempt_order_session($user_id, $assessment_id);

        $q_ids = $this->_get_question_ids($assessment_id);
        if (empty($q_ids)) {
            return false;
        }

        return (bool) $this->db
            ->where_in('question_id', $q_ids)
            ->where('user_id', (int) $user_id)
            ->where('archived', 0)
            ->update('assessment_answers', ['archived' => 1]);
    }

    /**
     * Post-assessment score summary for retake eligibility.
     *
     * @return array{passed:bool,percent:float,pending_essays:int}
     */
    public function get_user_assessment_result_summary($user_id, $assessment_id)
    {
        $this->load->helper('ka_format');
        $result = $this->get_result((int) $user_id, (int) $assessment_id);
        $thr    = (float) ka_assessment_pass_threshold();
        $pending = (int) ($result['pending'] ?? 0);
        $score   = (float) ($result['score'] ?? 0);

        return [
            'passed'         => $pending < 1 && $score >= $thr,
            'percent'        => $score,
            'pending_essays' => $pending,
        ];
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
        $payload = [
            'assessment_id' => (int) $data['assessment_id'],
            'question_text' => trim($data['question_text']),
            'question_type' => $data['question_type'],
            'is_required'   => empty($data['is_required']) ? 0 : 1,
            'min_words'     => ( ! empty($data['min_words']) && (int)$data['min_words'] > 0)
                               ? (int) $data['min_words'] : null,
            'date_encoded'  => date('Y-m-d H:i:s'),
            'encoded_by'    => (int) $data['encoded_by'],
        ];
        $payload = $this->_merge_essay_response_mode($payload, $data);

        $ok = $this->db->insert('lib_assessment_questions', $payload);
        if ( ! $ok) {
            return 0;
        }

        $qid = (int) $this->db->insert_id();
        $this->bump_assessment_content_version((int) $data['assessment_id']);

        return $qid;
    }

    /** Update a question's text/type/settings. */
    public function update_question($question_id, $data)
    {
        $qrow = $this->db
            ->select('assessment_id')
            ->where('id', (int) $question_id)
            ->get('lib_assessment_questions', 1)
            ->row();

        $payload = [
            'question_text'      => trim($data['question_text']),
            'question_type'      => $data['question_type'],
            'is_required'        => empty($data['is_required']) ? 0 : 1,
            'min_words'          => ( ! empty($data['min_words']) && (int)$data['min_words'] > 0)
                                    ? (int) $data['min_words'] : null,
            'date_last_modified' => date('Y-m-d H:i:s'),
            'modified_by'        => (int) $data['modified_by'],
        ];
        $payload = $this->_merge_essay_response_mode($payload, $data);

        $ok = (bool) $this->db
            ->where('id', (int) $question_id)
            ->update('lib_assessment_questions', $payload);

        if ($ok && $qrow) {
            $this->bump_assessment_content_version((int) $qrow->assessment_id);
        }

        return $ok;
    }

    /**
     * @param array $payload
     * @param array $data
     * @return array
     */
    private function _merge_essay_response_mode(array $payload, array $data)
    {
        if (($data['question_type'] ?? '') !== 'essay') {
            return $payload;
        }
        if ( ! $this->db->field_exists('essay_response_mode', 'lib_assessment_questions')) {
            return $payload;
        }

        $mode = trim((string) ($data['essay_response_mode'] ?? 'text'));
        if ( ! in_array($mode, ['text', 'pdf', 'text_or_pdf'], true)) {
            $mode = 'text';
        }
        $payload['essay_response_mode'] = $mode;

        return $payload;
    }

    /** Soft-delete question and its choices. */
    public function delete_question($question_id)
    {
        $qrow = $this->db
            ->select('assessment_id')
            ->where('id', (int) $question_id)
            ->get('lib_assessment_questions', 1)
            ->row();

        $this->db->where('question_id', (int) $question_id)
                 ->update('lib_assessment_choices', ['archived' => 1]);

        $ok = (bool) $this->db
            ->where('id', (int) $question_id)
            ->update('lib_assessment_questions', ['archived' => 1]);

        if ($ok && $qrow) {
            $this->bump_assessment_content_version((int) $qrow->assessment_id);
        }

        return $ok;
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
     * Parse is_correct reliably (avoids empty("0") and truthy string quirks).
     */
    public function parse_is_correct($value)
    {
        return ($value === true || $value === 1 || $value === '1') ? 1 : 0;
    }

    /**
     * Normalize choice rows before save. For multiple_choice, only the first
     * correct flag is kept.
     *
     * @param string $question_type
     * @param array  $choices [['text'=>'...','is_correct'=>0|1], ...]
     */
    public function prepare_choices_for_save($question_type, array $choices)
    {
        $out           = [];
        $first_correct = false;

        foreach ($choices as $c) {
            $text = trim((string) ($c['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $is_correct = $this->parse_is_correct($c['is_correct'] ?? 0);

            if ($question_type === 'multiple_choice' && $is_correct) {
                if ($first_correct) {
                    $is_correct = 0;
                } else {
                    $first_correct = true;
                }
            }

            $out[] = [
                'text'       => $text,
                'is_correct' => $is_correct,
            ];
        }

        return $out;
    }

    /** Whether the same question text already exists on this assessment. */
    public function question_text_exists($assessment_id, $text, $exclude_id = 0)
    {
        $needle = mb_strtolower(trim((string) $text));
        if ($needle === '') {
            return false;
        }

        $r = $this->db
            ->select('id, question_text')
            ->where('assessment_id', (int) $assessment_id)
            ->where('archived', 0)
            ->where('id !=', (int) $exclude_id)
            ->get('lib_assessment_questions');

        if ( ! $r || $r->num_rows() === 0) {
            return false;
        }

        foreach ($r->result() as $row) {
            if (mb_strtolower(trim((string) $row->question_text)) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Insert multiple questions in one transaction.
     *
     * @param int   $assessment_id
     * @param array $items  validated rows with question_text, question_type, etc.
     * @return array{success:bool,questions:array,message?:string}
     */
    public function create_questions_batch($assessment_id, array $items)
    {
        if (empty($items)) {
            return ['success' => false, 'questions' => [], 'message' => 'No questions to save.'];
        }

        $this->db->trans_start();
        $saved_ids = [];

        foreach ($items as $item) {
            $qid = $this->create_question($item);
            if ($qid <= 0) {
                $this->db->trans_rollback();

                return ['success' => false, 'questions' => [], 'message' => 'Failed to save question.'];
            }

            if (in_array($item['question_type'], ['multiple_choice', 'fill_blank'], true)
                && ! empty($item['choices'])) {
                $this->save_choices($qid, $item['choices']);
            }

            $saved_ids[] = $qid;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            return ['success' => false, 'questions' => [], 'message' => 'Failed to save questions.'];
        }

        $this->db->trans_complete();

        $this->bump_assessment_content_version((int) $assessment_id);

        $questions = [];
        foreach ($saved_ids as $qid) {
            $q = $this->get_question($qid);
            if ($q) {
                $questions[] = $q;
            }
        }

        return ['success' => true, 'questions' => $questions];
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
        $qrow = $this->db
            ->select('assessment_id')
            ->where('id', (int) $question_id)
            ->get('lib_assessment_questions', 1)
            ->row();

        $this->db->where('question_id', (int) $question_id)
                 ->update('lib_assessment_choices', ['archived' => 1]);

        if (empty($choices)) {
            if ($qrow) {
                $this->bump_assessment_content_version((int) $qrow->assessment_id);
            }

            return;
        }

        $order = 1;
        foreach ($choices as $c) {
            $text = trim($c['text'] ?? '');
            if ($text === '') continue;
            $this->db->insert('lib_assessment_choices', [
                'question_id'  => (int) $question_id,
                'choice_text'  => $text,
                'is_correct'   => $this->parse_is_correct($c['is_correct'] ?? 0),
                'choice_order' => $order++,
                'archived'     => 0,
            ]);
        }

        if ($qrow) {
            $this->bump_assessment_content_version((int) $qrow->assessment_id);
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
    public function submit_answers($user_id, $assessment_id, $answers, array $essay_files = [])
    {
        $questions   = $this->get_questions($assessment_id);
        $submitted   = 0;
        $auto_scored = 0;
        $pending     = 0;
        $now         = date('Y-m-d H:i:s');
        $has_essay_path_col = $this->db->field_exists('essay_file_path', 'assessment_answers');

        foreach ($questions as $q) {
            $answer_text = trim($answers[$q->id] ?? '');
            $essay_path  = trim((string) ($essay_files[$q->id] ?? ''));

            if ($answer_text === '' && $essay_path === '' && ! $q->is_required) {
                continue;
            }

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
                $accepted = [];
                foreach ($q->choices as $choice) {
                    if ((int) $choice->is_correct === 1) {
                        $accepted[] = strtolower(trim($choice->choice_text));
                    }
                }
                $score = in_array(strtolower($answer_text), $accepted, true) ? 100.00 : 0.00;
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

            $payload = [
                'answer_text'        => $answer_text,
                'score'              => $score,
                'checked_by'         => null,
                'checked_at'         => null,
                'date_last_modified' => $now,
                'modified_by'        => (int) $user_id,
            ];
            if ($has_essay_path_col && $essay_path !== '') {
                $payload['essay_file_path'] = $essay_path;
            }

            if ($existing) {
                $this->db->where('id', $existing->id)->update('assessment_answers', $payload);
            } else {
                $insert = [
                    'question_id'  => $q->id,
                    'user_id'      => (int) $user_id,
                    'answer_text'  => $answer_text,
                    'score'        => $score,
                    'date_encoded' => $now,
                    'encoded_by'   => (int) $user_id,
                ];
                if ($has_essay_path_col && $essay_path !== '') {
                    $insert['essay_file_path'] = $essay_path;
                }
                $this->db->insert('assessment_answers', $insert);
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
    // VIDEO CHECKPOINTS (type=checkpoint, context=video)
    // =========================================================

    /**
     * Single-question MCQ pass during YouTube playback. Stores a row in assessment_answers
     * only when the selected choice is correct (same behaviour as legacy user_video_checkpoint_passes).
     *
     * @param int $user_id
     * @param int $assessment_id lib_assessments.id (POST: assessment_id; legacy alias still accepted)
     * @param int $choice_index  0-based index matching ordered choices
     * @param int $expected_module_id
     * @return array{ok:bool,message:string}
     */
    public function save_checkpoint_pass($user_id, $assessment_id, $choice_index, $expected_module_id)
    {
        if ( ! $this->assessments_checkpoint_schema_ready()) {
            return ['ok' => false, 'message' => 'Checkpoints are not available.'];
        }

        $aid = (int) $assessment_id;
        $mid = (int) $expected_module_id;
        $uid = (int) $user_id;
        $idx = (int) $choice_index;

        $a = $this->db
            ->where('id', $aid)
            ->where('archived', 0)
            ->get('lib_assessments', 1)
            ->row();

        if ( ! $a
            || ($a->type ?? '') !== 'checkpoint'
            || ($a->context ?? '') !== 'video'
            || (int) $a->module_id !== $mid
        ) {
            return ['ok' => false, 'message' => 'Checkpoint not found.'];
        }

        $questions = $this->get_questions($aid);
        if (count($questions) !== 1) {
            return ['ok' => false, 'message' => 'Invalid checkpoint configuration.'];
        }

        $q = $questions[0];
        if (($q->question_type ?? '') !== 'multiple_choice') {
            return ['ok' => false, 'message' => 'Invalid checkpoint configuration.'];
        }

        $choices = $q->choices;
        if (empty($choices) || $idx < 0 || $idx >= count($choices)) {
            return ['ok' => false, 'message' => 'Invalid answer.'];
        }

        $selected = $choices[$idx];
        if ((int) $selected->is_correct !== 1) {
            return ['ok' => false, 'message' => 'Incorrect. Review the material and try again.'];
        }

        $now         = date('Y-m-d H:i:s');
        $answer_text = (string) $selected->id;

        $existing = $this->db
            ->where('question_id', (int) $q->id)
            ->where('user_id',     $uid)
            ->where('archived',    0)
            ->get('assessment_answers', 1)
            ->row();

        if ($existing) {
            $ok = (bool) $this->db
                ->where('id', (int) $existing->id)
                ->update('assessment_answers', [
                    'answer_text'        => $answer_text,
                    'score'              => 100.00,
                    'checked_by'         => null,
                    'checked_at'         => null,
                    'date_last_modified' => $now,
                    'modified_by'        => $uid,
                ]);
        } else {
            $ok = (bool) $this->db->insert('assessment_answers', [
                'question_id'  => (int) $q->id,
                'user_id'      => $uid,
                'answer_text'  => $answer_text,
                'score'        => 100.00,
                'date_encoded' => $now,
                'encoded_by'   => $uid,
            ]);
        }

        if ( ! $ok) {
            log_message('error', 'save_checkpoint_pass: DB error ' . json_encode($this->db->error()));

            return ['ok' => false, 'message' => 'Could not save your answer. Please try again.'];
        }

        return ['ok' => true, 'message' => 'Correct!'];
    }

    /**
     * One-time: copy course_module_video_checkpoints (+ passes) into lib_assessments / questions /
     * choices / assessment_answers. Safe to re-run (skips rows already linked via legacy_checkpoint_id).
     *
     * @return array{ok:bool,message?:string,migrated_checkpoints?:int,migrated_passes?:int}
     */
    public function migrate_legacy_video_checkpoints()
    {
        if ( ! $this->assessments_checkpoint_schema_ready()) {
            return [
                'ok'      => false,
                'message' => 'Run application/sql/alter_lib_assessments_unified_checkpoints.sql first.',
            ];
        }

        $link_col = $this->legacy_checkpoint_link_column();
        if ( ! $link_col) {
            return [
                'ok'      => false,
                'message' => 'Link column legacy_checkpoint_id (or legacy_youtube_quiz_id) is missing on lib_assessments. Run patch/alter SQL, then migration_rename_quiz_to_video_checkpoint_UP.sql if upgrading from old names.',
            ];
        }

        if ( ! $this->db->table_exists('course_module_video_checkpoints')) {
            return [
                'ok'               => true,
                'message'          => 'No legacy checkpoint table.',
                'migrated_checkpoints' => 0,
                'migrated_passes'      => 0,
            ];
        }

        $this->load->model('Module_video_checkpoint_model', 'video_checkpoint_codec');

        $migrated_checkpoints = 0;
        $migrated_passes  = 0;

        $old_rows = $this->db
            ->where('archived', 0)
            ->order_by('module_id', 'ASC')
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get('course_module_video_checkpoints')
            ->result();

        foreach ($old_rows as $row) {
            $legacy_id = (int) $row->id;
            $dup       = (int) $this->db
                ->where($link_col, $legacy_id)
                ->where('archived', 0)
                ->count_all_results('lib_assessments');

            if ($dup > 0) {
                continue;
            }

            $choices = $this->video_checkpoint_codec->decode_choices($row->choices ?? '');
            if (empty($choices)) {
                log_message('error', 'migrate_legacy_video_checkpoints: empty choices for legacy id ' . $legacy_id);

                continue;
            }

            $qtext = trim((string) ($row->question ?? ''));
            $title = ($qtext !== '') ? $qtext : 'Video checkpoint';
            if (function_exists('mb_strlen') && mb_strlen($title) > 255) {
                $title = mb_substr($title, 0, 252) . '...';
            } elseif (strlen($title) > 255) {
                $title = substr($title, 0, 252) . '...';
            }

            $ts = (int) ($row->trigger_seconds ?? 0);
            if ($ts > 0) {
                $trigger_type  = 'seconds';
                $trigger_value = (float) $ts;
            } else {
                $trigger_type  = 'percent';
                $trigger_value = $row->trigger_percent !== null && $row->trigger_percent !== ''
                    ? (float) $row->trigger_percent
                    : 0.0;
            }

            $now = date('Y-m-d H:i:s');
            $enc = ! empty($row->date_encoded) ? $row->date_encoded : $now;

            $insert_row = [
                'module_id'              => (int) $row->module_id,
                'type'                   => 'checkpoint',
                'title'                  => $title,
                'context'                => 'video',
                'trigger_type'           => $trigger_type,
                'trigger_value'          => $trigger_value,
                'is_required'            => (int) ($row->is_required ?? 1) === 1 ? 1 : 0,
                'sort_order'             => (int) ($row->sort_order ?? 0),
                'created_at'             => $enc,
                'date_encoded'           => $enc,
                'encoded_by'             => (int) ($row->encoded_by ?? 1),
                'archived'               => 0,
            ];
            $insert_row[$link_col] = $legacy_id;
            $this->db->insert('lib_assessments', $insert_row);

            $assessment_id = (int) $this->db->insert_id();
            if ($assessment_id < 1) {
                continue;
            }

            $this->db->insert('lib_assessment_questions', [
                'assessment_id'  => $assessment_id,
                'question_text'  => $qtext !== '' ? $qtext : $title,
                'question_type'  => 'multiple_choice',
                'is_required'    => 1,
                'min_words'      => null,
                'date_encoded'   => $now,
                'encoded_by'     => (int) ($row->encoded_by ?? 1),
                'archived'       => 0,
            ]);

            $question_id = (int) $this->db->insert_id();
            $correct_idx = (int) $row->correct_choice_index;

            $order = 1;
            foreach ($choices as $i => $label) {
                $this->db->insert('lib_assessment_choices', [
                    'question_id'  => $question_id,
                    'choice_text'  => trim((string) $label),
                    'is_correct'   => ((int) $i === $correct_idx) ? 1 : 0,
                    'choice_order' => $order++,
                    'archived'     => 0,
                ]);
            }

            $migrated_checkpoints++;
        }

        if ($this->db->table_exists('user_video_checkpoint_passes')) {
            $passes = $this->db
                ->where('archived', 0)
                ->get('user_video_checkpoint_passes')
                ->result();

            foreach ($passes as $p) {
                $legacy_qid = (int) $p->checkpoint_id;
                $la         = $this->db
                    ->where($link_col, $legacy_qid)
                    ->where('archived', 0)
                    ->get('lib_assessments', 1)
                    ->row();

                if ( ! $la) {
                    continue;
                }

                $q = $this->db
                    ->where('assessment_id', (int) $la->id)
                    ->where('archived', 0)
                    ->order_by('id', 'ASC')
                    ->get('lib_assessment_questions', 1)
                    ->row();

                if ( ! $q) {
                    continue;
                }

                $correct = $this->db
                    ->where('question_id', (int) $q->id)
                    ->where('is_correct', 1)
                    ->where('archived', 0)
                    ->order_by('choice_order', 'ASC')
                    ->get('lib_assessment_choices', 1)
                    ->row();

                if ( ! $correct) {
                    continue;
                }

                $exists = (int) $this->db
                    ->where('question_id', (int) $q->id)
                    ->where('user_id', (int) $p->user_id)
                    ->where('archived', 0)
                    ->count_all_results('assessment_answers');

                if ($exists > 0) {
                    continue;
                }

                $ts = ! empty($p->passed_at) ? $p->passed_at : date('Y-m-d H:i:s');

                $this->db->insert('assessment_answers', [
                    'question_id'  => (int) $q->id,
                    'user_id'      => (int) $p->user_id,
                    'answer_text'  => (string) $correct->id,
                    'score'        => 100.00,
                    'date_encoded' => $ts,
                    'encoded_by'   => (int) $p->user_id,
                ]);
                $migrated_passes++;
            }
        }

        return [
            'ok'                   => true,
            'message'              => 'Migration finished.',
            'migrated_checkpoints' => $migrated_checkpoints,
            'migrated_passes'      => $migrated_passes,
        ];
    }

    /**
     * @deprecated Use migrate_legacy_video_checkpoints()
     */
    public function migrate_legacy_youtube_quizzes()
    {
        return $this->migrate_legacy_video_checkpoints();
    }

    /**
     * @deprecated Use migrate_legacy_video_checkpoints()
     */
    public function migrate_legacy_youtube_checkpoints()
    {
        return $this->migrate_legacy_video_checkpoints();
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    /** Public accessor for analytics / integrity models. */
    public function get_question_ids_for_assessment($assessment_id)
    {
        return $this->_get_question_ids($assessment_id);
    }

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
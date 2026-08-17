<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Course_model
 *
 * Handles all DB operations for the course system.
 *
 * Tables (exact schema):
 * ─────────────────────────────────────────────────────────────
 * courses
 *   id, title, description, created_by, created_at,
 *   category_id, modality_id, access_type, publish_status,
 *   date_encoded, encoded_by, date_last_modified, modified_by,
 *   archived, expiry_days
 *
 * course_categories
 *   id, name, description, created_at, date_encoded, encoded_by,
 *   date_last_modified, modified_by, archived
 *
 * course_modules
 *   id, course_id, title, description, content_type, content_path,
 *   weight_percentage, module_order, created_at, date_encoded,
 *   encoded_by, date_last_modified, modified_by, archived
 *
 * module_progress
 *   id, user_id, module_id, status, score, completed_at
 *   status ENUM: 'not_started' | 'in_progress' | 'completed'
 *
 * enrollments
 *   id, user_id, course_id, status (pending|approved|rejected), enrolled_at
 *
 * lib_course_modality
 *   modality_id, modality_desc, archived
 *
 * @property CI_DB_mysqli_driver $db
 * @property Course_phase2_model $course_phase2
 * @property User_model          $user_model
 */
class Course_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('course_completion_service');
    }

    // =========================================================
    // CATALOG
    // =========================================================

    /**
     * Get all published courses for the catalog page.
     * Joins: category, modality, creator, module count,
     *        enrollment count.
     *
     * @param  string $keyword    Search against title / description / category name
     * @param  int    $filter_cat Filter by category_id (0 = all)
     * @return object[]
     */
    public function get_catalog($keyword = '', $filter_cat = 0)
    {
        $this->apply_expiry_unpublish();

        $this->db
            ->select('
                c.id,
                c.title,
                c.description,
                c.created_at,
                c.expiry_days,
                c.category_id,
                c.modality_id,
                c.access_type,
                c.publish_status,
                cc.name              AS category_name,
                lm.modality_desc     AS modality_name,
                creator.fullname     AS creator_name,
                COALESCE(cm_cnt.total_modules, 0)   AS total_modules,
                COALESCE(en_cnt.total_enrolled, 0)  AS total_enrolled
            ', false)
            ->from('courses c')
            ->join('course_categories cc',
                   'cc.id = c.category_id', 'left')
            ->join('lib_course_modality lm',
                   'lm.modality_id = c.modality_id', 'left')
            ->join('aauth_users creator',
                   'creator.id = c.created_by', 'left')
            ->join(
                '(SELECT course_id, COUNT(*) AS total_modules
                  FROM course_modules
                  WHERE archived = 0
                  GROUP BY course_id) cm_cnt',
                'cm_cnt.course_id = c.id', 'left'
            )
            ->join(
                '(SELECT course_id, COUNT(*) AS total_enrolled
                  FROM enrollments
                  WHERE status = \'approved\'
                  GROUP BY course_id) en_cnt',
                'en_cnt.course_id = c.id', 'left'
            )
            ->where('c.archived', 0);

        $CI =& get_instance();
        $CI->load->model('Course_phase2_model', 'course_phase2');
        /** @var Course_phase2_model $course_phase2 */
        $course_phase2 = $CI->{'course_phase2'};
        if ($course_phase2->schema_ready()) {
            $this->db
                ->where('c.publish_status', 'published')
                ->group_start()
                    ->where('c.access_type !=', 'hidden')
                    ->or_where('c.access_type IS NULL', null, false)
                ->group_end();
        }
        if (isset($GLOBALS['ka_catalog_viewer'])) {
            $course_phase2->apply_catalog_filters_for_viewer($GLOBALS['ka_catalog_viewer']);
        }

        if ($keyword !== '') {
            $this->db
                ->group_start()
                    ->like('c.title',       $keyword)
                    ->or_like('c.description', $keyword)
                    ->or_like('cc.name',    $keyword)
                ->group_end();
        }

        if ((int) $filter_cat > 0) {
            $course_phase2->apply_category_filter((int) $filter_cat);
        }

        $result = $this->db->order_by('c.created_at', 'DESC')->get();

        if ( ! $result || $result->num_rows() === 0) {
            return [];
        }

        $rows = $result->result();
        $this->_hydrate_access_type_names($rows);

        return $rows;
    }

    /**
     * Get all course IDs the given user is enrolled in.
     *
     * @param  int   $user_id
     * @return int[]
     */
    public function get_enrolled_ids($user_id)
    {
        $result = $this->db
            ->select('course_id')
            ->where('user_id', (int) $user_id)
            ->where('status', 'approved')
            ->get('enrollments');

        if ( ! $result || $result->num_rows() === 0) return [];

        return array_map('intval',
            array_column($result->result_array(), 'course_id')
        );
    }

    /**
     * course_id => status for the given user (pending / approved / rejected).
     *
     * @param  int $user_id
     * @return array<int,string>
     */
    public function get_user_enrollment_status_map($user_id)
    {
        $result = $this->db
            ->select('course_id, status')
            ->where('user_id', (int) $user_id)
            ->get('enrollments');

        if ( ! $result || $result->num_rows() === 0) {
            return [];
        }

        $map = [];
        foreach ($result->result() as $row) {
            $map[(int) $row->course_id] = (string) $row->status;
        }

        return $map;
    }

    /**
     * Whether the user has an approved enrollment for the course.
     *
     * @param  int $user_id
     * @param  int $course_id
     * @return bool
     */
    public function has_approved_enrollment($user_id, $course_id)
    {
        $row = $this->get_enrollment($user_id, $course_id);
        if ( ! $row) {
            return false;
        }

        $st = isset($row->status) ? (string) $row->status : 'approved';

        return $st === 'approved';
    }

    // =========================================================
    // SINGLE COURSE
    // =========================================================

    /**
     * Get one published course with all joined lookup data.
     *
     * @param  int        $course_id
     * @return object|null
     */
    public function get_course($course_id)
    {
        $result = $this->db
            ->select('
                c.*,
                cc.name              AS category_name,
                lm.modality_desc     AS modality_name,
                creator.fullname     AS creator_name
            ', false)
            ->from('courses c')
            ->join('course_categories cc',
                   'cc.id = c.category_id', 'left')
            ->join('lib_course_modality lm',
                   'lm.modality_id = c.modality_id', 'left')
            ->join('aauth_users creator',
                   'creator.id = c.created_by', 'left')
            ->where('c.id',       (int) $course_id)
            ->where('c.archived', 0)
            ->get();

        if ( ! $result || $result->num_rows() === 0) {
            return null;
        }

        $row = $result->row();
        $this->apply_expiry_unpublish((int) $course_id);
        $this->_hydrate_access_type_names($row);

        return $row;
    }

    /**
     * Get one course regardless of archived status (for admin/manage).
     *
     * @param  int        $course_id
     * @return object|null
     */
    public function get_course_any($course_id)
    {
        $result = $this->db
            ->select('
                c.*,
                cc.name              AS category_name,
                lm.modality_desc     AS modality_name,
                creator.fullname     AS creator_name
            ', false)
            ->from('courses c')
            ->join('course_categories cc',
                   'cc.id = c.category_id', 'left')
            ->join('lib_course_modality lm',
                   'lm.modality_id = c.modality_id', 'left')
            ->join('aauth_users creator',
                   'creator.id = c.created_by', 'left')
            ->where('c.id', (int) $course_id)
            ->get();

        if ( ! $result || $result->num_rows() === 0) {
            return null;
        }

        $row = $result->row();
        $this->_hydrate_access_type_names($row);

        return $row;
    }

    // =========================================================
    // MODULES
    // =========================================================

    /**
     * Get all non-archived modules for a course, ordered by module_order.
     *
     * Contract (certificate / completion rely on this):
     * - When $user_id > 0: LEFT JOIN module_progress mp
     *       ON mp.module_id = cm.id AND mp.user_id = (bound user id)
     * - Completion state is read from mp.status only.
     *
     * @param  int $course_id
     * @param  int $user_id   Pass 0 to skip progress join
     * @return object[]
     */
    public function get_modules($course_id, $user_id = 0)
    {
        $this->db
            ->select('
                cm.id,
                cm.course_id,
                cm.title,
                cm.description,
                cm.content_type,
                cm.content_path,
                cm.weight_percentage,
                cm.module_order,
                cm.created_at
            ', false)
            ->from('course_modules cm')
            ->where('cm.course_id', (int) $course_id)
            ->where('cm.archived',  0);

        if ((int) $user_id > 0) {
            $uid = (int) $user_id;
            $this->db
                ->select('
                    mp.status       AS status,
                    mp.score        AS my_score,
                    mp.completed_at AS my_completed_at
                ', false)
                ->join(
                    'module_progress mp',
                    'mp.module_id = cm.id AND mp.user_id = ' . $uid,
                    'left'
                );
        } else {
            // Provide null placeholders so view code works without errors
            $this->db->select(
                'NULL AS status,
                 NULL AS my_score,
                 NULL AS my_completed_at',
                false
            );
        }

        $result = $this->db
            ->order_by('cm.module_order', 'ASC')
            ->get();

        return ($result && $result->num_rows() > 0)
            ? $result->result()
            : [];
    }

    /**
     * Get a single module by ID (non-archived).
     *
     * @param  int        $module_id
     * @return object|null
     */
    public function get_module($module_id)
    {
        $result = $this->db
            ->select('cm.*, c.title AS course_title')
            ->from('course_modules cm')
            ->join('courses c', 'c.id = cm.course_id', 'left')
            ->where('cm.id',       (int) $module_id)
            ->where('cm.archived', 0)
            ->get();

        return ($result && $result->num_rows() > 0)
            ? $result->row()
            : null;
    }

    /**
     * Count non-archived modules in a course.
     *
     * @param  int $course_id
     * @return int
     */
    public function count_modules($course_id)
    {
        return (int) $this->db
            ->where('course_id', (int) $course_id)
            ->where('archived',  0)
            ->count_all_results('course_modules');
    }

    // =========================================================
    // ENROLLMENTS
    // =========================================================

    /**
     * Get the enrollment row for a user + course (or null).
     *
     * @param  int        $user_id
     * @param  int        $course_id
     * @return object|null
     */
    public function get_enrollment($user_id, $course_id)
    {
        $result = $this->db
            ->where('user_id',   (int) $user_id)
            ->where('course_id', (int) $course_id)
            ->get('enrollments');

        return ($result && $result->num_rows() > 0)
            ? $result->row()
            : null;
    }

    /**
     * Request enrollment (employee): pending row, or resubmit from rejected.
     * Returns true if a new pending row was created or a rejected row was reset to pending.
     *
     * @param  int  $user_id
     * @param  int  $course_id
     * @return bool
     */
    public function request_enrollment($user_id, $course_id)
    {
        $CI =& get_instance();
        $CI->load->model('Course_phase2_model', 'course_phase2');
        $CI->load->model('User_model', 'user_model');
        /** @var Course_phase2_model $course_phase2 */
        $course_phase2 = $CI->{'course_phase2'};
        /** @var User_model $user_model */
        $user_model = $CI->{'user_model'};

        $course = $this->get_course((int) $course_id);
        $viewer = $user_model->get_user((int) $user_id);
        $vis    = $course_phase2->employee_may_view_course((int) $course_id, $viewer);
        if ( ! $vis['allowed']) {
            return false;
        }

        $access = $course_phase2->get_course_access_type($course);
        if ($access === 'invitation_only') {
            return false;
        }

        $target_status = $course_phase2->enrollment_status_for_request($course);

        $row = $this->get_enrollment($user_id, $course_id);
        if ($row) {
            $st = isset($row->status) ? (string) $row->status : 'approved';
            if ($st === 'approved' || $st === 'pending') {
                return false;
            }
            if ($st === 'rejected') {
                $this->reset_course_learning_state((int) $user_id, (int) $course_id);
                $data = [
                    'status'       => $target_status,
                    'enrolled_at'  => date('Y-m-d H:i:s'),
                ];
                log_message('debug', 'ENROLL REQUEST DATA: ' . json_encode([
                    'mode' => 'resubmit',
                    'id'   => (int) $row->id,
                    'data' => $data,
                ]));
                $ok = (bool) $this->db
                    ->where('id', (int) $row->id)
                    ->update('enrollments', $data);
                if ( ! $ok) {
                    log_message('error', 'ENROLL REQUEST UPDATE FAILED: ' . json_encode($this->db->error()));
                }

                return $ok;
            }
        }

        $data = [
            'user_id'     => (int) $user_id,
            'course_id'   => (int) $course_id,
            'status'      => $target_status,
            'enrolled_at' => date('Y-m-d H:i:s'),
        ];
        $batch_id = (int) ($GLOBALS['ka_enrollment_batch_id'] ?? 0);
        if ($batch_id < 1) {
            $batch_id = $this->resolve_enrollment_batch_id((int) $course_id, 0);
        }
        if ($batch_id > 0 && $this->db->field_exists('batch_id', 'enrollments')) {
            $data['batch_id'] = $batch_id;
        } elseif ($batch_id > 0) {
            log_message(
                'debug',
                'Phase3: enrollments.batch_id column missing; not persisting batch_id=' . $batch_id . ' (course_id=' . (int) $course_id . ').'
            );
        }
        log_message('debug', 'ENROLL REQUEST DATA: ' . json_encode([
            'mode' => 'insert',
            'data' => $data,
        ]));
        $ok = (bool) $this->db->insert('enrollments', $data);
        if ( ! $ok) {
            log_message('error', 'ENROLL REQUEST INSERT FAILED: ' . json_encode($this->db->error()));
        }

        return $ok;
    }

    /**
     * @deprecated Use request_enrollment(); kept for callers that expect enroll().
     */
    public function enroll($user_id, $course_id)
    {
        return $this->request_enrollment($user_id, $course_id);
    }

    /**
     * Count total enrollments for a course.
     *
     * @param  int $course_id
     * @return int
     */
    public function count_enrollments($course_id)
    {
        return (int) $this->db
            ->where('course_id', (int) $course_id)
            ->where('status', 'approved')
            ->count_all_results('enrollments');
    }

    /**
     * Count pending enrollment requests for a course.
     *
     * @param  int $course_id
     * @return int
     */
    public function count_pending_enrollments($course_id)
    {
        return (int) $this->db
            ->where('course_id', (int) $course_id)
            ->where('status', 'pending')
            ->count_all_results('enrollments');
    }

    /**
     * Rules for delete / structure edits when learners are tied to a course.
     *
     * @param  int $course_id
     * @return array{
     *   approved:bool,
     *   pending:int,
     *   approved_count:int,
     *   blocks_delete:bool,
     *   blocks_structure:bool,
     *   delete_message:string,
     *   structure_message:string
     * }
     */
    public function get_course_enrollment_guard($course_id)
    {
        $course_id = (int) $course_id;
        $approved  = $this->count_enrollments($course_id);
        $pending   = $this->count_pending_enrollments($course_id);

        $delete_parts = [];
        if ($approved > 0) {
            $delete_parts[] = $approved . ' enrolled learner' . ($approved === 1 ? '' : 's');
        }
        if ($pending > 0) {
            $delete_parts[] = $pending . ' pending enrollment request' . ($pending === 1 ? '' : 's');
        }

        $delete_message = $delete_parts !== []
            ? 'This course cannot be deleted or archived because it has ' . implode(' and ', $delete_parts) . '.'
            : '';

        $structure_message = $approved > 0
            ? 'This course has ' . $approved . ' enrolled learner' . ($approved === 1 ? '' : 's')
                . '. Adding, deleting, or reordering modules is locked to protect learner progress.'
                . ' You can still update course details and edit existing module content.'
            : '';

        return [
            'approved'           => $approved,
            'pending'            => $pending,
            'approved_count'     => $approved,
            'blocks_delete'      => ($approved + $pending) > 0,
            'blocks_structure'   => $approved > 0,
            'delete_message'     => $delete_message,
            'structure_message'  => $structure_message,
        ];
    }

    /**
     * Attach enrollment guard flags to a course row for list UIs.
     *
     * @param object $course
     * @return object
     */
    public function attach_enrollment_guard($course)
    {
        if ( ! is_object($course) || empty($course->id)) {
            return $course;
        }

        $guard = $this->get_course_enrollment_guard((int) $course->id);
        $course->enrolled_count   = $guard['approved'];
        $course->pending_count    = $guard['pending'];
        $course->can_delete       = ! $guard['blocks_delete'];
        $course->structure_locked = $guard['blocks_structure'];
        $course->enrollment_guard = $guard;

        return $course;
    }

    /**
     * Get enrolled students for a course with their progress %.
     *
     * @param  int $course_id
     * @param  int $limit     0 = no limit
     * @return object[]
     */
    public function get_enrolled_students($course_id, $limit = 0)
    {
        $this->db
            ->select('u.id, u.fullname, u.employee_id, u.role, e.enrolled_at')
            ->from('enrollments e')
            ->join('aauth_users u', 'u.id = e.user_id', 'left')
            ->where('e.course_id', (int) $course_id)
            ->where('e.status',    'approved')
            ->where('u.DELETED',   0)
            ->order_by('e.enrolled_at', 'DESC');

        if ($limit > 0) $this->db->limit((int) $limit);

        $result   = $this->db->get();
        $students = ($result && $result->num_rows() > 0)
            ? $result->result()
            : [];

        foreach ($students as $s) {
            $sid = (int) $s->id;
            if ($sid < 1) {
                $s->progress_pct = 0;
                continue;
            }
            $state = $this->course_completion_service->evaluate_user_course_state($sid, (int) $course_id);
            $s->progress_pct = (int) ($state['progress_percent'] ?? 0);
        }

        return $students;
    }

    /**
     * @param  int $enrollment_id
     * @return object|null  includes course_id, user_id, status
     */
    public function get_enrollment_by_id($enrollment_id)
    {
        $result = $this->db
            ->where('id', (int) $enrollment_id)
            ->get('enrollments');

        return ($result && $result->num_rows() > 0) ? $result->row() : null;
    }

    /**
     * @param  int    $enrollment_id
     * @param  string $status pending|approved|rejected
     * @return bool
     */
    public function set_enrollment_status($enrollment_id, $status)
    {
        if ( ! in_array($status, ['pending', 'approved', 'rejected'], true)) {
            return false;
        }

        return (bool) $this->db
            ->where('id', (int) $enrollment_id)
            ->update('enrollments', ['status' => $status]);
    }

    /**
     * Pending requests for courses owned by the instructor.
     *
     * @param  int $instructor_user_id
     * @return object[]
     */
    public function get_pending_enrollments_for_instructor($instructor_user_id)
    {
        $uid = (int) $instructor_user_id;
        $CI =& get_instance();
        $CI->load->model('Course_phase2_model', 'course_phase2');
        /** @var Course_phase2_model $course_phase2 */
        $course_phase2 = $CI->{'course_phase2'};

        $this->db
            ->select('
                e.id AS enrollment_id,
                e.user_id,
                e.course_id,
                e.enrolled_at,
                e.status,
                u.fullname AS student_name,
                u.employee_id,
                c.title AS course_title
            ', false)
            ->from('enrollments e')
            ->join('courses c', 'c.id = e.course_id', 'inner')
            ->join('aauth_users u', 'u.id = e.user_id', 'left');

        $course_phase2->restrict_query_to_instructor_courses($uid);

        $result = $this->db
            ->where('c.archived', 0)
            ->where('e.status', 'pending')
            ->where('u.DELETED', 0)
            ->order_by('e.enrolled_at', 'DESC')
            ->get();

        return ($result && $result->num_rows() > 0) ? $result->result() : [];
    }

    /**
     * All pending enrollment requests (admin).
     *
     * @return object[]
     */
    public function get_all_pending_enrollments()
    {
        $result = $this->db
            ->select('
                e.id AS enrollment_id,
                e.user_id,
                e.course_id,
                e.enrolled_at,
                e.status,
                u.fullname AS student_name,
                u.employee_id,
                c.title AS course_title,
                c.created_by AS course_owner_id
            ', false)
            ->from('enrollments e')
            ->join('courses c', 'c.id = e.course_id', 'inner')
            ->join('aauth_users u', 'u.id = e.user_id', 'left')
            ->where('c.archived', 0)
            ->where('e.status', 'pending')
            ->where('u.DELETED', 0)
            ->order_by('e.enrolled_at', 'DESC')
            ->get();

        return ($result && $result->num_rows() > 0) ? $result->result() : [];
    }

    // =========================================================
    // MODULE PROGRESS
    // =========================================================

    /**
     * Get the module_progress row for a user + module.
     *
     * @param  int        $user_id
     * @param  int        $module_id
     * @return object|null
     */
    public function get_module_progress($user_id, $module_id)
    {
        $result = $this->db
            ->where('user_id',   (int) $user_id)
            ->where('module_id', (int) $module_id)
            ->get('module_progress');

        return ($result && $result->num_rows() > 0)
            ? $result->row()
            : null;
    }

    /**
     * Mark a module as in_progress.
     * Creates row if it doesn't exist; never downgrades from completed.
     *
     * @param  int  $user_id
     * @param  int  $module_id
     * @return bool
     */
    public function start_module($user_id, $module_id)
    {
        $existing = $this->get_module_progress($user_id, $module_id);

        if ( ! $existing) {
            return (bool) $this->db->insert('module_progress', [
                'user_id'   => (int) $user_id,
                'module_id' => (int) $module_id,
                'status'    => 'in_progress',
            ]);
        }

        if ($existing->status === 'not_started') {
            return (bool) $this->db
                ->where('user_id',   (int) $user_id)
                ->where('module_id', (int) $module_id)
                ->update('module_progress', ['status' => 'in_progress']);
        }

        return true;
    }

    /**
     * Mark a module as completed. Upserts the progress row.
     *
     * @param  int        $user_id
     * @param  int        $module_id
     * @param  float|null $score
     * @return bool
     */
    public function complete_module($user_id, $module_id, $score = null)
    {
        $data = [
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ];
        if ($score !== null) {
            $data['score'] = (float) $score;
        }

        $existing = $this->get_module_progress($user_id, $module_id);

        if ($existing) {
            return (bool) $this->db
                ->where('user_id',   (int) $user_id)
                ->where('module_id', (int) $module_id)
                ->update('module_progress', $data);
        }

        $data['user_id']   = (int) $user_id;
        $data['module_id'] = (int) $module_id;
        return (bool) $this->db->insert('module_progress', $data);
    }

    /**
     * Reset module progress for full-course retake (non-managerial post-assessment failure).
     *
     * @param int $user_id
     * @param int $module_id
     * @return bool
     */
    public function reset_module_progress_for_retake($user_id, $module_id)
    {
        $existing = $this->get_module_progress($user_id, $module_id);
        if ( ! $existing) {
            return true;
        }

        log_message('debug', 'ETD retake: reset module_progress user=' . (int) $user_id . ' module=' . (int) $module_id);

        $update = [
            'status'       => 'not_started',
            'completed_at' => null,
            'score'        => null,
        ];
        if ($this->module_progress_has_resume_column()) {
            $update['resume_state'] = null;
        }

        return (bool) $this->db
            ->where('user_id', (int) $user_id)
            ->where('module_id', (int) $module_id)
            ->update('module_progress', $update);
    }

    /**
     * Full-course retake reset — all modules, no certificate archival.
     *
     * @param int $user_id
     * @param int $course_id
     * @return bool
     */
    public function reset_all_modules_for_retake($user_id, $course_id)
    {
        $uid = (int) $user_id;
        $cid = (int) $course_id;
        if ($uid < 1 || $cid < 1) {
            return false;
        }

        $module_ids = $this->db
            ->select('id')
            ->from('course_modules')
            ->where('course_id', $cid)
            ->where('archived', 0)
            ->get()
            ->result_array();
        $module_ids = array_map('intval', array_column($module_ids, 'id'));

        if ($module_ids === []) {
            return true;
        }

        log_message('debug', 'ETD retake: reset all modules user=' . $uid . ' course=' . $cid);

        $update = [
            'status'       => 'not_started',
            'completed_at' => null,
            'score'        => null,
        ];
        if ($this->module_progress_has_resume_column()) {
            $update['resume_state'] = null;
        }

        $this->db
            ->where('user_id', $uid)
            ->where_in('module_id', $module_ids)
            ->update('module_progress', $update);

        return true;
    }

    /**
     * Reset a learner's saved state for a course (used on rejected->resubmitted and stale approvals).
     *
     * @param int $user_id
     * @param int $course_id
     * @return bool
     */
    public function reset_course_learning_state($user_id, $course_id)
    {
        $uid = (int) $user_id;
        $cid = (int) $course_id;
        if ($uid < 1 || $cid < 1) {
            return false;
        }

        log_message('debug', 'Enrollment reset learning state user=' . $uid . ' course=' . $cid);

        $module_ids = $this->db
            ->select('id')
            ->from('course_modules')
            ->where('course_id', $cid)
            ->where('archived', 0)
            ->get()
            ->result_array();
        $module_ids = array_map('intval', array_column($module_ids, 'id'));

        if ( ! empty($module_ids)) {
            $this->db
                ->where('user_id', $uid)
                ->where_in('module_id', $module_ids)
                ->delete('module_progress');

            // Clean checkpoint and assessment answers for a true fresh start.
            $assessment_ids = $this->db
                ->select('id')
                ->from('lib_assessments')
                ->where_in('module_id', $module_ids)
                ->where('archived', 0)
                ->get()
                ->result_array();
            $assessment_ids = array_map('intval', array_column($assessment_ids, 'id'));

            if ( ! empty($assessment_ids)) {
                $this->db
                    ->where('user_id', $uid)
                    ->where_in('assessment_id', $assessment_ids)
                    ->delete('assessment_answers');
            }
        }

        if ($this->db->table_exists('lib_certificates')) {
            $this->db
                ->where('user_id', $uid)
                ->where('course_id', $cid)
                ->where('archived', 0)
                ->update('lib_certificates', [
                    'archived'           => 1,
                    'date_last_modified' => date('Y-m-d H:i:s'),
                ]);
            log_message('debug', 'Enrollment reset: archived certificates user=' . $uid . ' course=' . $cid);
        }

        return true;
    }

    /**
     * Whether module_progress has resume_state column (migration_state_resume.sql).
     */
    public function module_progress_has_resume_column()
    {
        return $this->db->field_exists('resume_state', 'module_progress');
    }

    /**
     * @param int $user_id
     * @param int $module_id
     * @return array{type:string,position:float,meta:array}
     */
    public function get_module_resume_state($user_id, $module_id)
    {
        if ( ! $this->module_progress_has_resume_column()) {
            return ka_resume_normalize_state(null);
        }

        $row = $this->get_module_progress($user_id, $module_id);
        if ( ! $row || empty($row->resume_state)) {
            return ka_resume_normalize_state(null);
        }

        return ka_resume_normalize_state($row->resume_state);
    }

    /**
     * Persist resume JSON for a module (upserts in_progress row).
     *
     * @param int   $user_id
     * @param int   $module_id
     * @param array $state
     * @return bool
     */
    public function save_module_resume_state($user_id, $module_id, array $state)
    {
        if ( ! $this->module_progress_has_resume_column()) {
            return false;
        }

        $uid = (int) $user_id;
        $mid = (int) $module_id;
        if ($uid < 1 || $mid < 1) {
            return false;
        }

        $normalized = ka_resume_normalize_state($state);
        $existing   = $this->get_module_resume_state($uid, $mid);
        $meta         = is_array($existing['meta'] ?? null) ? $existing['meta'] : [];
        if (is_array($normalized['meta'] ?? null)) {
            $meta = array_merge($meta, $normalized['meta']);
        }
        $normalized['meta'] = $meta;

        $has_playback_flag = ! empty($meta['playback_completed']);
        if ($normalized['type'] === '' && $normalized['position'] <= 0 && ! $has_playback_flag) {
            return false;
        }

        $this->start_module($uid, $mid);
        $json = json_encode($normalized);

        $ok = (bool) $this->db
            ->where('user_id', $uid)
            ->where('module_id', $mid)
            ->update('module_progress', ['resume_state' => $json]);

        if ( ! $ok) {
            $this->start_module($uid, $mid);
            $ok = (bool) $this->db
                ->where('user_id', $uid)
                ->where('module_id', $mid)
                ->update('module_progress', ['resume_state' => $json]);
        }

        if ($ok) {
            log_message(
                'debug',
                'Resume saved user=' . $uid . ' module=' . $mid . ' type=' . $normalized['type']
                . ' pos=' . $normalized['position']
            );
        }

        return $ok;
    }

    /**
     * Whether the learner has watched the module video through to the end.
     *
     * @param int $user_id
     * @param int $module_id
     * @return bool
     */
    public function has_video_playback_completed($user_id, $module_id)
    {
        if ( ! $this->module_progress_has_resume_column()) {
            return false;
        }

        $state = $this->get_module_resume_state((int) $user_id, (int) $module_id);

        return ! empty($state['meta']['playback_completed']);
    }

    /**
     * Persist that the learner finished watching the module video (playback ended).
     *
     * @param int $user_id
     * @param int $module_id
     * @return bool
     */
    public function mark_video_playback_completed($user_id, $module_id)
    {
        $uid = (int) $user_id;
        $mid = (int) $module_id;
        if ($uid < 1 || $mid < 1) {
            return false;
        }

        $existing = $this->get_module_resume_state($uid, $mid);
        $meta     = is_array($existing['meta'] ?? null) ? $existing['meta'] : [];
        $meta['playback_completed']    = true;
        $meta['playback_completed_at'] = date('c');

        $type = (string) ($existing['type'] ?? '');
        if ($type === '') {
            $type = 'video';
        }

        return $this->save_module_resume_state($uid, $mid, [
            'type'     => $type,
            'position' => (float) ($existing['position'] ?? 0),
            'meta'     => $meta,
        ]);
    }

    /**
     * Count completed modules for a user in a specific course.
     *
     * @param  int $user_id
     * @param  int $course_id
     * @return int
     */
    public function count_completed_modules($user_id, $course_id)
    {
        $row = $this->db
            ->select('COUNT(*) AS cnt')
            ->from('module_progress mp')
            ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
            ->where('cm.course_id', (int) $course_id)
            ->where('cm.archived',  0)
            ->where('mp.user_id',   (int) $user_id)
            ->where('mp.status',    'completed')
            ->get()
            ->row();

        return $row ? (int) $row->cnt : 0;
    }

    // =========================================================
    // CATEGORIES
    // =========================================================

    /**
     * Get all non-archived categories ordered by name.
     *
     * @return object[]
     */
    public function get_categories()
    {
        $result = $this->db
            ->where('archived', 0)
            ->order_by('name', 'ASC')
            ->get('course_categories');

        return ($result && $result->num_rows() > 0)
            ? $result->result()
            : [];
    }

    /**
     * Whether nested categories are available (parent_id column).
     */
    public function categories_have_parent_column()
    {
        return $this->db->field_exists('parent_id', 'course_categories');
    }

    /**
     * Categories for dropdowns — indented when hierarchy is enabled.
     *
     * @return object[]
     */
    public function get_categories_for_display()
    {
        $flat = $this->get_categories();
        if ( ! $this->categories_have_parent_column() || $flat === []) {
            return $flat;
        }

        $by_parent = [];
        foreach ($flat as $cat) {
            $pid = (int) ($cat->parent_id ?? 0);
            if ( ! isset($by_parent[$pid])) {
                $by_parent[$pid] = [];
            }
            $by_parent[$pid][] = $cat;
        }

        $out = [];
        $walk = function ($parent_id, $depth) use (&$walk, &$out, $by_parent) {
            $pid = (int) $parent_id;
            if (empty($by_parent[$pid])) {
                return;
            }
            usort($by_parent[$pid], function ($a, $b) {
                return strcasecmp((string) ($a->name ?? ''), (string) ($b->name ?? ''));
            });
            foreach ($by_parent[$pid] as $cat) {
                $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
                $display        = clone $cat;
                $display->name  = $prefix . (string) ($cat->name ?? '');
                $display->depth = $depth;
                $out[]          = $display;
                $walk((int) $cat->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $out !== [] ? $out : $flat;
    }

    /**
     * Category id plus all descendant ids (for catalog filtering).
     *
     * @param int $category_id
     * @return int[]
     */
    public function get_category_descendant_ids($category_id)
    {
        $root = (int) $category_id;
        if ($root < 1) {
            return [];
        }
        if ( ! $this->categories_have_parent_column()) {
            return [$root];
        }

        $flat = $this->get_categories();
        $children = [];
        foreach ($flat as $cat) {
            $pid = (int) ($cat->parent_id ?? 0);
            if ( ! isset($children[$pid])) {
                $children[$pid] = [];
            }
            $children[$pid][] = (int) $cat->id;
        }

        $ids   = [$root];
        $queue = [$root];
        while ($queue !== []) {
            $pid = array_shift($queue);
            foreach ($children[$pid] ?? [] as $cid) {
                if ( ! in_array($cid, $ids, true)) {
                    $ids[]   = $cid;
                    $queue[] = $cid;
                }
            }
        }

        return $ids;
    }

    /**
     * Get a single category by ID.
     *
     * @param  int        $cat_id
     * @return object|null
     */
    public function get_category($cat_id)
    {
        $result = $this->db
            ->where('id', (int) $cat_id)
            ->get('course_categories');

        return ($result && $result->num_rows() > 0)
            ? $result->row()
            : null;
    }

    // =========================================================
    // MODALITY & ACCESS TYPE LOOKUPS
    // =========================================================

    /**
     * Get all non-archived modalities.
     * Table: lib_course_modality (modality_id, modality_desc, archived)
     *
     * @return object[]
     */
    public function get_modalities()
    {
        $result = $this->db
            ->where('archived', 0)
            ->order_by('modality_desc', 'ASC')
            ->get('lib_course_modality');

        return ($result && $result->num_rows() > 0)
            ? $result->result()
            : [];
    }

    /**
     * @deprecated Use course_phase2_access_types() / course_phase2_access_label() in views.
     * @return object[] { code, label }
     */
    public function get_access_types()
    {
        $this->load->helper('course_phase2');
        $out = [];
        foreach (course_phase2_access_types() as $code) {
            $out[] = (object) [
                'code'  => $code,
                'label' => course_phase2_access_label($code),
            ];
        }

        return $out;
    }

    // =========================================================
    // ADMIN / INSTRUCTOR COURSE LISTS
    // =========================================================

    /**
     * Get all courses (admin view) with joined lookups + counts.
     *
     * @param  bool $include_archived
     * @return object[]
     */
    public function get_all_courses($include_archived = false)
    {
        $this->db
            ->select('
                c.id, c.title, c.description, c.archived,
                c.created_at, c.category_id, c.modality_id,
                c.access_type, c.publish_status,
                c.expiry_days,
                cc.name              AS category_name,
                lm.modality_desc     AS modality_name,
                creator.fullname     AS creator_name
            ', false)
            ->from('courses c')
            ->join('course_categories cc',
                   'cc.id = c.category_id', 'left')
            ->join('lib_course_modality lm',
                   'lm.modality_id = c.modality_id', 'left')
            ->join('aauth_users creator',
                   'creator.id = c.created_by', 'left');

        if ( ! $include_archived) {
            $this->db->where('c.archived', 0);
        }

        $result = $this->db->order_by('c.created_at', 'DESC')->get();

        if ( ! $result || $result->num_rows() === 0) return [];

        $courses = $result->result();
        $this->_hydrate_access_type_names($courses);
        foreach ($courses as $course) {
            $course->module_count   = $this->count_modules($course->id);
            $course->avg_progress   = $this->get_avg_progress($course->id);
            $this->attach_enrollment_guard($course);
        }

        return $courses;
    }

    /**
     * Get courses created by a specific instructor.
     *
     * @param  int  $user_id
     * @param  bool $include_archived
     * @return object[]
     */
    public function get_courses_by_instructor($user_id, $include_archived = false)
    {
        $this->db
            ->select('
                c.id, c.title, c.description, c.archived,
                c.created_at, c.category_id, c.modality_id,
                c.access_type, c.publish_status,
                c.expiry_days,
                cc.name              AS category_name,
                lm.modality_desc     AS modality_name
            ', false)
            ->from('courses c')
            ->join('course_categories cc',
                   'cc.id = c.category_id', 'left')
            ->join('lib_course_modality lm',
                   'lm.modality_id = c.modality_id', 'left');

        $CI =& get_instance();
        $CI->load->model('Course_phase2_model', 'course_phase2');
        /** @var Course_phase2_model $course_phase2 */
        $course_phase2 = $CI->{'course_phase2'};
        $course_phase2->restrict_query_to_instructor_courses((int) $user_id);

        if ( ! $include_archived) {
            $this->db->where('c.archived', 0);
        }

        $result = $this->db->order_by('c.created_at', 'DESC')->get();

        if ( ! $result || $result->num_rows() === 0) return [];

        $courses = $result->result();
        $this->_hydrate_access_type_names($courses);
        foreach ($courses as $course) {
            $course->module_count   = $this->count_modules($course->id);
            $course->avg_progress   = $this->get_avg_progress($course->id);
            $this->attach_enrollment_guard($course);
        }

        return $courses;
    }

    /**
     * Set access_type_name from courses.access_type (Phase 2 single source).
     *
     * @param object|object[]|null $rows
     */
    private function _hydrate_access_type_names($rows)
    {
        if ($rows === null) {
            return;
        }

        $this->load->helper('course_phase2');
        $list = is_array($rows) ? $rows : [$rows];
        foreach ($list as $row) {
            if ( ! is_object($row)) {
                continue;
            }
            $key = isset($row->access_type) ? strtolower(trim((string) $row->access_type)) : '';
            $row->access_type_name = $key !== ''
                ? course_phase2_access_label($key)
                : '';
        }
    }

    /**
     * Average completion % across ALL enrolled students for a course.
     *
     * @param  int $course_id
     * @return int 0–100
     */
    public function get_avg_progress($course_id)
    {
        $cid = (int) $course_id;
        if ($cid < 1) {
            return 0;
        }

        $enrollments = $this->db
            ->select('user_id')
            ->from('enrollments')
            ->where('course_id', $cid)
            ->where('status', 'approved')
            ->get();

        if ( ! $enrollments || $enrollments->num_rows() === 0) {
            return 0;
        }

        $sum = 0;
        $cnt = 0;
        foreach ($enrollments->result() as $enrollment) {
            $uid     = (int) $enrollment->user_id;
            $state = $this->course_completion_service->evaluate_user_course_state($uid, $cid);
            $sum += (int) ($state['progress_percent'] ?? 0);
            $cnt++;
        }

        return $cnt > 0 ? (int) round($sum / $cnt) : 0;
    }

    /**
     * Get all in-progress courses for a user (progress > 0% and < 100%).
     * Used by the "Continue Learning" dashboard widget.
     *
     * @param  int $user_id
     * @return object[]
     */
    public function get_in_progress_courses($user_id)
    {
        $uid = (int) $user_id;
        if ($uid < 1) {
            return [];
        }

        $enrolled_ids = $this->get_enrolled_ids($uid);
        if (empty($enrolled_ids)) {
            return [];
        }

        $courses = [];
        foreach ($enrolled_ids as $course_id) {
            $cid = (int) $course_id;
            $state = $this->course_completion_service->evaluate_user_course_state($uid, $cid);
            $pct = (int) ($state['progress_percent'] ?? 0);
            if ($pct > 0 && $pct < 100) {
                $course = $this->get_course($cid);
                if ($course) {
                    $course->progress_pct = $pct;
                    $course->module_count = count((array) ($state['module_states'] ?? []));
                    $courses[]            = $course;
                }
            }
        }

        return $courses;
    }

    // =========================================================
    // ASSESSMENT HELPERS
    // (lib_assessments / lib_assessment_questions)
    // =========================================================

    /**
     * Get assessments for a module, optionally filtered by type.
     *
     * @param  int    $module_id
     * @param  string $type  'pre' | 'post' | '' (both)
     * @return object[]
     */
    public function get_assessments($module_id, $type = '')
    {
        $this->db
            ->select('la.*, COUNT(DISTINCT laq.id) AS question_count', false)
            ->from('lib_assessments la')
            ->join(
                'lib_assessment_questions laq',
                'laq.assessment_id = la.id AND laq.archived = 0',
                'left'
            )
            ->where('la.module_id', (int) $module_id)
            ->where('la.archived',  0);

        if ($type !== '') {
            $this->db->where('la.type', $type);
        }

        $result = $this->db
            ->group_by('la.id')
            ->order_by('question_count', 'DESC')
            ->order_by('la.created_at', 'DESC')
            ->order_by('la.id', 'DESC')
            ->get();

        return ($result && $result->num_rows() > 0)
            ? $result->result()
            : [];
    }

    /**
     * Get questions for an assessment.
     *
     * @param  int $assessment_id
     * @return object[]
     */
    public function get_questions($assessment_id)
    {
        $result = $this->db
            ->where('assessment_id', (int) $assessment_id)
            ->where('archived',      0)
            ->get('lib_assessment_questions');

        return ($result && $result->num_rows() > 0)
            ? $result->result()
            : [];
    }

    /**
     * Whether the user may use the module player (video, checkpoints, etc.).
     * Employees need approved enrollment; other roles may preview.
     *
     * @param object $user   aauth_users row
     * @param object $module course_modules row
     */
    public function user_can_access_module_player($user, $module)
    {
        if (($user->role ?? '') === 'employee') {
            return $this->has_approved_enrollment((int) $user->id, (int) $module->course_id);
        }

        return true;
    }

    // =========================================================
    // COURSE CRUD  (create / update / delete / reassign)
    // =========================================================

    /**
     * Create a new course.
     * Returns the new course ID.
     *
     * @param  array $data
     * @param  int   $user_id  encoded_by / created_by
     * @return int
     */
    public function create_course($data, $user_id)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->insert('courses', [
            'title'          => trim($data['title']),
            'description'    => isset($data['description']) ? trim($data['description']) : null,
            'created_by'     => (int) ($data['created_by'] ?? $user_id),
            'category_id'    => ! empty($data['category_id'])    ? (int) $data['category_id']    : null,
            'modality_id'    => ! empty($data['modality_id'])    ? (int) $data['modality_id']    : null,
            'expiry_days'    => ! empty($data['expiry_days'])    ? (int) $data['expiry_days']    : null,
            'certificate_prefix'  => isset($data['certificate_prefix']) ? strtoupper(trim((string) $data['certificate_prefix'])) : null,
            'signatory_name'      => isset($data['signatory_name']) ? trim((string) $data['signatory_name']) : null,
            'signatory_title'     => isset($data['signatory_title']) ? trim((string) $data['signatory_title']) : null,
            'archived'       => 0,
            'created_at'     => $now,
            'date_encoded'   => $now,
            'encoded_by'     => (int) $user_id,
        ]);

        $new_id = (int) $this->db->insert_id();
        if ($new_id > 0 && $this->db->field_exists('access_type', 'courses')) {
            $access = strtolower(trim((string) ($data['access_type'] ?? 'approval_required')));
            if ( ! in_array($access, ['open', 'approval_required', 'invitation_only', 'hidden'], true)) {
                $access = 'approval_required';
            }
            $this->db->where('id', $new_id)->update('courses', [
                'access_type'    => $access,
                'publish_status' => 'draft',
            ]);
        }

        if ($new_id > 0 && $this->db->field_exists('training_hours', 'courses')
            && array_key_exists('training_hours', $data)) {
            $hours = $this->_normalize_training_hours($data['training_hours']);
            $this->db->where('id', $new_id)->update('courses', [
                'training_hours' => $hours,
            ]);
        }

        return $new_id;
    }

    /**
     * Update course details.
     *
     * @param  int   $course_id
     * @param  array $data
     * @param  int   $user_id   modified_by
     * @return bool
     */
    public function update_course($course_id, $data, $user_id)
    {
        $ok = (bool) $this->db
            ->where('id', (int) $course_id)
            ->update('courses', array_merge([
                'title'              => trim($data['title']),
                'description'        => isset($data['description']) ? trim($data['description']) : null,
                'category_id'        => ! empty($data['category_id'])    ? (int) $data['category_id']    : null,
                'modality_id'        => ! empty($data['modality_id'])    ? (int) $data['modality_id']    : null,
                'expiry_days'        => ! empty($data['expiry_days'])    ? (int) $data['expiry_days']    : null,
                'certificate_prefix' => isset($data['certificate_prefix']) ? strtoupper(trim((string) $data['certificate_prefix'])) : null,
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $user_id,
            ], array_key_exists('signatory_name', $data) ? [
                'signatory_name' => trim((string) $data['signatory_name']),
            ] : [], array_key_exists('signatory_title', $data) ? [
                'signatory_title' => trim((string) $data['signatory_title']),
            ] : []));

        if ($ok && $this->db->field_exists('access_type', 'courses')) {
            if (array_key_exists('access_type', $data)) {
                $access = strtolower(trim((string) $data['access_type']));
                if (in_array($access, ['open', 'approval_required', 'invitation_only', 'hidden'], true)) {
                    $this->db->where('id', (int) $course_id)->update('courses', ['access_type' => $access]);
                }
            }
        }

        if ($ok && $this->db->field_exists('training_hours', 'courses')
            && array_key_exists('training_hours', $data)) {
            $this->db->where('id', (int) $course_id)->update('courses', [
                'training_hours' => $this->_normalize_training_hours($data['training_hours']),
            ]);
        }

        // Face-to-face schedule fields (present on courses when Phase 3/4 migrations applied).
        if ($ok) {
            $schedule_upd = [];
            foreach (['schedule_date', 'schedule_time', 'venue'] as $col) {
                if (array_key_exists($col, $data) && $this->db->field_exists($col, 'courses')) {
                    $val = $data[$col];
                    if ($val === '' || $val === null) {
                        $schedule_upd[$col] = null;
                    } else {
                        $schedule_upd[$col] = is_string($val) ? trim($val) : $val;
                    }
                }
            }
            if ( ! empty($schedule_upd)) {
                $this->db->where('id', (int) $course_id)->update('courses', $schedule_upd);
            }
        }

        return $ok;
    }

    /**
     * Normalize posted training hours for courses.training_hours.
     *
     * @param mixed $raw
     * @return float|null
     */
    private function _normalize_training_hours($raw)
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if ( ! is_numeric($raw)) {
            return null;
        }
        $hours = (float) $raw;
        if ($hours <= 0) {
            return null;
        }

        return round($hours, 1);
    }

    public function publish_course($course_id, $user_id)
    {
        return $this->_set_publish_status((int) $course_id, 'published', (int) $user_id);
    }

    public function unpublish_course($course_id, $user_id)
    {
        return $this->_set_publish_status((int) $course_id, 'unpublished', (int) $user_id);
    }

    private function _set_publish_status($course_id, $status, $user_id)
    {
        if ($course_id < 1 || ! in_array($status, ['draft', 'published', 'unpublished'], true)) {
            return false;
        }
        if ( ! $this->db->field_exists('publish_status', 'courses')) {
            return false;
        }

        return (bool) $this->db
            ->where('id', $course_id)
            ->where('archived', 0)
            ->update('courses', [
                'publish_status'      => $status,
                'date_last_modified'  => date('Y-m-d H:i:s'),
                'modified_by'         => (int) $user_id,
            ]);
    }

    /**
     * Soft-delete (archive) a course and all its modules.
     *
     * @param  int $course_id
     * @param  int $user_id
     * @return bool
     */
    public function delete_course($course_id, $user_id)
    {
        $guard = $this->get_course_enrollment_guard((int) $course_id);
        if ($guard['blocks_delete']) {
            return false;
        }

        // Archive all modules first
        $this->db
            ->where('course_id', (int) $course_id)
            ->update('course_modules', [
                'archived'           => 1,
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $user_id,
            ]);

        return (bool) $this->db
            ->where('id', (int) $course_id)
            ->update('courses', [
                'archived'           => 1,
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $user_id,
            ]);
    }

    /**
     * Reassign a course to a different instructor (admin only).
     *
     * @param  int $course_id
     * @param  int $new_owner_id   New created_by user
     * @param  int $admin_id       modified_by
     * @return bool
     */
    public function reassign_course($course_id, $new_owner_id, $admin_id)
    {
        return (bool) $this->db
            ->where('id', (int) $course_id)
            ->update('courses', [
                'created_by'         => (int) $new_owner_id,
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $admin_id,
            ]);
    }

    /**
     * Whether the user may manage this course (course_instructors pivot).
     * Admin role check is done at controller level.
     *
     * @param  int $course_id
     * @param  int $user_id
     * @return bool
     */
    public function owns_course($course_id, $user_id)
    {
        $CI =& get_instance();
        $CI->load->model('Course_phase2_model', 'course_phase2');

        return $CI->{'course_phase2'}->user_manages_course((int) $user_id, (int) $course_id);
    }

    // =========================================================
    // MODULE CRUD  (create / update / delete / reorder)
    // =========================================================

    /**
     * Create a module inside a course.
     * Returns new module ID.
     *
     * @param  array $data
     * @param  int   $user_id
     * @return int
     */
    public function create_module($data, $user_id)
    {
        // Auto-assign the next module_order
        $max = $this->db
            ->select_max('module_order')
            ->where('course_id', (int) $data['course_id'])
            ->where('archived',  0)
            ->get('course_modules')
            ->row();

        $order = $max && $max->module_order ? (int) $max->module_order + 1 : 1;
        $now   = date('Y-m-d H:i:s');

        $this->load->helper('course_phase3');
        $ctype = course_phase3_effective_module_type(
            $data['content_type'] ?? '',
            'create_module course_id=' . (int) ($data['course_id'] ?? 0),
            $data['content_path'] ?? null
        );

        $this->db->insert('course_modules', [
            'course_id'          => (int) $data['course_id'],
            'title'              => trim($data['title']),
            'description'        => isset($data['description']) ? trim($data['description']) : null,
            'content_type'       => $ctype,
            'content_path'       => isset($data['content_path']) ? trim($data['content_path']) : null,
            'weight_percentage'  => ! empty($data['weight_percentage']) ? (float) $data['weight_percentage'] : 0,
            'module_order'       => $order,
            'archived'           => 0,
            'created_at'         => $now,
            'date_encoded'       => $now,
            'encoded_by'         => (int) $user_id,
        ]);
        return (int) $this->db->insert_id();
    }

    /**
     * Update a module.
     *
     * @param  int   $module_id
     * @param  array $data
     * @param  int   $user_id
     * @return bool
     */
    public function update_module($module_id, $data, $user_id)
    {
        $this->load->helper('course_phase3');
        $ctype = course_phase3_effective_module_type(
            $data['content_type'] ?? '',
            'update_module module_id=' . (int) $module_id,
            $data['content_path'] ?? null
        );

        return (bool) $this->db
            ->where('id', (int) $module_id)
            ->update('course_modules', [
                'title'              => trim($data['title']),
                'description'        => isset($data['description']) ? trim($data['description']) : null,
                'content_type'       => $ctype,
                'content_path'       => isset($data['content_path']) ? trim($data['content_path']) : null,
                'weight_percentage'  => ! empty($data['weight_percentage']) ? (float) $data['weight_percentage'] : 0,
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $user_id,
            ]);
    }

    /**
     * Sum active module weights for a course, optionally excluding one module.
     *
     * @param  int $course_id
     * @param  int $exclude_module_id
     * @return float
     */
    public function sum_module_weights($course_id, $exclude_module_id = 0)
    {
        $this->db
            ->select_sum('weight_percentage', 'total_weight')
            ->from('course_modules')
            ->where('course_id', (int) $course_id)
            ->where('archived', 0);

        if ((int) $exclude_module_id > 0) {
            $this->db->where('id !=', (int) $exclude_module_id);
        }

        $result = $this->db->get();
        if ( ! $result || $result->num_rows() === 0) {
            return 0.0;
        }

        $row = $result->row();
        return (float) ($row->total_weight ?? 0);
    }

    /**
     * Soft-delete a module.
     *
     * @param  int $module_id
     * @param  int $user_id
     * @return bool
     */
    public function delete_module($module_id, $user_id)
    {
        return (bool) $this->db
            ->where('id', (int) $module_id)
            ->update('course_modules', [
                'archived'           => 1,
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $user_id,
            ]);
    }

    /**
     * Reorder modules. Accepts an array of module IDs in the desired order.
     *
     * @param  int[] $ordered_ids
     * @return void
     */
    public function reorder_modules($ordered_ids)
    {
        foreach ($ordered_ids as $position => $module_id) {
            $this->db
                ->where('id', (int) $module_id)
                ->update('course_modules', ['module_order' => $position + 1]);
        }
    }

    /**
     * Get all teachers (role = teacher, not deleted, active).
     * Used by admin reassign dropdown.
     *
     * @return object[]
     */
    public function get_teachers()
    {
        $result = $this->db
            ->select('id, fullname, employee_id')
            ->where('role',    'teacher')
            ->where('status',  'active')
            ->where('DELETED', 0)
            ->order_by('fullname', 'ASC')
            ->get('aauth_users');

        return ($result && $result->num_rows() > 0)
            ? $result->result()
            : [];
    }

    // =========================================================
    // Phase 3 — expiry auto-unpublish, batches
    // =========================================================

    /**
     * Unpublish published courses past expiry_days from created_at.
     *
     * @param  int|null $course_id Single course or all when null
     * @return int Rows updated
     */
    public function apply_expiry_unpublish($course_id = null)
    {
        if ( ! $this->db->field_exists('expiry_days', 'courses')
            || ! $this->db->field_exists('publish_status', 'courses')) {
            return 0;
        }

        $this->db
            ->where('archived', 0)
            ->where('publish_status', 'published')
            ->where('expiry_days IS NOT NULL', null, false)
            ->where('expiry_days >', 0)
            ->where(
                'DATE(DATE_ADD(created_at, INTERVAL expiry_days DAY)) < CURDATE()',
                null,
                false
            );

        if ($course_id !== null && (int) $course_id > 0) {
            $this->db->where('id', (int) $course_id);
        }

        $this->db->update('courses', [
            'publish_status'     => 'unpublished',
            'date_last_modified' => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->affected_rows();
    }

    public function batches_table_ready()
    {
        return $this->db->table_exists('course_batches');
    }

    /**
     * @param  int $course_id
     * @return object[]
     */
    public function get_course_batches($course_id)
    {
        if ( ! $this->batches_table_ready()) {
            return [];
        }

        $r = $this->db
            ->where('course_id', (int) $course_id)
            ->where('archived', 0)
            ->order_by('start_date', 'ASC')
            ->order_by('id', 'ASC')
            ->get('course_batches');

        if ($r === false) {
            log_message('error', 'Phase3: course_batches query failed: ' . json_encode($this->db->error()));

            return [];
        }

        return ($r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * @param  int $course_id
     * @return object|null
     */
    public function get_active_batch_for_course($course_id)
    {
        if ( ! $this->batches_table_ready()) {
            return null;
        }

        $r = $this->db
            ->where('course_id', (int) $course_id)
            ->where('archived', 0)
            ->where('status', 'active')
            ->order_by('start_date', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('course_batches');

        if ($r === false) {
            log_message('error', 'Phase3: course_batches active lookup failed: ' . json_encode($this->db->error()));

            return null;
        }

        return ($r->num_rows() > 0) ? $r->row() : null;
    }

    /**
     * Resolve batch for new enrollment.
     *
     * @param  int $course_id
     * @param  int $preferred_batch_id
     * @return int
     */
    public function resolve_enrollment_batch_id($course_id, $preferred_batch_id = 0)
    {
        if ( ! $this->batches_table_ready()) {
            if ((int) $preferred_batch_id > 0) {
                log_message(
                    'debug',
                    'Phase3: course_batches table not present; ignoring requested batch_id=' . (int) $preferred_batch_id . ' (single-batch mode).'
                );
            }

            return 0;
        }

        $preferred = (int) $preferred_batch_id;
        if ($preferred > 0) {
            $ok = (int) $this->db
                ->where('id', $preferred)
                ->where('course_id', (int) $course_id)
                ->where('archived', 0)
                ->count_all_results('course_batches');
            if ($ok > 0) {
                return $preferred;
            }
        }

        $active = $this->get_active_batch_for_course($course_id);

        return $active ? (int) $active->id : 0;
    }

    /**
     * Replace all batches for a course from form POST rows.
     *
     * @param  int   $course_id
     * @param  array $rows Each: batch_name, start_date, end_date, status, id (optional)
     * @param  int   $actor_id
     */
    public function sync_course_batches($course_id, array $rows, $actor_id = 0)
    {
        if ( ! $this->batches_table_ready()) {
            log_message('debug', 'Phase3: course_batches table not present; skipping batch sync for course_id=' . (int) $course_id . '.');

            return;
        }

        $cid   = (int) $course_id;
        $now   = date('Y-m-d H:i:s');
        $keep  = [];

        foreach ($rows as $row) {
            if ( ! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['batch_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $status = strtolower(trim((string) ($row['status'] ?? 'planned')));
            if ( ! in_array($status, ['planned', 'active', 'closed'], true)) {
                $status = 'planned';
            }
            $payload = [
                'batch_name'         => $name,
                'start_date'         => ! empty($row['start_date']) ? $row['start_date'] : null,
                'end_date'           => ! empty($row['end_date']) ? $row['end_date'] : null,
                'status'             => $status,
                'date_last_modified' => $now,
                'modified_by'        => (int) $actor_id,
            ];
            $bid = (int) ($row['id'] ?? 0);
            if ($bid > 0) {
                $this->db->where('id', $bid)->where('course_id', $cid)->update('course_batches', $payload);
                $keep[] = $bid;
            } else {
                $payload['course_id']     = $cid;
                $payload['date_encoded']  = $now;
                $payload['encoded_by']    = (int) $actor_id;
                $payload['archived']      = 0;
                $this->db->insert('course_batches', $payload);
                $keep[] = (int) $this->db->insert_id();
            }
        }

        $this->db->where('course_id', $cid)->where('archived', 0);
        if ( ! empty($keep)) {
            $this->db->where_not_in('id', $keep);
        }
        $this->db->update('course_batches', [
            'archived'           => 1,
            'date_last_modified' => $now,
            'modified_by'        => (int) $actor_id,
        ]);
    }
}
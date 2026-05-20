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

        $total_modules = $this->count_modules($course_id);
        if ( ! class_exists('Assessment_service', false)) {
            require_once APPPATH . 'libraries/Assessment_service.php';
        }
        $assessment_service = new Assessment_service();

        foreach ($students as $s) {
            $sid = (int) $s->id;
            if ($total_modules < 1 || $sid < 1) {
                $s->progress_pct = 0;
                continue;
            }
            $modules         = $this->get_modules((int) $course_id, $sid);
            $agg             = $assessment_service->get_course_progress_aggregate($sid, (int) $course_id, $modules);
            $s->progress_pct = (int) $agg['course_progress_percent'];
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
            ->order_by('e.enrolled_at', 'ASC')
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
            ->order_by('e.enrolled_at', 'ASC')
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
            $course->enrolled_count = $this->count_enrollments($course->id);
            $course->avg_progress   = $this->get_avg_progress($course->id);
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
            $course->enrolled_count = $this->count_enrollments($course->id);
            $course->avg_progress   = $this->get_avg_progress($course->id);
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

        if ( ! class_exists('Assessment_service', false)) {
            require_once APPPATH . 'services/Assessment_service.php';
        }
        $service = new Assessment_service();

        $sum = 0;
        $cnt = 0;
        foreach ($enrollments->result() as $enrollment) {
            $uid     = (int) $enrollment->user_id;
            $modules = $this->get_modules($cid, $uid);
            if (empty($modules)) {
                continue;
            }
            $agg = $service->get_course_progress_aggregate($uid, $cid, $modules);
            $sum += (int) ($agg['course_progress_percent'] ?? 0);
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

        if ( ! class_exists('Assessment_service', false)) {
            require_once APPPATH . 'libraries/Assessment_service.php';
        }
        $assessment_service = new Assessment_service();

        $courses = [];
        foreach ($enrolled_ids as $course_id) {
            $cid = (int) $course_id;
            $modules = $this->get_modules($cid, $uid);
            $agg     = $assessment_service->get_course_progress_aggregate($uid, $cid, $modules);
            $pct     = (int) $agg['course_progress_percent'];
            if ($pct > 0 && $pct < 100) {
                $course = $this->get_course($cid);
                if ($course) {
                    $course->progress_pct = $pct;
                    $course->module_count = (int) $agg['total_modules'];
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
            ->update('courses', [
                'title'              => trim($data['title']),
                'description'        => isset($data['description']) ? trim($data['description']) : null,
                'category_id'        => ! empty($data['category_id'])    ? (int) $data['category_id']    : null,
                'modality_id'        => ! empty($data['modality_id'])    ? (int) $data['modality_id']    : null,
                'expiry_days'        => ! empty($data['expiry_days'])    ? (int) $data['expiry_days']    : null,
                'certificate_prefix' => isset($data['certificate_prefix']) ? strtoupper(trim((string) $data['certificate_prefix'])) : null,
                'signatory_name'     => isset($data['signatory_name']) ? trim((string) $data['signatory_name']) : null,
                'signatory_title'    => isset($data['signatory_title']) ? trim((string) $data['signatory_title']) : null,
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $user_id,
            ]);

        if ($ok && $this->db->field_exists('access_type', 'courses')) {
            if (array_key_exists('access_type', $data)) {
                $access = strtolower(trim((string) $data['access_type']));
                if (in_array($access, ['open', 'approval_required', 'invitation_only', 'hidden'], true)) {
                    $this->db->where('id', (int) $course_id)->update('courses', ['access_type' => $access]);
                }
            }
        }

        return $ok;
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

        return $CI->course_phase2->user_manages_course((int) $user_id, (int) $course_id);
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

        $this->db->insert('course_modules', [
            'course_id'          => (int) $data['course_id'],
            'title'              => trim($data['title']),
            'description'        => isset($data['description']) ? trim($data['description']) : null,
            'content_type'       => $data['content_type'],
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
        return (bool) $this->db
            ->where('id', (int) $module_id)
            ->update('course_modules', [
                'title'              => trim($data['title']),
                'description'        => isset($data['description']) ? trim($data['description']) : null,
                'content_type'       => $data['content_type'],
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
}
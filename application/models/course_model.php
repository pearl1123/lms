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
 *   category_id, modality_id, access_type_id,
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
 * lib_course_access_type
 *   access_type_id, access_type_desc, archived
 *
 * @property CI_DB_mysqli_driver $db
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
     * Joins: category, modality, access_type, creator, module count,
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
                c.access_type_id,
                cc.name              AS category_name,
                lm.modality_desc     AS modality_name,
                lat.access_type_desc AS access_type_name,
                creator.fullname     AS creator_name,
                COALESCE(cm_cnt.total_modules, 0)   AS total_modules,
                COALESCE(en_cnt.total_enrolled, 0)  AS total_enrolled
            ', false)
            ->from('courses c')
            ->join('course_categories cc',
                   'cc.id = c.category_id', 'left')
            ->join('lib_course_modality lm',
                   'lm.modality_id = c.modality_id', 'left')
            ->join('lib_course_access_type lat',
                   'lat.access_type_id = c.access_type_id', 'left')
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

        if ($keyword !== '') {
            $this->db
                ->group_start()
                    ->like('c.title',       $keyword)
                    ->or_like('c.description', $keyword)
                    ->or_like('cc.name',    $keyword)
                ->group_end();
        }

        if ((int) $filter_cat > 0) {
            $this->db->where('c.category_id', (int) $filter_cat);
        }

        $result = $this->db->order_by('c.created_at', 'DESC')->get();

        return ($result && $result->num_rows() > 0)
            ? $result->result()
            : [];
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
                lat.access_type_desc AS access_type_name,
                creator.fullname     AS creator_name
            ', false)
            ->from('courses c')
            ->join('course_categories cc',
                   'cc.id = c.category_id', 'left')
            ->join('lib_course_modality lm',
                   'lm.modality_id = c.modality_id', 'left')
            ->join('lib_course_access_type lat',
                   'lat.access_type_id = c.access_type_id', 'left')
            ->join('aauth_users creator',
                   'creator.id = c.created_by', 'left')
            ->where('c.id',       (int) $course_id)
            ->where('c.archived', 0)
            ->get();

        return ($result && $result->num_rows() > 0)
            ? $result->row()
            : null;
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
                lat.access_type_desc AS access_type_name,
                creator.fullname     AS creator_name
            ', false)
            ->from('courses c')
            ->join('course_categories cc',
                   'cc.id = c.category_id', 'left')
            ->join('lib_course_modality lm',
                   'lm.modality_id = c.modality_id', 'left')
            ->join('lib_course_access_type lat',
                   'lat.access_type_id = c.access_type_id', 'left')
            ->join('aauth_users creator',
                   'creator.id = c.created_by', 'left')
            ->where('c.id', (int) $course_id)
            ->get();

        return ($result && $result->num_rows() > 0)
            ? $result->row()
            : null;
    }

    // =========================================================
    // MODULES
    // =========================================================

    /**
     * Get all non-archived modules for a course, ordered by module_order.
     * Joins module_progress for the given user (LEFT JOIN → null if no row).
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
            $this->db
                ->select('
                    COALESCE(mp.status, "not_started") AS my_status,
                    mp.score        AS my_score,
                    mp.completed_at AS my_completed_at
                ', false)
                ->join(
                    'module_progress mp',
                    'mp.module_id = cm.id AND mp.user_id = ' . (int) $user_id,
                    'left'
                );
        } else {
            // Provide null placeholders so view code works without errors
            $this->db->select(
                '"not_started" AS my_status,
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
        $row = $this->get_enrollment($user_id, $course_id);
        if ($row) {
            $st = isset($row->status) ? (string) $row->status : 'approved';
            if ($st === 'approved' || $st === 'pending') {
                return false;
            }
            if ($st === 'rejected') {
                return (bool) $this->db
                    ->where('id', (int) $row->id)
                    ->update('enrollments', [
                        'status'       => 'pending',
                        'enrolled_at'  => date('Y-m-d H:i:s'),
                    ]);
            }
        }

        return (bool) $this->db->insert('enrollments', [
            'user_id'     => (int) $user_id,
            'course_id'   => (int) $course_id,
            'status'      => 'pending',
            'enrolled_at' => date('Y-m-d H:i:s'),
        ]);
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

        foreach ($students as $s) {
            $s->progress_pct = ($total_modules > 0)
                ? $this->get_progress_pct($s->id, $course_id)
                : 0;
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
        $result = $this->db
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
            ->join('aauth_users u', 'u.id = e.user_id', 'left')
            ->where('c.created_by', (int) $instructor_user_id)
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

    /**
     * Get overall progress % (0–100) for a user in a course.
     *
     * @param  int $user_id
     * @param  int $course_id
     * @return int
     */
    public function get_progress_pct($user_id, $course_id)
    {
        $total = $this->count_modules($course_id);
        if ($total === 0) return 0;

        $done = $this->count_completed_modules($user_id, $course_id);
        return (int) round(($done / $total) * 100);
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
     * Get all non-archived access types.
     * Table: lib_course_access_type (access_type_id, access_type_desc, archived)
     *
     * @return object[]
     */
    public function get_access_types()
    {
        $result = $this->db
            ->where('archived', 0)
            ->order_by('access_type_desc', 'ASC')
            ->get('lib_course_access_type');

        return ($result && $result->num_rows() > 0)
            ? $result->result()
            : [];
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
                c.created_at, c.category_id, c.modality_id, c.access_type_id,
                c.expiry_days,
                cc.name              AS category_name,
                lm.modality_desc     AS modality_name,
                lat.access_type_desc AS access_type_name,
                creator.fullname     AS creator_name
            ', false)
            ->from('courses c')
            ->join('course_categories cc',
                   'cc.id = c.category_id', 'left')
            ->join('lib_course_modality lm',
                   'lm.modality_id = c.modality_id', 'left')
            ->join('lib_course_access_type lat',
                   'lat.access_type_id = c.access_type_id', 'left')
            ->join('aauth_users creator',
                   'creator.id = c.created_by', 'left');

        if ( ! $include_archived) {
            $this->db->where('c.archived', 0);
        }

        $result = $this->db->order_by('c.created_at', 'DESC')->get();

        if ( ! $result || $result->num_rows() === 0) return [];

        $courses = $result->result();
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
                c.created_at, c.category_id, c.modality_id, c.access_type_id,
                c.expiry_days,
                cc.name              AS category_name,
                lm.modality_desc     AS modality_name,
                lat.access_type_desc AS access_type_name
            ', false)
            ->from('courses c')
            ->join('course_categories cc',
                   'cc.id = c.category_id', 'left')
            ->join('lib_course_modality lm',
                   'lm.modality_id = c.modality_id', 'left')
            ->join('lib_course_access_type lat',
                   'lat.access_type_id = c.access_type_id', 'left')
            ->where('c.created_by', (int) $user_id);

        if ( ! $include_archived) {
            $this->db->where('c.archived', 0);
        }

        $result = $this->db->order_by('c.created_at', 'DESC')->get();

        if ( ! $result || $result->num_rows() === 0) return [];

        $courses = $result->result();
        foreach ($courses as $course) {
            $course->module_count   = $this->count_modules($course->id);
            $course->enrolled_count = $this->count_enrollments($course->id);
            $course->avg_progress   = $this->get_avg_progress($course->id);
        }

        return $courses;
    }

    /**
     * Average completion % across ALL enrolled students for a course.
     *
     * @param  int $course_id
     * @return int 0–100
     */
    public function get_avg_progress($course_id)
    {
        $total_modules  = $this->count_modules($course_id);
        $total_students = $this->count_enrollments($course_id);

        if ($total_modules === 0 || $total_students === 0) return 0;

        $total_possible = $total_modules * $total_students;

        $row = $this->db
            ->select('COUNT(*) AS cnt')
            ->from('module_progress mp')
            ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
            ->where('cm.course_id', (int) $course_id)
            ->where('cm.archived',  0)
            ->where('mp.status',    'completed')
            ->get()
            ->row();

        $done = $row ? (int) $row->cnt : 0;
        return (int) round(($done / $total_possible) * 100);
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
        $enrolled_ids = $this->get_enrolled_ids($user_id);
        if (empty($enrolled_ids)) return [];

        $courses = [];
        foreach ($enrolled_ids as $course_id) {
            $pct = $this->get_progress_pct($user_id, $course_id);
            if ($pct > 0 && $pct < 100) {
                $course = $this->get_course($course_id);
                if ($course) {
                    $course->my_progress  = $pct;
                    $course->module_count = $this->count_modules($course_id);
                    $courses[] = $course;
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
            ->where('module_id', (int) $module_id)
            ->where('archived',  0);

        if ($type !== '') {
            $this->db->where('type', $type);
        }

        $result = $this->db->get('lib_assessments');

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
            'access_type_id' => ! empty($data['access_type_id']) ? (int) $data['access_type_id'] : null,
            'expiry_days'    => ! empty($data['expiry_days'])    ? (int) $data['expiry_days']    : null,
            'archived'       => 0,
            'created_at'     => $now,
            'date_encoded'   => $now,
            'encoded_by'     => (int) $user_id,
        ]);
        return (int) $this->db->insert_id();
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
        return (bool) $this->db
            ->where('id', (int) $course_id)
            ->update('courses', [
                'title'              => trim($data['title']),
                'description'        => isset($data['description']) ? trim($data['description']) : null,
                'category_id'        => ! empty($data['category_id'])    ? (int) $data['category_id']    : null,
                'modality_id'        => ! empty($data['modality_id'])    ? (int) $data['modality_id']    : null,
                'access_type_id'     => ! empty($data['access_type_id']) ? (int) $data['access_type_id'] : null,
                'expiry_days'        => ! empty($data['expiry_days'])    ? (int) $data['expiry_days']    : null,
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $user_id,
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
     * Check whether a user owns a course (created_by = user_id).
     * Admin role check is done at controller level.
     *
     * @param  int $course_id
     * @param  int $user_id
     * @return bool
     */
    public function owns_course($course_id, $user_id)
    {
        return (bool) $this->db
            ->where('id',         (int) $course_id)
            ->where('created_by', (int) $user_id)
            ->where('archived',   0)
            ->count_all_results('courses');
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
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * dashboard_model
 *
 * All data queries that were previously living in Dashboard.php
 * controller methods. The controller now calls these methods and
 * passes the results to views — zero DB calls remain in the controller.
 *
 * @property CI_DB_mysqli_driver $db
 */
class dashboard_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // =========================================================
    // ADMIN ANALYTICS
    // =========================================================

    /**
     * Aggregate user and course counts for the admin dashboard.
     *
     * @return array
     */
    public function get_admin_analytics()
    {
        // Single query using conditional aggregation — one round-trip
        $row = $this->db
            ->select("
                COUNT(*)                                        AS total_users,
                SUM(CASE WHEN status  = 'active'   THEN 1 ELSE 0 END) AS active_users,
                SUM(CASE WHEN status  = 'inactive' THEN 1 ELSE 0 END) AS inactive_users,
                SUM(CASE WHEN role    = 'admin'    THEN 1 ELSE 0 END) AS total_admins,
                SUM(CASE WHEN role    = 'teacher'  THEN 1 ELSE 0 END) AS total_teachers,
                SUM(CASE WHEN role    = 'employee' THEN 1 ELSE 0 END) AS total_employees
            ", false)
            ->where('DELETED', 0)
            ->get('aauth_users')
            ->row();

        $total_courses = (int) $this->db
            ->where('archived', 0)
            ->count_all_results('courses');

        return [
            'total_users'     => $row ? (int) $row->total_users     : 0,
            'active_users'    => $row ? (int) $row->active_users     : 0,
            'inactive_users'  => $row ? (int) $row->inactive_users   : 0,
            'total_admins'    => $row ? (int) $row->total_admins     : 0,
            'total_teachers'  => $row ? (int) $row->total_teachers   : 0,
            'total_employees' => $row ? (int) $row->total_employees  : 0,
            'total_courses'   => $total_courses,
        ];
    }

    /**
     * Latest enrollments across all courses (for admin activity feed).
     *
     * @param  int $limit
     * @return object[]
     */
    public function get_latest_enrollments($limit = 10)
    {
        $r = $this->db
            ->select('e.id, e.enrolled_at,
                      u.fullname, u.employee_id, u.role,
                      c.title AS course_title', false)
            ->from('enrollments e')
            ->join('aauth_users u', 'u.id = e.user_id',  'left')
            ->join('courses c',     'c.id = e.course_id', 'left')
            ->where('e.status', 'approved')
            ->where('u.DELETED', 0)
            ->order_by('e.enrolled_at', 'DESC')
            ->limit((int) $limit)
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    // =========================================================
    // TEACHER / INSTRUCTOR ANALYTICS
    // =========================================================

    /**
     * Summary analytics for a teacher's own courses.
     *
     * @param  int $teacher_id
     * @return array
     */
    public function get_teacher_analytics($teacher_id)
    {
        $course_ids = $this->_get_teacher_course_ids($teacher_id);

        $my_courses = count($course_ids);
        $enrolled   = 0;
        $avg_comp   = 0;

        if ( ! empty($course_ids)) {
            $enrolled = (int) $this->db
                ->where_in('course_id', $course_ids)
                ->where('status', 'approved')
                ->count_all_results('enrollments');

            $comp_row = $this->db
                ->select('COUNT(DISTINCT mp.user_id) AS cnt', false)
                ->from('module_progress mp')
                ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
                ->where_in('cm.course_id', $course_ids)
                ->where('mp.status', 'completed')
                ->get()
                ->row();

            $completed = $comp_row ? (int) $comp_row->cnt : 0;
            $avg_comp  = $enrolled > 0 ? round(($completed / $enrolled) * 100) : 0;
        }

        return [
            'my_courses'        => $my_courses,
            'enrolled_students' => $enrolled,
            'avg_completion'    => $avg_comp,
            'pending_reviews'   => 0,
        ];
    }

    /**
     * A teacher's course list with per-course stats.
     *
     * @param  int $teacher_id
     * @return object[]
     */
    public function get_teacher_courses($teacher_id)
    {
        $r = $this->db
            ->select('c.id, c.title, c.archived, c.created_at', false)
            ->from('courses c')
            ->where('c.created_by', (int) $teacher_id)
            ->where('c.archived',   0)
            ->order_by('c.created_at', 'DESC')
            ->get();

        if ( ! $r || $r->num_rows() === 0) return [];

        $courses = $r->result();
        foreach ($courses as $course) {
            $course->enrolled_count = (int) $this->db
                ->where('course_id', $course->id)
                ->where('status', 'approved')
                ->count_all_results('enrollments');

            $course->module_count = (int) $this->db
                ->where('course_id', $course->id)
                ->where('archived',  0)
                ->count_all_results('course_modules');

            $course->avg_progress   = $this->_calc_avg_progress($course->id,
                                                                  $course->enrolled_count,
                                                                  $course->module_count);
            $course->status_label   = 'Published';
        }

        return $courses;
    }

    /**
     * Recent student activity across a teacher's courses.
     *
     * @param  int $teacher_id
     * @param  int $limit
     * @return object[]
     */
    public function get_teacher_recent_students($teacher_id, $limit = 10)
    {
        $course_ids = $this->_get_teacher_course_ids($teacher_id);
        if (empty($course_ids)) return [];

        $r = $this->db
            ->select('u.id, u.fullname, u.employee_id,
                      e.course_id, e.enrolled_at,
                      c.title AS course_title', false)
            ->from('enrollments e')
            ->join('aauth_users u', 'u.id = e.user_id',  'left')
            ->join('courses c',     'c.id = e.course_id', 'left')
            ->where_in('e.course_id', $course_ids)
            ->where('e.status', 'approved')
            ->where('u.DELETED', 0)
            ->order_by('e.enrolled_at', 'DESC')
            ->limit((int) $limit)
            ->get();

        if ( ! $r || $r->num_rows() === 0) return [];

        $students = $r->result();
        foreach ($students as $s) {
            $s->progress_pct = $this->_calc_user_progress($s->id, $s->course_id);
        }

        return $students;
    }

    // =========================================================
    // EMPLOYEE ANALYTICS
    // =========================================================

    /**
     * All enrolled courses for an employee with per-course progress.
     *
     * @param  int $user_id
     * @return object[]
     */
    public function get_employee_enrolled_courses($user_id)
    {
        $r = $this->db
            ->select('e.course_id, e.enrolled_at, c.title', false)
            ->from('enrollments e')
            ->join('courses c', 'c.id = e.course_id', 'left')
            ->where('e.user_id',   (int) $user_id)
            ->where('e.status',    'approved')
            ->where('c.archived',  0)
            ->order_by('e.enrolled_at', 'DESC')
            ->get();

        if ( ! $r || $r->num_rows() === 0) return [];

        $courses = $r->result();
        foreach ($courses as $c) {
            $module_count = (int) $this->db
                ->where('course_id', $c->course_id)
                ->where('archived',  0)
                ->count_all_results('course_modules');

            $c->module_count = $module_count;

            if ($module_count > 0) {
                $done_row = $this->db
                    ->select('COUNT(*) AS cnt', false)
                    ->from('module_progress mp')
                    ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
                    ->where('cm.course_id', $c->course_id)
                    ->where('mp.user_id',   (int) $user_id)
                    ->where('mp.status',    'completed')
                    ->get()
                    ->row();

                $done            = $done_row ? (int) $done_row->cnt : 0;
                $c->modules_done = $done;
                $c->progress_pct = round(($done / $module_count) * 100);
            } else {
                $c->modules_done = 0;
                $c->progress_pct = 0;
            }
        }

        return $courses;
    }

    /**
     * Aggregate stats for an employee from their enrolled courses.
     *
     * @param  object[] $enrolled_courses  Output of get_employee_enrolled_courses()
     * @return array
     */
    public function calc_employee_analytics(array $enrolled_courses)
    {
        $completed  = 0;
        $done_total = 0;

        foreach ($enrolled_courses as $c) {
            if ($c->progress_pct >= 100) $completed++;
            $done_total += $c->modules_done;
        }

        $minutes = $done_total * 30;

        return [
            'courses_enrolled'  => count($enrolled_courses),
            'courses_completed' => $completed,
            'learning_hours'    => floor($minutes / 60) . 'h ' . ($minutes % 60) . 'm',
            'badges'            => 0,
        ];
    }

    /**
     * Next 5 modules to study for an employee
     * (from their first in-progress or first enrolled course).
     *
     * @param  int      $user_id
     * @param  object[] $enrolled_courses
     * @return string[]  Module titles
     */
    public function get_employee_next_modules($user_id, array $enrolled_courses)
    {
        $next_course_id = null;
        foreach ($enrolled_courses as $c) {
            if ($c->progress_pct > 0 && $c->progress_pct < 100) {
                $next_course_id = $c->course_id;
                break;
            }
        }
        if ( ! $next_course_id && ! empty($enrolled_courses)) {
            $next_course_id = $enrolled_courses[0]->course_id;
        }
        if ( ! $next_course_id) return [];

        $r = $this->db
            ->select('cm.title', false)
            ->from('course_modules cm')
            ->join('module_progress mp',
                   'mp.module_id = cm.id AND mp.user_id = ' . (int) $user_id,
                   'left')
            ->where('cm.course_id', $next_course_id)
            ->where('cm.archived',  0)
            ->order_by('cm.module_order', 'ASC')
            ->limit(5)
            ->get();

        if ($r && $r->num_rows() > 0) {
            return array_column($r->result_array(), 'title');
        }

        return [];
    }

    /**
     * Top learners leaderboard (by completed modules).
     *
     * @param  int $limit
     * @return array[]
     */
    public function get_top_learners($limit = 5)
    {
        $r = $this->db
            ->select('u.fullname AS name, COUNT(mp.id) AS points', false)
            ->from('module_progress mp')
            ->join('aauth_users u', 'u.id = mp.user_id', 'left')
            ->where('mp.status', 'completed')
            ->where('u.DELETED', 0)
            ->where('u.role',    'employee')
            ->group_by('mp.user_id')
            ->order_by('points', 'DESC')
            ->limit((int) $limit)
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->result_array() : [];
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    /**
     * Get IDs of all active courses owned by a teacher.
     *
     * @param  int   $teacher_id
     * @return int[]
     */
    private function _get_teacher_course_ids($teacher_id)
    {
        $r = $this->db
            ->select('id')
            ->where('created_by', (int) $teacher_id)
            ->where('archived',   0)
            ->get('courses');

        if ( ! $r || $r->num_rows() === 0) return [];

        return array_column($r->result_array(), 'id');
    }

    /**
     * Average completion % across all enrolled students for a course.
     *
     * @param  int $course_id
     * @param  int $enrolled_count
     * @param  int $module_count
     * @return int
     */
    private function _calc_avg_progress($course_id, $enrolled_count, $module_count)
    {
        $total_possible = $enrolled_count * $module_count;
        if ($total_possible === 0) return 0;

        $row = $this->db
            ->select('COUNT(*) AS cnt', false)
            ->from('module_progress mp')
            ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
            ->where('cm.course_id', (int) $course_id)
            ->where('mp.status',    'completed')
            ->get()
            ->row();

        $done = $row ? (int) $row->cnt : 0;
        return (int) round(($done / $total_possible) * 100);
    }

    /**
     * Progress % for one user in one course.
     *
     * @param  int $user_id
     * @param  int $course_id
     * @return int
     */
    private function _calc_user_progress($user_id, $course_id)
    {
        $total = (int) $this->db
            ->where('course_id', (int) $course_id)
            ->where('archived',  0)
            ->count_all_results('course_modules');

        if ($total === 0) return 0;

        $row = $this->db
            ->select('COUNT(*) AS cnt', false)
            ->from('module_progress mp')
            ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
            ->where('cm.course_id', (int) $course_id)
            ->where('mp.user_id',   (int) $user_id)
            ->where('mp.status',    'completed')
            ->get()
            ->row();

        $done = $row ? (int) $row->cnt : 0;
        return (int) round(($done / $total) * 100);
    }
}
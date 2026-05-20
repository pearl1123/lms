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
 * @property Assessment_service $assessment_service
 * @property Course_model       $course_model
 * @property Course_phase2_model $course_phase2
 */
class dashboard_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('Course_model', 'course_model');
        $this->load->model('Course_phase2_model', 'course_phase2');
        $this->load->library('assessment_service');
    }

    /**
     * Standard dashboard data contract for all roles.
     *
     * @return array
     */
    public function empty_dashboard_data()
    {
        return [
            'stats' => [],
            'courses' => [],
            'notifications' => [],
            'certificates' => [],
            'requests' => [],
            'charts' => [],
        ];
    }

    /**
     * Build admin dashboard contract data.
     *
     * @param int $user_id
     * @return array
     */
    public function get_admin_dashboard_data($user_id)
    {
        $data = $this->empty_dashboard_data();
        $analytics = $this->get_admin_analytics();
        $pending_requests = $this->_safe_count('enrollments', ['status' => 'pending']);
        $certificate_total = $this->_safe_count('lib_certificates', ['archived' => 0]);

        $cert_today       = $this->_count_certificates_issue_day();
        $cert_week        = $this->_count_certificates_issue_week();
        $prog_agg         = $this->_enrollment_module_progress_stats(null);
        $this->_log_course_module_status_sample(__FUNCTION__);

        $total_enroll     = max(0, (int) ($prog_agg['total'] ?? 0));
        $fully_done       = max(0, (int) ($prog_agg['fully_completed'] ?? 0));
        $avg_pct_global   = (int) ($prog_agg['avg_pct'] ?? 0);
        $completion_rate  = $total_enroll > 0 ? (int) round(($fully_done / $total_enroll) * 100) : 0;

        log_message('debug', 'ADMIN COMPLETION DEBUG: ' . json_encode([
            'enrollments_with_modules' => $total_enroll,
            'fully_completed_courses'  => $fully_done,
            'completion_rate_pct'      => $completion_rate,
            'avg_pct_per_enrollment'   => $avg_pct_global,
        ]));

        $data['stats'] = [
            'total_users' => (int) ($analytics['total_users'] ?? 0),
            'active_users' => (int) ($analytics['active_users'] ?? 0),
            'inactive_users' => (int) ($analytics['inactive_users'] ?? 0),
            'total_courses' => (int) ($analytics['total_courses'] ?? 0),
            'pending_requests_count' => (int) $pending_requests,
            'certificate_total' => (int) $certificate_total,
            'certificate_issued_today' => (int) $cert_today,
            'certificate_issued_week' => (int) $cert_week,
            'completion_rate_pct'           => $completion_rate,
            'avg_course_progress_pct'       => $avg_pct_global,
            // Back-compat: cohort share of enrollees whose course has all modules marked completed on module_progress.
            'course_completion_pct'       => $completion_rate,
        ];

        $data['courses'] = [
            'latest_enrollments' => $this->get_latest_enrollments(10),
            'top_by_enrollments' => $this->get_top_courses_by_enrollments(8),
        ];
        $data['notifications'] = $this->get_role_notifications('admin', (int) $user_id, [], 14);
        $data['requests'] = $this->get_pending_enrollment_rows(10);
        $data['charts'] = [
            'users_over_time' => $this->chart_admin_users_over_time(12),
            'enrollment_trends' => $this->chart_admin_enrollment_trends(12),
            'course_completion_overview' => $this->chart_admin_course_completion_overview(),
            'certificate_issuance_trend' => $this->chart_admin_certificate_issuance_trend(12),
        ];

        return $data;
    }

    /**
     * Build instructor dashboard contract data.
     *
     * @param int $user_id
     * @return array
     */
    public function get_instructor_dashboard_data($user_id)
    {
        $data = $this->empty_dashboard_data();
        $analytics = $this->get_teacher_analytics((int) $user_id);
        $course_ids = $this->_get_teacher_course_ids((int) $user_id);

        $data['stats'] = [
            'my_courses' => (int) ($analytics['my_courses'] ?? 0),
            'enrolled_students' => (int) ($analytics['enrolled_students'] ?? 0),
            'avg_completion' => (int) ($analytics['avg_completion'] ?? 0),
            'pending_reviews' => (int) $this->_safe_count_in('enrollments', 'course_id', empty($course_ids) ? [0] : $course_ids, ['status' => 'pending']),
        ];

        $data['courses'] = [
            'assigned' => $this->get_teacher_courses((int) $user_id),
            'students' => $this->get_teacher_recent_students((int) $user_id, 10),
        ];
        $data['requests'] = $this->get_pending_enrollment_rows_for_courses($course_ids, 10);
        $data['certificates'] = $this->get_certificates_by_instructor_courses($course_ids, 10);
        $data['notifications'] = $this->get_role_notifications('instructor', (int) $user_id, $course_ids, 14);
        $data['charts'] = [
            'course_progress_distribution' => $this->chart_instructor_course_progress($course_ids, 8),
            'student_performance' => $this->chart_instructor_student_performance($course_ids, 10),
            'enrollment_per_course' => $this->chart_instructor_enrollment_per_course($course_ids, 8),
            'completion_distribution' => $this->chart_instructor_completion_distribution($course_ids),
        ];

        $data['courses']['struggling_learners'] = $this->get_instructor_struggling_learners($course_ids, 12);
        $data['courses']['certificate_ready'] = $this->get_instructor_certificate_ready_learners($course_ids, 12);

        return $data;
    }

    /**
     * Build student/employee dashboard contract data.
     *
     * @param int $user_id
     * @return array
     */
    public function get_student_dashboard_data($user_id)
    {
        $data = $this->empty_dashboard_data();
        $uid = (int) $user_id;
        $courses = $this->get_employee_enrolled_courses($uid);
        $analytics = $this->calc_employee_analytics($courses);
        $continue = $this->build_student_continue_learning($uid, $courses);

        $data['stats'] = [
            'courses_enrolled' => (int) ($analytics['courses_enrolled'] ?? 0),
            'courses_completed' => (int) ($analytics['courses_completed'] ?? 0),
            'learning_hours' => (string) ($analytics['learning_hours'] ?? '0h 0m'),
            'badges' => (int) ($analytics['badges'] ?? 0),
            'certificate_count' => (int) $this->_safe_count('lib_certificates', [
                'user_id'  => $uid,
                'archived' => 0,
            ]),
        ];
        $data['courses'] = [
            'enrolled' => $courses,
            'continue_learning' => $continue,
            'leaderboard' => [],
        ];
        $data['certificates'] = $this->get_user_certificates_lite($uid, 12);
        $data['notifications'] = $this->get_role_notifications('student', $uid, [], 12);
        $data['requests'] = [];
        $data['charts'] = [
            'learning_progress_trend' => $this->chart_student_learning_progress_trend($uid),
            'course_completion_distribution' => $this->chart_student_course_completion_distribution($courses),
            'weekly_learning_activity' => $this->chart_student_weekly_learning_activity($uid),
        ];

        $progress_snap = [];
        foreach ($courses as $c) {
            $progress_snap[] = [
                'course_id' => (int) ($c->course_id ?? 0),
                'pct'       => (int) ($c->progress_pct ?? 0),
                'modules'   => (int) ($c->modules_done ?? 0) . '/' . (int) ($c->module_count ?? 0),
            ];
        }
        log_message('debug', 'STUDENT DASHBOARD PROGRESS: ' . json_encode($progress_snap));
        log_message('debug', 'STUDENT DASHBOARD ACTIVE COURSE: ' . json_encode($continue));
        log_message('debug', 'STUDENT DASHBOARD CHART DATA: ' . json_encode($data['charts']));

        return $data;
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

            $prog      = $this->_enrollment_module_progress_stats($course_ids);
            $avg_comp  = (int) ($prog['avg_pct'] ?? 0);
            log_message('debug', 'INSTRUCTOR COMPLETION DEBUG: ' . json_encode([
                'context'                   => __FUNCTION__,
                'course_ids'                => array_values(array_map('intval', $course_ids)),
                'enrollments_with_modules'  => (int) ($prog['total'] ?? 0),
                'fully_completed'           => (int) ($prog['fully_completed'] ?? 0),
                'avg_pct_per_enrollment'    => $avg_comp,
            ]));
            $this->_log_course_module_status_sample(__FUNCTION__);
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
        return $this->course_model->get_courses_by_instructor((int) $teacher_id, false);
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
     * Uses {@see Assessment_service::get_course_progress_aggregate()} with
     * {@see Course_model::get_modules()} — same pairing as My_courses::_employee().
     *
     * @param  int $user_id
     * @return object[]
     */
    public function get_employee_enrolled_courses($user_id)
    {
        $uid = (int) $user_id;

        $r = $this->db
            ->select('e.course_id, e.enrolled_at, c.title', false)
            ->from('enrollments e')
            ->join('courses c', 'c.id = e.course_id', 'left')
            ->where('e.user_id', $uid)
            ->where('e.status', 'approved')
            ->where('c.archived', 0);
        if ($this->db->field_exists('publish_status', 'courses')) {
            $this->db->where('c.publish_status', 'published');
        }
        $r = $this->db
            ->order_by('e.enrolled_at', 'DESC')
            ->get();

        if ( ! $r || $r->num_rows() === 0) return [];

        $courses = $r->result();
        $course_ids = [];
        foreach ($courses as $c) {
            $course_ids[] = (int) $c->course_id;
        }

        $last_done_map = $this->_student_max_completed_at_by_course($uid, $course_ids);

        foreach ($courses as $c) {
            $cid               = (int) $c->course_id;
            $modules           = $this->course_model->get_modules($cid, $uid);
            $agg               = $this->assessment_service->get_course_progress_aggregate($uid, $cid, $modules);
            $c->module_count   = (int) ($agg['total_modules'] ?? 0);
            $c->modules_done   = (int) ($agg['completed_modules'] ?? 0);
            $c->progress_pct   = (int) ($agg['course_progress_percent'] ?? 0);
            $c->course_progress_percent = $c->progress_pct;

            $c->resume_url = site_url('course/' . $cid);
            foreach ($modules as $m) {
                if (($m->status ?? '') !== 'completed') {
                    $c->resume_url = site_url('courses/module/' . (int) $m->id);
                    break;
                }
            }

            $enrolled_ts       = strtotime((string) ($c->enrolled_at ?? '')) ?: 0;
            $last_completed    = $last_done_map[$cid] ?? null;
            $last_completed_ts = $last_completed ? (strtotime((string) $last_completed) ?: 0) : 0;
            $c->last_activity_ts = max($enrolled_ts, $last_completed_ts);
            $c->last_activity_label = $c->last_activity_ts > 0
                ? date('M j, Y', (int) $c->last_activity_ts)
                : '';

            $c->thumb_tone = abs(crc32((string) $cid)) % 8;

            $instructor_ids = $this->course_phase2->get_course_instructor_ids($cid);
            $c->instructor_name = '';
            if ( ! empty($instructor_ids)) {
                $inst = $this->db->select('fullname')->where('id', (int) $instructor_ids[0])->get('aauth_users', 1)->row();
                $c->instructor_name = ($inst && ! empty($inst->fullname)) ? (string) $inst->fullname : '';
            }
        }

        usort($courses, [$this, '_compare_student_dashboard_courses']);

        return $courses;
    }

    /**
     * @param int   $user_id
     * @param int[] $course_ids
     * @return array<int, string> course_id => max(completed_at)
     */
    private function _student_max_completed_at_by_course($user_id, array $course_ids)
    {
        if (empty($course_ids)) return [];

        $q = $this->db
            ->select('cm.course_id, MAX(mp.completed_at) AS last_done', false)
            ->from('module_progress mp')
            ->join('course_modules cm', 'cm.id = mp.module_id AND cm.archived = 0', 'inner')
            ->where('mp.user_id', (int) $user_id)
            ->where('mp.status', 'completed')
            ->where_in('cm.course_id', array_map('intval', $course_ids))
            ->group_by('cm.course_id')
            ->get();

        $out = [];
        if ($q && $q->num_rows() > 0) {
            foreach ($q->result() as $row) {
                $out[(int) $row->course_id] = $row->last_done;
            }
        }

        return $out;
    }

    /**
     * Sort: in-progress first, then not started, completed last; by recent activity.
     *
     * @param object $a
     * @param object $b
     * @return int
     */
    private function _compare_student_dashboard_courses($a, $b)
    {
        $tier = function ($c) {
            $p = (int) ($c->progress_pct ?? 0);

            return ($p >= 100) ? 2 : (($p > 0) ? 0 : 1);
        };

        $ta = $tier($a);
        $tb = $tier($b);
        if ($ta !== $tb) {
            return $ta <=> $tb;
        }

        $sa = (int) ($a->last_activity_ts ?? 0);
        $sb = (int) ($b->last_activity_ts ?? 0);
        if ($sa !== $sb) {
            return $sb <=> $sa;
        }

        return strcmp((string) ($b->enrolled_at ?? ''), (string) ($a->enrolled_at ?? ''));
    }

    /**
     * Backend-only “Continue learning” hero payload (most recently touched incomplete course).
     *
     * Next module follows {@see Course_model::get_modules()} order & mp.status === 'completed'.
     *
     * @param int        $user_id
     * @param object[]   $courses_enrolled_sorted Output of get_employee_enrolled_courses()
     * @return object|null
     */
    public function build_student_continue_learning($user_id, array $courses_enrolled_sorted)
    {
        $uid   = (int) $user_id;
        $candidates = [];

        foreach ($courses_enrolled_sorted as $row) {
            if ((int) ($row->progress_pct ?? 0) >= 100) {
                continue;
            }

            $candidates[] = $row;
        }

        usort($candidates, function ($a, $b) {
            $sa = (int) ($a->last_activity_ts ?? 0);
            $sb = (int) ($b->last_activity_ts ?? 0);

            if ($sa !== $sb) {
                return $sb <=> $sa;
            }

            return strcmp((string) ($b->enrolled_at ?? ''), (string) ($a->enrolled_at ?? ''));
        });

        $best = $candidates ? $candidates[0] : null;
        if ( ! $best) {
            return null;
        }

        $cid     = (int) $best->course_id;
        $modules = $this->course_model->get_modules($cid, $uid);
        $next    = null;
        foreach ($modules as $m) {
            if (($m->status ?? '') !== 'completed') {
                $next = $m;
                break;
            }
        }

        $remain = max(0, (int) ($best->module_count ?? 0) - (int) ($best->modules_done ?? 0));
        $plan   = $remain === 0
            ? 'Finish remaining steps in player'
            : ($remain === 1 ? '1 module left' : $remain . ' modules left');

        $hero                     = clone $best;
        $hero->resume_url         = $next !== null ? site_url('courses/module/' . (int) $next->id) : site_url('course/' . $cid);
        $hero->outline_url        = site_url('course/' . $cid);
        $hero->next_module_id      = $next ? (int) $next->id : null;
        $hero->next_module_title   = $next ? (string) $next->title : '';
        $hero->estimate_label      = $plan;

        return $hero;
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
     * Top published courses by approved enrollment volume.
     *
     * @param int $limit
     * @return object[]
     */
    public function get_top_courses_by_enrollments($limit = 8)
    {
        $limit = max(1, (int) $limit);
        $sql   = "
            SELECT c.id AS course_id, c.title AS course_title, COUNT(e.id) AS enrollment_count
            FROM courses c
            LEFT JOIN enrollments e ON e.course_id = c.id AND e.status = 'approved'
            WHERE c.archived = 0
            GROUP BY c.id
            ORDER BY enrollment_count DESC, c.title ASC
            LIMIT {$limit}
        ";
        $rows = $this->_safe_query_array($sql);
        $out  = [];
        foreach ($rows as $row) {
            $o                     = new stdClass();
            $o->course_id          = isset($row['course_id']) ? (int) $row['course_id'] : 0;
            $o->course_title       = (string) ($row['course_title'] ?? '');
            $o->enrollment_count   = (int) ($row['enrollment_count'] ?? 0);
            $out[]                 = $o;
        }

        return $out;
    }

    /**
     * Low completion enrollees among instructor-owned courses (module completion ratio).
     *
     * @param int[] $course_ids
     * @param int   $limit
     * @return object[]
     */
    public function get_instructor_struggling_learners(array $course_ids, $limit = 12)
    {
        $course_ids = array_values(array_filter(array_map('intval', $course_ids)));
        if (empty($course_ids)) return [];

        $r = $this->db
            ->select('e.id AS enrollment_id, e.user_id, e.course_id, u.fullname, c.title AS course_title', false)
            ->from('enrollments e')
            ->join('courses c', 'c.id = e.course_id', 'inner')
            ->join('aauth_users u', 'u.id = e.user_id', 'inner')
            ->where('e.status', 'approved')
            ->where('c.archived', 0)
            ->where('u.DELETED', 0)
            ->where_in('e.course_id', $course_ids)
            ->get();

        if ( ! $r || $r->num_rows() === 0) return [];

        $candidates = [];
        foreach ($r->result() as $row) {
            $uid     = (int) $row->user_id;
            $cid     = (int) $row->course_id;
            $modules = $this->course_model->get_modules($cid, $uid);
            if (empty($modules)) continue;

            $pct = $this->_enrollment_progress_pct($uid, $cid);
            if ($pct >= 100) continue;

            $o                = new stdClass();
            $o->enrollment_id = (int) $row->enrollment_id;
            $o->user_id       = $uid;
            $o->course_id     = $cid;
            $o->fullname      = (string) ($row->fullname ?? '');
            $o->course_title  = (string) ($row->course_title ?? '');
            $o->progress_pct  = $pct;
            $candidates[]     = $o;
        }

        usort($candidates, static function ($a, $b) {
            if ($a->progress_pct === $b->progress_pct) {
                return $a->enrollment_id <=> $b->enrollment_id;
            }

            return $a->progress_pct <=> $b->progress_pct;
        });

        return array_slice($candidates, 0, max(1, (int) $limit));
    }

    /**
     * Completed all modules (DB rows) under the course but no issued certificate yet.
     *
     * @param int[] $course_ids
     * @param int   $limit
     * @return object[]
     */
    public function get_instructor_certificate_ready_learners(array $course_ids, $limit = 12)
    {
        $course_ids = array_values(array_filter(array_map('intval', $course_ids)));
        if (empty($course_ids)) return [];

        $ids      = implode(',', $course_ids);
        $safe_lim = max(1, (int) $limit);
        $sql      = "
            SELECT 
                e.id AS enrollment_id,
                e.user_id,
                e.course_id,
                u.fullname,
                c.title AS course_title
            FROM enrollments e
            INNER JOIN courses c ON c.id = e.course_id AND c.archived = 0
            INNER JOIN aauth_users u ON u.id = e.user_id AND u.DELETED = 0
            INNER JOIN (
                SELECT course_id, COUNT(*) AS mod_cnt
                FROM course_modules
                WHERE archived = 0
                GROUP BY course_id
                HAVING mod_cnt > 0
            ) mc ON mc.course_id = e.course_id
            LEFT JOIN lib_certificates uc
                ON uc.course_id = e.course_id AND uc.user_id = e.user_id AND uc.archived = 0
            WHERE e.status = 'approved'
              AND e.course_id IN ({$ids})
              AND uc.id IS NULL
              AND (
                SELECT COUNT(DISTINCT mp.module_id)
                FROM module_progress mp
                INNER JOIN course_modules cm2 ON cm2.id = mp.module_id AND cm2.course_id = e.course_id AND cm2.archived = 0
                WHERE mp.user_id = e.user_id AND mp.status = 'completed'
              ) = mc.mod_cnt
            ORDER BY e.enrolled_at DESC
            LIMIT {$safe_lim}
        ";
        $rows = $this->_safe_query_array($sql);
        $out  = [];
        foreach ($rows as $row) {
            $o                = new stdClass();
            $o->enrollment_id = (int) ($row['enrollment_id'] ?? 0);
            $o->user_id       = (int) ($row['user_id'] ?? 0);
            $o->course_id     = (int) ($row['course_id'] ?? 0);
            $o->fullname      = (string) ($row['fullname'] ?? '');
            $o->course_title  = (string) ($row['course_title'] ?? '');
            $out[]            = $o;
        }

        return $out;
    }

    public function get_pending_enrollment_rows($limit = 10)
    {
        $r = $this->db
            ->select('e.id, e.id AS enrollment_id, e.user_id, e.course_id, e.enrolled_at, u.fullname, c.title AS course_title', false)
            ->from('enrollments e')
            ->join('aauth_users u', 'u.id = e.user_id', 'left')
            ->join('courses c', 'c.id = e.course_id', 'left')
            ->where('e.status', 'pending')
            ->where('u.DELETED', 0)
            ->order_by('e.enrolled_at', 'DESC')
            ->limit((int) $limit)
            ->get();
        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    public function get_pending_enrollment_rows_for_courses(array $course_ids, $limit = 10)
    {
        if (empty($course_ids)) return [];
        $r = $this->db
            ->select('e.id, e.id AS enrollment_id, e.user_id, e.course_id, e.enrolled_at, u.fullname, c.title AS course_title', false)
            ->from('enrollments e')
            ->join('aauth_users u', 'u.id = e.user_id', 'left')
            ->join('courses c', 'c.id = e.course_id', 'left')
            ->where_in('e.course_id', $course_ids)
            ->where('e.status', 'pending')
            ->where('u.DELETED', 0)
            ->order_by('e.enrolled_at', 'DESC')
            ->limit((int) $limit)
            ->get();
        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    public function get_user_certificates_lite($user_id, $limit = 10)
    {
        $r = $this->db
            ->select('uc.id, uc.user_id, uc.course_id, uc.file_path, uc.issued_at, c.title AS course_title', false)
            ->from('lib_certificates uc')
            ->join('courses c', 'c.id = uc.course_id', 'left')
            ->where('uc.user_id', (int) $user_id)
            ->where('uc.archived', 0)
            ->order_by('uc.issued_at', 'DESC')
            ->limit((int) $limit)
            ->get();
        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    public function get_certificates_by_instructor_courses(array $course_ids, $limit = 10)
    {
        if (empty($course_ids)) return [];
        $r = $this->db
            ->select('uc.id, uc.user_id, uc.course_id, uc.file_path, uc.issued_at, u.fullname AS user_name, c.title AS course_title', false)
            ->from('lib_certificates uc')
            ->join('aauth_users u', 'u.id = uc.user_id', 'left')
            ->join('courses c', 'c.id = uc.course_id', 'left')
            ->where_in('uc.course_id', $course_ids)
            ->where('uc.archived', 0)
            ->where('u.DELETED', 0)
            ->order_by('uc.issued_at', 'DESC')
            ->limit((int) $limit)
            ->get();
        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    public function get_role_notifications($role, $user_id, array $course_ids = [], $limit = 10)
    {
        if ($role === 'admin') {
            $r = $this->db
                ->select('0 AS user_notification_id, 0 AS is_read, n.date_encoded, n.notification_id, n.notification_title AS title, n.notification_message AS message, n.reference_id, n.notification_type_id AS type_id, nt.notification_type_desc AS type_name', false)
                ->from('lib_notification n')
                ->join('lib_notification_type nt', 'nt.notification_type_id = n.notification_type_id', 'left')
                ->where('n.archived', 0)
                ->order_by('n.date_encoded', 'DESC')
                ->limit((int) $limit)
                ->get();
            if (! $r || $r->num_rows() === 0) return [];
            $rows = $r->result();
            foreach ($rows as $row) {
                $row->type_key = $this->normalize_type_key($row);
            }
            return $rows;
        }

        $r = $this->db
            ->select('un.user_notification_id, un.is_read, un.date_encoded, n.notification_id, n.notification_title AS title, n.notification_message AS message, n.reference_id, n.notification_type_id AS type_id, nt.notification_type_desc AS type_name', false)
            ->from('lib_user_notification un')
            ->join('lib_notification n', 'n.notification_id = un.notification_id', 'left')
            ->join('lib_notification_type nt', 'nt.notification_type_id = n.notification_type_id', 'left')
            ->where('un.user_id', (int) $user_id)
            ->where('un.archived', 0)
            ->where('n.archived', 0)
            ->order_by('un.date_encoded', 'DESC')
            ->limit((int) $limit * 4)
            ->get();

        if (! $r || $r->num_rows() === 0) return [];

        $out = [];
        foreach ($r->result() as $row) {
            $type_key = $this->normalize_type_key($row);
            if ($this->allow_notification_for_role($role, $type_key, (int) ($row->reference_id ?? 0), $course_ids)) {
                $row->type_key = $type_key;
                $out[] = $row;
                if (count($out) >= (int) $limit) break;
            }
        }
        return $out;
    }

    private function normalize_type_key($row)
    {
        $txt = strtolower(trim((string) ($row->type_name ?? '')));
        $title = strtolower(trim((string) ($row->title ?? '')));
        if (strpos($txt, 'cert') !== false || strpos($title, 'cert') !== false) return 'certificate';
        if (strpos($txt, 'approv') !== false || strpos($title, 'approv') !== false) return 'approval';
        if (strpos($txt, 'reject') !== false || strpos($title, 'reject') !== false) return 'rejection';
        if (strpos($txt, 'request') !== false || strpos($title, 'request') !== false || strpos($txt, 'enroll') !== false) return 'request';
        return 'system';
    }

    private function allow_notification_for_role($role, $type_key, $reference_id, array $course_ids)
    {
        if ($role === 'admin') return true;

        if ($role === 'teacher' || $role === 'instructor') {
            if (in_array($type_key, ['approval', 'rejection', 'request'], true)) return true;
            if ($type_key === 'certificate' && !empty($course_ids) && in_array((int) $reference_id, $course_ids, true)) return true;
            return false;
        }

        return in_array($type_key, ['certificate', 'approval', 'rejection', 'system'], true);
    }

    public function chart_admin_users_over_time($months = 12)
    {
        $sql = "SELECT DATE_FORMAT(date_added, '%Y-%m') AS ym, COUNT(*) AS cnt
             FROM aauth_users
             WHERE DELETED = 0
             GROUP BY DATE_FORMAT(date_added, '%Y-%m')
             ORDER BY ym DESC
             LIMIT " . (int) $months;
        $rows = $this->_safe_query_array($sql);
        $rows = array_reverse($rows);
        return $this->_chart_payload('admin.users_over_time', array_column($rows, 'ym'), array_map('intval', array_column($rows, 'cnt')), $sql);
    }

    public function chart_admin_course_completion_overview()
    {
        $pairs = $this->_fetch_approved_enrollment_pairs(null);
        $b1    = 0;
        $b2    = 0;
        $b3    = 0;

        foreach ($pairs as $pair) {
            $uid     = (int) $pair->user_id;
            $cid     = (int) $pair->course_id;
            $modules = $this->course_model->get_modules($cid, $uid);
            if (empty($modules)) continue;

            $pct = $this->_enrollment_progress_pct($uid, $cid);
            if ($pct < 50) {
                $b1++;
            } elseif ($pct < 80) {
                $b2++;
            } else {
                $b3++;
            }
        }

        log_message('debug', 'ADMIN COMPLETION DEBUG (chart_course_completion_overview): ' . json_encode([
            'b1' => $b1, 'b2' => $b2, 'b3' => $b3,
        ]));

        return $this->_chart_payload('admin.course_completion_overview', ['0-49%', '50-79%', '80-100%'], [$b1, $b2, $b3]);
    }

    public function chart_admin_enrollment_trends($months = 12)
    {
        $sql = "SELECT DATE_FORMAT(enrolled_at, '%Y-%m') AS ym, COUNT(*) AS cnt
             FROM enrollments
             WHERE status = 'approved'
             GROUP BY DATE_FORMAT(enrolled_at, '%Y-%m')
             ORDER BY ym DESC
             LIMIT " . (int) $months;
        $rows = $this->_safe_query_array($sql);
        $rows = array_reverse($rows);
        return $this->_chart_payload('admin.enrollment_trends', array_column($rows, 'ym'), array_map('intval', array_column($rows, 'cnt')), $sql);
    }

    public function chart_admin_certificate_issuance_trend($months = 12)
    {
        $sql = "SELECT DATE_FORMAT(issued_at, '%Y-%m') AS ym, COUNT(*) AS cnt
                FROM lib_certificates
                WHERE archived = 0
                GROUP BY DATE_FORMAT(issued_at, '%Y-%m')
                ORDER BY ym DESC
                LIMIT " . (int) $months;
        $rows = $this->_safe_query_array($sql);
        $rows = array_reverse($rows);
        return $this->_chart_payload('admin.certificate_issuance_trend', array_column($rows, 'ym'), array_map('intval', array_column($rows, 'cnt')), $sql);
    }

    public function chart_instructor_course_progress(array $course_ids, $limit = 8)
    {
        $course_ids = array_values(array_filter(array_map('intval', $course_ids)));
        if (empty($course_ids)) return $this->_chart_payload('instructor.course_progress_distribution', [], [], 'NO_COURSE_IDS');

        $rows = [];
        foreach ($course_ids as $cid) {
            $course = $this->course_model->get_course($cid);
            if ( ! $course) continue;
            $rows[] = [
                'title' => (string) ($course->title ?? ''),
                'pct'   => $this->course_model->get_avg_progress($cid),
            ];
        }

        usort($rows, static function ($a, $b) {
            return $b['pct'] <=> $a['pct'];
        });

        $rows = array_slice($rows, 0, max(1, (int) $limit));
        log_message('debug', 'INSTRUCTOR COMPLETION DEBUG (chart_course_progress): ' . json_encode(array_slice($rows, 0, 5)));

        return $this->_chart_payload(
            'instructor.course_progress_distribution',
            array_column($rows, 'title'),
            array_map('intval', array_column($rows, 'pct'))
        );
    }

    public function chart_instructor_student_performance(array $course_ids, $limit = 10)
    {
        $course_ids = array_values(array_filter(array_map('intval', $course_ids)));
        if (empty($course_ids)) return $this->_chart_payload('instructor.student_performance', [], [], 'NO_COURSE_IDS');

        $ids      = implode(',', $course_ids);
        $safe_lim = max(1, (int) $limit);
        $sql      = "
            SELECT u.fullname, t.completed_cnt
            FROM (
                SELECT mp.user_id, COUNT(*) AS completed_cnt
                FROM module_progress mp
                INNER JOIN course_modules cm ON cm.id = mp.module_id AND cm.archived = 0
                WHERE cm.course_id IN ({$ids})
                  AND mp.status = 'completed'
                GROUP BY mp.user_id
            ) t
            INNER JOIN aauth_users u ON u.id = t.user_id AND u.DELETED = 0
            ORDER BY t.completed_cnt DESC
            LIMIT {$safe_lim}
        ";
        $rows = $this->_safe_query_array($sql);
        log_message('debug', 'INSTRUCTOR COMPLETION DEBUG (chart_student_performance): ' . json_encode(array_slice($rows, 0, 5)));

        return $this->_chart_payload('instructor.student_performance', array_column($rows, 'fullname'), array_map('intval', array_column($rows, 'completed_cnt')), $sql);
    }

    public function chart_instructor_enrollment_per_course(array $course_ids, $limit = 8)
    {
        if (empty($course_ids)) return $this->_chart_payload('instructor.enrollment_per_course', [], [], 'NO_COURSE_IDS');
        $r = $this->db
            ->select('c.title, COUNT(e.id) AS enrolled_cnt', false)
            ->from('enrollments e')
            ->join('courses c', 'c.id = e.course_id', 'inner')
            ->where_in('e.course_id', $course_ids)
            ->where('e.status', 'approved')
            ->group_by('e.course_id')
            ->order_by('enrolled_cnt', 'DESC')
            ->limit((int) $limit)
            ->get();
        $rows = ($r && $r->num_rows() > 0) ? $r->result_array() : [];
        return $this->_chart_payload('instructor.enrollment_per_course', array_column($rows, 'title'), array_map('intval', array_column($rows, 'enrolled_cnt')));
    }

    /**
     * Learner enrollment completion buckets scoped to instructor courses.
     *
     * @param int[] $course_ids
     * @return array{labels: string[], values: int[]}
     */
    public function chart_instructor_completion_distribution(array $course_ids)
    {
        $course_ids = array_values(array_filter(array_map('intval', $course_ids)));
        if (empty($course_ids)) return $this->_chart_payload('instructor.completion_distribution', [], [], 'NO_COURSE_IDS');

        $pairs = $this->_fetch_approved_enrollment_pairs($course_ids);
        $b1    = 0;
        $b2    = 0;
        $b3    = 0;
        $b4    = 0;

        foreach ($pairs as $pair) {
            $uid     = (int) $pair->user_id;
            $cid     = (int) $pair->course_id;
            $modules = $this->course_model->get_modules($cid, $uid);
            if (empty($modules)) continue;

            $pct = $this->_enrollment_progress_pct($uid, $cid);
            if ($pct < 50) {
                $b1++;
            } elseif ($pct < 80) {
                $b2++;
            } elseif ($pct < 100) {
                $b3++;
            } else {
                $b4++;
            }
        }

        return $this->_chart_payload(
            'instructor.completion_distribution',
            ['0-49%', '50-79%', '80-99%', 'Completed'],
            [$b1, $b2, $b3, $b4]
        );
    }

    /**
     * Cumulative completed modules over time (month buckets) for the learner.
     *
     * @param int $user_id
     * @return array{labels: string[], values: int[]}
     */
    public function chart_student_learning_progress_trend($user_id)
    {
        $uid = (int) $user_id;
        $sql = "SELECT DATE_FORMAT(mp.completed_at, '%Y-%m') AS ym, COUNT(*) AS cnt
                FROM module_progress mp
                WHERE mp.user_id = " . $uid . "
                  AND mp.status = 'completed'
                  AND mp.completed_at IS NOT NULL
                GROUP BY ym
                ORDER BY ym ASC
                LIMIT 18";
        $rows   = $this->_safe_query_array($sql);
        $labels = [];
        $values = [];
        $cum    = 0;
        foreach ($rows as $row) {
            $cum      += (int) ($row['cnt'] ?? 0);
            $labels[] = (string) ($row['ym'] ?? '');
            $values[] = $cum;
        }

        return $this->_chart_payload('student.learning_progress_trend', $labels, $values, $sql);
    }

    /**
     * Enrolled course buckets from live dashboard course rows (same progress % as learning UI).
     *
     * @param object[] $courses get_employee_enrolled_courses()
     * @return array{labels: string[], values: int[]}
     */
    public function chart_student_course_completion_distribution(array $courses)
    {
        $done  = 0;
        $prog  = 0;
        $start = 0;
        foreach ($courses as $c) {
            $p = (int) ($c->progress_pct ?? 0);
            if ($p >= 100) {
                $done++;
            } elseif ($p > 0) {
                $prog++;
            } else {
                $start++;
            }
        }

        return $this->_chart_payload(
            'student.course_completion_distribution',
            ['Completed', 'In progress', 'Not started'],
            [$done, $prog, $start],
            ''
        );
    }

    /**
     * Modules completed per day for the last 7 days.
     *
     * @param int $user_id
     * @return array{labels: string[], values: int[]}
     */
    public function chart_student_weekly_learning_activity($user_id)
    {
        $uid    = (int) $user_id;
        $keys   = [];
        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $ts       = strtotime('-' . $i . ' days');
            $keys[]   = date('Y-m-d', $ts);
            $labels[] = date('D', $ts);
        }

        $sql = "SELECT DATE(mp.completed_at) AS d, COUNT(*) AS cnt
                FROM module_progress mp
                WHERE mp.user_id = " . $uid . "
                  AND mp.status = 'completed'
                  AND mp.completed_at IS NOT NULL
                  AND DATE(mp.completed_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(mp.completed_at)";
        $rows = $this->_safe_query_array($sql);
        $map  = [];
        foreach ($rows as $row) {
            $map[(string) ($row['d'] ?? '')] = (int) ($row['cnt'] ?? 0);
        }

        $vals = [];
        foreach ($keys as $k) {
            $vals[] = (int) ($map[$k] ?? 0);
        }

        return $this->_chart_payload('student.weekly_learning_activity', $labels, $vals, $sql);
    }

    /**
     * @return int
     */
    private function _count_certificates_issue_day()
    {
        $q = $this->db->query("SELECT COUNT(*) AS cnt FROM lib_certificates WHERE archived = 0 AND DATE(issued_at) = CURDATE()");
        if (! $q) return 0;
        $row = $q->row();

        return $row ? (int) ($row->cnt ?? 0) : 0;
    }

    /**
     * Last 7 calendar days including today.
     *
     * @return int
     */
    private function _count_certificates_issue_week()
    {
        $q = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM lib_certificates WHERE archived = 0 AND DATE(issued_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)"
        );
        if (! $q) return 0;
        $row = $q->row();

        return $row ? (int) ($row->cnt ?? 0) : 0;
    }

    /**
     * Approved enrollments with course context for progress aggregation.
     *
     * @param int[]|null $course_ids null = all courses; empty array = none
     * @return object[]
     */
    private function _fetch_approved_enrollment_pairs($course_ids = null)
    {
        if (is_array($course_ids) && empty($course_ids)) {
            return [];
        }

        $this->db
            ->select('e.user_id, e.course_id, e.id AS enrollment_id', false)
            ->from('enrollments e')
            ->join('courses c', 'c.id = e.course_id', 'inner')
            ->where('e.status', 'approved')
            ->where('c.archived', 0);

        if (is_array($course_ids)) {
            $this->db->where_in('e.course_id', array_map('intval', $course_ids));
        }

        $r = $this->db->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * Progress % for one enrollment via Assessment_service.
     *
     * @param int $user_id
     * @param int $course_id
     * @return int
     */
    private function _enrollment_progress_pct($user_id, $course_id)
    {
        $uid     = (int) $user_id;
        $cid     = (int) $course_id;
        $modules = $this->course_model->get_modules($cid, $uid);
        if (empty($modules)) {
            return 0;
        }

        $agg = $this->assessment_service->get_course_progress_aggregate($uid, $cid, $modules);

        return (int) ($agg['course_progress_percent'] ?? 0);
    }

    /**
     * Cohort progress stats for approved enrollments (Assessment_service-driven).
     *
     * @param int[]|null $course_ids null = all instructor courses / platform; empty array = zeroed result
     * @return array{total:int, fully_completed:int, avg_pct:int}
     */
    private function _enrollment_module_progress_stats($course_ids = null)
    {
        if (is_array($course_ids) && empty($course_ids)) {
            return ['total' => 0, 'fully_completed' => 0, 'avg_pct' => 0];
        }

        $pairs = $this->_fetch_approved_enrollment_pairs($course_ids);
        if (empty($pairs)) {
            return ['total' => 0, 'fully_completed' => 0, 'avg_pct' => 0];
        }

        $total = 0;
        $fully = 0;
        $sum   = 0;

        foreach ($pairs as $pair) {
            $uid     = (int) $pair->user_id;
            $cid     = (int) $pair->course_id;
            $modules = $this->course_model->get_modules($cid, $uid);
            if (empty($modules)) {
                continue;
            }

            $pct = $this->_enrollment_progress_pct($uid, $cid);
            $total++;
            $sum += $pct;
            if ($pct >= 100) {
                $fully++;
            }
        }

        return [
            'total'           => $total,
            'fully_completed' => $fully,
            'avg_pct'         => $total > 0 ? (int) round($sum / $total) : 0,
        ];
    }

    /**
     * Average learner progress % for one course (approved enrollments only).
     *
     * @param int $course_id
     * @return int
     */
    private function _avg_enrollment_pct_for_course($course_id)
    {
        return $this->course_model->get_avg_progress((int) $course_id);
    }

    /**
     * @param string $context caller name for correlation in logs
     */
    private function _log_course_module_status_sample($context = '')
    {
        $sql  = "
            SELECT mp.user_id, mp.module_id, mp.status, cm.course_id
            FROM module_progress mp
            INNER JOIN course_modules cm ON cm.id = mp.module_id AND cm.archived = 0
            ORDER BY mp.user_id ASC, mp.module_id ASC
            LIMIT 8
        ";
        $rows = $this->_safe_query_array($sql);
        log_message('debug', 'COURSE MODULE STATUS SAMPLE (' . $context . '): ' . json_encode($rows));
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    /**
     * Safe count helper that never returns query objects.
     * Returns 0 when query fails.
     *
     * @param string $table
     * @param array  $where
     * @return int
     */
    private function _safe_count($table, array $where = [])
    {
        $qb = $this->db
            ->select('COUNT(*) AS cnt', false)
            ->from($table);

        foreach ($where as $k => $v) {
            $qb->where($k, $v);
        }

        $q = $qb->get();
        if (! $q) {
            return 0;
        }

        $row = $q->row();
        return $row ? (int) ($row->cnt ?? 0) : 0;
    }

    /**
     * Safe count helper with where_in + where clauses.
     *
     * @param string $table
     * @param string $in_field
     * @param array  $in_values
     * @param array  $where
     * @return int
     */
    private function _safe_count_in($table, $in_field, array $in_values, array $where = [])
    {
        $qb = $this->db
            ->select('COUNT(*) AS cnt', false)
            ->from($table)
            ->where_in($in_field, $in_values);

        foreach ($where as $k => $v) {
            $qb->where($k, $v);
        }

        $q = $qb->get();
        if (! $q) {
            return 0;
        }

        $row = $q->row();
        return $row ? (int) ($row->cnt ?? 0) : 0;
    }

    /**
     * Safe raw-query fetch helper that always returns array rows.
     *
     * @param string $sql
     * @return array
     */
    private function _safe_query_array($sql)
    {
        log_message('debug', 'CHART SQL: ' . $sql);
        $q = $this->db->query($sql);
        if (! $q) {
            log_message('debug', 'CHART SQL FAILED');
            return [];
        }
        return $q->result_array();
    }

    /**
     * Normalize chart output for Chart.js consumers.
     * Always returns non-empty labels/values and logs payload.
     *
     * @param string $chart_name
     * @param array $labels
     * @param array $values
     * @param string $sql
     * @return array
     */
    private function _chart_payload($chart_name, array $labels, array $values, $sql = '')
    {
        if (empty($labels) || empty($values) || count($labels) !== count($values)) {
            if (!empty($sql)) {
                log_message('debug', 'CHART EMPTY DATASET: ' . $chart_name . ' | SQL: ' . $sql);
            } else {
                log_message('debug', 'CHART EMPTY DATASET: ' . $chart_name);
            }
            $result = ['labels' => ['No Data'], 'values' => [0]];
            log_message('debug', 'CHART DATA: ' . json_encode($result));
            return $result;
        }

        $result = [
            'labels' => array_values($labels),
            'values' => array_map('intval', array_values($values)),
        ];
        log_message('debug', 'CHART DATA: ' . json_encode($result));
        return $result;
    }

    /**
     * Get IDs of all active courses assigned to a teacher via course_instructors.
     *
     * @param  int   $teacher_id
     * @return int[]
     */
    private function _get_teacher_course_ids($teacher_id)
    {
        return $this->course_phase2->get_instructor_course_ids((int) $teacher_id);
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
        return $this->_enrollment_progress_pct((int) $user_id, (int) $course_id);
    }
}
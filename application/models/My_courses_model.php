<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read queries for My_courses controller dashboards (no SQL in controller).
 *
 * @property Course_model $course_model
 * @property Course_phase2_model $course_phase2
 */
class My_courses_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Course_model', 'course_model');
        $this->load->model('Course_phase2_model', 'course_phase2');
    }

    /**
     * @return object[]
     */
    public function get_admin_courses_with_stats()
    {
        $result = $this->db
            ->select('c.id, c.title, c.description, c.archived,
                      c.created_at, c.category_id,
                      cc.name AS category_name')
            ->from('courses c')
            ->join('course_categories cc', 'cc.id = c.category_id', 'left')
            ->order_by('c.created_at', 'DESC')
            ->get();

        if ( ! $result || $result->num_rows() === 0) {
            return [];
        }

        $courses = [];
        foreach ($result->result() as $course) {
            $course->module_count = $this->count_modules((int) $course->id);
            $course->avg_progress = $this->course_model->get_avg_progress((int) $course->id);
            $this->course_model->attach_enrollment_guard($course);
            $courses[] = $course;
        }

        return $courses;
    }

    /**
     * @param  int $instructor_id
     * @return object[]
     */
    public function get_instructor_courses_with_stats($instructor_id)
    {
        $instructor_id = (int) $instructor_id;

        $this->db
            ->select('c.id, c.title, c.description, c.archived,
                      c.created_at, c.category_id, c.access_type, c.publish_status,
                      cc.name AS category_name')
            ->from('courses c')
            ->join('course_categories cc', 'cc.id = c.category_id', 'left');
        $this->course_phase2->restrict_query_to_instructor_courses($instructor_id);
        $result = $this->db->order_by('c.created_at', 'DESC')->get();

        if ( ! $result || $result->num_rows() === 0) {
            return [];
        }

        $courses = [];
        foreach ($result->result() as $course) {
            $course->module_count = $this->count_modules((int) $course->id);
            $course->avg_progress = $this->course_model->get_avg_progress((int) $course->id);
            $this->course_model->attach_enrollment_guard($course);
            $courses[] = $course;
        }

        return $courses;
    }

    /**
     * @param  int    $user_id
     * @param  string $status pending|rejected|approved
     * @return object[]
     */
    public function get_user_enrollments_by_status($user_id, $status)
    {
        $user_id = (int) $user_id;
        $status  = (string) $status;

        $result = $this->db
            ->select('e.course_id, e.enrolled_at,
                      c.title, c.description, c.category_id,
                      cc.name AS category_name')
            ->from('enrollments e')
            ->join('courses c', 'c.id = e.course_id', 'left')
            ->join('course_categories cc', 'cc.id = c.category_id', 'left')
            ->where('e.user_id', $user_id)
            ->where('e.status', $status)
            ->where('c.archived', 0)
            ->where('c.publish_status', 'published')
            ->order_by('e.enrolled_at', 'DESC')
            ->get();

        if ( ! $result || $result->num_rows() === 0) {
            return [];
        }

        $rows = [];
        foreach ($result->result() as $row) {
            $row->module_count = $this->count_modules((int) $row->course_id);
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Course IDs with pending or approved enrollment (exclude from catalog).
     *
     * @param  int $user_id
     * @return int[]
     */
    public function get_blocked_enrollment_course_ids($user_id)
    {
        $result = $this->db
            ->select('course_id')
            ->where('user_id', (int) $user_id)
            ->where_in('status', ['pending', 'approved'])
            ->get('enrollments');

        if ( ! $result || $result->num_rows() === 0) {
            return [];
        }

        $ids = [];
        foreach ($result->result() as $row) {
            $ids[] = (int) $row->course_id;
        }

        return $ids;
    }

    /**
     * @return object[]
     */
    public function get_active_categories()
    {
        return $this->course_model->get_categories();
    }

    /**
     * @param  int $course_id
     * @return int
     */
    public function count_modules($course_id)
    {
        return (int) $this->db
            ->where('course_id', (int) $course_id)
            ->where('archived', 0)
            ->count_all_results('course_modules');
    }

    /**
     * @param  int $course_id
     * @return int
     */
    public function count_approved_enrollments($course_id)
    {
        return (int) $this->db
            ->where('course_id', (int) $course_id)
            ->where('status', 'approved')
            ->count_all_results('enrollments');
    }
}

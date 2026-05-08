<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Instructor / admin enrollment approvals.
 *
 * @property Course_model        $course_model
 * @property Event_dispatcher    $event_dispatcher
 */
class Enrollments extends KA_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Course_model', 'course_model');
        $this->load->library('event_dispatcher');
        $this->require_manager();
    }

    public function requests()
    {
        $user = $this->auth_user;
        if ($user->role === 'admin') {
            $pending_rows = $this->course_model->get_all_pending_enrollments();
        } else {
            $pending_rows = $this->course_model
                ->get_pending_enrollments_for_instructor((int) $user->id);
        }

        $this->render('enrollments/requests', [
            'page_title'   => 'Enrollment requests',
            'pending_rows' => $pending_rows,
        ], [
            ['label' => 'Dashboard', 'url' => 'dashboard'],
            ['label' => 'Enrollment requests'],
        ]);
    }

    public function approve($id = null)
    {
        if (ENVIRONMENT === 'development') {
            log_message('debug', 'Enrollments::approve reached id=' . var_export($id, true));
        }
        $this->_set_request_status($id, 'approved');
    }

    public function reject($id = null)
    {
        if (ENVIRONMENT === 'development') {
            log_message('debug', 'Enrollments::reject reached id=' . var_export($id, true));
        }
        $this->_set_request_status($id, 'rejected');
    }

    /**
     * @param int|null $enrollment_id
     * @param string   $status        approved|rejected
     */
    private function _set_request_status($enrollment_id, $status)
    {
        if ($this->input->method(false) !== 'post') {
            $this->flash(
                'info',
                'Enrollment actions must be submitted from this page using the Approve or Reject buttons (POST only).'
            );
            redirect('enrollments/requests');

            return;
        }

        $eid = (int) $enrollment_id;
        if ($eid < 1) {
            $this->flash('error', 'Invalid request.');
            redirect('enrollments/requests');
        }

        $row = $this->course_model->get_enrollment_by_id($eid);
        if ( ! $row || (string) $row->status !== 'pending') {
            $this->flash('error', 'That enrollment request is no longer pending.');
            redirect('enrollments/requests');
        }

        $course = $this->course_model->get_course_any((int) $row->course_id);
        if ( ! $course) {
            $this->flash('error', 'Course not found.');
            redirect('enrollments/requests');
        }

        $user = $this->auth_user;
        if ($user->role === 'teacher' && (int) $course->created_by !== (int) $user->id) {
            $this->flash('error', 'You can only manage requests for your own courses.');
            redirect('enrollments/requests');
        }

        $this->course_model->set_enrollment_status($eid, $status);

        if ($status === 'approved') {
            $this->event_dispatcher->dispatch('enrollment.approved', [
                'user_id'   => (int) $row->user_id,
                'course_id' => (int) $row->course_id,
            ]);
        } else {
            $this->event_dispatcher->dispatch('enrollment.rejected', [
                'user_id'   => (int) $row->user_id,
                'course_id' => (int) $row->course_id,
            ]);
        }

        $this->flash(
            'success',
            $status === 'approved' ? 'Enrollment approved.' : 'Enrollment request rejected.'
        );
        redirect('enrollments/requests');
    }
}

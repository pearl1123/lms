<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Listener adapter from domain events to Notification_service methods.
 */
class Notification_listener {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('notification_service');
    }

    public function onCourseCompleted(array $event)
    {
        $svc = $this->CI->{'notification_service'};
        $svc->course_completed(
            (int) ($event['user_id'] ?? 0),
            (int) ($event['course_id'] ?? 0),
            (int) ($event['certificate_id'] ?? 0)
        );
    }

    public function onEnrollmentRequested(array $event)
    {
        $svc = $this->CI->{'notification_service'};
        $svc->enrollment_requested(
            (int) ($event['request_id'] ?? 0)
        );
    }

    public function onEnrollmentApproved(array $event)
    {
        $svc = $this->CI->{'notification_service'};
        $svc->enrollment_approved(
            (int) ($event['user_id'] ?? 0),
            (int) ($event['course_id'] ?? 0)
        );
    }

    public function onEnrollmentRejected(array $event)
    {
        $svc = $this->CI->{'notification_service'};
        $svc->enrollment_rejected(
            (int) ($event['user_id'] ?? 0),
            (int) ($event['course_id'] ?? 0)
        );
    }

    public function onCertificateIssued(array $event)
    {
        $svc = $this->CI->{'notification_service'};
        $svc->certificate_issued(
            (int) ($event['user_id'] ?? 0),
            (int) ($event['course_id'] ?? 0),
            (int) ($event['certificate_id'] ?? 0),
            (string) ($event['certificate_code'] ?? '')
        );
    }

    public function onCertificateRevoked(array $event)
    {
        $svc = $this->CI->{'notification_service'};
        $svc->certificate_revoked(
            (int) ($event['user_id'] ?? 0),
            (int) ($event['certificate_id'] ?? 0),
            (string) ($event['course_title'] ?? ''),
            (string) ($event['certificate_code'] ?? ''),
            (string) ($event['remarks'] ?? ''),
            (int) ($event['actor_id'] ?? 0)
        );
    }
}


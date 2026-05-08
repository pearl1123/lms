<?php
defined('BASEPATH') OR exit('No direct script access allowed');

return [
    'course.completed' => [
        'Notification_listener@onCourseCompleted',
    ],
    'enrollment.requested' => [
        'Notification_listener@onEnrollmentRequested',
    ],
    'enrollment.approved' => [
        'Notification_listener@onEnrollmentApproved',
    ],
    'enrollment.rejected' => [
        'Notification_listener@onEnrollmentRejected',
    ],
    // Existing behavior preserved (certificate check/revoke paths).
    'certificate.issued' => [
        'Notification_listener@onCertificateIssued',
    ],
    'certificate.revoked' => [
        'Notification_listener@onCertificateRevoked',
    ],
];


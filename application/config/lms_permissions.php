<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * LMS permission manifest — Module → Submodule → permission names (aauth_perms.name).
 * Permission_seed_service inserts missing rows only (never overwrites existing).
 */
return [
    'groups' => [
        'Super Administrator' => [
            'definition' => 'Full LMS access; all permissions.',
            'permissions' => ['*'],
        ],
        'LMS Administrator' => [
            'definition' => 'Course, assessment, certificate, report, and library administration.',
            'permissions' => [
                'dashboard.view',
                'courses.view', 'manage_courses.view', 'manage_courses.add', 'manage_courses.edit',
                'manage_courses.delete', 'manage_courses.publish',
                'my_courses.view', 'assessments.view', 'assessments.add', 'assessments.edit',
                'assessments.delete', 'assessments.grade',
                'certificates.view', 'certificates.issue', 'certificates.edit',
                'reports.view', 'reports.export',
                'libraries.view', 'libraries.add', 'libraries.edit', 'libraries.delete',
                'enrollments.view', 'enrollments.approve',
                'announcements.view', 'announcements.manage',
                'users.view', 'groups.view', 'groups.manage',
                'settings.view', 'settings.edit',
            ],
        ],
        'ETD Manager' => [
            'definition' => 'ETD oversight: courses, learners, reports, certificates.',
            'permissions' => [
                'dashboard.view', 'courses.view', 'manage_courses.view', 'manage_courses.edit',
                'my_courses.view', 'assessments.view', 'assessments.grade',
                'certificates.view', 'certificates.issue',
                'reports.view', 'reports.export',
                'enrollments.view', 'enrollments.approve',
                'announcements.view',
            ],
        ],
        'ETD Staff' => [
            'definition' => 'ETD support: view courses and reports, manage enrollments.',
            'permissions' => [
                'dashboard.view', 'courses.view', 'manage_courses.view',
                'reports.view', 'enrollments.view', 'enrollments.approve',
                'announcements.view',
            ],
        ],
        'Instructor' => [
            'definition' => 'Teach assigned courses; manage content and assessments.',
            'permissions' => [
                'dashboard.view', 'courses.view', 'manage_courses.view', 'manage_courses.edit',
                'my_courses.view', 'assessments.view', 'assessments.add', 'assessments.edit',
                'assessments.grade', 'certificates.view',
                'announcements.view',
            ],
        ],
        'Department Head' => [
            'definition' => 'Department reporting and learner progress.',
            'permissions' => [
                'dashboard.view', 'courses.view', 'my_courses.view',
                'reports.view', 'progress.view',
            ],
        ],
        'Employee' => [
            'definition' => 'Standard learner access.',
            'permissions' => [
                'dashboard.view', 'courses.view', 'my_courses.view',
                'assessments.view', 'assessments.take',
                'certificates.view', 'progress.view',
                'profile.view', 'profile.edit',
                'learning_notes.view',
            ],
        ],
    ],

    'modules' => [
        'Dashboard' => [
            'Dashboard' => [
                'view' => 'dashboard.view',
            ],
        ],
        'Learning Management' => [
            'Course Catalog' => [
                'view' => 'courses.view',
            ],
            'Manage Courses' => [
                'view'   => 'manage_courses.view',
                'add'    => 'manage_courses.add',
                'edit'   => 'manage_courses.edit',
                'delete' => 'manage_courses.delete',
                'extra_permissions' => [
                    ['name' => 'manage_courses.publish', 'label' => 'Publish'],
                ],
            ],
            'My Learning' => [
                'view' => 'my_courses.view',
            ],
            'Enrollments' => [
                'view' => 'enrollments.view',
                'edit' => 'enrollments.approve',
            ],
        ],
        'Assessments' => [
            'Assessments' => [
                'view'   => 'assessments.view',
                'add'    => 'assessments.add',
                'edit'   => 'assessments.edit',
                'delete' => 'assessments.delete',
                'extra_permissions' => [
                    ['name' => 'assessments.take', 'label' => 'Take'],
                    ['name' => 'assessments.grade', 'label' => 'Grade'],
                ],
            ],
        ],
        'Certificates' => [
            'Certificates' => [
                'view'   => 'certificates.view',
                'add'    => 'certificates.issue',
                'edit'   => 'certificates.edit',
                'delete' => 'certificates.delete',
            ],
        ],
        'Reports' => [
            'Reports & Analytics' => [
                'view' => 'reports.view',
                'extra_permissions' => [
                    ['name' => 'reports.export', 'label' => 'Export'],
                ],
            ],
        ],
        'Libraries' => [
            'System Libraries' => [
                'view'   => 'libraries.view',
                'add'    => 'libraries.add',
                'edit'   => 'libraries.edit',
                'delete' => 'libraries.delete',
            ],
        ],
        'User Management' => [
            'Users' => [
                'view' => 'users.view',
                'edit' => 'users.manage',
                'extra_permissions' => [
                    ['name' => 'users.permissions', 'label' => 'Permission Overrides'],
                ],
            ],
            'Groups' => [
                'view' => 'groups.view',
                'edit' => 'groups.manage',
            ],
        ],
        'Settings' => [
            'Administration' => [
                'view' => 'settings.view',
                'edit' => 'settings.edit',
            ],
            'Announcements' => [
                'view' => 'announcements.view',
                'edit' => 'announcements.manage',
            ],
            'My Profile' => [
                'view' => 'profile.view',
                'edit' => 'profile.edit',
            ],
            'Learning Notes' => [
                'view' => 'learning_notes.view',
            ],
            'Progress' => [
                'view' => 'progress.view',
            ],
        ],
    ],

    /** Sidebar / route → minimum permission (empty = role-only legacy). */
    'nav' => [
        'dashboard'       => 'dashboard.view',
        'courses'         => 'courses.view',
        'my_courses'      => 'my_courses.view',
        'manage_courses'  => 'manage_courses.view',
        'categories'      => 'libraries.view',
        'assessments'     => 'assessments.view',
        'certificates'    => 'certificates.view',
        'reports'         => 'reports.view',
        'libraries'       => 'libraries.view',
        'users'           => 'users.view',
        'permissions'     => 'groups.view',
        'settings'        => 'settings.view',
        'enrollments'     => 'enrollments.view',
        'announcements'   => 'announcements.view',
        'notifications'   => 'announcements.view',
        'progress'        => 'progress.view',
        'my_progress'     => 'progress.view',
        'learning_notes'  => 'learning_notes.view',
        'my_notes'        => 'learning_notes.view',
        'my_profile'      => 'profile.view',
    ],
];

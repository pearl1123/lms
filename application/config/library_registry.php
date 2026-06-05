<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Database-driven admin library registry (source: application/sql/db_lms.sql).
 *
 * Keys map to URI segment: index.php/libraries/{key}
 * Controllers: application/controllers/libraries/{Class}.php
 */
$config['library_registry'] = [
    'groups' => [
        'course' => [
            'label' => 'Course Libraries',
            'order' => 10,
        ],
        'assessment' => [
            'label' => 'Assessment Libraries',
            'order' => 20,
        ],
        'certificate' => [
            'label' => 'Certificate Libraries',
            'order' => 30,
        ],
        'notification' => [
            'label' => 'Notification Libraries',
            'order' => 40,
        ],
        'system' => [
            'label' => 'System Libraries',
            'order' => 50,
        ],
    ],

    'modules' => [

        'course_categories' => [
            'table'       => 'course_categories',
            'primary_key' => 'id',
            'title'       => 'Course Categories',
            'subtitle'    => 'Lookup categories for course catalog and filtering.',
            'group'       => 'course',
            'controller'  => 'libraries/course_categories',
            'soft_delete' => 'archived',
            'order_by'    => 'name ASC',
            'searchable'  => ['name', 'description'],
            'list_columns' => [
                ['field' => 'id', 'label' => 'ID', 'width' => '70px'],
                ['field' => 'name', 'label' => 'Name'],
                ['field' => 'color_hex', 'label' => 'Color', 'type' => 'color'],
                ['field' => 'archived', 'label' => 'Status', 'type' => 'status'],
            ],
            'form_fields' => [
                ['field' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 150],
                ['field' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['field' => 'color_hex', 'label' => 'Color (hex)', 'type' => 'text', 'maxlength' => 7, 'placeholder' => '#2563eb'],
            ],
            'insert_defaults' => ['created_at' => 'NOW'],
        ],

        'lib_course_access_type' => [
            'table'       => 'lib_course_access_type',
            'primary_key' => 'access_type_id',
            'title'       => 'Course Access Types',
            'subtitle'    => 'Access type lookup referenced by courses.access_type_id.',
            'group'       => 'course',
            'controller'  => 'libraries/lib_course_access_type',
            'soft_delete' => 'archived',
            'order_by'    => 'access_type_desc ASC',
            'searchable'  => ['access_type_desc'],
            'list_columns' => [
                ['field' => 'access_type_id', 'label' => 'ID', 'width' => '70px'],
                ['field' => 'access_type_desc', 'label' => 'Description'],
                ['field' => 'archived', 'label' => 'Status', 'type' => 'status'],
            ],
            'form_fields' => [
                ['field' => 'access_type_desc', 'label' => 'Description', 'type' => 'text', 'required' => true, 'maxlength' => 100],
            ],
        ],

        'lib_course_modality' => [
            'table'       => 'lib_course_modality',
            'primary_key' => 'modality_id',
            'title'       => 'Course Modalities',
            'subtitle'    => 'Modality lookup (online, blended, etc.) for courses.modality_id.',
            'group'       => 'course',
            'controller'  => 'libraries/lib_course_modality',
            'soft_delete' => 'archived',
            'order_by'    => 'modality_desc ASC',
            'searchable'  => ['modality_desc'],
            'list_columns' => [
                ['field' => 'modality_id', 'label' => 'ID', 'width' => '70px'],
                ['field' => 'modality_desc', 'label' => 'Description'],
                ['field' => 'archived', 'label' => 'Status', 'type' => 'status'],
            ],
            'form_fields' => [
                ['field' => 'modality_desc', 'label' => 'Description', 'type' => 'text', 'required' => true, 'maxlength' => 100],
            ],
        ],

        'lib_assessment_questions' => [
            'table'       => 'lib_assessment_questions',
            'primary_key' => 'id',
            'title'       => 'Assessment Questions',
            'subtitle'    => 'Question bank rows linked to lib_assessments. Changes affect live exams.',
            'group'       => 'assessment',
            'controller'  => 'libraries/lib_assessment_questions',
            'soft_delete' => 'archived',
            'order_by'    => 'id DESC',
            'searchable'  => ['question_text'],
            'list_columns' => [
                ['field' => 'id', 'label' => 'ID', 'width' => '70px'],
                ['field' => 'assessment_id', 'label' => 'Assessment', 'type' => 'fk', 'fk' => 'assessment_id'],
                ['field' => 'question_text', 'label' => 'Question', 'truncate' => 80],
                ['field' => 'question_type', 'label' => 'Type', 'type' => 'badge'],
                ['field' => 'archived', 'label' => 'Status', 'type' => 'status'],
            ],
            'form_fields' => [
                [
                    'field'    => 'assessment_id',
                    'label'    => 'Assessment',
                    'type'     => 'select',
                    'required' => true,
                    'fk'       => [
                        'table'  => 'lib_assessments',
                        'key'    => 'id',
                        'label'  => 'title',
                        'where'  => ['archived' => 0],
                        'order'  => 'title ASC',
                    ],
                ],
                ['field' => 'question_text', 'label' => 'Question text', 'type' => 'textarea', 'required' => true],
                [
                    'field'    => 'question_type',
                    'label'    => 'Question type',
                    'type'     => 'enum',
                    'required' => true,
                    'options'  => ['multiple_choice', 'essay', 'likert', 'fill_blank'],
                ],
                ['field' => 'is_required', 'label' => 'Required', 'type' => 'checkbox'],
                ['field' => 'min_words', 'label' => 'Min words (essay)', 'type' => 'number'],
                [
                    'field'   => 'essay_response_mode',
                    'label'   => 'Essay response mode',
                    'type'    => 'enum',
                    'options' => ['text', 'pdf', 'text_or_pdf'],
                ],
            ],
        ],

        'assessment_choices' => [
            'custom'      => true,
            'title'       => 'Assessment Choices',
            'subtitle'    => 'MCQ options (lib_assessment_choices).',
            'group'       => 'assessment',
            'controller'  => 'libraries/assessment_choices',
        ],

        'lib_certificate_types' => [
            'table'       => 'lib_certificate_types',
            'primary_key' => 'certificate_type_id',
            'title'       => 'Certificate Types',
            'subtitle'    => 'Certificate type lookup.',
            'group'       => 'certificate',
            'controller'  => 'libraries/lib_certificate_types',
            'soft_delete' => 'archived',
            'order_by'    => 'certificate_type_name ASC',
            'searchable'  => ['certificate_type_name', 'description'],
            'list_columns' => [
                ['field' => 'certificate_type_id', 'label' => 'ID', 'width' => '70px'],
                ['field' => 'certificate_type_name', 'label' => 'Name'],
                ['field' => 'archived', 'label' => 'Status', 'type' => 'status'],
            ],
            'form_fields' => [
                ['field' => 'certificate_type_name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 100],
                ['field' => 'description', 'label' => 'Description', 'type' => 'textarea'],
            ],
        ],

        'lib_certificate_templates' => [
            'table'       => 'lib_certificate_templates',
            'primary_key' => 'template_id',
            'title'       => 'Certificate Templates',
            'subtitle'    => 'PDF/HTML certificate template registry.',
            'group'       => 'certificate',
            'controller'  => 'libraries/lib_certificate_templates',
            'soft_delete' => 'archived',
            'order_by'    => 'template_name ASC',
            'searchable'  => ['template_name', 'description', 'template_file'],
            'list_columns' => [
                ['field' => 'template_id', 'label' => 'ID', 'width' => '70px'],
                ['field' => 'template_name', 'label' => 'Name'],
                ['field' => 'template_file', 'label' => 'File', 'truncate' => 40],
                ['field' => 'archived', 'label' => 'Status', 'type' => 'status'],
            ],
            'form_fields' => [
                ['field' => 'template_name', 'label' => 'Template name', 'type' => 'text', 'required' => true, 'maxlength' => 150],
                ['field' => 'template_file', 'label' => 'Template file path', 'type' => 'text', 'maxlength' => 255],
                ['field' => 'description', 'label' => 'Description', 'type' => 'textarea'],
            ],
        ],

        'lib_notification_type' => [
            'table'       => 'lib_notification_type',
            'primary_key' => 'notification_type_id',
            'title'       => 'Notification Types',
            'subtitle'    => 'Type lookup for lib_notification.',
            'group'       => 'notification',
            'controller'  => 'libraries/lib_notification_type',
            'soft_delete' => 'archived',
            'order_by'    => 'notification_type_desc ASC',
            'searchable'  => ['notification_type_desc'],
            'list_columns' => [
                ['field' => 'notification_type_id', 'label' => 'ID', 'width' => '70px'],
                ['field' => 'notification_type_desc', 'label' => 'Description'],
                ['field' => 'archived', 'label' => 'Status', 'type' => 'status'],
            ],
            'form_fields' => [
                ['field' => 'notification_type_desc', 'label' => 'Description', 'type' => 'text', 'required' => true, 'maxlength' => 100],
            ],
        ],

        'lib_notification_channel' => [
            'table'       => 'lib_notification_channel',
            'primary_key' => 'channel_id',
            'title'       => 'Notification Channels',
            'subtitle'    => 'Delivery channels (email, in-app, etc.). Uses is_active instead of archived.',
            'group'       => 'notification',
            'controller'  => 'libraries/lib_notification_channel',
            'soft_delete' => 'is_active',
            'soft_delete_invert' => true,
            'order_by'    => 'channel_name ASC',
            'searchable'  => ['channel_name', 'channel_desc'],
            'list_columns' => [
                ['field' => 'channel_id', 'label' => 'ID', 'width' => '70px'],
                ['field' => 'channel_name', 'label' => 'Name'],
                ['field' => 'channel_desc', 'label' => 'Description', 'truncate' => 50],
                ['field' => 'is_active', 'label' => 'Status', 'type' => 'active_flag'],
            ],
            'form_fields' => [
                ['field' => 'channel_name', 'label' => 'Channel name', 'type' => 'text', 'required' => true, 'maxlength' => 50],
                ['field' => 'channel_desc', 'label' => 'Description', 'type' => 'text', 'maxlength' => 150],
                ['field' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => 1],
            ],
        ],

        'lms_settings' => [
            'table'       => 'lms_settings',
            'primary_key' => 'id',
            'title'       => 'LMS Settings',
            'subtitle'    => 'Key/value configuration (lms_settings). No archive — delete disabled.',
            'group'       => 'system',
            'controller'  => 'libraries/lms_settings',
            'soft_delete' => false,
            'order_by'    => 'setting_key ASC',
            'searchable'  => ['setting_key', 'setting_value'],
            'list_columns' => [
                ['field' => 'id', 'label' => 'ID', 'width' => '70px'],
                ['field' => 'setting_key', 'label' => 'Key'],
                ['field' => 'setting_value', 'label' => 'Value', 'truncate' => 60],
            ],
            'form_fields' => [
                ['field' => 'setting_key', 'label' => 'Setting key', 'type' => 'text', 'required' => true, 'maxlength' => 120],
                ['field' => 'setting_value', 'label' => 'Setting value', 'type' => 'textarea'],
            ],
        ],
    ],
];

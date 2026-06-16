<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'Auth';
$route['dashboard'] = 'Dashboard/index';
$route['progress'] = 'Progress/index';
$route['libraries'] = 'libraries_portal/index';
$route['libraries/assessment_choices/get_by_question/(:num)'] = 'libraries/assessment_choices/get_by_question/$1';
$route['my_progress'] = 'Progress/index';
$route['my_profile'] = 'Profile/index';
$route['my_notes'] = 'learning_notes/index';
$route['learning_notes'] = 'learning_notes/index';
$route['learning_notes/api_list'] = 'learning_notes/api_list';
$route['learning_notes/api_save'] = 'learning_notes/api_save';
$route['learning_notes/api_update/(:num)'] = 'learning_notes/api_update/$1';
$route['learning_notes/api_delete/(:num)'] = 'learning_notes/api_delete/$1';
$route['learning_notes/api_toggle_pin/(:num)'] = 'learning_notes/api_toggle_pin/$1';
$route['learning_notes/api_toggle_favorite/(:num)'] = 'learning_notes/api_toggle_favorite/$1';
$route['learning_notes/export/(:num)'] = 'learning_notes/export/$1';
$route['learning_notes/export'] = 'learning_notes/export';
$route['settings/profile'] = 'Profile/index';

// Auth routes
$route['auth/login'] = 'Auth/login';
$route['auth/login_process'] = 'Auth/login_process';
$route['auth/logout'] = 'Auth/logout';
$route['auth/forgot-password'] = 'Auth/forgot_password';
$route['auth/forgot_password'] = 'Auth/forgot_password';
$route['auth/forgot_password_process'] = 'Auth/forgot_password_process';
$route['auth/reset-password/(:any)'] = 'Auth/reset_password/$1';
$route['auth/reset_password/(:any)'] = 'Auth/reset_password/$1';
$route['auth/reset_password_process'] = 'Auth/reset_password_process';
$route['auth/register'] = 'Auth/register';
$route['auth/register_process'] = 'Auth/register_process';
$route['auth/check_employee'] = 'Auth/check_employee';

// User management (Aauth access control)
$route['users'] = 'Users/index';
$route['users/access_data/(:num)'] = 'Users/access_data/$1';
$route['users/group_add'] = 'Users/group_add';
$route['users/group_remove'] = 'Users/group_remove';
$route['users/permissions_save'] = 'Users/permissions_save';

// Permission management (group matrix)
$route['permissions'] = 'Permissions/index';
$route['permissions/groups'] = 'Permissions/groups';
$route['permissions/group_save'] = 'Permissions/group_save';
$route['permissions/sync'] = 'Permissions/sync';

// Course routes
$route['courses'] = 'Courses/index';
$route['course/(:num)'] = 'Courses/view/$1';
$route['courses/enroll/(:num)'] = 'Courses/enroll/$1';
$route['courses/accept_invitation/(:num)'] = 'courses/accept_invitation/$1';
$route['courses/reject_invitation/(:num)'] = 'courses/reject_invitation/$1';
$route['courses/complete_module/(:num)'] = 'courses/complete_module/$1';
$route['courses/save_resume_state/(:num)'] = 'courses/save_resume_state/$1';
$route['my_courses'] = 'My_courses/index';
$route['my-learning'] = 'My_courses/index';
$route['leaderboard'] = 'leaderboard/index';
$route['manage_courses/modules/(:num)'] = 'manage_courses/modules/$1';
$route['manage_courses/publish/(:num)'] = 'manage_courses/publish/$1';
$route['manage_courses/unpublish/(:num)'] = 'manage_courses/unpublish/$1';
$route['manage_courses/upload_module_file'] = 'manage_courses/upload_module_file';
// Legacy / mistaken URL from older My Learning cards → same as courses/view
$route['my_courses/view/(:num)'] = 'courses/view/$1';

// Video checkpoints — Assessments controller (canonical URLs: assessments/video_checkpoints, …/video_checkpoint_submit)
$route['courses/video_checkpoints/(:num)']  = 'assessments/video_checkpoints/$1';
$route['courses/video_checkpoint_submit']  = 'assessments/video_checkpoint_submit';
// Legacy course player URLs (backward compatible)
$route['courses/youtube_quizzes/(:num)']   = 'assessments/video_checkpoints/$1';
$route['courses/youtube_quiz_submit']      = 'assessments/video_checkpoint_submit';
$route['assessments/integrity_analytics'] = 'assessments/integrity_analytics';
$route['assessments/migrate_youtube_checkpoints'] = 'assessments/migrate_video_checkpoints';
$route['assessments/save_checkpoint_meta']         = 'assessments/save_checkpoint_meta';
$route['assessments/ajax_auto_generate_checkpoints'] = 'assessments/ajax_auto_generate_checkpoints';

// Dashboard role shortcuts
$route['admin'] = 'Dashboard/admin';
$route['instructor'] = 'Dashboard/instructor';

//Administrator routes
$route['users'] = 'Users/index';
$route['reports'] = 'Reports/index';
$route['reports/export/csv']   = 'reports/export_csv';
$route['reports/export/excel'] = 'reports/export_excel';
$route['reports/export/pdf']   = 'reports/export_pdf';

// Enrollment approvals (explicit routes for approve/reject POST targets)
$route['enrollments/requests']         = 'Enrollments/requests';
$route['enrollments/approve/(:num)'] = 'Enrollments/approve/$1';
$route['enrollments/reject/(:num)']  = 'Enrollments/reject/$1';

// Notification center API
$route['notifications'] = 'Announcements/index';
$route['notifications/unread_count'] = 'Notifications/unread_count';
$route['notifications/latest']       = 'Notifications/latest';
$route['notifications/mark_read/(:num)'] = 'Notifications/mark_read/$1';

// REST API v1 (session-authenticated JSON)
$route['api/v1/courses']           = 'api_v1/courses/index';
$route['api/v1/courses/(:num)']    = 'api_v1/courses/show/$1';

$route['404_override'] = 'error_pages/not_found';
$route['coming_soon'] = 'error_pages/under_construction';
$route['under_construction'] = 'error_pages/under_construction';
$route['translate_uri_dashes'] = FALSE;
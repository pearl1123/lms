<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Assessments Controller
 *
 * Routes:
 *   GET  index.php/assessments              → list (role-based)
 *   GET  index.php/assessments/take/{id}    → employee takes assessment
 *   POST index.php/assessments/submit/{id}  → employee submits answers
 *   GET  index.php/assessments/result/{id}  → employee sees own result
 *   GET  index.php/assessments/review/{id}  → admin/teacher sees all submissions
 *   GET  index.php/assessments/grade/{assessment_id}/{user_id} → grade one student
 *   POST index.php/assessments/save_grade   → save scores (AJAX)
 *   GET  index.php/assessments/create       → admin/teacher: new assessment form
 *   POST index.php/assessments/create       → save new assessment
 *   GET  index.php/assessments/edit/{id}    → edit assessment + questions
 *   POST index.php/assessments/save_question→ add/update question (AJAX)
 *   POST index.php/assessments/delete_question → soft-delete question (AJAX)
 *   GET  index.php/assessments/delete/{id}  → soft-delete assessment
 *   GET  index.php/assessments/video_checkpoints/{module_id} → JSON (course player)
 *   POST index.php/assessments/video_checkpoint_submit      → JSON (checkpoint answer)
 *
 * @property CI_DB_mysqli_driver  $db
 * @property CI_Session           $session
 * @property CI_Input             $input
 * @property CI_Form_validation   $form_validation
 * @property User_model           $user_model
 * @property Assessment_model         $assessment_model
 * @property Course_model             $course_model
 * @property Module_video_checkpoint_model $video_checkpoint_model  Video checkpoints (lib_assessments type=checkpoint)
 * @property Assessment_service       $assessment_service
 * @property CI_Output                $output
 */
class Assessments extends CI_Controller {

    private $user;

    private function _is_ajax()
    {
        return strtolower((string) $this->input->server('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
    }

    private function _json_error($status, $message)
    {
        $payload = json_encode([
            'success' => false,
            'message' => (string) $message,
        ]);

        $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json')
            ->set_output($payload !== false ? $payload : '{"success":false,"message":"Request failed."}');

        $this->output->_display();
        exit;
    }

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->model('User_model',       'user_model');
        $this->load->model('Assessment_model',          'assessment_model');
        $this->load->model('Course_model',              'course_model');
        $this->load->model('Module_video_checkpoint_model', 'video_checkpoint_model');
        $this->load->library('assessment_service');
        $this->load->helper('url');

        // Auth guard
        $user_id = $this->session->userdata('user_id');
        if ( ! $user_id) {
            if ($this->_is_ajax()) {
                $this->_json_error(401, 'Authentication required.');
            }
            redirect('auth/login');
        }

        $user = $this->user_model->get_user($user_id);
        if ( ! $user) {
            if ($this->_is_ajax()) {
                $this->_json_error(401, 'Authentication required.');
            }
            $this->session->sess_destroy(); redirect('auth/login');
        }
        if ((int)$user->banned   === 1) {
            if ($this->_is_ajax()) {
                $this->_json_error(403, 'Account is banned.');
            }
            $this->session->sess_destroy(); redirect('auth/login');
        }
        if ($user->status       !== 'active') {
            if ($this->_is_ajax()) {
                $this->_json_error(403, 'Account is not active.');
            }
            $this->session->sess_destroy(); redirect('auth/login');
        }
        if ((int)$user->DELETED  === 1) {
            if ($this->_is_ajax()) {
                $this->_json_error(403, 'Account is unavailable.');
            }
            $this->session->sess_destroy(); redirect('auth/login');
        }
        if ( ! empty($user->locked_until) && strtotime($user->locked_until) > time()) {
            if ($this->_is_ajax()) {
                $this->_json_error(423, 'Account is temporarily locked.');
            }
            $this->session->sess_destroy(); redirect('auth/login');
        }

        $this->user = $user;

        $this->session->set_userdata('user', [
            'id'          => $user->id,
            'fullname'    => $user->fullname,
            'employee_id' => $user->employee_id,
            'role'        => $user->role,
            'status'      => $user->status,
        ]);
    }

    // =========================================================
    // index() — Assessment list (role-based)
    // =========================================================
    public function index()
    {
        $user = $this->user;

        if ($user->role === 'admin') {
            $assessments = $this->assessment_model->get_assessments(0, '', true);
        } elseif ($user->role === 'teacher') {
            $assessments = $this->assessment_model
                ->get_assessments_by_instructor($user->id, true);
        } else {
            // Employee — show assessments for enrolled courses only
            $enrolled_ids = $this->course_model->get_enrolled_ids($user->id);
            $assessments  = [];

            if ( ! empty($enrolled_ids)) {
                // Get all modules for enrolled courses
                $modules_result = $this->db
                    ->select('id')
                    ->where_in('course_id', $enrolled_ids)
                    ->where('archived', 0)
                    ->get('course_modules');

                $module_ids = [];
                if ($modules_result && $modules_result->num_rows() > 0) {
                    $module_ids = array_column(
                        $modules_result->result_array(), 'id'
                    );
                }

                foreach ($module_ids as $mid) {
                    $list = $this->assessment_model->get_assessments($mid);
                    foreach ($list as $a) {
                        if (($a->type ?? '') === 'checkpoint') {
                            continue;
                        }
                        $a->already_answered = $this->assessment_model
                            ->has_answered($user->id, $a->id);
                        $a->result = $a->already_answered
                            ? $this->assessment_model->get_result($user->id, $a->id)
                            : null;
                        $assessments[] = $a;
                    }
                }
            }
        }

        $is_manager = in_array($user->role, ['admin', 'teacher'], true);

        $assessment_stats = [
            'total'             => count($assessments),
            'pre_count'         => 0,
            'post_count'        => 0,
            'checkpoint_count'  => 0,
        ];
        foreach ($assessments as $a) {
            $t = $a->type ?? '';
            if ($t === 'pre') {
                $assessment_stats['pre_count']++;
            } elseif ($t === 'post') {
                $assessment_stats['post_count']++;
            } elseif ($t === 'checkpoint') {
                $assessment_stats['checkpoint_count']++;
            }
        }

        foreach ($assessments as $a) {
            $a->created_at_display = ! empty($a->created_at)
                ? date('M j, Y', strtotime($a->created_at))
                : '';

            if ( ! $is_manager && ! empty($a->already_answered) && ! empty($a->result)) {
                $chip = ka_assessment_score_chip(
                    (float) ($a->result['score'] ?? 0),
                    (int) ($a->result['pending'] ?? 0)
                );
                $a->score_chip_class = $chip['class'];
                $a->score_chip_text  = $chip['text'];
            }
        }

        $data = [
            'user'               => $user,
            'page_title'         => 'Assessments',
            'assessments'        => $assessments,
            'is_manager'         => $is_manager,
            'assessment_stats'   => $assessment_stats,
            'breadcrumbs'        => [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => 'Assessments'],
            ],
            'view'               => 'assessments/index',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // take($id) — Employee takes an assessment
    // =========================================================
    public function take($id = null)
    {
        if ( ! $id) redirect('assessments');
        $user = $this->user;
        $id   = (int) $id;

        if ($user->role !== 'employee') redirect('assessments');

        $assessment = $this->assessment_model->get_assessment($id);
        if ( ! $assessment) show_404();
        $this->_reject_checkpoint_assessment($assessment);

        // Check employee has approved enrollment in the course
        if ( ! $this->course_model->has_approved_enrollment($user->id, (int) $assessment->course_id)) {
            $this->session->set_flashdata(
                'error', 'You must be approved for this course before taking assessments.'
            );
            redirect('courses/view/' . $assessment->course_id);
        }

        // Already answered — redirect to results
        if ($this->assessment_model->has_answered($user->id, $id)) {
            redirect('assessments/result/' . $id);
        }

        // Strict post gate: unlock only after the module video/checkpoints are completed.
        if (($assessment->type ?? '') === 'post'
            && (int) ($assessment->module_id ?? 0) > 0
            && ! $this->assessment_service->can_start_post_assessment(
                (int) $user->id,
                (int) $assessment->module_id
            )) {
            $this->session->set_flashdata(
                'info',
                'Complete the video and checkpoints first to unlock the post-assessment'
            );
            redirect('courses/module/' . (int) $assessment->module_id);
        }

        $questions = $this->assessment_model->get_questions($id);
        if (empty($questions)) {
            log_message('debug', 'ASSESSMENT ID: ' . $id);
            log_message('debug', 'QUESTIONS COUNT: 0');

            $fallback = null;
            if (in_array((string) ($assessment->type ?? ''), ['pre', 'post'], true)
                && (int) ($assessment->module_id ?? 0) > 0) {
                $fallback = $this->assessment_model->get_assessment_with_questions_for_module(
                    (int) $assessment->module_id,
                    (string) $assessment->type
                );
            }

            if ($fallback && (int) $fallback->id !== $id) {
                log_message('debug', 'ASSESSMENT EMPTY FALLBACK: ' . json_encode([
                    'from_assessment_id' => $id,
                    'to_assessment_id'   => (int) $fallback->id,
                    'module_id'          => (int) $assessment->module_id,
                    'type'               => (string) $assessment->type,
                ]));
                redirect('assessments/take/' . (int) $fallback->id);
            }

            $this->session->set_flashdata(
                'info', 'This assessment has no questions yet.'
            );
            redirect('assessments');
        }

        $data = [
            'user'        => $user,
            'page_title'  => 'Take Assessment — ' . $assessment->title,
            'assessment'  => $assessment,
            'questions'   => $questions,
            'breadcrumbs' => [
                ['label' => 'Dashboard',   'url'  => 'dashboard'],
                ['label' => 'Assessments', 'url'  => 'assessments'],
                ['label' => $assessment->title],
            ],
            'view' => 'assessments/take',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // submit($id) — POST: save employee's answers
    // =========================================================
    public function submit($id = null)
    {
        if ( ! $id || $this->user->role !== 'employee') redirect('assessments');
        $id = (int) $id;

        $assessment = $this->assessment_model->get_assessment($id);
        if ( ! $assessment) show_404();
        $this->_reject_checkpoint_assessment($assessment);

        if ($this->assessment_model->has_answered($this->user->id, $id)) {
            $this->session->set_flashdata('info', 'You have already submitted this assessment.');
            redirect('assessments/result/' . $id);
        }

        // Collect answers from POST [answer_{question_id}]
        $raw     = $this->input->post();
        $answers = [];
        foreach ($raw as $key => $val) {
            if (strpos($key, 'answer_') === 0) {
                $q_id           = (int) substr($key, 7);
                $answers[$q_id] = $val;
            }
        }

        $result = $this->assessment_model
            ->submit_answers($this->user->id, $id, $answers);

        $msg = 'Assessment submitted successfully! '
             . $result['auto_scored'] . ' question(s) auto-scored.';

        if ($result['pending_review'] > 0) {
            $msg .= ' ' . $result['pending_review']
                  . ' question(s) awaiting instructor review.';
        }

        // Pre-assessment on a video module: return to module with modal feedback (full result page still available).
        if ($this->_pre_assessment_use_module_modal_redirect($assessment)) {
            $agg    = $this->assessment_model->get_result($this->user->id, $id);
            $passed = $this->assessment_service->is_passing_assessment_result($agg);
            $this->session->set_flashdata('pre_assessment_modal', [
                'module_id'      => (int) $assessment->module_id,
                'assessment_id' => $id,
                'title'         => (string) ($assessment->title ?? 'Pre-assessment'),
                'score'         => round((float) ($agg['score'] ?? 0), 1),
                'passed'        => $passed,
                'pending_count' => (int) ($agg['pending'] ?? 0),
                'threshold'     => $this->assessment_service->pass_threshold(),
                'success_note' => $msg,
            ]);
            redirect('courses/module/' . (int) $assessment->module_id);
        }

        $this->session->set_flashdata('success', $msg);
        redirect('assessments/result/' . $id);
    }

    /**
     * Pre-assessment only: video-module players get instant modal UX; exams & posts keep result URLs.
     */
    private function _pre_assessment_use_module_modal_redirect($assessment)
    {
        if (($assessment->type ?? '') !== 'pre') {
            return false;
        }

        $mid = (int) ($assessment->module_id ?? 0);
        if ($mid < 1) {
            return false;
        }

        $mod = $this->course_model->get_module($mid);

        return $mod && ($mod->content_type ?? '') === 'video';
    }

    // =========================================================
    // result($id) — Employee sees own result
    // =========================================================
    public function result($id = null)
    {
        if ( ! $id) redirect('assessments');
        $user = $this->user;
        $id   = (int) $id;

        if ($user->role !== 'employee') redirect('assessments');

        $assessment = $this->assessment_model->get_assessment($id);
        if ( ! $assessment) show_404();
        $this->_reject_checkpoint_assessment($assessment);

        if ( ! $this->assessment_model->has_answered($user->id, $id)) {
            redirect('assessments/take/' . $id);
        }

        $questions   = $this->assessment_model->get_questions($id);
        $user_answers= $this->assessment_model->get_user_answers($user->id, $id);
        $result      = $this->assessment_model->get_result($user->id, $id);

        $data = [
            'user'         => $user,
            'page_title'   => 'Assessment Result — ' . $assessment->title,
            'assessment'   => $assessment,
            'questions'    => $questions,
            'user_answers' => $user_answers,
            'result'       => $result,
            'breadcrumbs'  => [
                ['label' => 'Dashboard',   'url' => 'dashboard'],
                ['label' => 'Assessments', 'url' => 'assessments'],
                ['label' => 'Result: ' . $assessment->title],
            ],
            'view' => 'assessments/result',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // review($id) — Admin/Teacher: see all submissions
    // =========================================================
    public function review($id = null)
    {
        if ( ! $id) redirect('assessments');
        $this->_require_manager();

        $id         = (int) $id;
        $assessment = $this->assessment_model->get_assessment($id);
        if ( ! $assessment) show_404();

        $this->_check_ownership($assessment);

        $attempts  = $this->assessment_model->get_attempt_summary($id);
        $questions = $this->assessment_model->get_questions($id);

        $data = [
            'user'        => $this->user,
            'page_title'  => 'Review: ' . $assessment->title,
            'assessment'  => $assessment,
            'questions'   => $questions,
            'attempts'    => $attempts,
            'breadcrumbs' => [
                ['label' => 'Dashboard',   'url' => 'dashboard'],
                ['label' => 'Assessments', 'url' => 'assessments'],
                ['label' => 'Review: ' . $assessment->title],
            ],
            'view' => 'assessments/review',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // grade($assessment_id, $user_id) — Grade one student
    // =========================================================
    public function grade($assessment_id = null, $student_id = null)
    {
        if ( ! $assessment_id || ! $student_id) redirect('assessments');
        $this->_require_manager();

        $assessment_id = (int) $assessment_id;
        $student_id    = (int) $student_id;

        $assessment = $this->assessment_model->get_assessment($assessment_id);
        if ( ! $assessment) show_404();

        $this->_check_ownership($assessment);

        // Get student info
        $student = $this->user_model->get_user($student_id);
        if ( ! $student) show_404();

        $answers = $this->assessment_model
            ->get_student_answers($student_id, $assessment_id);

        $result = $this->assessment_model
            ->get_result($student_id, $assessment_id);

        $data = [
            'user'          => $this->user,
            'page_title'    => 'Grade: ' . $student->fullname,
            'assessment'    => $assessment,
            'student'       => $student,
            'answers'       => $answers,
            'result'        => $result,
            'breadcrumbs'   => [
                ['label' => 'Dashboard',   'url'  => 'dashboard'],
                ['label' => 'Assessments', 'url'  => 'assessments'],
                ['label' => 'Review',      'url'  => 'assessments/review/' . $assessment_id],
                ['label' => 'Grade: ' . $student->fullname],
            ],
            'view' => 'assessments/grade',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // save_grade() — AJAX POST: save a score for one answer
    // =========================================================
    public function save_grade()
    {
        $this->_require_manager();
        header('Content-Type: application/json');

        $answer_id = (int) $this->input->post('answer_id');
        $score     = (float) $this->input->post('score');

        if ($score < 0 || $score > 100) {
            echo json_encode(['success' => false, 'message' => 'Score must be between 0 and 100.']);
            return;
        }

        $ok = $this->assessment_model
            ->score_answer($answer_id, $score, $this->user->id);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Score saved.' : 'Failed to save score.',
        ]);
    }

    // =========================================================
    // create() — GET: form | POST: save new assessment
    // =========================================================
    public function create()
    {
        $this->_require_manager();
        $user = $this->user;

        if ($this->input->method() === 'post') {
            $this->form_validation
                ->set_rules('module_id', 'Module',          'required|integer')
                ->set_rules('type',      'Assessment Type', 'required|in_list[pre,post,checkpoint]')
                ->set_rules('title',     'Title',           'required|max_length[255]');

            $post_type = (string) $this->input->post('type');
            $checkpoint_auto = ($post_type === 'checkpoint' && $this->input->post('checkpoint_auto_generate'));
            if ($post_type === 'checkpoint') {
                if ($checkpoint_auto) {
                    $this->form_validation->set_rules(
                        'video_duration_seconds',
                        'Video duration (seconds)',
                        'required|integer|greater_than[0]'
                    );
                } else {
                    $this->form_validation->set_rules(
                        'trigger_seconds',
                        'Video timestamp (seconds)',
                        'integer|greater_than_equal_to[0]'
                    );
                }
            }

            if ($this->form_validation->run()) {
                if ($post_type === 'checkpoint'
                    && ! $this->assessment_model->assessments_checkpoint_schema_ready()) {
                    $this->session->set_flashdata(
                        'error',
                        'Video checkpoints require the unified assessment columns on the server. Run the checkpoint SQL migration first.'
                    );
                } elseif ($post_type === 'checkpoint' && $checkpoint_auto) {
                    $batch = $this->assessment_service->create_auto_distributed_video_checkpoints(
                        (int) $this->input->post('module_id'),
                        (int) $user->id,
                        [
                            'title'                    => $this->input->post('title'),
                            'is_required'              => $this->input->post('checkpoint_required'),
                            'video_duration_seconds'   => (int) $this->input->post('video_duration_seconds'),
                        ]
                    );
                    if (empty($batch['ok'])) {
                        $this->session->set_flashdata('error', $batch['message'] ?? 'Could not auto-generate checkpoints.');
                    } else {
                        $ids = $batch['created_ids'] ?? [];
                        $first = ! empty($ids) ? (int) $ids[0] : 0;
                        $this->session->set_flashdata('success', $batch['message'] ?? 'Checkpoints created.');
                        if ($first > 0) {
                            redirect('assessments/edit/' . $first);
                        }
                    }
                } else {
                    $payload = [
                        'module_id'  => $this->input->post('module_id'),
                        'type'       => $post_type,
                        'title'      => $this->input->post('title'),
                        'encoded_by' => $user->id,
                    ];
                    if ($post_type === 'checkpoint') {
                        $payload['trigger_seconds'] = $this->input->post('trigger_seconds');
                        $payload['trigger_percent'] = $this->input->post('trigger_percent');
                        $payload['is_required']    = $this->input->post('checkpoint_required');
                        $payload['sort_order']     = $this->input->post('sort_order');
                    }

                    $id = $this->assessment_model->create_assessment($payload);

                    if ( ! $id) {
                        $this->session->set_flashdata(
                            'error',
                            'Could not create this video checkpoint. The module may already have the maximum (3), or another checkpoint uses the same timestamp (seconds).'
                        );
                    } else {
                        $msg = $post_type === 'checkpoint'
                            ? 'Video checkpoint created. Add one multiple-choice question (shown during video playback).'
                            : 'Assessment created. Now add your questions.';
                        $this->session->set_flashdata('success', $msg);
                        redirect('assessments/edit/' . $id);
                    }
                }
            }
        }

        // Build module list for dropdown
        $modules = $this->_get_available_modules($user);

        $data = [
            'user'           => $user,
            'page_title'     => 'Create Assessment',
            'modules'        => $modules,
            'checkpoint_schema_ready' => $this->assessment_model->assessments_checkpoint_schema_ready(),
            'preselect_mod'  => (int) ($this->input->get('module_id') ?? 0),
            'preselect_type' => $this->input->get('type') ?? '',
            'breadcrumbs'    => [
                ['label' => 'Dashboard',   'url' => 'dashboard'],
                ['label' => 'Assessments', 'url' => 'assessments'],
                ['label' => 'Create'],
            ],
            'view' => 'assessments/create',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // edit($id) — GET/POST: edit assessment & manage questions
    // =========================================================
    public function edit($id = null)
    {
        if ( ! $id) redirect('assessments');
        $this->_require_manager();

        $id         = (int) $id;
        $assessment = $this->assessment_model->get_assessment($id);
        if ( ! $assessment) show_404();

        $this->_check_ownership($assessment);

        if ($this->input->method() === 'post') {
            $this->form_validation
                ->set_rules('module_id', 'Module', 'required|integer')
                ->set_rules('type',  'Type',  'required|in_list[pre,post,checkpoint]')
                ->set_rules('title', 'Title', 'required|max_length[255]');

            $post_type = (string) $this->input->post('type');
            if ($post_type === 'checkpoint') {
                $this->form_validation->set_rules(
                    'trigger_seconds',
                    'Video timestamp (seconds)',
                    'integer|greater_than_equal_to[0]'
                );
            }

            if ($this->form_validation->run()) {
                if ($post_type === 'checkpoint'
                    && ! $this->assessment_model->assessments_checkpoint_schema_ready()) {
                    $this->session->set_flashdata(
                        'error',
                        'Video checkpoints require the unified assessment columns on the server.'
                    );
                } else {
                    $upd = [
                        'type'        => $post_type,
                        'title'       => $this->input->post('title'),
                        'modified_by' => $this->user->id,
                        'module_id'   => $this->input->post('module_id'),
                    ];
                    if ($post_type === 'checkpoint') {
                        $upd['trigger_seconds'] = $this->input->post('trigger_seconds');
                        $upd['trigger_percent'] = $this->input->post('trigger_percent');
                        $upd['is_required']     = $this->input->post('checkpoint_required');
                        $upd['sort_order']      = $this->input->post('sort_order');
                    }

                    $updated = $this->assessment_model->update_assessment($id, $upd);
                    if ( ! $updated) {
                        $this->session->set_flashdata(
                            'error',
                            'Could not update: another video checkpoint on this module may already use that timestamp (seconds).'
                        );
                    } else {
                        $this->session->set_flashdata('success', 'Assessment updated.');
                    }
                    redirect('assessments/edit/' . $id);
                }
            }
        }

        $questions = $this->assessment_model->get_questions($id);
        $modules   = $this->_get_available_modules($this->user);

        $data = [
            'user'        => $this->user,
            'page_title'  => 'Edit Assessment',
            'assessment'  => $assessment,
            'questions'   => $questions,
            'modules'     => $modules,
            'checkpoint_schema_ready' => $this->assessment_model->assessments_checkpoint_schema_ready(),
            'breadcrumbs' => [
                ['label' => 'Dashboard',   'url' => 'dashboard'],
                ['label' => 'Assessments', 'url' => 'assessments'],
                ['label' => 'Edit: ' . $assessment->title],
            ],
            'view' => 'assessments/edit',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    // =========================================================
    // save_question() — AJAX POST: add or update a question
    // =========================================================
    public function save_question()
    {
        $this->_require_manager();
        header('Content-Type: application/json');

        $assessment_id = (int) $this->input->post('assessment_id');
        $assessment    = $this->assessment_model->get_assessment($assessment_id);
        if ( ! $assessment) {
            echo json_encode(['success' => false, 'message' => 'Assessment not found.']);

            return;
        }
        $this->_check_ownership($assessment);

        $question_id     = (int) $this->input->post('question_id'); // 0 = new
        $question_text   = trim($this->input->post('question_text'));
        $question_type   = $this->input->post('question_type');

        if (($assessment->type ?? '') === 'checkpoint') {
            if ($question_type !== 'multiple_choice') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Video checkpoints only support a multiple-choice question.',
                ]);

                return;
            }
            if ($question_id <= 0) {
                $existing = $this->assessment_model->get_questions($assessment_id);
                if (count($existing) >= 1) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'This video checkpoint already has a question. Edit or delete it before adding another.',
                    ]);

                    return;
                }
            }
        }

        $is_required   = (int) $this->input->post('is_required');
        $min_words     = (int) $this->input->post('min_words');
        $choices_raw   = $this->input->post('choices'); // array of {text, is_correct}

        if ($question_text === '') {
            echo json_encode(['success' => false, 'message' => 'Question text is required.']);
            return;
        }

        $q_data = [
            'assessment_id' => $assessment_id,
            'question_text' => $question_text,
            'question_type' => $question_type,
            'is_required'   => $is_required,
            'min_words'     => $min_words,
            'encoded_by'    => $this->user->id,
            'modified_by'   => $this->user->id,
        ];

        if ($question_id > 0) {
            $saved = $this->assessment_model->update_question($question_id, $q_data);
            $qid = $question_id;
        } else {
            $qid = $this->assessment_model->create_question($q_data);
            $saved = $qid > 0;
        }

        if ( ! $saved) {
            echo json_encode(['success' => false, 'message' => 'Failed to save question.']);

            return;
        }

        // Save choices if provided
        if (in_array($question_type, ['multiple_choice', 'fill_blank'])
            && is_array($choices_raw)) {
            $this->assessment_model->save_choices($qid, $choices_raw);
        }

        // Return the updated question with choices for re-rendering
        $q = $this->assessment_model->get_question($qid);

        echo json_encode([
            'success'     => true,
            'message'     => $question_id > 0 ? 'Question updated.' : 'Question added.',
            'question_id' => $qid,
            'question'    => $q,
        ]);
    }

    // =========================================================
    // delete_question() — AJAX POST: soft-delete a question
    // =========================================================
    public function delete_question()
    {
        $this->_require_manager();
        header('Content-Type: application/json');

        $question_id = (int) $this->input->post('question_id');
        $qrow         = $this->assessment_model->get_question($question_id);
        if ( ! $qrow) {
            echo json_encode(['success' => false, 'message' => 'Question not found.']);

            return;
        }
        $asmt = $this->assessment_model->get_assessment((int) $qrow->assessment_id);
        if ( ! $asmt) {
            echo json_encode(['success' => false, 'message' => 'Assessment not found.']);

            return;
        }
        $this->_check_ownership($asmt);

        $ok = $this->assessment_model->delete_question($question_id);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Question deleted.' : 'Failed to delete question.',
        ]);
    }

    // =========================================================
    // delete($id) — Soft-delete an assessment
    // =========================================================
    public function delete($id = null)
    {
        if ( ! $id) redirect('assessments');
        $this->_require_manager();

        $id         = (int) $id;
        $assessment = $this->assessment_model->get_assessment($id);
        if ( ! $assessment) show_404();

        $this->_check_ownership($assessment);

        $this->assessment_model->delete_assessment($id);
        $this->session->set_flashdata('success', 'Assessment deleted.');
        redirect('assessments');
    }

    /**
     * One-time data migration (admin): legacy course_module_video_checkpoints → lib_assessments.
     * GET index.php/assessments/migrate_video_checkpoints
     *
     * @deprecated Legacy URL still routed: migrate_youtube_checkpoints
     */
    public function migrate_video_checkpoints()
    {
        if ($this->user->role !== 'admin') {
            show_404();
        }

        $out = $this->assessment_model->migrate_legacy_video_checkpoints();

        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        $this->output->enable_profiler(false);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($out, $flags));
    }

    /**
     * GET JSON — video checkpoint definitions for a module (course player; assessment-backed).
     * URL: index.php/assessments/video_checkpoints/{module_id}
     */
    public function video_checkpoints($module_id = null)
    {
        $user = $this->user;
        $mid  = (int) $module_id;
        if ($mid < 1) {
            return $this->_checkpoint_json_response(['success' => false, 'checkpoints' => []], 400);
        }

        $module = $this->course_model->get_module($mid);
        if ( ! $module || ! $this->course_model->user_can_access_module_player($user, $module)) {
            return $this->_checkpoint_json_response(['success' => false, 'message' => 'Not found.', 'checkpoints' => []], 404);
        }

        if (Module_video_checkpoint_model::extract_youtube_video_id($module->content_path ?? '') === null) {
            return $this->_checkpoint_json_response(['success' => true, 'checkpoints' => []]);
        }

        $checkpoints = $this->video_checkpoint_model->get_public_checkpoints_payload($mid);

        return $this->_checkpoint_json_response(['success' => true, 'checkpoints' => $checkpoints]);
    }

    /**
     * POST JSON — submit a video checkpoint answer (FormData). Same as assessment MCQ storage.
     * URL: index.php/assessments/video_checkpoint_submit
     */
    public function video_checkpoint_submit()
    {
        try {
            if ($this->input->method(false) !== 'post') {
                return $this->_checkpoint_json_response(
                    ['success' => false, 'ok' => false, 'message' => 'Invalid request method.'],
                    405
                );
            }

            $user = $this->user;

            $assessment_id = $this->_checkpoint_assessment_id_from_post();
            $module_id = (int) $this->input->post('module_id');
            $rawChoice = $this->input->post('choice_index');

            if ($assessment_id < 1) {
                return $this->_checkpoint_json_response(['success' => false, 'ok' => false, 'message' => 'Invalid checkpoint.'], 400);
            }

            if ($module_id < 1) {
                return $this->_checkpoint_json_response(['success' => false, 'ok' => false, 'message' => 'Invalid module.'], 400);
            }

            $choiceVal = filter_var($rawChoice, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            if ($choiceVal === null) {
                return $this->_checkpoint_json_response(['success' => false, 'ok' => false, 'message' => 'Invalid answer selection.'], 400);
            }

            $choice = (int) $choiceVal;

            $checkpoint = $this->video_checkpoint_model->get_checkpoint_assessment($assessment_id);
            if ( ! $checkpoint) {
                return $this->_checkpoint_json_response(['success' => false, 'ok' => false, 'message' => 'Checkpoint not found.'], 404);
            }

            if ((int) $checkpoint->module_id !== $module_id) {
                return $this->_checkpoint_json_response(['success' => false, 'ok' => false, 'message' => 'Checkpoint does not belong to this module.'], 400);
            }

            $module = $this->course_model->get_module($module_id);
            if ( ! $module
                || (int) $module->id !== $module_id
                || ! $this->course_model->user_can_access_module_player($user, $module)
                || ! $this->video_checkpoint_model->is_youtube_module($module)
            ) {
                return $this->_checkpoint_json_response(['success' => false, 'ok' => false, 'message' => 'Access denied.'], 403);
            }

            $res = $this->video_checkpoint_model->submit_checkpoint_answer(
                (int) $user->id,
                $assessment_id,
                $choice,
                $module_id
            );

            return $this->_checkpoint_json_response([
                'success' => $res['ok'],
                'ok'      => $res['ok'],
                'message' => $res['message'],
            ], 200);
        } catch (Throwable $e) {
            log_message('error', 'video_checkpoint_submit: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

            return $this->_checkpoint_json_response([
                'success' => false,
                'ok'      => false,
                'message' => 'Server error. Please try again.',
            ], 500);
        }
    }

    /**
     * JSON for video checkpoint AJAX (course module player).
     *
     * @param array $data
     * @param int   $status
     */
    private function _checkpoint_json_response(array $data, $status = 200)
    {
        if ( ! array_key_exists('success', $data) && array_key_exists('ok', $data)) {
            $data['success'] = (bool) $data['ok'];
        }
        if ( ! array_key_exists('ok', $data) && array_key_exists('success', $data)) {
            $data['ok'] = (bool) $data['success'];
        }

        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        $payload = json_encode($data, $flags);
        if ($payload === false) {
            log_message('error', 'checkpoint_json_response: json_encode failed — ' . json_last_error_msg());
            $payload = '{"success":false,"ok":false,"message":"Server error."}';
        }

        $this->output->enable_profiler(false);

        $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json')
            ->set_output($payload);
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    /**
     * Video checkpoint `lib_assessments.id` from POST (`assessment_id` or `checkpoint_id`).
     *
     * @deprecated POST `quiz_id` is accepted temporarily; prefer `assessment_id`.
     * @return int
     */
    private function _checkpoint_assessment_id_from_post()
    {
        $id = (int) $this->input->post('assessment_id');
        if ($id > 0) {
            return $id;
        }

        $id = (int) $this->input->post('checkpoint_id');
        if ($id > 0) {
            return $id;
        }

        $deprecated = (int) $this->input->post('quiz_id');
        if ($deprecated > 0) {
            log_message('warning', 'Deprecated POST quiz_id in video_checkpoint_submit; use assessment_id or checkpoint_id.');

            return $deprecated;
        }

        return 0;
    }

    /** Video checkpoints are taken in the course module player only. */
    private function _reject_checkpoint_assessment($assessment)
    {
        if ($assessment && ($assessment->type ?? '') === 'checkpoint') {
            show_404();
        }
    }

    /** Redirect non-managers (admin/teacher) away. */
    private function _require_manager()
    {
        if ( ! in_array($this->user->role, ['admin', 'teacher', 'instructor'], true)) {
            if ($this->_is_ajax()) {
                $this->_json_error(403, 'You do not have permission to do that.');
            }
            $this->session->set_flashdata(
                'error', 'You do not have permission to do that.'
            );
            redirect('assessments');
        }
    }

    /**
     * For teachers: ensure they only manage their own course's assessments.
     * Admins can access everything.
     */
    private function _check_ownership($assessment)
    {
        if ($this->user->role === 'admin') return;

        if ((int) $assessment->course_owner !== (int) $this->user->id) {
            if ($this->_is_ajax()) {
                $this->_json_error(403, 'You can only manage assessments for your own courses.');
            }
            $this->session->set_flashdata(
                'error', 'You can only manage assessments for your own courses.'
            );
            redirect('assessments');
        }
    }

    /**
     * Build module dropdown list.
     * Admin gets all modules; teacher gets only their courses' modules.
     */
    private function _get_available_modules($user)
    {
        $this->db
            ->select('cm.id, cm.title AS module_title, c.title AS course_title, c.id AS course_id')
            ->from('course_modules cm')
            ->join('courses c', 'c.id = cm.course_id', 'left')
            ->where('cm.archived', 0)
            ->where('c.archived',  0)
            ->order_by('c.title', 'ASC')
            ->order_by('cm.module_order', 'ASC');

        if (in_array($user->role, ['teacher', 'instructor'], true)) {
            $this->db->where('c.created_by', $user->id);
        }

        $r = $this->db->get();
        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }
}
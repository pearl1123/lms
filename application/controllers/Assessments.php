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
 *
 * @property CI_DB_mysqli_driver  $db
 * @property CI_Session           $session
 * @property CI_Input             $input
 * @property CI_Form_validation   $form_validation
 * @property User_model           $user_model
 * @property Assessment_model     $assessment_model
 * @property Course_model         $course_model
 */
class Assessments extends CI_Controller {

    private $user;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->model('User_model',       'user_model');
        $this->load->model('Assessment_model', 'assessment_model');
        $this->load->model('Course_model',     'course_model');
        $this->load->helper('url');

        // Auth guard
        $user_id = $this->session->userdata('user_id');
        if ( ! $user_id) redirect('auth/login');

        $user = $this->user_model->get_user($user_id);
        if ( ! $user)                                           { $this->session->sess_destroy(); redirect('auth/login'); }
        if ((int)$user->banned   === 1)                        { $this->session->sess_destroy(); redirect('auth/login'); }
        if ($user->status       !== 'active')                  { $this->session->sess_destroy(); redirect('auth/login'); }
        if ((int)$user->DELETED  === 1)                        { $this->session->sess_destroy(); redirect('auth/login'); }
        if ( ! empty($user->locked_until) && strtotime($user->locked_until) > time()) {
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
            $assessments = $this->assessment_model->get_assessments();
        } elseif ($user->role === 'teacher') {
            $assessments = $this->assessment_model
                ->get_assessments_by_instructor($user->id);
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

        $data = [
            'user'        => $user,
            'page_title'  => 'Assessments',
            'assessments' => $assessments,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => 'Assessments'],
            ],
            'view' => 'assessments/index',
        ];

        $this->load->view('layouts/main', $data);
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

        // Check employee is enrolled in the course
        $enrollment = $this->course_model
            ->get_enrollment($user->id, $assessment->course_id);
        if ( ! $enrollment) {
            $this->session->set_flashdata(
                'error', 'You must be enrolled in this course to take the assessment.'
            );
            redirect('courses/view/' . $assessment->course_id);
        }

        // Already answered — redirect to results
        if ($this->assessment_model->has_answered($user->id, $id)) {
            redirect('assessments/result/' . $id);
        }

        $questions = $this->assessment_model->get_questions($id);
        if (empty($questions)) {
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

        $this->load->view('layouts/main', $data);
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

        $this->session->set_flashdata('success', $msg);
        redirect('assessments/result/' . $id);
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

        $this->load->view('layouts/main', $data);
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

        $this->load->view('layouts/main', $data);
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

        $this->load->view('layouts/main', $data);
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
                ->set_rules('module_id', 'Module',         'required|integer')
                ->set_rules('type',      'Assessment Type', 'required|in_list[pre,post]')
                ->set_rules('title',     'Title',           'required|max_length[255]');

            if ($this->form_validation->run()) {
                $id = $this->assessment_model->create_assessment([
                    'module_id'  => $this->input->post('module_id'),
                    'type'       => $this->input->post('type'),
                    'title'      => $this->input->post('title'),
                    'encoded_by' => $user->id,
                ]);

                $this->session->set_flashdata(
                    'success', 'Assessment created. Now add your questions.'
                );
                redirect('assessments/edit/' . $id);
            }
        }

        // Build module list for dropdown
        $modules = $this->_get_available_modules($user);

        $data = [
            'user'           => $user,
            'page_title'     => 'Create Assessment',
            'modules'        => $modules,
            'preselect_mod'  => (int) ($this->input->get('module_id') ?? 0),
            'preselect_type' => $this->input->get('type') ?? '',
            'breadcrumbs'    => [
                ['label' => 'Dashboard',   'url' => 'dashboard'],
                ['label' => 'Assessments', 'url' => 'assessments'],
                ['label' => 'Create'],
            ],
            'view' => 'assessments/create',
        ];

        $this->load->view('layouts/main', $data);
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
                ->set_rules('type',  'Type',  'required|in_list[pre,post]')
                ->set_rules('title', 'Title', 'required|max_length[255]');

            if ($this->form_validation->run()) {
                $this->assessment_model->update_assessment($id, [
                    'type'        => $this->input->post('type'),
                    'title'       => $this->input->post('title'),
                    'modified_by' => $this->user->id,
                ]);
                $this->session->set_flashdata('success', 'Assessment updated.');
                redirect('assessments/edit/' . $id);
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
            'breadcrumbs' => [
                ['label' => 'Dashboard',   'url' => 'dashboard'],
                ['label' => 'Assessments', 'url' => 'assessments'],
                ['label' => 'Edit: ' . $assessment->title],
            ],
            'view' => 'assessments/edit',
        ];

        $this->load->view('layouts/main', $data);
    }

    // =========================================================
    // save_question() — AJAX POST: add or update a question
    // =========================================================
    public function save_question()
    {
        $this->_require_manager();
        header('Content-Type: application/json');

        $assessment_id = (int) $this->input->post('assessment_id');
        $question_id   = (int) $this->input->post('question_id'); // 0 = new
        $question_text = trim($this->input->post('question_text'));
        $question_type = $this->input->post('question_type');
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
            $this->assessment_model->update_question($question_id, $q_data);
            $qid = $question_id;
        } else {
            $qid = $this->assessment_model->create_question($q_data);
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
        $ok          = $this->assessment_model->delete_question($question_id);

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

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    /** Redirect non-managers (admin/teacher) away. */
    private function _require_manager()
    {
        if ( ! in_array($this->user->role, ['admin', 'teacher'])) {
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

        if ($user->role === 'teacher') {
            $this->db->where('c.created_by', $user->id);
        }

        $r = $this->db->get();
        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }
}
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Assessment Choices Library (lib_assessment_choices).
 *
 * URL: index.php/libraries/assessment_choices
 *
 * @property Assessment_choice_model $assessment_choice_model
 */
class Assessment_choices extends KA_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Assessment_choice_model', 'assessment_choice_model');
        $this->load->library('form_validation');
        $this->require_role('admin', 'dashboard');
    }

    public function index()
    {
        $filters = [
            'question_id'      => $this->get_int('question_id'),
            'q'                => $this->get_param('q'),
            'include_archived' => $this->get_int('show_archived') === 1,
        ];

        $choices   = $this->assessment_choice_model->get_all($filters);
        $questions = $this->assessment_choice_model->get_mcq_questions_for_dropdown();

        $stats = [
            'total'    => count($choices),
            'active'   => 0,
            'archived' => 0,
            'correct'  => 0,
        ];
        foreach ($choices as $c) {
            if ((int) $c->archived === 1) {
                $stats['archived']++;
            } else {
                $stats['active']++;
            }
            if ((int) $c->is_correct === 1) {
                $stats['correct']++;
            }
        }

        $this->render('libraries/assessment_choices/listview', [
            'page_title' => 'Assessment Choices Library',
            'choices'    => $choices,
            'questions'  => $questions,
            'filters'    => $filters,
            'stats'      => $stats,
        ], [
            ['label' => 'Dashboard', 'url' => 'dashboard'],
            ['label' => 'Libraries', 'url' => 'libraries'],
            ['label' => 'Assessment Choices'],
        ]);
    }

    public function create()
    {
        if ($this->input->method() !== 'post') {
            return $this->json_error('POST required.');
        }

        if ( ! $this->_validate_choice_payload()) {
            return $this->json_error(validation_errors(' ', ' '));
        }

        $id = $this->assessment_choice_model->insert([
            'question_id'  => (int) $this->input->post('question_id'),
            'choice_text'  => $this->input->post('choice_text'),
            'is_correct'   => $this->input->post('is_correct'),
            'choice_order' => (int) $this->input->post('choice_order'),
        ]);

        if ($id < 1) {
            return $this->json_error('Unable to save assessment choice. Check that the question exists.');
        }

        return $this->json_ok([
            'message' => 'Assessment choice added successfully.',
            'choice'  => $this->assessment_choice_model->get_by_id($id),
        ]);
    }

    public function update($id = null)
    {
        if ($this->input->method() !== 'post') {
            return $this->json_error('POST required.');
        }

        $id = (int) ($id ?: $this->input->post('id'));
        if ($id < 1) {
            return $this->json_error('Invalid choice id.');
        }

        if ( ! $this->assessment_choice_model->get_by_id($id)) {
            return $this->json_error('Assessment choice not found.', ['http_code' => 404]);
        }

        if ( ! $this->_validate_choice_payload(true)) {
            return $this->json_error(validation_errors(' ', ' '));
        }

        $ok = $this->assessment_choice_model->update($id, [
            'question_id'  => (int) $this->input->post('question_id'),
            'choice_text'  => $this->input->post('choice_text'),
            'is_correct'   => $this->input->post('is_correct'),
            'choice_order' => (int) $this->input->post('choice_order'),
        ]);

        if ( ! $ok) {
            return $this->json_error('Unable to update assessment choice.');
        }

        return $this->json_ok([
            'message' => 'Assessment choice updated successfully.',
            'choice'  => $this->assessment_choice_model->get_by_id($id),
        ]);
    }

    public function delete($id = null)
    {
        if ($this->input->method() !== 'post') {
            return $this->json_error('POST required.');
        }

        $id = (int) ($id ?: $this->input->post('id'));
        if ($id < 1) {
            return $this->json_error('Invalid choice id.');
        }

        if ( ! $this->assessment_choice_model->get_by_id($id)) {
            return $this->json_error('Assessment choice not found.');
        }

        $ok = $this->assessment_choice_model->soft_delete($id);

        return $this->json_ok(['message' => $ok ? 'Assessment choice archived successfully.' : 'Unable to archive assessment choice.']);
    }

    public function restore($id = null)
    {
        if ($this->input->method() !== 'post') {
            return $this->json_error('POST required.');
        }

        $id = (int) ($id ?: $this->input->post('id'));
        if ($id < 1) {
            return $this->json_error('Invalid choice id.');
        }

        $ok = $this->assessment_choice_model->restore($id);

        if ( ! $ok) {
            return $this->json_error('Unable to restore assessment choice. The question may be missing or archived.');
        }

        return $this->json_ok(['message' => 'Assessment choice restored successfully.']);
    }

    public function get_by_question($question_id = null)
    {
        $qid = (int) ($question_id ?: $this->input->get('question_id'));
        if ($qid < 1) {
            return $this->json_error('question_id required.');
        }

        $include = $this->get_int('include_archived') === 1;
        $rows    = $this->assessment_choice_model->get_by_question($qid, $include);

        return $this->json_ok(['choices' => $rows]);
    }

    private function _validate_choice_payload($is_update = false)
    {
        $this->form_validation->set_error_delimiters('', '');

        $this->form_validation->set_rules('question_id', 'Question', 'required|integer');
        $this->form_validation->set_rules('choice_text', 'Choice text', 'required|max_length[500]');
        $this->form_validation->set_rules('choice_order', 'Order', 'integer');

        if ($is_update) {
            $this->form_validation->set_rules('id', 'ID', 'integer');
        }

        return (bool) $this->form_validation->run();
    }
}

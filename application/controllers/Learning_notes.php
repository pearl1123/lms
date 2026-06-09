<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Personal learning notes — learner workspace + JSON API.
 *
 * @property CI_Session           $session
 * @property CI_Input             $input
 * @property User_model           $user_model
 * @property Learning_notes_model $learning_notes_model
 * @property Course_model         $course_model
 */
class Learning_notes extends KA_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Learning_notes_model', 'learning_notes_model');
        $this->load->model('Course_model', 'course_model');
        $this->load->helper(['url', 'form', 'ka_layout']);

        $this->require_permission('learning_notes.view');
    }

    /**
     * GET — My Notes dashboard.
     */
    public function index()
    {
        $filter = $this->input->get('filter', true) ?: 'all';
        $course_id = (int) $this->input->get('course_id');

        $filters = ['q' => trim((string) $this->input->get('q', true))];
        if ($course_id > 0) {
            $filters['course_id'] = $course_id;
        }
        if ($filter === 'favorites') {
            $filters['favorite'] = 1;
        }
        if ($filter === 'recent') {
            $filters['recent'] = 1;
            $filters['limit'] = 100;
        }

        $notes = $this->learning_notes_model->get_notes_for_user((int) $this->auth_user->id, $filters);
        $courses = $this->learning_notes_model->get_courses_with_notes((int) $this->auth_user->id);

        $data = [
            'user'        => $this->auth_user,
            'page_title'  => 'My Notes',
            'notes'       => $notes,
            'courses'     => $courses,
            'filter'      => $filter,
            'course_id'   => $course_id,
            'search_q'    => $filters['q'] ?? '',
            'table_ready' => $this->learning_notes_model->table_ready(),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => 'My Notes'],
            ],
            'view' => 'learning_notes/index',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    /**
     * GET — JSON list for module sidebar.
     */
    public function api_list()
    {
        $course_id = (int) $this->input->get('course_id');
        $module_id = (int) $this->input->get('module_id');
        $q         = trim((string) $this->input->get('q', true));

        if ($course_id < 1) {
            return $this->_json(['success' => false, 'message' => 'course_id required.'], 400);
        }

        if ( ! $this->_user_may_access_course($course_id)) {
            return $this->_json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $filters = [
            'course_id' => $course_id,
            'q'         => $q,
        ];
        if ($module_id > 0) {
            $filters['module_id'] = $module_id;
        }

        $notes = $this->learning_notes_model->get_notes_for_user((int) $this->auth_user->id, $filters);

        return $this->_json([
            'success' => true,
            'notes'   => $this->_notes_to_array($notes),
        ]);
    }

    /**
     * POST — create note.
     */
    public function api_save()
    {
        if ($this->input->method() !== 'post') {
            return $this->_json(['success' => false, 'message' => 'POST required.'], 405);
        }

        $payload = $this->_read_json_body();
        $course_id = (int) ($payload['course_id'] ?? $this->input->post('course_id'));
        $module_id = (int) ($payload['module_id'] ?? $this->input->post('module_id'));

        if ($course_id < 1) {
            return $this->_json(['success' => false, 'message' => 'course_id required.'], 400);
        }
        if ( ! $this->_user_may_access_course($course_id)) {
            return $this->_json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $content = trim((string) ($payload['note_content'] ?? $this->input->post('note_content')));
        if ($content === '') {
            return $this->_json(['success' => false, 'message' => 'Note content is required.'], 422);
        }

        $id = $this->learning_notes_model->create_note((int) $this->auth_user->id, [
            'course_id'         => $course_id,
            'module_id'         => $module_id > 0 ? $module_id : null,
            'note_title'        => $payload['note_title'] ?? $this->input->post('note_title'),
            'note_content'      => $content,
            'content_type'      => $payload['content_type'] ?? $this->input->post('content_type'),
            'timestamp_seconds' => $payload['timestamp_seconds'] ?? $this->input->post('timestamp_seconds'),
            'pdf_page'          => $payload['pdf_page'] ?? $this->input->post('pdf_page'),
            'slide_number'      => $payload['slide_number'] ?? $this->input->post('slide_number'),
            'tags'              => $payload['tags'] ?? $this->input->post('tags'),
            'is_pinned'         => $payload['is_pinned'] ?? $this->input->post('is_pinned'),
            'is_favorite'       => $payload['is_favorite'] ?? $this->input->post('is_favorite'),
            'color_label'       => $payload['color_label'] ?? $this->input->post('color_label'),
        ]);

        if ($id < 1) {
            return $this->_json(['success' => false, 'message' => 'Could not save note. Is the database migrated?'], 500);
        }

        $note = $this->learning_notes_model->get_note($id, (int) $this->auth_user->id);

        return $this->_json([
            'success' => true,
            'message' => 'Note saved.',
            'note'    => $note ? $this->_note_to_array($note) : null,
        ]);
    }

    /**
     * POST — update note.
     */
    public function api_update($id = null)
    {
        $note_id = (int) $id;
        if ($note_id < 1) {
            return $this->_json(['success' => false, 'message' => 'Invalid note.'], 400);
        }

        $payload = $this->_read_json_body();
        $upd     = [];
        if (array_key_exists('note_title', $payload) || $this->input->post('note_title') !== null) {
            $upd['note_title'] = $payload['note_title'] ?? $this->input->post('note_title');
        }
        if (array_key_exists('note_content', $payload) || $this->input->post('note_content') !== null) {
            $upd['note_content'] = $payload['note_content'] ?? $this->input->post('note_content');
        }
        if (array_key_exists('tags', $payload) || $this->input->post('tags') !== null) {
            $upd['tags'] = $payload['tags'] ?? $this->input->post('tags');
        }
        if (array_key_exists('is_pinned', $payload)) {
            $upd['is_pinned'] = $payload['is_pinned'];
        }
        if (array_key_exists('is_favorite', $payload)) {
            $upd['is_favorite'] = $payload['is_favorite'];
        }
        if (array_key_exists('color_label', $payload) || $this->input->post('color_label') !== null) {
            $upd['color_label'] = $payload['color_label'] ?? $this->input->post('color_label');
        }

        $ok = $this->learning_notes_model->update_note($note_id, (int) $this->auth_user->id, $upd);

        if ( ! $ok) {
            return $this->_json(['success' => false, 'message' => 'Note not found or update failed.'], 404);
        }

        $note = $this->learning_notes_model->get_note($note_id, (int) $this->auth_user->id);

        return $this->_json([
            'success' => true,
            'message' => 'Note updated.',
            'note'    => $note ? $this->_note_to_array($note) : null,
        ]);
    }

    /**
     * POST — soft-delete.
     */
    public function api_delete($id = null)
    {
        $note_id = (int) $id;
        if ($note_id < 1) {
            return $this->_json(['success' => false, 'message' => 'Invalid note.'], 400);
        }

        $ok = $this->learning_notes_model->archive_note($note_id, (int) $this->auth_user->id);

        return $this->_json([
            'success' => $ok,
            'message' => $ok ? 'Note deleted.' : 'Note not found.',
        ], $ok ? 200 : 404);
    }

    public function api_toggle_pin($id = null)
    {
        return $this->_toggle($id, 'pin');
    }

    public function api_toggle_favorite($id = null)
    {
        return $this->_toggle($id, 'favorite');
    }

    private function _toggle($id, $type)
    {
        $note_id = (int) $id;
        if ($note_id < 1) {
            return $this->_json(['success' => false, 'message' => 'Invalid note.'], 400);
        }

        $ok = $type === 'pin'
            ? $this->learning_notes_model->toggle_pin($note_id, (int) $this->auth_user->id)
            : $this->learning_notes_model->toggle_favorite($note_id, (int) $this->auth_user->id);

        $note = $this->learning_notes_model->get_note($note_id, (int) $this->auth_user->id);

        return $this->_json([
            'success' => $ok,
            'note'    => $note ? $this->_note_to_array($note) : null,
        ], $ok ? 200 : 404);
    }

    private function _user_may_access_course($course_id)
    {
        if ($this->user_can('manage_courses.delete')) {
            return true;
        }

        return $this->course_model->has_approved_enrollment((int) $this->auth_user->id, (int) $course_id);
    }

    /**
     * @param object[] $notes
     */
    private function _notes_to_array(array $notes)
    {
        $out = [];
        foreach ($notes as $n) {
            $out[] = $this->_note_to_array($n);
        }

        return $out;
    }

    private function _note_to_array($n)
    {
        return [
            'id'                => (int) $n->id,
            'course_id'         => (int) $n->course_id,
            'module_id'         => (int) ($n->module_id ?? 0),
            'note_title'        => (string) ($n->note_title ?? ''),
            'note_content'      => (string) ($n->note_content ?? ''),
            'content_type'      => (string) ($n->content_type ?? 'general'),
            'timestamp_seconds' => $n->timestamp_seconds !== null ? (int) $n->timestamp_seconds : null,
            'pdf_page'          => $n->pdf_page !== null ? (int) $n->pdf_page : null,
            'slide_number'      => $n->slide_number !== null ? (int) $n->slide_number : null,
            'tags'              => $n->tags ?? [],
            'is_pinned'         => (int) ($n->is_pinned ?? 0) === 1,
            'is_favorite'       => (int) ($n->is_favorite ?? 0) === 1,
            'color_label'       => $n->color_label ?? null,
            'context_label'     => (string) ($n->context_label ?? ''),
            'return_url'        => (string) ($n->return_url ?? ''),
            'course_title'      => (string) ($n->course_title ?? ''),
            'module_title'      => (string) ($n->module_title ?? ''),
            'created_at'        => (string) ($n->created_at ?? ''),
            'updated_at'        => (string) ($n->updated_at ?? ''),
            'preview'           => mb_strimwidth(strip_tags((string) ($n->note_content ?? '')), 0, 120, '…'),
            'created_ago'       => $this->_time_ago($n->updated_at ?? $n->created_at ?? ''),
        ];
    }

    private function _time_ago($datetime)
    {
        $ts = strtotime((string) $datetime);
        if ( ! $ts) {
            return '';
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            return (int) floor($diff / 60) . ' min ago';
        }
        if ($diff < 86400) {
            return (int) floor($diff / 3600) . ' hr ago';
        }
        if ($diff < 604800) {
            return (int) floor($diff / 86400) . ' days ago';
        }

        return date('M j, Y', $ts);
    }

    private function _read_json_body()
    {
        $raw = $this->input->raw_input_stream;
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function _is_ajax()
    {
        return strtolower((string) $this->input->server('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
    }

    private function _json(array $data, $status = 200)
    {
        $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));

        return null;
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller for registry-driven admin library CRUD.
 *
 * Child controllers set: protected $library_key = 'course_categories';
 *
 * @property Library_crud_model   $library_crud_model
 * @property CI_Form_validation   $form_validation
 * @property CI_Config            $config
 */
class KA_Library_controller extends KA_Controller {

    /** @var string Registry key */
    protected $library_key = '';

    /** @var array<string, mixed> */
    protected $lib_config = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->config('library_registry');
        $this->load->model('Library_crud_model', 'library_crud_model');
        $this->load->library('form_validation');
        $this->require_role('admin', 'dashboard');

        if ($this->library_key === '') {
            show_error('Library key not configured.', 500);
        }

        $modules = $this->_library_modules();
        if (empty($modules[$this->library_key]) || ! empty($modules[$this->library_key]['custom'])) {
            show_error('Invalid library module.', 404);
        }

        $this->lib_config = $modules[$this->library_key];
        $this->library_crud_model->set_config($this->lib_config);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function _library_modules()
    {
        $registry = $this->config->item('library_registry');
        if ( ! is_array($registry) || ! isset($registry['modules']) || ! is_array($registry['modules'])) {
            return [];
        }

        return $registry['modules'];
    }

    public function index()
    {
        $filters = [
            'q'                => $this->get_param('q'),
            'include_archived' => $this->get_int('show_archived') === 1,
        ];

        $rows = $this->library_crud_model->get_all($filters);
        $stats = $this->_build_stats($rows);

        $this->load->helper('library_crud');

        $this->render('libraries/' . $this->library_key . '/listview', [
            'page_title'     => $this->lib_config['title'],
            'lib'            => $this->lib_config,
            'library_key'    => $this->library_key,
            'rows'           => $rows,
            'filters'        => $filters,
            'stats'          => $stats,
            'base_url'       => site_url($this->lib_config['controller']),
            'select_options' => $this->library_crud_model->get_form_select_options(),
        ], $this->_breadcrumbs());
    }

    public function create()
    {
        if ($this->input->method() !== 'post') {
            return $this->json_error('POST required.');
        }

        if ( ! $this->_validate_form()) {
            return $this->json_error(validation_errors(' ', ' '));
        }

        $id = $this->library_crud_model->insert($this->input->post());
        if ( ! $id) {
            return $this->json_error('Unable to save library item.');
        }

        return $this->json_ok([
            'message' => 'Library item added successfully.',
            'row'     => $this->library_crud_model->get_by_id($id),
        ]);
    }

    public function update($id = null)
    {
        if ($this->input->method() !== 'post') {
            return $this->json_error('POST required.');
        }

        $pk = $this->lib_config['primary_key'];
        $id = $id ?: $this->input->post($pk);
        if ( ! $this->library_crud_model->get_by_id($id)) {
            return $this->json_error('Library item not found.', ['http_code' => 404]);
        }

        if ( ! $this->_validate_form(true)) {
            return $this->json_error(validation_errors(' ', ' '));
        }

        if ( ! $this->library_crud_model->update($id, $this->input->post())) {
            return $this->json_error('Unable to update library item.');
        }

        return $this->json_ok([
            'message' => 'Library item updated successfully.',
            'row'     => $this->library_crud_model->get_by_id($id),
        ]);
    }

    public function delete($id = null)
    {
        if ($this->input->method() !== 'post') {
            return $this->json_error('POST required.');
        }

        if (empty($this->lib_config['soft_delete'])) {
            return $this->json_error('Archive not supported for this library.');
        }

        $pk = $this->lib_config['primary_key'];
        $id = $id ?: $this->input->post($pk);
        if ( ! $this->library_crud_model->get_by_id($id)) {
            return $this->json_error('Library item not found.');
        }

        $ok = $this->library_crud_model->soft_delete($id);

        return $this->json_ok(['message' => $ok ? 'Library item archived successfully.' : 'Unable to archive library item.']);
    }

    public function restore($id = null)
    {
        if ($this->input->method() !== 'post') {
            return $this->json_error('POST required.');
        }

        if (empty($this->lib_config['soft_delete'])) {
            return $this->json_error('Restore not supported for this library.');
        }

        $pk = $this->lib_config['primary_key'];
        $id = $id ?: $this->input->post($pk);
        $ok = $this->library_crud_model->restore($id);

        if ( ! $ok) {
            return $this->json_error('Unable to restore library item.');
        }

        return $this->json_ok(['message' => 'Library item restored successfully.']);
    }

    private function _validate_form($is_update = false)
    {
        $this->form_validation->set_error_delimiters('', '');

        foreach ($this->lib_config['form_fields'] ?? [] as $field) {
            $rules = [];
            if ( ! empty($field['required'])) {
                $rules[] = 'required';
            }
            if (($field['type'] ?? '') === 'number') {
                $rules[] = 'integer';
            }
            if ( ! empty($field['maxlength'])) {
                $rules[] = 'max_length[' . (int) $field['maxlength'] . ']';
            }
            if (($field['type'] ?? '') === 'enum' && ! empty($field['options'])) {
                $rules[] = 'in_list[' . implode(',', $field['options']) . ']';
            }

            if ( ! empty($rules)) {
                $this->form_validation->set_rules($field['field'], $field['label'], implode('|', $rules));
            }
        }

        return (bool) $this->form_validation->run();
    }

    /**
     * @param object[] $rows
     */
    private function _build_stats(array $rows)
    {
        $stats = ['total' => count($rows), 'active' => 0, 'archived' => 0];
        if (empty($this->lib_config['soft_delete'])) {
            $stats['active'] = count($rows);

            return $stats;
        }

        foreach ($rows as $row) {
            if ($this->library_crud_model->is_archived_row($row)) {
                $stats['archived']++;
            } else {
                $stats['active']++;
            }
        }

        return $stats;
    }

    private function _breadcrumbs()
    {
        return [
            ['label' => 'Dashboard', 'url' => 'dashboard'],
            ['label' => 'Libraries', 'url' => 'libraries'],
            ['label' => $this->lib_config['title']],
        ];
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reports — enterprise analytics workspace (admin).
 *
 * @property CI_Session    $session
 * @property CI_Input      $input
 * @property User_model    $user_model
 * @property Reports_model  $reports_model
 * @property Reports_export $reports_export
 */
class Reports extends CI_Controller {

    /** @var object */
    private $user;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('User_model', 'user_model');
        $this->load->model('Reports_model', 'reports_model');
        $this->load->helper(['url', 'form', 'ka_layout', 'report_export']);

        $user_id = $this->session->userdata('user_id');
        if ( ! $user_id) {
            redirect('auth/login');
        }

        $user = $this->user_model->get_user($user_id);
        if ( ! $user) {
            $this->session->sess_destroy();
            redirect('auth/login');
        }
        if ((int) $user->banned === 1 || $user->status !== 'active' || (int) $user->DELETED === 1) {
            $this->session->sess_destroy();
            redirect('auth/login');
        }
        if ( ! empty($user->locked_until) && strtotime($user->locked_until) > time()) {
            $this->session->sess_destroy();
            redirect('auth/login');
        }

        if (strtolower((string) ($user->role ?? '')) !== 'admin') {
            $this->session->set_flashdata('error', 'Only administrators can access reports and analytics.');
            redirect('dashboard');
        }

        $this->load->helper('permission');
        ka_gate_permission($user, 'reports.view', 'dashboard');

        $this->user = $user;
    }

    public function index()
    {
        $range = $this->input->get('range', true) ?: '30d';
        $report = $this->reports_model->get_workspace_data($range);

        $data = [
            'user'        => $this->user,
            'page_title'  => 'Reports & Analytics',
            'report'      => $report,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => 'Reports'],
            ],
            'view' => 'reports/index',
        ];

        if ($this->input->get('exported')) {
            $this->session->set_flashdata('success', 'Your report export has started. Check your downloads folder.');
        }

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }

    public function export_csv()
    {
        $this->_run_export('csv', function (array $bundle) {
            $this->load->library('reports_export');
            $this->reports_export->stream_csv($bundle);
        });
    }

    public function export_excel()
    {
        $this->_run_export('excel', function (array $bundle) {
            $this->load->library('reports_export');
            $this->reports_export->stream_excel($bundle);
        });
    }

    public function export_pdf()
    {
        $this->_run_export('pdf', function (array $bundle) {
            $this->load->library('reports_export');
            $this->reports_export->stream_pdf($bundle);
        }, 'all');
    }

    /**
     * @param string               $format
     * @param callable(array):void $streamer
     * @param string|null          $force_section
     */
    private function _run_export($format, callable $streamer, $force_section = null)
    {
        $this->load->helper('permission');
        ka_gate_permission($this->user, 'reports.export', 'reports');

        report_export_prepare_response();

        $range   = $this->input->get('range', true) ?: '30d';
        $section = $force_section !== null ? $force_section : ($this->input->get('section', true) ?: 'all');

        report_export_log('export_request', [
            'format'   => $format,
            'range'    => $range,
            'section'  => $section,
            'user_id'  => (int) ($this->user->id ?? 0),
            'base_url' => base_url(),
            'host'     => $_SERVER['HTTP_HOST'] ?? '',
            'uri'      => $_SERVER['REQUEST_URI'] ?? '',
        ]);

        try {
            $bundle = $this->_export_bundle($force_section);
            report_export_log('export_bundle_ready', [
                'format'  => $format,
                'section' => $bundle['meta']['section'] ?? $section,
                'rows'    => [
                    'overview'     => count($bundle['overview'] ?? []),
                    'courses'      => count($bundle['courses'] ?? []),
                    'certificates' => count($bundle['certificates'] ?? []),
                ],
            ]);

            $streamer($bundle);
        } catch (Throwable $e) {
            report_export_log('export_failed', [
                'format' => $format,
                'error'  => $e->getMessage(),
                'file'   => $e->getFile(),
                'line'   => $e->getLine(),
            ]);

            show_error('Report export failed. Check application/logs/report_export_debug.log on the server.', 500);
        }
    }

    /**
     * @param string|null $force_section
     * @return array<string,mixed>
     */
    private function _export_bundle($force_section = null)
    {
        $range   = $this->reports_model->normalize_range($this->input->get('range', true) ?: '30d');
        $section = $force_section !== null
            ? $force_section
            : ($this->input->get('section', true) ?: 'all');

        return $this->reports_model->get_export_bundle($range, $section, $this->user);
    }
}

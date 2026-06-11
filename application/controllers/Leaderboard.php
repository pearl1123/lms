<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Learner leaderboard — global, department, monthly.
 *
 * @property Leaderboard_service $leaderboard_service
 */
class Leaderboard extends KA_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('leaderboard_service');
    }

    public function index()
    {
        $tab = trim((string) ($this->input->get('tab') ?? 'global'));
        if ( ! in_array($tab, ['global', 'department', 'monthly'], true)) {
            $tab = 'global';
        }

        $viewer_id = (int) ($this->auth_user->id ?? 0);
        $department = trim((string) ($this->auth_user->office ?? ''));

        if ( ! $this->leaderboard_service->is_available()) {
            $data = [
                'page_title'  => 'Leaderboard',
                'schema_ready'=> false,
                'breadcrumbs' => [
                    ['label' => 'Dashboard', 'url' => 'dashboard'],
                    ['label' => 'Leaderboard'],
                ],
                'view' => 'leaderboard/index',
            ];
            $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));

            return;
        }

        if ($tab === 'department') {
            $board = $this->leaderboard_service->get_department($department, 25, $viewer_id);
        } elseif ($tab === 'monthly') {
            $board = $this->leaderboard_service->get_monthly(25, $viewer_id);
        } else {
            $board = $this->leaderboard_service->get_global(25, $viewer_id);
        }

        $data = [
            'page_title'   => 'Leaderboard',
            'schema_ready' => true,
            'tab'          => $tab,
            'board'        => $board,
            'department'   => $department,
            'breadcrumbs'  => [
                ['label' => 'Dashboard', 'url' => 'dashboard'],
                ['label' => 'Leaderboard'],
            ],
            'view' => 'leaderboard/index',
        ];

        $this->load->view('layouts/main', ka_merge_layout_vars($this, $data));
    }
}

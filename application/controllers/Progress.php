<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * My Progress module placeholder.
 *
 * Avoids raw 404 and provides premium fallback UX while feature is in-progress.
 */
class Progress extends KA_Controller {

    public function index()
    {
        $this->render('errors/under_construction', [
            'page_title'       => 'My Progress',
            'ef_title'         => 'My Progress is being enhanced',
            'ef_subtitle'      => 'A richer progress workspace is currently under construction.',
            'ef_hint'          => 'You can still track active learning in Dashboard and My Learning while this module is finalized.',
            'ef_icon'          => 'rocket',
            'ef_mode'          => 'construction',
            'ef_dashboard_url' => site_url('dashboard'),
            'ef_primary_label' => 'Back to Dashboard',
            'embedded_in_app'  => true,
        ], [
            ['label' => 'Dashboard', 'url' => 'dashboard'],
            ['label' => 'My Progress'],
        ]);
    }
}

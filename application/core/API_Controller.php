<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base JSON API controller — session or X-API-Token header.
 */
class API_Controller extends KA_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->output->set_content_type('application/json');
    }

    /**
     * @param array $data
     * @param int   $status
     */
    protected function json_response(array $data, $status = 200)
    {
        $encoded = json_encode($data);
        $this->output
            ->set_status_header((int) $status)
            ->set_output($encoded !== false ? $encoded : '{"ok":false}');
    }

    protected function json_ok(array $data = [], $status = 200)
    {
        $this->json_response(array_merge(['ok' => true], $data), $status);
    }

    protected function json_error($message, $status = 400)
    {
        $this->json_response(['ok' => false, 'message' => (string) $message], $status);
    }
}

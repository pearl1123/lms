<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API v1 — Courses (read-only catalog for enrolled/visible courses).
 */
class Courses extends API_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->require_permission('courses.view');
        $this->load->model('Course_model', 'course_model');
        $this->load->model('Course_phase2_model', 'course_phase2');
    }

    /**
     * GET api/v1/courses
     */
    public function index()
    {
        $user = $this->auth_user;
        $rows = $this->course_model->get_catalog();
        $out  = [];

        foreach ($rows as $c) {
            $out[] = [
                'id'          => (int) ($c->id ?? 0),
                'title'       => (string) ($c->title ?? ''),
                'description' => (string) ($c->description ?? ''),
                'modality'    => (string) ($c->modality_name ?? ''),
            ];
        }

        $this->json_ok(['data' => $out, 'version' => 'v1']);
    }

    /**
     * GET api/v1/courses/show/{id}
     */
    public function show($id = null)
    {
        $id = (int) $id;
        if ($id < 1) {
            $this->json_error('Invalid course id', 400);

            return;
        }

        $course = $this->course_model->get_course($id);
        if ( ! $course) {
            $this->json_error('Not found', 404);

            return;
        }

        $this->json_ok([
            'data' => [
                'id'          => (int) $course->id,
                'title'       => (string) ($course->title ?? ''),
                'description' => (string) ($course->description ?? ''),
                'modality'    => (string) ($course->modality_name ?? ''),
            ],
            'version' => 'v1',
        ]);
    }
}

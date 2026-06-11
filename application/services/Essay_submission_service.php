<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Essay PDF upload validation and storage.
 */
class Essay_submission_service {

    const MAX_BYTES = 5242880; // 5 MB

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * @param array $file $_FILES element
     * @return array{ok:bool,message:string}
     */
    public function validate_pdf_upload(array $file)
    {
        if (empty($file['tmp_name']) || ! is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'message' => 'No PDF file uploaded.'];
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            return ['ok' => false, 'message' => 'PDF must be 5 MB or smaller.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mime !== 'application/pdf') {
            return ['ok' => false, 'message' => 'Only PDF files are allowed for essay submissions.'];
        }

        return ['ok' => true, 'message' => ''];
    }

    /**
     * @param int   $user_id
     * @param int   $assessment_id
     * @param int   $question_id
     * @param array $file $_FILES element
     * @return array{ok:bool,path:string,message:string}
     */
    public function store_submission($user_id, $assessment_id, $question_id, array $file)
    {
        $check = $this->validate_pdf_upload($file);
        if ( ! $check['ok']) {
            return ['ok' => false, 'path' => '', 'message' => $check['message']];
        }

        $dir = FCPATH . 'uploads/essay_submissions/' . (int) $user_id . '/' . (int) $assessment_id . '/';
        if ( ! is_dir($dir) && ! mkdir($dir, 0755, true)) {
            return ['ok' => false, 'path' => '', 'message' => 'Could not create upload directory.'];
        }

        $filename = 'q' . (int) $question_id . '_' . time() . '.pdf';
        $dest     = $dir . $filename;
        if ( ! move_uploaded_file($file['tmp_name'], $dest)) {
            return ['ok' => false, 'path' => '', 'message' => 'Failed to save PDF upload.'];
        }

        $rel = 'uploads/essay_submissions/' . (int) $user_id . '/' . (int) $assessment_id . '/' . $filename;

        return ['ok' => true, 'path' => $rel, 'message' => ''];
    }
}

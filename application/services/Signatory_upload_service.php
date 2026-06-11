<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Certificate signatory PNG/JPEG upload — DOMPDF-safe paths under uploads/certificates/signatures/.
 */
class Signatory_upload_service {

    /** @var int Max bytes (500 KB) */
    protected $max_bytes = 512000;

    /**
     * @param array $file One file slice from $_FILES (name, tmp_name, size, error)
     * @param int   $course_id
     * @param int   $signatory_id
     * @return array{ok:bool,message:string,path?:string}
     */
    public function store(array $file, $course_id, $signatory_id)
    {
        $cid = (int) $course_id;
        $sid = (int) $signatory_id;
        if ($cid < 1 || $sid < 1) {
            return ['ok' => false, 'message' => 'Invalid signatory.'];
        }

        if (empty($file['name']) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'No signature image uploaded.'];
        }

        if ((int) ($file['size'] ?? 0) > $this->max_bytes) {
            return ['ok' => false, 'message' => 'Signature image must be 500 KB or smaller.'];
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ( ! in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
            return ['ok' => false, 'message' => 'Use PNG or JPEG for signature images.'];
        }

        $image_info = @getimagesize((string) ($file['tmp_name'] ?? ''));
        if ($image_info === false) {
            return ['ok' => false, 'message' => 'Upload a valid image file.'];
        }

        $mime = (string) ($image_info['mime'] ?? '');
        if ( ! in_array($mime, ['image/png', 'image/jpeg'], true)) {
            return ['ok' => false, 'message' => 'Invalid image type.'];
        }

        $dir = FCPATH . 'uploads/certificates/signatures/';
        if ( ! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $use_ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $filename = 'course_' . $cid . '_sig_' . $sid . '_' . time() . '.' . $use_ext;
        $rel      = 'uploads/certificates/signatures/' . $filename;
        $abs      = $dir . $filename;

        if ( ! move_uploaded_file((string) $file['tmp_name'], $abs)) {
            return ['ok' => false, 'message' => 'Could not save signature image.'];
        }

        return ['ok' => true, 'message' => 'Signature saved.', 'path' => $rel];
    }

    /**
     * @param string $relative_path
     */
    public function delete_if_exists($relative_path)
    {
        $rel = ltrim(str_replace(['../', '..\\'], '', (string) $relative_path), '/\\');
        if ($rel === '') {
            return;
        }

        $abs = FCPATH . $rel;
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}

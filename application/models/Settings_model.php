<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Platform settings (lms_settings key-value store).
 */
class Settings_model extends CI_Model {

    protected $table = 'lms_settings';

    /**
     * Default platform settings (merged with DB on load).
     *
     * @return array<string,mixed>
     */
    public function default_settings()
    {
        return [
            'general' => [
                'lms_name'           => 'KABAGA Academy',
                'organization_name'  => 'Lung Center of the Philippines',
                'support_email'      => '',
                'timezone'           => 'Asia/Manila',
                'date_format'        => 'M j, Y',
                'homepage_title'     => 'KABAGA Academy | Learning Management',
                'maintenance_mode'   => '0',
            ],
            'branding' => [
                'logo_path'          => '',
                'favicon_path'       => '',
                'login_bg_path'      => '',
                'accent_color'       => '#6dabcf',
                'sidebar_style'      => 'navy',
                'certificate_banner' => '',
            ],
            'learning' => [
                'default_enrollment_mode'   => 'approval_required',
                'require_approval'          => '1',
                'completion_threshold'      => '100',
                'default_expiry_days'       => '',
                'auto_unpublish_expired'    => '0',
            ],
            'certificates' => [
                'serial_prefix'        => 'CERT',
                'verification_enabled' => '1',
                'default_signatory'    => '',
                'default_signatory_title' => '',
                'cert_expiry_days'     => '',
            ],
            'notifications' => [
                'smtp_host'       => '',
                'smtp_port'       => '587',
                'smtp_user'       => '',
                'smtp_pass'       => '',
                'smtp_encryption' => 'tls',
                'from_email'      => '',
                'from_name'       => 'KABAGA Academy',
                'invite_email'    => '1',
                'approval_email'  => '1',
                'certificate_email' => '1',
            ],
            'security' => [
                'min_password_length' => '8',
                'session_timeout_mins'=> '120',
                'max_login_attempts'  => '5',
                'lockout_minutes'     => '15',
                'allowed_domains'     => '',
            ],
            'storage' => [
                'max_upload_mb'     => '25',
                'allowed_extensions'=> 'pdf,jpg,jpeg,png,webp,mp4',
                'max_pdf_mb'        => '15',
                'quota_mb'          => '1024',
            ],
            'advanced' => [
                'debug_mode' => '0',
            ],
        ];
    }

    public function table_ready()
    {
        return $this->db->table_exists($this->table);
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function get_all_settings()
    {
        $out = $this->default_settings();
        if ( ! $this->table_ready()) {
            return $out;
        }

        $rows = $this->db->get($this->table)->result();
        foreach ($rows as $row) {
            $key = (string) ($row->setting_key ?? '');
            if ($key === '') {
                continue;
            }
            $decoded = json_decode((string) ($row->setting_value ?? ''), true);
            if (is_array($decoded)) {
                $out[$key] = array_merge($out[$key] ?? [], $decoded);
            }
        }

        return $out;
    }

    /**
     * @param string $section
     * @param string $field
     * @param mixed  $default
     * @param array  $settings
     * @return mixed
     */
    public function val($section, $field, $default, array $settings)
    {
        if (isset($settings[$section][$field])) {
            return $settings[$section][$field];
        }

        return $default;
    }

    /**
     * @param array<string,array> $sections
     * @param int                   $actor_id
     * @return bool
     */
    public function save_sections(array $sections, $actor_id)
    {
        if ( ! $this->table_ready()) {
            return false;
        }

        $defaults = $this->default_settings();
        $now      = date('Y-m-d H:i:s');
        $actor    = (int) $actor_id;

        foreach ($defaults as $section => $default_fields) {
            if ( ! isset($sections[$section]) || ! is_array($sections[$section])) {
                continue;
            }
            $incoming = $sections[$section];
            $merged   = array_merge($default_fields, $incoming);

            foreach ($merged as $k => $v) {
                if (is_string($v)) {
                    $merged[$k] = trim($v);
                }
            }

            $payload = [
                'setting_value'      => json_encode($merged, JSON_UNESCAPED_UNICODE),
                'date_last_modified' => $now,
                'modified_by'        => $actor,
            ];

            $exists = $this->db->where('setting_key', $section)->count_all_results($this->table) > 0;
            if ($exists) {
                $this->db->where('setting_key', $section)->update($this->table, $payload);
            } else {
                $payload['setting_key']  = $section;
                $payload['date_encoded'] = $now;
                $payload['encoded_by']   = $actor;
                $this->db->insert($this->table, $payload);
            }
        }

        return true;
    }

    /**
     * @param string $section branding
     * @param string $field   logo_path|favicon_path|login_bg_path
     * @param array  $file    $_FILES element
     * @param array  $settings current settings
     * @param int    $actor_id
     * @return array{ok:bool,message:string,path?:string}
     */
    public function save_branding_upload($section, $field, array $file, array $settings, $actor_id = 0)
    {
        if (empty($file['name']) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'No file uploaded.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['logo_path' => ['png', 'jpg', 'jpeg', 'webp', 'svg'], 'favicon_path' => ['ico', 'png'], 'login_bg_path' => ['jpg', 'jpeg', 'png', 'webp']];
        $ok_ext = $allowed[$field] ?? ['png', 'jpg', 'jpeg'];
        if ( ! in_array($ext, $ok_ext, true)) {
            return ['ok' => false, 'message' => 'File type not allowed for this upload.'];
        }

        $dir = FCPATH . 'uploads/branding/';
        if ( ! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $field . '_' . time() . '.' . $ext;
        $rel      = 'uploads/branding/' . $filename;
        if ( ! move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            return ['ok' => false, 'message' => 'Upload failed.'];
        }

        $branding = $settings['branding'] ?? $this->default_settings()['branding'];
        $branding[$field] = $rel;
        $this->save_sections(['branding' => $branding], (int) $actor_id);

        return ['ok' => true, 'message' => 'File uploaded.', 'path' => $rel];
    }

    /**
     * Admin workspace context (HRMIS, storage stats).
     *
     * @return array<string,mixed>
     */
    public function build_admin_context()
    {
        $ctx = [
            'settings_ready' => $this->table_ready(),
            'hrmis'          => [
                'connected'     => false,
                'status'        => 'disconnected',
                'status_label'  => 'Disconnected',
                'department_count' => 0,
                'employee_count'   => 0,
                'last_sync'     => null,
            ],
            'storage_stats'  => [
                'uploads_bytes' => 0,
                'uploads_label' => '0 MB',
                'quota_mb'      => 1024,
            ],
        ];

        $CI =& get_instance();
        $CI->load->model('Course_phase2_model', 'course_phase2');

        if ($CI->course_phase2->hrmis_connection_ok()) {
            $ctx['hrmis']['connected'] = true;
            $ctx['hrmis']['status'] = 'connected';
            $ctx['hrmis']['status_label'] = 'Connected';
            $depts = $CI->course_phase2->get_departments();
            $ctx['hrmis']['department_count'] = count($depts);
            $hrmis = $CI->load->database('hrmis', true);
            if ($hrmis) {
                $r = $hrmis->where('status', 'ACTIVE')->count_all_results('tblemployee');
                $ctx['hrmis']['employee_count'] = (int) $r;
            }
        } else {
            $ctx['hrmis']['status'] = 'disconnected';
            $ctx['hrmis']['status_label'] = 'Disconnected';
        }

        $uploads_path = FCPATH . 'uploads';
        if (is_dir($uploads_path)) {
            $bytes = $this->directory_size($uploads_path);
            $ctx['storage_stats']['uploads_bytes'] = $bytes;
            $ctx['storage_stats']['uploads_label'] = $this->format_bytes($bytes);
        }

        return $ctx;
    }

    /**
     * @param string $path
     * @return int
     */
    protected function directory_size($path)
    {
        $size = 0;
        if ( ! is_dir($path)) {
            return 0;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * @param int $bytes
     * @return string
     */
    protected function format_bytes($bytes)
    {
        $bytes = (int) $bytes;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1073741824, 2) . ' GB';
    }
}

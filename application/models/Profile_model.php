<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Profile_model — user profile workspace data and updates.
 */
class Profile_model extends CI_Model {

    protected $table = 'aauth_users';

    /** @var CI_DB_mysqli_driver|null */
    protected $hrmis_db;

    public function __construct()
    {
        parent::__construct();
        $this->hrmis_db = $this->load->database('hrmis', true);
    }

    /**
     * Build profile view payload for the current user.
     *
     * @param object $user aauth_users row
     * @return array
     */
    public function build_profile_context($user)
    {
        $uid = (int) ($user->id ?? 0);
        $hr  = $this->get_hrmis_snapshot($user);
        $hrmis_linked = ($hr !== null);

        $email = $this->user_field($user, 'email', '');
        $contact = $this->user_field($user, 'contact_number', '');
        if ($contact === '' && $hr && ! empty($hr->celno)) {
            $contact = trim((string) $hr->celno);
        }
        if ($email === '' && $hr && ! empty($hr->emailadd)) {
            $email = trim((string) $hr->emailadd);
        }

        $department = trim((string) ($user->office ?? ''));
        if ($department === '' && $hr) {
            $department = trim((string) ($hr->Department ?? ''));
        }

        $profession = '';
        if ($hr && ! empty($hr->Position)) {
            $profession = trim((string) $hr->Position);
        }

        $editable = $this->editable_field_map($hrmis_linked);
        $stats    = $this->get_activity_stats($uid);
        $recent   = $this->get_recent_activity($uid, 8);
        $completeness = $this->profile_completeness($user, $email, $contact, $hr);

        return [
            'profile' => (object) [
                'user_id'       => $uid,
                'fullname'      => (string) ($user->fullname ?? ''),
                'employee_id'   => (string) ($user->employee_id ?? ''),
                'email'         => $email,
                'contact_number'=> $contact,
                'bio'           => $this->user_field($user, 'bio', ''),
                'avatar_path'   => $this->user_field($user, 'avatar_path', ''),
                'role'          => (string) ($user->role ?? ''),
                'office'        => $department,
                'profession'    => $profession,
                'status'        => (string) ($user->status ?? ''),
                'created_at'    => $user->created_at ?? null,
                'last_login'    => $user->last_login ?? null,
                'last_activity' => $user->last_activity ?? null,
            ],
            'hrmis_linked'    => $hrmis_linked,
            'editable'        => $editable,
            'stats'           => $stats,
            'recent_activity' => $recent,
            'completeness'    => $completeness,
            'schema'          => [
                'email'          => $this->db->field_exists('email', $this->table),
                'contact_number' => $this->db->field_exists('contact_number', $this->table),
                'bio'            => $this->db->field_exists('bio', $this->table),
                'avatar_path'    => $this->db->field_exists('avatar_path', $this->table),
            ],
        ];
    }

    /**
     * @param object $user
     * @return object|null
     */
    public function get_hrmis_snapshot($user)
    {
        $emp_id = trim((string) ($user->employee_id ?? ''));
        if ($emp_id === '') {
            return null;
        }

        if ( ! $this->hrmis_db) {
            return null;
        }

        return $this->hrmis_db
            ->where('idno', $emp_id)
            ->where('status', 'ACTIVE')
            ->get('tblemployee', 1)
            ->row();
    }

    /**
     * Which profile fields the user may edit in the LMS.
     *
     * @param bool $hrmis_linked
     * @return array<string,bool>
     */
    public function editable_field_map($hrmis_linked)
    {
        return [
            'fullname'       => false,
            'employee_id'    => false,
            'department'     => false,
            'profession'     => false,
            'email'          => $this->db->field_exists('email', $this->table),
            'contact_number' => $this->db->field_exists('contact_number', $this->table),
            'bio'            => $this->db->field_exists('bio', $this->table),
            'avatar'         => $this->db->field_exists('avatar_path', $this->table),
        ];
    }

    /**
     * @param object $user
     * @param array  $post
     * @return array{ok:bool,message:string}
     */
    public function update_profile_from_post($user, array $post)
    {
        $uid = (int) ($user->id ?? 0);
        if ($uid < 1) {
            return ['ok' => false, 'message' => 'Invalid user.'];
        }

        $hrmis_linked = ($this->get_hrmis_snapshot($user) !== null);
        $editable     = $this->editable_field_map($hrmis_linked);
        $update  = $this->audit_fields($uid);
        $changed = false;

        if ($editable['email'] && array_key_exists('email', $post)) {
            $email = strtolower(trim((string) $post['email']));
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['ok' => false, 'message' => 'Enter a valid email address.'];
            }
            $update['email'] = $email !== '' ? $email : null;
            $changed = true;
        }

        if ($editable['contact_number'] && array_key_exists('contact_number', $post)) {
            $update['contact_number'] = trim((string) $post['contact_number']) ?: null;
            $changed = true;
        }

        if ($editable['bio'] && array_key_exists('bio', $post)) {
            $bio = trim((string) $post['bio']);
            if (strlen($bio) > 500) {
                return ['ok' => false, 'message' => 'Bio must be 500 characters or fewer.'];
            }
            $update['bio'] = $bio !== '' ? $bio : null;
            $changed = true;
        }

        if ( ! $changed) {
            return ['ok' => true, 'message' => 'No profile text fields to update.'];
        }

        $ok = (bool) $this->db->where('id', $uid)->update($this->table, $update);

        return [
            'ok'      => $ok,
            'message' => $ok ? 'Profile updated successfully.' : 'Could not save profile.',
        ];
    }

    /**
     * @param int    $user_id
     * @param string $current_password
     * @param string $new_password
     * @return array{ok:bool,message:string}
     */
    public function change_password($user_id, $current_password, $new_password)
    {
        $uid = (int) $user_id;
        $row = $this->db->where('id', $uid)->get($this->table, 1)->row();
        if ( ! $row) {
            return ['ok' => false, 'message' => 'User not found.'];
        }

        if ( ! password_verify($current_password, $row->password)) {
            return ['ok' => false, 'message' => 'Current password is incorrect.'];
        }

        if (strlen($new_password) < 8) {
            return ['ok' => false, 'message' => 'New password must be at least 8 characters.'];
        }

        $update = array_merge(
            ['password' => password_hash($new_password, PASSWORD_DEFAULT)],
            $this->audit_fields($uid)
        );
        $ok = (bool) $this->db->where('id', $uid)->update($this->table, $update);

        return [
            'ok'      => $ok,
            'message' => $ok ? 'Password updated successfully.' : 'Could not update password.',
        ];
    }

    /**
     * @param int $user_id
     * @return array<string,int>
     */
    public function get_activity_stats($user_id)
    {
        $uid = (int) $user_id;
        $stats = [
            'enrolled'     => 0,
            'completed'    => 0,
            'certificates' => 0,
            'invitations'  => 0,
        ];

        if ($uid < 1) {
            return $stats;
        }

        if ($this->db->table_exists('enrollments')) {
            $stats['enrolled'] = (int) $this->db
                ->where('user_id', $uid)
                ->where('status', 'approved')
                ->count_all_results('enrollments');
        }

        if ($this->db->table_exists('lib_certificates')) {
            $this->db->where('user_id', $uid);
            if ($this->db->field_exists('archived', 'lib_certificates')) {
                $this->db->where('archived', 0);
            }
            $stats['certificates'] = (int) $this->db->count_all_results('lib_certificates');
            $stats['completed']    = $stats['certificates'];
        }

        if ($this->db->table_exists('course_invitations')) {
            $this->db->where('user_id', $uid)->where('status', 'pending');
            if ($this->db->field_exists('archived', 'course_invitations')) {
                $this->db->where('archived', 0);
            }
            $stats['invitations'] = (int) $this->db->count_all_results('course_invitations');
        }

        return $stats;
    }

    /**
     * @param int $user_id
     * @param int $limit
     * @return array<int,object>
     */
    public function get_recent_activity($user_id, $limit = 8)
    {
        $uid   = (int) $user_id;
        $limit = max(1, min(20, (int) $limit));
        $items = [];

        if ($uid < 1) {
            return $items;
        }

        if ($this->db->table_exists('lib_certificates')) {
            $this->db
                ->select('uc.issued_at AS activity_at, c.title AS label', false)
                ->from('lib_certificates uc')
                ->join('courses c', 'c.id = uc.course_id', 'left')
                ->where('uc.user_id', $uid);
            if ($this->db->field_exists('archived', 'lib_certificates')) {
                $this->db->where('uc.archived', 0);
            }
            $r = $this->db->order_by('uc.issued_at', 'DESC')->limit($limit)->get();
            if ($r) {
                foreach ($r->result() as $row) {
                    $items[] = (object) [
                        'type'  => 'certificate',
                        'label' => 'Certificate: ' . ($row->label ?: 'Course'),
                        'at'    => $row->activity_at,
                    ];
                }
            }
        }

        if ($this->db->table_exists('enrollments')) {
            $r = $this->db
                ->select('e.enrolled_at AS activity_at, c.title AS label', false)
                ->from('enrollments e')
                ->join('courses c', 'c.id = e.course_id', 'left')
                ->where('e.user_id', $uid)
                ->where('e.status', 'approved')
                ->order_by('e.enrolled_at', 'DESC')
                ->limit($limit)
                ->get();
            if ($r) {
                foreach ($r->result() as $row) {
                    $items[] = (object) [
                        'type'  => 'enrollment',
                        'label' => 'Enrolled: ' . ($row->label ?: 'Course'),
                        'at'    => $row->activity_at,
                    ];
                }
            }
        }

        usort($items, function ($a, $b) {
            return strtotime((string) ($b->at ?? '')) <=> strtotime((string) ($a->at ?? ''));
        });

        return array_slice($items, 0, $limit);
    }

    /**
     * @param int    $user_id
     * @param array  $file $_FILES['avatar'] structure
     * @return array{ok:bool,message:string,path?:string}
     */
    public function save_avatar_upload($user_id, array $file)
    {
        if ( ! $this->db->field_exists('avatar_path', $this->table)) {
            return ['ok' => false, 'message' => 'Avatar uploads require migration_profile_user_fields.sql.'];
        }

        if (empty($file['name']) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'No image uploaded.'];
        }

        $max_bytes = 2 * 1024 * 1024;
        if ((int) ($file['size'] ?? 0) > $max_bytes) {
            return ['ok' => false, 'message' => 'Profile photo must be 2 MB or smaller.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ( ! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return ['ok' => false, 'message' => 'Use JPG, PNG, or WebP for your profile photo.'];
        }

        $image_info = @getimagesize($file['tmp_name']);
        if ($image_info === false || empty($image_info[0]) || empty($image_info[1])) {
            return ['ok' => false, 'message' => 'Upload a valid image file (JPG, PNG, or WebP).'];
        }

        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
        if ( ! in_array((string) ($image_info['mime'] ?? ''), $allowed_mimes, true)) {
            return ['ok' => false, 'message' => 'Invalid image type. Use JPG, PNG, or WebP.'];
        }

        $dir = FCPATH . 'uploads/avatars/';
        if ( ! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'user_' . (int) $user_id . '_' . time() . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $tmp_abs  = $dir . $filename;

        if ( ! move_uploaded_file($file['tmp_name'], $tmp_abs)) {
            return ['ok' => false, 'message' => 'Upload failed.'];
        }

        $user_row = $this->db->where('id', (int) $user_id)->get($this->table, 1)->row();
        if ($user_row && ! empty($user_row->avatar_path)) {
            $CI =& get_instance();
            $CI->load->library('avatar_service');
            $CI->avatar_service->delete_avatar_files((string) $user_row->avatar_path);
        }

        $rel = 'uploads/avatars/' . $filename;
        if (function_exists('get_instance')) {
            $CI =& get_instance();
            $CI->load->library('avatar_service');
            $processed = $CI->avatar_service->process_saved_file($tmp_abs, (int) $user_id, $ext);
            $rel = (string) ($processed['full'] ?? $rel);
        }

        $update = array_merge(['avatar_path' => $rel], $this->audit_fields((int) $user_id));
        $ok = (bool) $this->db->where('id', (int) $user_id)->update($this->table, $update);

        return [
            'ok'      => $ok,
            'message' => $ok ? 'Profile photo updated.' : 'Could not save profile photo.',
            'path'    => $rel,
        ];
    }

    /**
     * @param object $user
     * @param string $email
     * @param string $contact
     * @param object|null $hr
     * @return int 0–100
     */
    public function profile_completeness($user, $email, $contact, $hr)
    {
        $checks = [
            ! empty($user->fullname),
            ! empty($user->employee_id),
            $email !== '',
            $contact !== '',
            $this->user_field($user, 'bio', '') !== '',
            $this->user_field($user, 'avatar_path', '') !== '',
            ! empty($user->office) || ($hr && ! empty($hr->Department)),
        ];

        $done = count(array_filter($checks));

        return (int) round(($done / count($checks)) * 100);
    }

    /**
     * @param object $user
     * @param string $field
     * @param string $default
     * @return string
     */
    protected function user_field($user, $field, $default = '')
    {
        if ( ! $this->db->field_exists($field, $this->table)) {
            return $default;
        }

        return trim((string) ($user->{$field} ?? $default));
    }

    /**
     * @param int $actor_id
     * @return array<string,string|int>
     */
    protected function audit_fields($actor_id)
    {
        $out = [];
        if ($this->db->field_exists('date_last_modified', $this->table)) {
            $out['date_last_modified'] = date('Y-m-d H:i:s');
        }
        if ($this->db->field_exists('modified_by', $this->table)) {
            $out['modified_by'] = (int) $actor_id;
        }

        return $out;
    }
}

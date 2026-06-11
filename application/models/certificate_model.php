<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Certificate_model
 *
 * Tables (exact schema):
 * ─────────────────────────────────────────────────────────────
 * lib_certificates
 *   id, user_id, course_id, certificate_code, issued_at,
 *   file_path, date_encoded, encoded_by, archived
 *
 * lib_certificate_logs
 *   log_id, certificate_id, action (issued|revoked|printed),
 *   action_by, action_at, remarks, archived
 *
 * lib_certificate_templates
 *   template_id, template_name, template_file, description, archived
 *
 * lib_certificate_types
 *   certificate_type_id, certificate_type_name, description, archived
 *
 * Eligibility rules:
 *   1. All course modules must be status = 'completed'
 *   2. All POST assessments for each module must be PASSED
 *      (score >= ka_assessment_pass_threshold() and no pending essay answers)
 *   3. No existing certificate (archived = 0) for this user + course
 *
 * @property CI_DB_mysqli_driver $db
 */
class certificate_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('ka_format');
        $this->load->library('course_completion_service');
    }

    // =========================================================
    // ELIGIBILITY CHECK
    // =========================================================

    /**
     * Check if a user is eligible for a certificate.
     *
     * @param  int $user_id
     * @param  int $course_id
     * @return array ['eligible'=>bool, 'reason'=>string, 'checks'=>array]
     */
    public function check_eligibility($user_id, $course_id)
    {
        $state = $this->course_completion_service
            ->evaluate_user_course_state((int) $user_id, (int) $course_id);
        $eligible = ! empty($state['is_certificate_eligible']);

        return [
            'eligible' => $eligible,
            'reason'   => $eligible ? 'eligible' : 'course_completion_service_ineligible',
            'checks'   => [
                'all_modules_complete' => ! empty($state['is_completed']),
                'post_assessments_done'=> (($state['assessment_state']['post'] ?? 'pending') === 'completed'),
                'no_existing_cert'     => ! empty($state['is_certificate_eligible']),
            ],
            'state'    => $state,
        ];
    }

    // =========================================================
    // ISSUE CERTIFICATE
    // =========================================================

    /**
     * Issue a certificate for a user who has met all requirements.
     * Generates a unique certificate code, records in lib_certificates,
     * logs the action, and triggers PDF generation.
     *
     * @param  int    $user_id
     * @param  int    $course_id
     * @param  int    $issued_by  0 = auto-issued by system
     * @return array  ['success'=>bool, 'certificate_id'=>int, 'code'=>string]
     */
    public function issue($user_id, $course_id, $issued_by = 0)
    {
        // Double-check eligibility
        $check = $this->check_eligibility($user_id, $course_id);
        if ( ! $check['eligible']) {
            return [
                'success' => false,
                'reason'  => $check['reason'],
            ];
        }

        $now  = date('Y-m-d H:i:s');
        $certificate_id = 0;
        $code = '';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            // Generate unique certificate code:
            // KABAGA-{COURSE_PREFIX}-{YEAR}-{INCREMENT}
            $code = $this->_generate_code((int) $course_id);

            $ok = $this->db->insert('lib_certificates', [
                'user_id'          => (int) $user_id,
                'course_id'        => (int) $course_id,
                'certificate_code' => $code,
                'issued_at'        => $now,
                'file_path'        => null,  // updated after PDF generation
                'date_encoded'     => $now,
                'encoded_by'       => (int) $issued_by,
                'archived'         => 0,
            ]);

            if ($ok) {
                $certificate_id = (int) $this->db->insert_id();
                if ($certificate_id > 0) {
                    break;
                }
            }

            // Retry only duplicate-code conflicts; stop on other DB errors.
            if ((int) $this->db->error()['code'] !== 1062) {
                break;
            }
        }

        if ($certificate_id === 0) {
            return ['success' => false, 'reason' => 'db_error'];
        }

        // Log the issuance
        $this->_log($certificate_id, 'issued', $issued_by, 'Auto-issued on course completion.');

        return [
            'success'        => true,
            'certificate_id' => $certificate_id,
            'code'           => $code,
        ];
    }

    /**
     * Update the file_path after PDF has been generated.
     *
     * @param  int    $certificate_id
     * @param  string $file_path
     * @return bool
     */
    public function save_file_path($certificate_id, $file_path)
    {
        return (bool) $this->db
            ->where('id', (int) $certificate_id)
            ->update('lib_certificates', [
                'file_path'          => $file_path,
                'date_last_modified' => date('Y-m-d H:i:s'),
            ]);
    }

    // =========================================================
    // REVOKE CERTIFICATE
    // =========================================================

    /**
     * Revoke (soft-archive) a certificate.
     *
     * @param  int    $certificate_id
     * @param  int    $revoked_by
     * @param  string $remarks
     * @return bool
     */
    public function revoke($certificate_id, $revoked_by, $remarks = '')
    {
        $ok = (bool) $this->db
            ->where('id', (int) $certificate_id)
            ->update('lib_certificates', [
                'archived'           => 1,
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $revoked_by,
            ]);

        if ($ok) {
            $this->_log($certificate_id, 'revoked', $revoked_by, $remarks);
        }

        return $ok;
    }

    // =========================================================
    // FETCH CERTIFICATES
    // =========================================================

    /**
     * Get a user's certificate for a specific course (active only).
     *
     * @param  int        $user_id
     * @param  int        $course_id
     * @return object|null
     */
    public function get_certificate($user_id, $course_id)
    {
        $r = $this->db
            ->where('user_id',   (int) $user_id)
            ->where('course_id', (int) $course_id)
            ->where('archived',  0)
            ->get('lib_certificates');

        return ($r && $r->num_rows() > 0) ? $r->row() : null;
    }

    /** @see get_certificate() */
    public function get_by_user_course($user_id, $course_id)
    {
        return $this->get_certificate($user_id, $course_id);
    }

    /**
     * Get a certificate by ID with user + course info.
     *
     * @param  int        $certificate_id
     * @return object|null
     */
    public function get_by_id($certificate_id)
    {
        $r = $this->db
            ->select('
                lc.*,
                u.fullname     AS student_name,
                u.employee_id,
                c.title        AS course_title,
                c.description  AS course_description,
                c.certificate_prefix,
                c.signatory_name,
                c.signatory_title,
                cc.name        AS category_name,
                lm.modality_desc AS modality_name
            ', false)
            ->from('lib_certificates lc')
            ->join('aauth_users u',      'u.id  = lc.user_id',   'left')
            ->join('courses c',           'c.id  = lc.course_id', 'left')
            ->join('course_categories cc','cc.id = c.category_id','left')
            ->join('lib_course_modality lm', 'lm.modality_id = c.modality_id', 'left')
            ->where('lc.id',       (int) $certificate_id)
            ->where('lc.archived', 0)
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->row() : null;
    }

    /**
     * Get a certificate by unique code (for verification).
     *
     * @param  string     $code
     * @return object|null
     */
    public function get_by_code($code)
    {
        $r = $this->db
            ->select('
                lc.*,
                u.fullname     AS student_name,
                u.employee_id,
                c.title        AS course_title,
                c.certificate_prefix,
                c.signatory_name,
                c.signatory_title,
                cc.name        AS category_name
            ', false)
            ->from('lib_certificates lc')
            ->join('aauth_users u',      'u.id  = lc.user_id',   'left')
            ->join('courses c',           'c.id  = lc.course_id', 'left')
            ->join('course_categories cc','cc.id = c.category_id','left')
            ->where('lc.certificate_code', $code)
            ->where('lc.archived',         0)
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->row() : null;
    }

    /**
     * Get all certificates for a user (their collection).
     *
     * @param  int $user_id
     * @return object[]
     */
    public function get_user_certificates($user_id)
    {
        $r = $this->db
            ->select('
                lc.id, lc.certificate_code, lc.issued_at, lc.file_path,
                c.id    AS course_id,
                c.title AS course_title,
                cc.name AS category_name,
                lm.modality_desc AS modality_name
            ', false)
            ->from('lib_certificates lc')
            ->join('courses c',             'c.id  = lc.course_id',    'left')
            ->join('course_categories cc',  'cc.id = c.category_id',   'left')
            ->join('lib_course_modality lm','lm.modality_id = c.modality_id','left')
            ->where('lc.user_id',  (int) $user_id)
            ->where('lc.archived', 0)
            ->order_by('lc.issued_at', 'DESC')
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * Get all certificates (admin view) with filters.
     *
     * @param  array $filters  ['course_id'=>int, 'keyword'=>string]
     * @return object[]
     */
    public function get_all_certificates($filters = [])
    {
        $this->db
            ->select('
                lc.id, lc.certificate_code, lc.issued_at, lc.file_path, lc.archived,
                u.fullname    AS student_name,
                u.employee_id,
                c.id          AS course_id,
                c.title       AS course_title,
                cc.name       AS category_name
            ', false)
            ->from('lib_certificates lc')
            ->join('aauth_users u',        'u.id  = lc.user_id',   'left')
            ->join('courses c',             'c.id  = lc.course_id', 'left')
            ->join('course_categories cc', 'cc.id = c.category_id','left')
            ->where('lc.archived', 0);

        if ( ! empty($filters['course_id'])) {
            $this->db->where('lc.course_id', (int) $filters['course_id']);
        }
        if ( ! empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $this->db->group_start()
                ->like('u.fullname',    $kw)
                ->or_like('c.title',    $kw)
                ->or_like('lc.certificate_code', $kw)
                ->group_end();
        }

        $r = $this->db->order_by('lc.issued_at', 'DESC')->get();
        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * Get all certificates for courses owned by a teacher.
     */
    public function get_certificates_by_instructor($instructor_id)
    {
        $CI =& get_instance();
        $CI->load->model('Course_phase2_model', 'course_phase2');
        /** @var Course_phase2_model $course_phase2 */
        $course_phase2 = $CI->{'course_phase2'};
        $course_ids = $course_phase2->get_instructor_course_ids((int) $instructor_id);
        if (empty($course_ids)) {
            return [];
        }

        $r = $this->db
            ->select('
                lc.id, lc.certificate_code, lc.issued_at, lc.file_path,
                u.fullname    AS student_name,
                u.employee_id,
                c.id          AS course_id,
                c.title       AS course_title,
                cc.name       AS category_name
            ', false)
            ->from('lib_certificates lc')
            ->join('aauth_users u',       'u.id  = lc.user_id',   'left')
            ->join('courses c',            'c.id  = lc.course_id', 'left')
            ->join('course_categories cc','cc.id = c.category_id','left')
            ->where_in('c.id', $course_ids)
            ->where('lc.archived',  0)
            ->order_by('lc.issued_at', 'DESC')
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    // =========================================================
    // LOGS
    // =========================================================

    /**
     * Get the audit log for a certificate.
     *
     * @param  int $certificate_id
     * @return object[]
     */
    public function get_logs($certificate_id)
    {
        $r = $this->db
            ->select('
                cl.log_id, cl.action, cl.action_at, cl.remarks,
                u.fullname AS action_by_name
            ', false)
            ->from('lib_certificate_logs cl')
            ->join('aauth_users u', 'u.id = cl.action_by', 'left')
            ->where('cl.certificate_id', (int) $certificate_id)
            ->where('cl.archived',       0)
            ->order_by('cl.action_at',   'ASC')
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    // =========================================================
    // TEMPLATES & TYPES
    // =========================================================

    /** Get active certificate templates. */
    public function get_templates()
    {
        $r = $this->db
            ->where('archived', 0)
            ->order_by('template_name', 'ASC')
            ->get('lib_certificate_templates');
        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /** Get active certificate types. */
    public function get_types()
    {
        $r = $this->db
            ->where('archived', 0)
            ->order_by('certificate_type_name', 'ASC')
            ->get('lib_certificate_types');
        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    public function signatories_table_ready()
    {
        return $this->db->table_exists('certificate_signatories');
    }

    /**
     * Active signatories for a course (ordered).
     *
     * @param  int $course_id
     * @return object[]
     */
    public function get_signatories_for_course($course_id)
    {
        if ( ! $this->signatories_table_ready()) {
            return [];
        }

        $r = $this->db
            ->where('course_id', (int) $course_id)
            ->where('is_active', 1)
            ->where('archived', 0)
            ->order_by('order_no', 'ASC')
            ->order_by('id', 'ASC')
            ->get('certificate_signatories');

        if ($r === false) {
            log_message('error', 'Phase3: certificate_signatories query failed: ' . json_encode($this->db->error()));

            return [];
        }

        return ($r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * Signatories for PDF; falls back to courses.signatory_name/title when none configured.
     *
     * @param  int         $course_id
     * @param  string      $fallback_name
     * @param  string      $fallback_title
     * @return object[] {name, title}
     */
    public function resolve_signatories_for_pdf($course_id, $fallback_name = '', $fallback_title = '')
    {
        if ( ! $this->signatories_table_ready()) {
            log_message(
                'debug',
                'Phase3 cert PDF: certificate_signatories table not present; using courses.signatory_name/title if set (course_id=' . (int) $course_id . ').'
            );
        }

        $rows = $this->get_signatories_for_course($course_id);
        if ( ! empty($rows)) {
            return $rows;
        }

        if ($this->signatories_table_ready()) {
            log_message(
                'debug',
                'Phase3 cert PDF: no active certificate_signatories rows for course_id=' . (int) $course_id . '; using courses.signatory_name/title if set.'
            );
        }

        $name  = trim((string) $fallback_name);
        $title = trim((string) $fallback_title);
        if ($name === '') {
            if ($this->signatories_table_ready()) {
                log_message('debug', 'Phase3 cert PDF: no signatory rows and empty course signatory_name for course_id=' . (int) $course_id . '.');
            } else {
                log_message('debug', 'Phase3 cert PDF: certificate_signatories absent and no course signatory_name for course_id=' . (int) $course_id . '.');
            }

            return [];
        }

        log_message('debug', 'Phase3 cert PDF: using course-level signatory fallback for course_id=' . (int) $course_id . '.');

        return [(object) ['name' => $name, 'title' => $title]];
    }

    /**
     * Replace signatories for a course from admin form rows.
     *
     * @param  int   $course_id
     * @param  array $rows Each: name, title, order_no, id (optional)
     * @param  int   $actor_id
     */
    public function sync_course_signatories($course_id, array $rows, $actor_id = 0)
    {
        if ( ! $this->signatories_table_ready()) {
            log_message('debug', 'Phase3: certificate_signatories table not present; skipping signatory sync for course_id=' . (int) $course_id . '.');

            return;
        }

        $cid  = (int) $course_id;
        $now  = date('Y-m-d H:i:s');
        $keep = [];
        $ord  = 1;

        foreach ($rows as $row) {
            if ( ! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $payload = [
                'name'               => $name,
                'title'              => trim((string) ($row['title'] ?? '')),
                'order_no'           => isset($row['order_no']) ? (int) $row['order_no'] : $ord,
                'is_active'          => 1,
                'date_last_modified' => $now,
                'modified_by'        => (int) $actor_id,
            ];
            $ord++;
            $sid = (int) ($row['id'] ?? 0);
            if ($sid > 0) {
                $this->db->where('id', $sid)->where('course_id', $cid)->update('certificate_signatories', $payload);
                $keep[] = $sid;
            } else {
                $payload['course_id']    = $cid;
                $payload['date_encoded'] = $now;
                $payload['encoded_by']   = (int) $actor_id;
                $payload['archived']     = 0;
                $this->db->insert('certificate_signatories', $payload);
                $keep[] = (int) $this->db->insert_id();
            }
        }

        $this->db->where('course_id', $cid)->where('archived', 0);
        if ( ! empty($keep)) {
            $this->db->where_not_in('id', $keep);
        }
        $this->db->update('certificate_signatories', [
            'archived'           => 1,
            'date_last_modified' => $now,
            'modified_by'        => (int) $actor_id,
        ]);
    }

    /**
     * @param int    $signatory_id
     * @param int    $course_id
     * @param string $relative_path
     * @param int    $actor_id
     */
    public function update_signatory_image_path($signatory_id, $course_id, $relative_path, $actor_id = 0)
    {
        if ( ! $this->signatories_table_ready()
            || ! $this->db->field_exists('signature_image_path', 'certificate_signatories')) {
            return false;
        }

        $sid = (int) $signatory_id;
        $cid = (int) $course_id;
        if ($sid < 1 || $cid < 1) {
            return false;
        }

        return (bool) $this->db
            ->where('id', $sid)
            ->where('course_id', $cid)
            ->where('archived', 0)
            ->update('certificate_signatories', [
                'signature_image_path' => (string) $relative_path,
                'date_last_modified' => date('Y-m-d H:i:s'),
                'modified_by'        => (int) $actor_id,
            ]);
    }

    /**
     * Generate a unique certificate code: {PREFIX}-{YEAR}-{NNNN}
     * (Legacy KABAGA-{PREFIX}-{YEAR}-{NNNN} codes remain valid.)
     */
    private function _generate_code($course_id)
    {
        $prefix_result = $this->db
            ->select('certificate_prefix')
            ->where('id', (int) $course_id)
            ->limit(1)
            ->get('courses');
        $prefix_row = ($prefix_result && $prefix_result->num_rows() > 0)
            ? $prefix_result->row()
            : null;

        $raw_prefix = strtoupper(trim((string) ($prefix_row->certificate_prefix ?? '')));
        $norm       = preg_replace('/[^A-Z0-9]/', '', $raw_prefix);
        $prefix     = $norm !== '' ? substr($norm, 0, 12) : 'AUTO';
        $year       = date('Y');

        do {
            $like_new  = $prefix . '-' . $year . '-';
            $like_old  = 'KABAGA-' . $prefix . '-' . $year . '-';

            $row_new = $this->db
                ->select('certificate_code')
                ->from('lib_certificates')
                ->like('certificate_code', $like_new, 'after')
                ->where('archived', 0)
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get()
                ->row();

            $row_old = $this->db
                ->select('certificate_code')
                ->from('lib_certificates')
                ->like('certificate_code', $like_old, 'after')
                ->where('archived', 0)
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get()
                ->row();

            $next = 1;
            foreach ([$row_new, $row_old] as $row) {
                if ($row && ! empty($row->certificate_code)) {
                    $parts = explode('-', (string) $row->certificate_code);
                    $last  = (int) end($parts);
                    if ($last >= $next) {
                        $next = $last + 1;
                    }
                }
            }

            $code   = $like_new . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = $this->db
                ->where('certificate_code', $code)
                ->count_all_results('lib_certificates');
        } while ($exists > 0);

        return $code;
    }

    /**
     * Write an entry to lib_certificate_logs.
     */
    private function _log($certificate_id, $action, $action_by, $remarks = '')
    {
        $now = date('Y-m-d H:i:s');
        $this->db->insert('lib_certificate_logs', [
            'certificate_id'     => (int) $certificate_id,
            'action'             => $action,
            'action_by'          => $action_by ? (int) $action_by : null,
            'action_at'          => $now,
            'remarks'            => $remarks ?: null,
            'date_encoded'       => $now,
            'encoded_by'         => $action_by ? (int) $action_by : null,
            'archived'           => 0,
        ]);
    }

    // =========================================================
    // PUBLIC LOG ACTION (used by Certificates controller)
    // =========================================================

    /**
     * Write a log entry for a certificate action.
     * Replaces the private _log() call so controllers can log
     * without holding DB logic themselves.
     *
     * @param  int    $certificate_id
     * @param  string $action  issued | revoked | printed
     * @param  int    $action_by
     * @param  string $remarks
     * @return bool
     */
    public function log_action($certificate_id, $action, $action_by, $remarks = '')
    {
        return $this->_log($certificate_id, $action, $action_by, $remarks);
    }

}
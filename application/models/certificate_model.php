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
        $checks = [
            'all_modules_complete' => false,
            'post_assessments_done'=> false,
            'no_existing_cert'     => false,
        ];

        // ── 1. Already has certificate? ───────────────────────
        $existing = $this->get_certificate($user_id, $course_id);
        if ($existing) {
            $checks['no_existing_cert'] = false;
            return [
                'eligible' => false,
                'reason'   => 'already_issued',
                'checks'   => $checks,
            ];
        }
        $checks['no_existing_cert'] = true;

        // ── 2. All modules completed? ─────────────────────────
        $total_modules = (int) $this->db
            ->where('course_id', (int) $course_id)
            ->where('archived',  0)
            ->count_all_results('course_modules');

        if ($total_modules === 0) {
            return [
                'eligible' => false,
                'reason'   => 'no_modules',
                'checks'   => $checks,
            ];
        }

        $completed_modules = (int) $this->db
            ->select('COUNT(*) AS cnt', false)
            ->from('module_progress mp')
            ->join('course_modules cm', 'cm.id = mp.module_id', 'inner')
            ->where('cm.course_id', (int) $course_id)
            ->where('cm.archived',  0)
            ->where('mp.user_id',   (int) $user_id)
            ->where('mp.status',    'completed')
            ->get()->row()->cnt ?? 0;

        $checks['all_modules_complete'] = ($completed_modules >= $total_modules);

        if ( ! $checks['all_modules_complete']) {
            return [
                'eligible' => false,
                'reason'   => 'modules_incomplete',
                'checks'   => $checks,
                'progress' => ['done' => $completed_modules, 'total' => $total_modules],
            ];
        }

        // ── 3. All POST assessments attempted? ────────────────
        // Get all module IDs for this course
        $mod_result = $this->db
            ->select('id')
            ->where('course_id', (int) $course_id)
            ->where('archived',  0)
            ->get('course_modules');

        $module_ids = $mod_result ? array_column($mod_result->result_array(), 'id') : [];

        if ( ! empty($module_ids)) {
            // Get all POST assessments for these modules
            $post_assessments = $this->db
                ->select('la.id, la.module_id')
                ->from('lib_assessments la')
                ->where_in('la.module_id', $module_ids)
                ->where('la.type',     'post')
                ->where('la.archived', 0)
                ->get();

            $post_assessment_rows = $post_assessments ? $post_assessments->result() : [];

            if ( ! empty($post_assessment_rows)) {
                // For each post assessment, check: attempted AND score >= pass threshold
                $all_passed  = true;
                $not_passed  = [];
                foreach ($post_assessment_rows as $pa) {
                    // Get question IDs for this assessment
                    $q_ids_result = $this->db
                        ->select('id')
                        ->where('assessment_id', $pa->id)
                        ->where('archived',      0)
                        ->get('lib_assessment_questions');

                    if ( ! $q_ids_result || $q_ids_result->num_rows() === 0) {
                        // Assessment has no questions — skip it
                        continue;
                    }

                    $q_ids = array_column($q_ids_result->result_array(), 'id');

                    // Check if user has submitted answers
                    $answers = $this->db
                        ->select('score')
                        ->where_in('question_id', $q_ids)
                        ->where('user_id',  (int) $user_id)
                        ->where('archived', 0)
                        ->get('assessment_answers');

                    if ( ! $answers || $answers->num_rows() === 0) {
                        // Not attempted at all
                        $all_passed = false;
                        $not_passed[] = ['reason' => 'not_attempted', 'assessment_id' => $pa->id];
                        break;
                    }

                    // Calculate score (average of scored questions)
                    $sum    = 0;
                    $scored = 0;
                    $pending= 0;
                    foreach ($answers->result() as $row) {
                        if ($row->score !== null) {
                            $sum += (float) $row->score;
                            $scored++;
                        } else {
                            $pending++;
                        }
                    }

                    // If answers have pending (essay not graded yet), not eligible
                    if ($pending > 0) {
                        $all_passed = false;
                        $not_passed[] = ['reason' => 'pending_grading', 'assessment_id' => $pa->id];
                        break;
                    }

                    $min_pass = ka_assessment_pass_threshold();
                    // Must meet pass threshold
                    $avg_score = $scored > 0 ? round($sum / $scored, 2) : 0;
                    if ($avg_score < $min_pass) {
                        $all_passed = false;
                        $not_passed[] = [
                            'reason'        => 'score_too_low',
                            'assessment_id' => $pa->id,
                            'score'         => $avg_score,
                        ];
                        break;
                    }
                }

                $checks['post_assessments_done'] = $all_passed;

                if ( ! $all_passed) {
                    $first_fail = $not_passed[0] ?? [];
                    $fail_reason = isset($first_fail['reason']) ? $first_fail['reason'] : 'not_attempted';
                    if ($fail_reason === 'pending_grading') {
                        $reason = 'post_assessment_pending';
                    } elseif ($fail_reason === 'score_too_low') {
                        $reason = 'post_assessment_failed';
                    } else {
                        $reason = 'post_assessment_missing';
                    }
                    return [
                        'eligible'    => false,
                        'reason'      => $reason,
                        'checks'      => $checks,
                        'failed_info' => $first_fail,
                    ];
                }
            } else {
                // No post assessments configured — requirement auto-passes
                $checks['post_assessments_done'] = true;
            }
        } else {
            $checks['post_assessments_done'] = true;
        }

        return [
            'eligible' => true,
            'reason'   => 'eligible',
            'checks'   => $checks,
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

        // Generate unique certificate code: KBGA-YYYYMMDD-XXXX
        $code = $this->_generate_code();
        $now  = date('Y-m-d H:i:s');

        // Insert into lib_certificates
        $this->db->insert('lib_certificates', [
            'user_id'          => (int) $user_id,
            'course_id'        => (int) $course_id,
            'certificate_code' => $code,
            'issued_at'        => $now,
            'file_path'        => null,  // updated after PDF generation
            'date_encoded'     => $now,
            'encoded_by'       => (int) $issued_by,
            'archived'         => 0,
        ]);

        $certificate_id = (int) $this->db->insert_id();

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
                cc.name        AS category_name
            ', false)
            ->from('lib_certificates lc')
            ->join('aauth_users u',      'u.id  = lc.user_id',   'left')
            ->join('courses c',           'c.id  = lc.course_id', 'left')
            ->join('course_categories cc','cc.id = c.category_id','left')
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
            ->where('c.created_by', (int) $instructor_id)
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

    /**
     * Generate a unique certificate code: KBGA-YYYYMMDD-RAND6
     */
    private function _generate_code()
    {
        do {
            $code = 'KBGA-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
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
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Phase 2 course features: pivots, visibility, access, invitations.
 *
 * @property CI_DB_mysqli_driver $db
 */
class Course_phase2_model extends CI_Model {

    /** @var bool|null */
    private $_ready;

    /** @var CI_DB_mysqli_driver|null|false false = connection failed */
    private $_hrmis;

    /** @var object[]|null */
    private $_departments_cache;

    /** @var bool|null */
    private $_hrmis_connection_ok;

    /** @var array<string,int>|null lowercase department name => tbldepartment.id */
    private $_dept_name_map;

    /** @var bool */
    private static $_hrmis_debug_logged = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('course_phase2');
    }

    /**
     * HRMIS secondary connection (config/database.php → $db['hrmis']).
     *
     * @return CI_DB_mysqli_driver|null
     */
    protected function hrmis_db()
    {
        if ($this->_hrmis === false) {
            return null;
        }

        if ($this->_hrmis === null) {
            $CI =& get_instance();
            $db  = $CI->load->database('hrmis', true);

            if ( ! $db || ! $db->conn_id) {
                $this->_hrmis = false;
                log_message('error', 'HRMIS: database group "hrmis" failed to connect.');
                $this->_log_hrmis_connection_debug(null);

                return null;
            }

            $this->_hrmis = $db;
            $this->_log_hrmis_connection_debug($db);
        }

        return $this->_hrmis instanceof CI_DB_mysqli_driver ? $this->_hrmis : null;
    }

    /**
     * Can query tbldepartment (dropdown + ID validation).
     */
    public function hrmis_connection_ok()
    {
        if ($this->_hrmis_connection_ok !== null) {
            return $this->_hrmis_connection_ok;
        }

        $hr = $this->hrmis_db();
        if ( ! $hr || ! $hr->conn_id) {
            $this->_hrmis_connection_ok = false;
            log_message('debug', 'HRMIS connection_ok: false (no active connection on group "hrmis").');

            return false;
        }

        $probe = $hr->query('SELECT id, department FROM tbldepartment LIMIT 1');
        $this->_hrmis_connection_ok = ($probe !== false);

        if ( ! $this->_hrmis_connection_ok) {
            $err = $hr->error();
            log_message('debug', 'HRMIS connection_ok: false (tbldepartment probe failed: ' . ($err['message'] ?? 'unknown') . ').');
        }

        return $this->_hrmis_connection_ok;
    }

    public function hrmis_ready()
    {
        static $memo = null;
        if ($memo !== null) {
            return $memo;
        }

        if ( ! $this->hrmis_connection_ok()) {
            log_message('debug', 'HRMIS ready: false (department connection not available).');
            $memo = false;

            return false;
        }

        $hr = $this->hrmis_db();
        if ( ! $hr->table_exists('tblemployee')) {
            log_message('debug', 'HRMIS ready: false (tblemployee not found in ' . ($hr->database ?? 'hrmis') . ').');
            $memo = false;

            return false;
        }

        $probe = $hr->query('SELECT idno FROM tblemployee LIMIT 1');
        $memo  = ($probe !== false);

        if ( ! $memo) {
            log_message('debug', 'HRMIS ready: false (tblemployee probe query failed).');
        }

        return $memo;
    }

    /**
     * Temporary diagnostics for HRMIS department loading (log_threshold >= 2).
     *
     * @param CI_DB_mysqli_driver|null $db
     */
    protected function _log_hrmis_connection_debug($db)
    {
        if (self::$_hrmis_debug_logged) {
            return;
        }
        self::$_hrmis_debug_logged = true;

        if ( ! $db || ! $db->conn_id) {
            log_message('debug', 'HRMIS debug: group=hrmis conn_id=inactive');

            return;
        }

        log_message(
            'debug',
            'HRMIS debug: group=hrmis hostname=' . ($db->hostname ?? '?')
            . ' database=' . ($db->database ?? '?')
            . ' conn_id=active dbdriver=' . ($db->dbdriver ?? '?')
        );
    }

    /**
     * @param CI_DB_result|false $result
     * @param object[]           $mapped
     */
    protected function _log_hrmis_department_query($result, array $mapped)
    {
        $count = ($result && is_object($result) && method_exists($result, 'num_rows'))
            ? (int) $result->num_rows()
            : 0;

        log_message('debug', 'HRMIS tbldepartment query row_count=' . $count . ' mapped=' . count($mapped));

        foreach (array_slice($mapped, 0, 5) as $dept) {
            log_message(
                'debug',
                'HRMIS dept sample: id=' . (int) $dept->id . ' department=' . (string) $dept->name
            );
        }
    }

    public function schema_ready()
    {
        if ($this->_ready === null) {
            $this->_ready = $this->db->table_exists('course_categories_map')
                && $this->db->field_exists('access_type', 'courses');
        }

        return $this->_ready;
    }

    // -------------------------------------------------------------------------
    // Pivots — categories
    // -------------------------------------------------------------------------

    public function get_course_category_ids($course_id)
    {
        if ( ! $this->schema_ready()) {
            $c = $this->db->select('category_id')->where('id', (int) $course_id)->get('courses', 1)->row();
            return ($c && (int) $c->category_id > 0) ? [(int) $c->category_id] : [];
        }

        $this->db->select('category_id')->where('course_id', (int) $course_id);
        $this->_where_pivot_active('course_categories_map');
        $r = $this->db->get('course_categories_map');

        if ( ! $r || $r->num_rows() === 0) {
            $c = $this->db->select('category_id')->where('id', (int) $course_id)->get('courses', 1)->row();

            return ($c && (int) $c->category_id > 0) ? [(int) $c->category_id] : [];
        }

        return array_map('intval', array_column($r->result_array(), 'category_id'));
    }

    public function sync_course_categories($course_id, array $category_ids, $actor_id = 0)
    {
        $cid = (int) $course_id;
        if ($cid < 1 || ! $this->schema_ready()) {
            return false;
        }

        $ids = course_phase2_normalize_ids($category_ids);
        $this->_sync_pivot('course_categories_map', $cid, 'category_id', $ids, (int) $actor_id);

        if ( ! empty($ids)) {
            $this->db->where('id', $cid)->update('courses', ['category_id' => (int) $ids[0]]);
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Pivots — instructors
    // -------------------------------------------------------------------------

    public function get_course_instructor_ids($course_id)
    {
        if ( ! $this->schema_ready() || ! $this->db->table_exists('course_instructors')) {
            return [];
        }

        $this->db->select('user_id')->where('course_id', (int) $course_id);
        $this->_where_pivot_active('course_instructors');
        $r = $this->db->get('course_instructors');

        $ids = ($r && $r->num_rows() > 0)
            ? array_map('intval', array_column($r->result_array(), 'user_id'))
            : [];

        return array_values(array_unique(array_filter($ids)));
    }

    public function sync_course_instructors($course_id, array $user_ids, $actor_id = 0)
    {
        $cid = (int) $course_id;
        if ($cid < 1 || ! $this->schema_ready()) {
            return false;
        }

        $ids = course_phase2_normalize_ids($user_ids);

        $this->_sync_pivot('course_instructors', $cid, 'user_id', $ids, (int) $actor_id);

        return true;
    }

    public function user_manages_course($user_id, $course_id)
    {
        $uid = (int) $user_id;
        $cid = (int) $course_id;
        if ($uid < 1 || $cid < 1) {
            return false;
        }

        if ( ! $this->schema_ready() || ! $this->db->table_exists('course_instructors')) {
            return false;
        }

        $c = $this->db->select('id')->where('id', $cid)->where('archived', 0)->get('courses', 1)->row();
        if ( ! $c) {
            return false;
        }

        $this->db->where('course_id', $cid)->where('user_id', $uid);
        $this->_where_pivot_active('course_instructors');

        return (int) $this->db->count_all_results('course_instructors') > 0;
    }

    // -------------------------------------------------------------------------
    // Pivots — departments / professions
    // -------------------------------------------------------------------------

    public function get_course_department_ids($course_id)
    {
        if ( ! $this->schema_ready() || ! $this->db->table_exists('course_departments')) {
            return [];
        }

        $this->db->select('department_id')->where('course_id', (int) $course_id);
        $this->_where_pivot_active('course_departments');
        $r = $this->db->get('course_departments');

        return ($r && $r->num_rows() > 0)
            ? array_map('intval', array_column($r->result_array(), 'department_id'))
            : [];
    }

    public function sync_course_departments($course_id, array $department_ids, $actor_id = 0)
    {
        if ( ! $this->schema_ready()) {
            return false;
        }

        if ( ! $this->hrmis_connection_ok()) {
            log_message(
                'debug',
                'HRMIS unavailable: skipping course_departments sync for course ' . (int) $course_id . ' (existing mappings preserved).'
            );

            return true;
        }

        $ids = $this->filter_valid_hrmis_department_ids($department_ids);

        $this->_sync_pivot(
            'course_departments',
            (int) $course_id,
            'department_id',
            $ids,
            (int) $actor_id
        );

        return true;
    }

    public function get_course_profession_ids($course_id)
    {
        if ( ! $this->schema_ready() || ! $this->db->table_exists('course_professions')) {
            return [];
        }

        $this->db->select('profession_id')->where('course_id', (int) $course_id);
        $this->_where_pivot_active('course_professions');
        $r = $this->db->get('course_professions');

        return ($r && $r->num_rows() > 0)
            ? array_map('intval', array_column($r->result_array(), 'profession_id'))
            : [];
    }

    public function sync_course_professions($course_id, array $profession_ids, $actor_id = 0)
    {
        if ( ! $this->schema_ready()) {
            return false;
        }

        $this->_sync_pivot(
            'course_professions',
            (int) $course_id,
            'profession_id',
            course_phase2_normalize_ids($profession_ids),
            (int) $actor_id
        );

        return true;
    }

    /**
     * Departments for manage-course UI — live from HRMIS tbldepartment.
     *
     * @return object[] {id, name}
     */
    public function get_departments()
    {
        if ($this->_departments_cache !== null) {
            return $this->_departments_cache;
        }

        $hr = $this->hrmis_db();
        if ( ! $hr || ! $hr->conn_id) {
            log_message('error', 'HRMIS get_departments: connection unavailable (group "hrmis").');
            $this->_departments_cache = [];

            return [];
        }

        $r = $hr->query('SELECT id, department FROM tbldepartment ORDER BY department ASC');

        if ($r === false) {
            $err = $hr->error();
            log_message('error', 'HRMIS get_departments query failed: ' . ($err['message'] ?? 'unknown'));
            $this->_log_hrmis_department_query(false, []);
            $this->_departments_cache = [];

            return [];
        }

        $out = [];
        foreach ($r->result() as $row) {
            $name = trim((string) ($row->department ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = (object) [
                'id'   => (int) $row->id,
                'name' => $name,
            ];
        }

        $this->_log_hrmis_department_query($r, $out);
        $this->_departments_cache = $out;

        return $out;
    }

    /**
     * Keep only department IDs that exist in HRMIS tbldepartment.
     *
     * @param  int[]|mixed $department_ids
     * @return int[]
     */
    public function filter_valid_hrmis_department_ids($department_ids)
    {
        $ids = course_phase2_normalize_ids($department_ids);
        if (empty($ids)) {
            return [];
        }

        if ( ! $this->hrmis_connection_ok()) {
            return [];
        }

        $valid = [];
        foreach ($this->get_departments() as $dept) {
            $valid[(int) $dept->id] = true;
        }

        return array_values(array_filter($ids, static function ($id) use ($valid) {
            return isset($valid[(int) $id]);
        }));
    }

    /**
     * @return array<string,int> lowercase trimmed department name => tbldepartment.id
     */
    protected function hrmis_department_name_map()
    {
        if ($this->_dept_name_map !== null) {
            return $this->_dept_name_map;
        }

        $this->_dept_name_map = [];
        foreach ($this->get_departments() as $dept) {
            $key = strtolower(trim((string) $dept->name));
            if ($key !== '') {
                $this->_dept_name_map[$key] = (int) $dept->id;
            }
        }

        return $this->_dept_name_map;
    }

    /**
     * @param  int[] $department_ids tbldepartment.id values
     * @return string[]
     */
    public function get_department_names_by_ids(array $department_ids)
    {
        $want = array_flip(course_phase2_normalize_ids($department_ids));
        if (empty($want)) {
            return [];
        }

        $names = [];
        foreach ($this->get_departments() as $dept) {
            $id = (int) $dept->id;
            if (isset($want[$id])) {
                $names[] = (string) $dept->name;
            }
        }

        return $names;
    }

    /**
     * Whether a learner falls within a course's department visibility rules.
     * Empty course mapping = visible to all.
     *
     * @param  int         $course_id
     * @param  object|null $user
     * @return bool
     */
    public function user_matches_course_departments($course_id, $user)
    {
        $allowed = $this->get_course_department_ids((int) $course_id);
        if (empty($allowed)) {
            return true;
        }

        $dept_id = $this->resolve_user_department_id($user);

        return $dept_id > 0 && in_array($dept_id, $allowed, true);
    }

    /**
     * Job titles for manage-course UI — distinct active HRMIS tblemployee.Position values.
     *
     * @return object[] {id, name} id = course_phase2_hrmis_position_key(Position)
     */
    public function get_professions()
    {
        $hr = $this->hrmis_db();
        if ( ! $hr || ! $hr->table_exists('tblemployee')) {
            return [];
        }

        $r = $hr
            ->select('Position')
            ->where('status', 'ACTIVE')
            ->where('Position IS NOT NULL', null, false)
            ->where("TRIM(Position) <> ''", null, false)
            ->group_by('Position')
            ->order_by('Position', 'ASC')
            ->get('tblemployee');

        if ( ! $r || $r->num_rows() < 1) {
            return [];
        }

        $out = [];
        foreach ($r->result() as $row) {
            $title = trim((string) ($row->Position ?? ''));
            if ($title === '') {
                continue;
            }
            $key = course_phase2_hrmis_position_key($title);
            if ($key < 1) {
                continue;
            }
            $out[$key] = (object) [
                'id'   => $key,
                'name' => $title,
            ];
        }

        return array_values($out);
    }

    /**
     * Resolve HRMIS tbldepartment.id for an employee (live lookup).
     *
     * @param object|null $user aauth_users row
     * @return int
     */
    public function resolve_user_department_id($user)
    {
        if ( ! $user) {
            return 0;
        }

        $hr_emp = $this->_hrmis_employee_row_for_user($user);
        $dept_name = $hr_emp
            ? trim((string) ($hr_emp->Department ?? ''))
            : trim((string) ($user->office ?? ''));

        return $this->resolve_hrmis_department_id_by_name($dept_name);
    }

    /**
     * Resolve position key for an employee from HRMIS tblemployee.Position.
     *
     * @param object|null $user
     * @return int
     */
    public function resolve_user_profession_id($user)
    {
        if ( ! $user) {
            return 0;
        }

        $hr_emp = $this->_hrmis_employee_row_for_user($user);
        if ( ! $hr_emp || empty($hr_emp->Position)) {
            return 0;
        }

        return course_phase2_hrmis_position_key((string) $hr_emp->Position);
    }

    /**
     * @param string $department_name HRMIS tbldepartment.department
     * @return int tbldepartment.id
     */
    public function resolve_hrmis_department_id_by_name($department_name)
    {
        $name = strtolower(trim((string) $department_name));
        if ($name === '') {
            return 0;
        }

        if ( ! $this->hrmis_ready()) {
            return 0;
        }

        $map = $this->hrmis_department_name_map();

        return (int) ($map[$name] ?? 0);
    }

    /**
     * @param object|null $user
     * @return object|null HRMIS tblemployee row
     */
    protected function _hrmis_employee_row_for_user($user)
    {
        $emp_id = trim((string) ($user->employee_id ?? ''));
        if ($emp_id === '') {
            return null;
        }

        $hr = $this->hrmis_db();
        if ( ! $hr || ! $hr->table_exists('tblemployee')) {
            return null;
        }

        $r = $hr
            ->where('idno', $emp_id)
            ->where('status', 'ACTIVE')
            ->get('tblemployee', 1);

        return ($r && $r->num_rows() > 0) ? $r->row() : null;
    }

    // -------------------------------------------------------------------------
    // Visibility & access
    // -------------------------------------------------------------------------

    public function get_course_access_type($course)
    {
        if ( ! $course) {
            return 'approval_required';
        }
        if ($this->schema_ready() && ! empty($course->access_type)) {
            $t = strtolower(trim((string) $course->access_type));
            if (in_array($t, course_phase2_access_types(), true)) {
                return $t;
            }
        }

        return 'approval_required';
    }

    public function get_course_publish_status($course)
    {
        if ( ! $course) {
            return 'published';
        }
        if ($this->schema_ready() && ! empty($course->publish_status)) {
            $s = strtolower(trim((string) $course->publish_status));
            if (in_array($s, course_phase2_publish_statuses(), true)) {
                return $s;
            }
        }

        return 'published';
    }

    /**
     * Apply employee catalog filters on active query builder (alias c).
     *
     * @param object|null $viewer aauth_users row
     */
    public function apply_catalog_filters_for_viewer($viewer = null)
    {
        if ( ! $viewer || ! in_array(strtolower($viewer->role ?? ''), ['employee', 'student'], true)) {
            return;
        }

        $this->_apply_pivot_visibility_filter(
            'course_departments',
            'department_id',
            $this->resolve_user_department_id($viewer)
        );

        $this->_apply_pivot_visibility_filter(
            'course_professions',
            'profession_id',
            $this->resolve_user_profession_id($viewer)
        );
    }

    /**
     * Catalog SQL: no pivot rows = visible to all; otherwise user value must match.
     *
     * @param string $table
     * @param string $value_column
     * @param int    $user_value_id
     */
    private function _apply_pivot_visibility_filter($table, $value_column, $user_value_id)
    {
        if ( ! $this->db->table_exists($table)) {
            return;
        }

        $alias     = ($table === 'course_departments') ? 'cd' : 'cp';
        $not_exist = 'NOT EXISTS (SELECT 1 FROM ' . $table . ' ' . $alias
            . ' WHERE ' . $alias . '.course_id = c.id AND ' . $alias . '.archived = 0)';
        $uid       = (int) $user_value_id;

        if ($uid > 0) {
            $match = 'EXISTS (SELECT 1 FROM ' . $table . ' ' . $alias
                . ' WHERE ' . $alias . '.course_id = c.id AND ' . $alias . '.archived = 0'
                . ' AND ' . $alias . '.' . $value_column . ' = ' . $uid . ')';
            $this->db->where('(' . $not_exist . ' OR ' . $match . ')', null, false);
        } else {
            $this->db->where('(' . $not_exist . ')', null, false);
        }
    }

    /**
     * @param object|null $viewer
     * @return array{allowed:bool,message:string}
     */
    public function employee_may_view_course($course_id, $viewer = null, $allow_invited = false)
    {
        $cid = (int) $course_id;
        $course = $this->db->where('id', $cid)->where('archived', 0)->get('courses', 1)->row();
        if ( ! $course) {
            return ['allowed' => false, 'message' => 'Course not found.'];
        }

        if ( ! $viewer || ! in_array(strtolower($viewer->role ?? ''), ['employee', 'student'], true)) {
            return ['allowed' => true, 'message' => ''];
        }

        $has_invitation = $allow_invited
            && $this->user_has_pending_or_accepted_invitation((int) ($viewer->id ?? 0), $cid);

        $pub = $this->get_course_publish_status($course);
        if ($pub !== 'published') {
            return ['allowed' => false, 'message' => 'This course is not published yet.'];
        }

        $access = $this->get_course_access_type($course);
        if ($access === 'hidden' && ! $has_invitation) {
            return ['allowed' => false, 'message' => 'This course is not available in the catalog.'];
        }

        if ($has_invitation) {
            return ['allowed' => true, 'message' => ''];
        }

        $dept_id = $this->resolve_user_department_id($viewer);
        $allowed_depts = $this->get_course_department_ids($cid);
        if ( ! empty($allowed_depts) && ! in_array($dept_id, $allowed_depts, true)) {
            return ['allowed' => false, 'message' => 'This course is restricted to specific departments.'];
        }

        $prof_id = $this->resolve_user_profession_id($viewer);
        $allowed_profs = $this->get_course_profession_ids($cid);
        if ( ! empty($allowed_profs) && ! in_array($prof_id, $allowed_profs, true)) {
            return ['allowed' => false, 'message' => 'This course is restricted to specific job titles.'];
        }

        return ['allowed' => true, 'message' => ''];
    }

    /**
     * Initial enrollment status when a learner requests access.
     */
    public function enrollment_status_for_request($course)
    {
        $access = $this->get_course_access_type($course);
        if ($access === 'open') {
            return 'approved';
        }

        return 'pending';
    }

    public function user_has_pending_or_accepted_invitation($user_id, $course_id)
    {
        if ( ! $this->schema_ready() || ! $this->db->table_exists('course_invitations')) {
            return false;
        }

        $uid = (int) $user_id;
        $cid = (int) $course_id;
        $user = $this->db->select('email')->where('id', $uid)->get('aauth_users', 1)->row();
        $email = ($user && ! empty($user->email)) ? strtolower(trim((string) $user->email)) : '';

        $this->db->where('course_id', $cid);
        $this->db->where_in('status', ['pending', 'accepted']);
        if ($this->db->field_exists('archived', 'course_invitations')) {
            $this->db->where('archived', 0);
        }
        $this->db->group_start();
        $this->db->where('user_id', $uid);
        if ($email !== '') {
            $this->db->or_where('email', $email);
        }
        $this->db->group_end();

        return (int) $this->db->count_all_results('course_invitations') > 0;
    }

    public function user_has_accepted_invitation($user_id, $course_id)
    {
        if ( ! $this->schema_ready() || ! $this->db->table_exists('course_invitations')) {
            return false;
        }

        $this->db
            ->where('course_id', (int) $course_id)
            ->where('user_id', (int) $user_id)
            ->where('status', 'accepted');
        if ($this->db->field_exists('archived', 'course_invitations')) {
            $this->db->where('archived', 0);
        }

        return (int) $this->db->count_all_results('course_invitations') > 0;
    }

    public function get_pending_invitation_for_user($user_id, $course_id)
    {
        if ( ! $this->schema_ready() || ! $this->db->table_exists('course_invitations')) {
            return null;
        }

        $uid = (int) $user_id;
        $user = $this->db->select('email')->where('id', $uid)->get('aauth_users', 1)->row();
        $email = ($user && ! empty($user->email)) ? strtolower(trim((string) $user->email)) : '';

        $this->db
            ->where('course_id', (int) $course_id)
            ->where('status', 'pending');
        if ($this->db->field_exists('archived', 'course_invitations')) {
            $this->db->where('archived', 0);
        }

        $this->db->group_start()->where('user_id', $uid);
        if ($email !== '') {
            $this->db->or_where('email', $email);
        }
        $this->db->group_end();

        $r = $this->db->order_by('created_at', 'DESC')->get('course_invitations', 1);

        return ($r && $r->num_rows() > 0) ? $r->row() : null;
    }

    /**
     * Pending invitations for a learner (My Learning → Invited Courses).
     *
     * @param  int $user_id
     * @return object[]
     */
    public function get_invited_courses_for_user($user_id)
    {
        if ( ! $this->schema_ready() || ! $this->db->table_exists('course_invitations')) {
            return [];
        }

        $uid = (int) $user_id;
        if ($uid < 1) {
            return [];
        }

        $user = $this->db->select('email')->where('id', $uid)->get('aauth_users', 1)->row();
        $email = ($user && ! empty($user->email)) ? strtolower(trim((string) $user->email)) : '';

        $this->db
            ->select('
                ci.id AS invitation_id,
                ci.course_id,
                ci.status AS invitation_status,
                c.title,
                c.description,
                c.category_id,
                cc.name AS category_name
            ', false)
            ->from('course_invitations ci')
            ->join('courses c', 'c.id = ci.course_id', 'inner')
            ->join('course_categories cc', 'cc.id = c.category_id', 'left')
            ->where('ci.status', 'pending')
            ->where('c.archived', 0);

        if ($this->db->field_exists('archived', 'course_invitations')) {
            $this->db->where('ci.archived', 0);
        }

        $this->db->group_start()->where('ci.user_id', $uid);
        if ($email !== '') {
            $this->db->or_where('ci.email', $email);
        }
        $this->db->group_end();

        $r = $this->db->order_by('ci.created_at', 'DESC')->get();
        if ( ! $r || $r->num_rows() === 0) {
            return [];
        }

        $CI =& get_instance();
        $CI->load->model('Course_model', 'course_model');
        /** @var Course_model $course_model */
        $course_model = $CI->{'course_model'};

        $out = [];
        $seen = [];
        foreach ($r->result() as $row) {
            $cid = (int) $row->course_id;
            if ($cid < 1 || isset($seen[$cid])) {
                continue;
            }
            if ($course_model->has_approved_enrollment($uid, $cid)) {
                continue;
            }
            $seen[$cid] = true;
            $row->module_count = (int) $course_model->count_modules($cid);
            $out[] = $row;
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Invitations
    // -------------------------------------------------------------------------

    public function get_course_invitations($course_id)
    {
        if ( ! $this->schema_ready() || ! $this->db->table_exists('course_invitations')) {
            return [];
        }

        $r = $this->db
            ->select('ci.*, u.fullname, u.employee_id')
            ->from('course_invitations ci')
            ->join('aauth_users u', 'u.id = ci.user_id', 'left')
            ->where('ci.course_id', (int) $course_id);
        if ($this->db->field_exists('archived', 'course_invitations')) {
            $this->db->where('ci.archived', 0);
        }
        $r = $this->db->order_by('ci.created_at', 'DESC')->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * @return array{ok:bool,message:string,id?:int}
     */
    public function create_invitation($course_id, $invited_by, $email = '', $user_id = 0)
    {
        if ( ! $this->schema_ready() || ! $this->db->table_exists('course_invitations')) {
            return ['ok' => false, 'message' => 'Invitations are not available on this server.'];
        }

        $cid = (int) $course_id;
        $uid = (int) $user_id;
        $email = strtolower(trim((string) $email));

        if ($cid < 1) {
            return ['ok' => false, 'message' => 'Invalid course.'];
        }
        if ($uid < 1 && $email === '') {
            return ['ok' => false, 'message' => 'Enter an email or select a user.'];
        }

        if ($uid > 0) {
            $target = $this->db->where('id', $uid)->where('DELETED', 0)->get('aauth_users', 1)->row();
            if ( ! $target) {
                return ['ok' => false, 'message' => 'User not found.'];
            }
            if ( ! $this->user_is_invitation_eligible($cid, $target)) {
                return ['ok' => false, 'message' => 'User is not eligible for this course based on HRMIS restrictions.'];
            }
        }

        if ($this->invitation_exists($cid, $uid, $email)) {
            return ['ok' => true, 'message' => 'Invitation already exists.', 'id' => 0, 'duplicate' => true];
        }

        $token = bin2hex(random_bytes(16));
        $row = array_merge([
            'course_id'   => $cid,
            'user_id'     => $uid > 0 ? $uid : null,
            'email'       => $email !== '' ? $email : null,
            'invited_by'  => (int) $invited_by,
            'token'       => $token,
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
        ], $this->db->field_exists('date_encoded', 'course_invitations')
            ? course_phase2_audit_insert_row((int) $invited_by)
            : []);
        $this->db->insert('course_invitations', $row);

        $id = (int) $this->db->insert_id();
        if ($id < 1) {
            return ['ok' => false, 'message' => 'Could not create invitation.'];
        }

        return ['ok' => true, 'message' => 'Invitation sent.', 'id' => $id, 'token' => $token];
    }

    public function invitation_exists($course_id, $user_id = 0, $email = '')
    {
        if ( ! $this->schema_ready() || ! $this->db->table_exists('course_invitations')) {
            return false;
        }

        $this->db->where('course_id', (int) $course_id)->where_in('status', ['pending', 'accepted']);
        if ($this->db->field_exists('archived', 'course_invitations')) {
            $this->db->where('archived', 0);
        }

        $this->db->group_start();
        if ((int) $user_id > 0) {
            $this->db->where('user_id', (int) $user_id);
        }
        $email = strtolower(trim((string) $email));
        if ($email !== '') {
            ((int) $user_id > 0) ? $this->db->or_where('email', $email) : $this->db->where('email', $email);
        }
        $this->db->group_end();

        return (int) $this->db->count_all_results('course_invitations') > 0;
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public function accept_invitation($invitation_id, $user_id)
    {
        if ( ! $this->schema_ready()) {
            return ['ok' => false, 'message' => 'Invitations are not available.'];
        }

        $row = $this->db->where('id', (int) $invitation_id)->get('course_invitations', 1)->row();
        if ( ! $row || $row->status !== 'pending' || (int) ($row->archived ?? 0) === 1) {
            return ['ok' => false, 'message' => 'Invitation not found or already handled.'];
        }

        if ((int) ($row->user_id ?? 0) > 0 && (int) $row->user_id !== (int) $user_id) {
            return ['ok' => false, 'message' => 'This invitation is for another user.'];
        }
        if ((int) ($row->user_id ?? 0) < 1 && ! empty($row->email)) {
            $recipient = $this->db->select('email')->where('id', (int) $user_id)->get('aauth_users', 1)->row();
            $recipient_email = ($recipient && ! empty($recipient->email)) ? strtolower(trim((string) $recipient->email)) : '';
            if ($recipient_email === '' || $recipient_email !== strtolower(trim((string) $row->email))) {
                return ['ok' => false, 'message' => 'This invitation is for another email address.'];
            }
        }

        $course = $this->db->where('id', (int) $row->course_id)->where('archived', 0)->get('courses', 1)->row();
        if ( ! $course) {
            return ['ok' => false, 'message' => 'Course not found.'];
        }
        if ($this->get_course_publish_status($course) !== 'published') {
            return ['ok' => false, 'message' => 'This course is not published yet.'];
        }

        $update = [
            'status'       => 'accepted',
            'user_id'      => (int) $user_id,
            'responded_at' => date('Y-m-d H:i:s'),
        ];
        if ($this->db->field_exists('date_last_modified', 'course_invitations')) {
            $update = array_merge($update, course_phase2_audit_update_row((int) $user_id));
        }
        $this->db->where('id', (int) $row->id)->update('course_invitations', $update);

        $this->approve_enrollment_from_invitation((int) $user_id, (int) $row->course_id);

        return ['ok' => true, 'message' => 'Invitation accepted.', 'course_id' => (int) $row->course_id];
    }

    public function reject_invitation($invitation_id, $user_id)
    {
        if ( ! $this->schema_ready() || ! $this->db->table_exists('course_invitations')) {
            return ['ok' => false, 'message' => 'Invitations are not available.'];
        }

        $row = $this->db->where('id', (int) $invitation_id)->get('course_invitations', 1)->row();
        if ( ! $row || $row->status !== 'pending' || (int) ($row->archived ?? 0) === 1) {
            return ['ok' => false, 'message' => 'Invitation not found or already handled.'];
        }
        if ((int) ($row->user_id ?? 0) > 0 && (int) $row->user_id !== (int) $user_id) {
            return ['ok' => false, 'message' => 'This invitation is for another user.'];
        }
        if ((int) ($row->user_id ?? 0) < 1 && ! empty($row->email)) {
            $recipient = $this->db->select('email')->where('id', (int) $user_id)->get('aauth_users', 1)->row();
            $recipient_email = ($recipient && ! empty($recipient->email)) ? strtolower(trim((string) $recipient->email)) : '';
            if ($recipient_email === '' || $recipient_email !== strtolower(trim((string) $row->email))) {
                return ['ok' => false, 'message' => 'This invitation is for another email address.'];
            }
        }

        $update = [
            'status'       => 'declined',
            'responded_at' => date('Y-m-d H:i:s'),
        ];
        if ($this->db->field_exists('date_last_modified', 'course_invitations')) {
            $update = array_merge($update, course_phase2_audit_update_row((int) $user_id));
        }
        $this->db->where('id', (int) $row->id)->update('course_invitations', $update);

        return ['ok' => true, 'message' => 'Invitation declined.', 'course_id' => (int) $row->course_id];
    }

    public function approve_enrollment_from_invitation($user_id, $course_id)
    {
        $uid = (int) $user_id;
        $cid = (int) $course_id;
        if ($uid < 1 || $cid < 1) {
            return false;
        }

        $row = $this->db->where('user_id', $uid)->where('course_id', $cid)->get('enrollments', 1)->row();
        $data = [
            'status'      => 'approved',
            'enrolled_at' => date('Y-m-d H:i:s'),
        ];

        if ($row) {
            return (bool) $this->db->where('id', (int) $row->id)->update('enrollments', $data);
        }

        $data['user_id'] = $uid;
        $data['course_id'] = $cid;

        return (bool) $this->db->insert('enrollments', $data);
    }

    public function user_is_invitation_eligible($course_id, $user)
    {
        if ( ! $user || ! in_array(strtolower((string) ($user->role ?? '')), ['employee', 'student'], true)) {
            return false;
        }
        if ((int) ($user->DELETED ?? 0) === 1 || (string) ($user->status ?? '') !== 'active') {
            return false;
        }

        $dept_id = $this->resolve_user_department_id($user);
        $allowed_depts = $this->get_course_department_ids((int) $course_id);
        if ( ! empty($allowed_depts) && ! in_array($dept_id, $allowed_depts, true)) {
            return false;
        }

        $prof_id = $this->resolve_user_profession_id($user);
        $allowed_profs = $this->get_course_profession_ids((int) $course_id);
        if ( ! empty($allowed_profs) && ! in_array($prof_id, $allowed_profs, true)) {
            return false;
        }

        return true;
    }

    public function get_invitable_users($course_id, array $department_ids = [])
    {
        $rows = $this->db
            ->select('id, employee_id, fullname, role, office, status, DELETED')
            ->where('DELETED', 0)
            ->where('status', 'active')
            ->where_in('role', ['employee', 'student'])
            ->order_by('fullname', 'ASC')
            ->get('aauth_users')
            ->result();

        $dept_filter = course_phase2_normalize_ids($department_ids);
        $course_depts = $this->get_course_department_ids((int) $course_id);
        if ( ! empty($course_depts)) {
            if (empty($dept_filter)) {
                $dept_filter = $course_depts;
            } else {
                $dept_filter = array_values(array_intersect($dept_filter, $course_depts));
            }
        }

        $out = [];
        foreach ($rows as $u) {
            if ( ! empty($dept_filter)) {
                $dept_id = $this->resolve_user_department_id($u);
                if ( ! in_array($dept_id, $dept_filter, true)) {
                    continue;
                }
            }
            if ($this->user_is_invitation_eligible((int) $course_id, $u)) {
                $out[] = $u;
            }
        }

        return $out;
    }

    /**
     * Persist phase-2 fields from manage course POST.
     */
    public function save_course_meta_from_post($course_id, array $post, $actor_id)
    {
        $cid = (int) $course_id;
        if ($cid < 1) {
            return;
        }

        if ($this->schema_ready()) {
            $current = $this->db->where('id', $cid)->get('courses', 1)->row();
            $access = strtolower(trim((string) ($post['access_type'] ?? $this->get_course_access_type($current))));
            if ( ! in_array($access, course_phase2_access_types(), true)) {
                $access = 'approval_required';
            }
            $this->db->where('id', $cid)->update('courses', [
                'access_type' => $access,
            ]);
        }

        $cat_ids = course_phase2_normalize_ids($post['category_ids'] ?? []);
        if (empty($cat_ids) && ! empty($post['category_id'])) {
            $cat_ids = [(int) $post['category_id']];
        }
        $actor = (int) $actor_id;
        $this->sync_course_categories($cid, $cat_ids, $actor);

        $this->sync_course_instructors($cid, course_phase2_normalize_ids($post['instructor_ids'] ?? []), $actor);
        $this->sync_course_departments($cid, course_phase2_normalize_ids($post['department_ids'] ?? []), $actor);
        $this->sync_course_professions($cid, course_phase2_normalize_ids($post['profession_ids'] ?? []), $actor);
    }

    /**
     * @return object|null
     */
    public function load_course_phase2_form_data($course_id)
    {
        $cid = (int) $course_id;
        if ($cid < 1) {
            return null;
        }

        $course = $this->db->where('id', $cid)->get('courses', 1)->row();
        if ( ! $course) {
            return null;
        }

        return (object) [
            'category_ids'    => $this->get_course_category_ids($cid),
            'instructor_ids'  => $this->get_course_instructor_ids($cid),
            'department_ids'  => $this->get_course_department_ids($cid),
            'profession_ids'  => $this->get_course_profession_ids($cid),
            'access_type'     => $this->get_course_access_type($course),
            'publish_status'  => $this->get_course_publish_status($course),
            'invitations'     => $this->get_course_invitations($cid),
        ];
    }

    // -------------------------------------------------------------------------
    // Instructor course list SQL fragment
    // -------------------------------------------------------------------------

    public function restrict_query_to_instructor_courses($instructor_user_id)
    {
        $uid = (int) $instructor_user_id;
        if ($uid < 1) {
            $this->db->where('1 = 0', null, false);

            return;
        }

        if ($this->schema_ready() && $this->db->table_exists('course_instructors')) {
            $this->db->where(
                'EXISTS (SELECT 1 FROM course_instructors ci WHERE ci.course_id = c.id AND ci.archived = 0 AND ci.user_id = ' . $uid . ')',
                null,
                false
            );
        } else {
            $this->db->where('1 = 0', null, false);
        }
    }

    /**
     * Course IDs assigned to an instructor via course_instructors pivot.
     *
     * @param  int   $instructor_user_id
     * @return int[]
     */
    public function get_instructor_course_ids($instructor_user_id)
    {
        $uid = (int) $instructor_user_id;
        if ($uid < 1) {
            return [];
        }

        if ( ! $this->schema_ready() || ! $this->db->table_exists('course_instructors')) {
            return [];
        }

        $this->db
            ->distinct()
            ->select('ci.course_id', false)
            ->from('course_instructors ci')
            ->join('courses c', 'c.id = ci.course_id', 'inner')
            ->where('ci.user_id', $uid)
            ->where('ci.archived', 0)
            ->where('c.archived', 0);

        $r = $this->db->get();
        if ( ! $r || $r->num_rows() === 0) {
            return [];
        }

        return array_values(array_unique(array_map('intval', array_column($r->result_array(), 'course_id'))));
    }

    public function apply_category_filter($filter_cat)
    {
        $fc = (int) $filter_cat;
        if ($fc < 1) {
            return;
        }

        if ($this->schema_ready() && $this->db->table_exists('course_categories_map')) {
            $this->db->group_start();
            $this->db->where('c.category_id', $fc);
            $this->db->or_where(
                'EXISTS (SELECT 1 FROM course_categories_map m WHERE m.course_id = c.id AND m.archived = 0 AND m.category_id = ' . $fc . ')',
                null,
                false
            );
            $this->db->group_end();
        } else {
            $this->db->where('c.category_id', $fc);
        }
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    /**
     * Restrict query to non-archived pivot rows when the column exists.
     *
     * @param string $table
     * @return void
     */
    private function _where_pivot_active($table)
    {
        if ($this->db->field_exists('archived', $table)) {
            $this->db->where('archived', 0);
        }
    }

    /**
     * Soft-sync pivot rows: archive removed mappings, insert or restore selected ones.
     *
     * @param string $table
     * @param int    $course_id
     * @param string $value_column
     * @param int[]  $ids
     * @param int    $actor_id
     * @return void
     */
    private function _sync_pivot($table, $course_id, $value_column, array $ids, $actor_id = 0)
    {
        $cid      = (int) $course_id;
        $actor_id = (int) $actor_id;
        $ids      = course_phase2_normalize_ids($ids);
        $want     = array_flip($ids);
        $has_audit = $this->db->field_exists('archived', $table);

        if ( ! $has_audit) {
            $this->db->where('course_id', $cid)->delete($table);
            foreach ($ids as $val) {
                $this->db->insert($table, [
                    'course_id'   => $cid,
                    $value_column => (int) $val,
                ]);
            }

            return;
        }

        $existing = $this->db->where('course_id', $cid)->get($table)->result();
        foreach ($existing as $row) {
            $val = (int) $row->{$value_column};
            if (isset($want[$val])) {
                if ((int) ($row->archived ?? 0) === 1) {
                    $this->db->where('id', (int) $row->id)->update(
                        $table,
                        array_merge(['archived' => 0], course_phase2_audit_update_row($actor_id))
                    );
                }
                unset($want[$val]);
            } elseif ((int) ($row->archived ?? 0) === 0) {
                $this->db->where('id', (int) $row->id)->update(
                    $table,
                    array_merge(['archived' => 1], course_phase2_audit_update_row($actor_id))
                );
            }
        }

        foreach (array_keys($want) as $val) {
            $this->db->insert($table, array_merge([
                'course_id'   => $cid,
                $value_column => (int) $val,
            ], course_phase2_audit_insert_row($actor_id)));
        }
    }
}

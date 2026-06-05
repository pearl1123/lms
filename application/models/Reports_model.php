<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reporting workspace — composes existing dashboard analytics for admin insights.
 *
 * @property dashboard_model     $dashboard_model
 * @property Course_model        $course_model
 * @property Course_phase2_model $course_phase2
 */
class Reports_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('dashboard_model');
        $this->load->model('Course_model', 'course_model');
        $this->load->model('Course_phase2_model', 'course_phase2');
        $this->load->model('Learning_notes_model', 'learning_notes_model');
    }

    /**
     * Full analytics workspace payload.
     *
     * @param string $range 7d|30d|90d|12m|all
     * @return array<string,mixed>
     */
    public function get_workspace_data($range = '30d')
    {
        $range     = $this->_normalize_range($range);
        $admin     = $this->dashboard_model->get_admin_dashboard_data(0);
        $stats     = $admin['stats'] ?? [];
        $charts    = $admin['charts'] ?? [];
        $analytics = $this->dashboard_model->get_admin_analytics();
        $course_ids = $this->_all_course_ids();

        $range_days = $this->_range_to_days($range);
        $at_risk = $this->dashboard_model->get_instructor_struggling_learners($course_ids, 20);
        $active_window = $this->_count_active_learners_days($range_days);
        $notes_analytics = $this->learning_notes_model->get_admin_analytics(8);

        $kpis = [
            'total_learners'      => (int) ($analytics['total_employees'] ?? 0),
            'active_learners'     => (int) ($analytics['active_users'] ?? 0),
            'inactive_learners'   => (int) ($analytics['inactive_users'] ?? 0),
            'courses_published'   => $this->_count_courses_by_publish('published'),
            'courses_draft'       => $this->_count_courses_by_publish('draft'),
            'courses_unpublished' => $this->_count_courses_by_publish('unpublished'),
            'completion_rate'     => (int) ($stats['completion_rate_pct'] ?? 0),
            'avg_progress'        => (int) ($stats['avg_course_progress_pct'] ?? 0),
            'certificates_total'  => (int) ($stats['certificate_total'] ?? 0),
            'certificates_week'   => (int) ($stats['certificate_issued_week'] ?? 0),
            'pending_approvals'   => (int) ($stats['pending_requests_count'] ?? 0),
            'at_risk_count'       => count($at_risk),
            'active_in_range'     => $active_window,
            'learning_notes_total'=> (int) ($notes_analytics['total_notes'] ?? 0),
        ];

        return [
            'range'               => $range,
            'last_updated'        => date('M j, Y g:i A'),
            'kpis'                => $kpis,
            'kpi_deltas'          => $this->_build_kpi_deltas($charts, $kpis),
            'charts'              => $charts,
            'insights'            => $this->_build_insights($kpis, $at_risk, $notes_analytics),
            'learning_notes'      => $notes_analytics,
            'top_courses'         => $this->_enrich_top_courses($admin['courses']['top_by_enrollments'] ?? []),
            'struggling'          => $at_risk,
            'active_learners'     => $this->get_top_active_learners(10, $range_days ?? 30),
            'recent_enrollments'  => $this->dashboard_model->get_latest_enrollments(8),
            'recent_certificates' => $this->_recent_certificates(8),
            'certificate_ready'   => $this->dashboard_model->get_instructor_certificate_ready_learners($course_ids, 8),
            'hrmis'               => $this->_hrmis_snapshot(),
            'export_presets'      => $this->_export_presets(),
        ];
    }

    /**
     * @param int $limit
     * @return object[]
     */
    /**
     * @param int      $limit
     * @param int|null $days  Activity window; null = all time
     * @return object[]
     */
    public function get_top_active_learners($limit = 10, $days = 30)
    {
        $limit = max(1, (int) $limit);
        $date_filter = '';
        if ($days !== null) {
            $days = max(1, (int) $days);
            $date_filter = ' AND mp.completed_at >= DATE_SUB(CURDATE(), INTERVAL ' . $days . ' DAY)';
        }

        $sql = "
            SELECT u.id, u.fullname, u.employee_id, t.completed_cnt
            FROM (
                SELECT mp.user_id, COUNT(*) AS completed_cnt
                FROM module_progress mp
                INNER JOIN course_modules cm ON cm.id = mp.module_id AND cm.archived = 0
                WHERE mp.status = 'completed'{$date_filter}
                GROUP BY mp.user_id
            ) t
            INNER JOIN aauth_users u ON u.id = t.user_id AND u.DELETED = 0
            ORDER BY t.completed_cnt DESC
            LIMIT {$limit}
        ";
        $q = $this->db->query($sql);

        return ($q && $q->num_rows() > 0) ? $q->result() : [];
    }

    /**
     * @param string $range
     * @return string
     */
    public function normalize_range($range)
    {
        return $this->_normalize_range($range);
    }

    /**
     * @param string $range
     * @return string
     */
    public function range_label($range)
    {
        $labels = [
            '7d'  => 'Last 7 days',
            '30d' => 'Last 30 days',
            '90d' => 'Last 90 days',
            '12m' => 'Last 12 months',
            'all' => 'All time',
        ];

        return $labels[$this->_normalize_range($range)] ?? 'Last 30 days';
    }

    /**
     * Export payload (reuses workspace analytics; expanded row limits).
     *
     * @param string   $range
     * @param string   $section overview|courses|learners|certificates|all
     * @param object   $exported_by
     * @return array<string,mixed>
     */
    public function get_export_bundle($range = '30d', $section = 'all', $exported_by = null)
    {
        $range   = $this->_normalize_range($range);
        $section = $this->_normalize_export_section($section);
        $workspace = $this->get_workspace_data($range);

        return [
            'meta' => [
                'export_date'  => date('Y-m-d H:i:s'),
                'exported_by'  => is_object($exported_by) ? (string) ($exported_by->fullname ?? 'Administrator') : 'Administrator',
                'employee_id'  => is_object($exported_by) ? (string) ($exported_by->employee_id ?? '') : '',
                'range'        => $range,
                'range_label'  => $this->range_label($range),
                'section'      => $section,
            ],
            'overview'     => $this->get_export_overview($workspace),
            'courses'      => $this->get_export_courses($workspace),
            'learners'     => $this->get_export_learners($workspace, $range),
            'certificates' => $this->get_export_certificates($range),
            'workspace'    => $workspace,
        ];
    }

    /**
     * @param array<string,mixed> $workspace
     * @return array<int,array<string,string>>
     */
    public function get_export_overview(array $workspace)
    {
        $kpis = $workspace['kpis'] ?? [];
        $hr   = $workspace['hrmis'] ?? [];
        $rows = [
            ['Metric', 'Value'],
            ['Total learners', (string) ($kpis['total_learners'] ?? 0)],
            ['Active accounts', (string) ($kpis['active_learners'] ?? 0)],
            ['Inactive accounts', (string) ($kpis['inactive_learners'] ?? 0)],
            ['Learners active in range', (string) ($kpis['active_in_range'] ?? 0)],
            ['Courses published', (string) ($kpis['courses_published'] ?? 0)],
            ['Courses draft', (string) ($kpis['courses_draft'] ?? 0)],
            ['Courses unpublished', (string) ($kpis['courses_unpublished'] ?? 0)],
            ['Completion rate %', (string) ($kpis['completion_rate'] ?? 0)],
            ['Average progress %', (string) ($kpis['avg_progress'] ?? 0)],
            ['Certificates issued (total)', (string) ($kpis['certificates_total'] ?? 0)],
            ['Certificates issued (7d)', (string) ($kpis['certificates_week'] ?? 0)],
            ['Pending enrollments', (string) ($kpis['pending_approvals'] ?? 0)],
            ['At-risk learners', (string) ($kpis['at_risk_count'] ?? 0)],
            ['HRMIS connected', ! empty($hr['connected']) ? 'Yes' : 'No'],
            ['HRMIS departments', (string) ($hr['department_count'] ?? 0)],
            ['HRMIS active employees', (string) ($hr['employee_count'] ?? 0)],
        ];

        $charts = $workspace['charts'] ?? [];
        foreach (['enrollment_trends' => 'Enrollment trend', 'certificate_issuance_trend' => 'Certificate trend', 'users_over_time' => 'User growth'] as $key => $label) {
            $chart = $charts[$key] ?? ['labels' => [], 'values' => []];
            $labels = $chart['labels'] ?? [];
            $values = $chart['values'] ?? [];
            foreach ($labels as $i => $ym) {
                if ($ym === 'No Data') {
                    continue;
                }
                $rows[] = [$label . ' ' . $ym, (string) ($values[$i] ?? 0)];
            }
        }

        foreach ($workspace['insights'] ?? [] as $ins) {
            $rows[] = ['Insight', (string) ($ins['text'] ?? '')];
        }

        return $rows;
    }

    /**
     * @param array<string,mixed> $workspace
     * @return array<int,array<string,string>>
     */
    public function get_export_courses(array $workspace)
    {
        $rows = [['Rank', 'Course', 'Enrollments', 'Avg progress %']];
        $top  = $this->_enrich_top_courses($this->dashboard_model->get_top_courses_by_enrollments(50));

        foreach ($top as $i => $c) {
            $rows[] = [
                (string) ((int) $i + 1),
                (string) ($c->course_title ?? ''),
                (string) ((int) ($c->enrollment_count ?? 0)),
                (string) ((int) ($c->avg_progress ?? 0)),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string,mixed> $workspace
     * @param string             $range
     * @return array<string,array<int,array<string,string>>>
     */
    public function get_export_learners(array $workspace, $range = '30d')
    {
        $days = $this->_range_to_days($this->_normalize_range($range));
        $course_ids = $this->_all_course_ids();
        $at_risk = $this->dashboard_model->get_instructor_struggling_learners($course_ids, 100);
        $active  = $this->get_top_active_learners(100, $days);
        $ready   = $workspace['certificate_ready'] ?? $this->dashboard_model->get_instructor_certificate_ready_learners($course_ids, 50);

        $active_rows = [['Learner', 'Employee ID', 'Modules completed (range)']];
        foreach ($active as $row) {
            $active_rows[] = [
                (string) ($row->fullname ?? ''),
                (string) ($row->employee_id ?? ''),
                (string) ((int) ($row->completed_cnt ?? 0)),
            ];
        }

        $risk_rows = [['Learner', 'Course', 'Progress %']];
        foreach ($at_risk as $row) {
            $risk_rows[] = [
                (string) ($row->fullname ?? ''),
                (string) ($row->course_title ?? ''),
                (string) ((int) ($row->progress_pct ?? 0)),
            ];
        }

        $ready_rows = [['Learner', 'Course']];
        foreach ($ready as $row) {
            $ready_rows[] = [
                (string) ($row->fullname ?? ''),
                (string) ($row->course_title ?? ''),
            ];
        }

        return [
            'active'   => $active_rows,
            'at_risk'  => $risk_rows,
            'ready'    => $ready_rows,
        ];
    }

    /**
     * @param string $range
     * @return array<int,array<string,string>>
     */
    public function get_export_certificates($range = '30d')
    {
        $range = $this->_normalize_range($range);
        $days  = $this->_range_to_days($range);

        $this->db
            ->select('uc.issued_at, u.fullname, u.employee_id, c.title AS course_title', false)
            ->from('lib_certificates uc')
            ->join('aauth_users u', 'u.id = uc.user_id', 'left')
            ->join('courses c', 'c.id = uc.course_id', 'left')
            ->where('uc.archived', 0)
            ->order_by('uc.issued_at', 'DESC')
            ->limit(500);

        if ($days !== null) {
            $this->db->where('uc.issued_at >=', date('Y-m-d H:i:s', strtotime('-' . (int) $days . ' days')));
        }

        $r = $this->db->get();
        $rows = [['Issued at', 'Learner', 'Employee ID', 'Course']];

        if ($r && $r->num_rows() > 0) {
            foreach ($r->result() as $row) {
                $rows[] = [
                    ! empty($row->issued_at) ? date('Y-m-d H:i', strtotime($row->issued_at)) : '',
                    (string) ($row->fullname ?? ''),
                    (string) ($row->employee_id ?? ''),
                    (string) ($row->course_title ?? ''),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param string $section
     * @return string
     */
    private function _normalize_export_section($section)
    {
        $allowed = ['overview', 'courses', 'learners', 'certificates', 'all'];
        $section = strtolower(trim((string) $section));

        return in_array($section, $allowed, true) ? $section : 'all';
    }

    /**
     * @param string $range
     * @return string
     */
    private function _normalize_range($range)
    {
        $allowed = ['7d', '30d', '90d', '12m', 'all'];
        $range   = strtolower(trim((string) $range));

        return in_array($range, $allowed, true) ? $range : '30d';
    }

    /**
     * @param string $range
     * @return int|null
     */
    private function _range_to_days($range)
    {
        switch ($range) {
            case '7d':  return 7;
            case '30d': return 30;
            case '90d': return 90;
            case '12m': return 365;
            default:    return null;
        }
    }

    /**
     * @param int|null $days
     * @return int
     */
    private function _count_active_learners_days($days)
    {
        $this->db
            ->select('COUNT(DISTINCT mp.user_id) AS cnt', false)
            ->from('module_progress mp')
            ->join('aauth_users u', 'u.id = mp.user_id AND u.DELETED = 0', 'inner')
            ->where('mp.status', 'completed');

        if ($days !== null) {
            $this->db->where('mp.completed_at >=', date('Y-m-d H:i:s', strtotime('-' . (int) $days . ' days')));
        }

        $row = $this->db->get()->row();

        return $row ? (int) ($row->cnt ?? 0) : 0;
    }

    /**
     * @return int[]
     */
    private function _all_course_ids()
    {
        $r = $this->db->select('id')->from('courses')->where('archived', 0)->get();
        if ( ! $r || $r->num_rows() === 0) {
            return [];
        }

        $ids = [];
        foreach ($r->result() as $row) {
            $ids[] = (int) $row->id;
        }

        return $ids;
    }

    /**
     * @param string $status
     * @return int
     */
    private function _count_courses_by_publish($status)
    {
        if ( ! $this->db->field_exists('publish_status', 'courses')) {
            return $status === 'published'
                ? (int) $this->db->where('archived', 0)->count_all_results('courses')
                : 0;
        }

        return (int) $this->db
            ->where('archived', 0)
            ->where('publish_status', $status)
            ->count_all_results('courses');
    }

    /**
     * @param object[] $rows
     * @return object[]
     */
    private function _enrich_top_courses(array $rows)
    {
        $out = [];
        foreach ($rows as $row) {
            $cid = (int) ($row->course_id ?? 0);
            if ($cid < 1) {
                continue;
            }
            $row->avg_progress = (int) $this->course_model->get_avg_progress($cid);
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @param int $limit
     * @return object[]
     */
    private function _recent_certificates($limit = 8)
    {
        $r = $this->db
            ->select('uc.id, uc.issued_at, u.fullname, c.title AS course_title', false)
            ->from('lib_certificates uc')
            ->join('aauth_users u', 'u.id = uc.user_id', 'left')
            ->join('courses c', 'c.id = uc.course_id', 'left')
            ->where('uc.archived', 0)
            ->order_by('uc.issued_at', 'DESC')
            ->limit((int) $limit)
            ->get();

        return ($r && $r->num_rows() > 0) ? $r->result() : [];
    }

    /**
     * @return array<string,mixed>
     */
    private function _hrmis_snapshot()
    {
        $connected = $this->course_phase2->hrmis_connection_ok();
        $depts     = $connected ? $this->course_phase2->get_departments() : [];
        $employees = 0;

        if ($connected) {
            $hrmis = $this->load->database('hrmis', true);
            if ($hrmis) {
                $employees = (int) $hrmis->where('status', 'ACTIVE')->count_all_results('tblemployee');
            }
        }

        $matrix = [];
        foreach (array_slice($depts, 0, 8) as $dept) {
            $matrix[] = [
                'name'       => (string) ($dept->name ?? ''),
                'coverage'   => null,
                'learners'   => '—',
                'compliance' => '—',
            ];
        }

        return [
            'connected'          => $connected,
            'department_count'   => count($depts),
            'employee_count'     => $employees,
            'departments'        => array_slice($depts, 0, 12),
            'compliance_matrix'  => $matrix,
        ];
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function _export_presets()
    {
        return [
            [
                'id'     => 'executive_pdf',
                'label'  => 'Executive summary (PDF)',
                'desc'   => 'Branded KPI overview with courses, completion, certificates, and HRMIS status.',
                'format' => 'pdf',
                'section'=> 'all',
                'icon'   => 'pdf',
            ],
            [
                'id'     => 'full_excel',
                'label'  => 'Full analytics workbook (Excel)',
                'desc'   => 'Multi-sheet export: Overview, Courses, Learners, Certificates.',
                'format' => 'excel',
                'section'=> 'all',
                'icon'   => 'excel',
            ],
            [
                'id'     => 'full_csv',
                'label'  => 'Full analytics pack (CSV)',
                'desc'   => 'UTF-8 CSV with KPI summary, courses, learners, and certificates.',
                'format' => 'csv',
                'section'=> 'all',
                'icon'   => 'csv',
            ],
            [
                'id'     => 'learners_csv',
                'label'  => 'Learner completion analytics',
                'desc'   => 'Active learners, at-risk progress, and certificate-ready rows.',
                'format' => 'csv',
                'section'=> 'learners',
                'icon'   => 'csv',
            ],
            [
                'id'     => 'certificates_csv',
                'label'  => 'Certificate issuance report',
                'desc'   => 'Issued certificates filtered by the selected date range.',
                'format' => 'csv',
                'section'=> 'certificates',
                'icon'   => 'csv',
            ],
            [
                'id'     => 'courses_csv',
                'label'  => 'Course performance export',
                'desc'   => 'Top courses by enrollment with average progress.',
                'format' => 'csv',
                'section'=> 'courses',
                'icon'   => 'csv',
            ],
        ];
    }

    /**
     * @param array<string,array> $charts
     * @param array<string,int>   $kpis
     * @return array<string,array{value:int,direction:string,label:string}>
     */
    private function _build_kpi_deltas(array $charts, array $kpis)
    {
        $enroll = $charts['enrollment_trends'] ?? ['labels' => [], 'values' => []];
        $values = $enroll['values'] ?? [];
        $enroll_delta = 0;
        $enroll_dir   = 'flat';

        if (count($values) >= 2) {
            $prev = (int) $values[count($values) - 2];
            $cur  = (int) $values[count($values) - 1];
            if ($prev > 0) {
                $enroll_delta = (int) round((($cur - $prev) / $prev) * 100);
            } elseif ($cur > 0) {
                $enroll_delta = 100;
            }
            $enroll_dir = $enroll_delta > 0 ? 'up' : ($enroll_delta < 0 ? 'down' : 'flat');
        }

        $cert = $charts['certificate_issuance_trend'] ?? ['values' => []];
        $cert_vals = $cert['values'] ?? [];
        $cert_delta = 0;
        $cert_dir   = 'flat';
        if (count($cert_vals) >= 2) {
            $prev = (int) $cert_vals[count($cert_vals) - 2];
            $cur  = (int) $cert_vals[count($cert_vals) - 1];
            if ($prev > 0) {
                $cert_delta = (int) round((($cur - $prev) / $prev) * 100);
            } elseif ($cur > 0) {
                $cert_delta = 100;
            }
            $cert_dir = $cert_delta > 0 ? 'up' : ($cert_delta < 0 ? 'down' : 'flat');
        }

        return [
            'enrollments' => [
                'value'     => abs($enroll_delta),
                'direction' => $enroll_dir,
                'label'     => ($enroll_delta >= 0 ? '+' : '−') . abs($enroll_delta) . '% vs prior month',
            ],
            'certificates' => [
                'value'     => abs($cert_delta),
                'direction' => $cert_dir,
                'label'     => ($cert_delta >= 0 ? '+' : '−') . abs($cert_delta) . '% issuance',
            ],
            'completion' => [
                'value'     => (int) ($kpis['completion_rate'] ?? 0),
                'direction' => 'flat',
                'label'     => 'Platform completion rate',
            ],
            'active' => [
                'value'     => (int) ($kpis['active_in_range'] ?? 0),
                'direction' => 'up',
                'label'     => 'Learners with module activity in range',
            ],
        ];
    }

    /**
     * @param array<string,int> $kpis
     * @param object[]          $at_risk
     * @return array<int,array{type:string,text:string}>
     */
    private function _build_insights(array $kpis, array $at_risk, array $notes_analytics = [])
    {
        $insights = [];

        $notes_total = (int) ($notes_analytics['total_notes'] ?? 0);
        if ($notes_total > 0) {
            $insights[] = [
                'type' => 'info',
                'text' => number_format($notes_total) . ' personal learning notes captured across the platform.',
            ];
        }

        if ((int) ($kpis['pending_approvals'] ?? 0) > 0) {
            $insights[] = [
                'type' => 'warning',
                'text' => (int) $kpis['pending_approvals'] . ' enrollment requests are waiting for approval.',
            ];
        }

        if (count($at_risk) > 0) {
            $insights[] = [
                'type' => 'warning',
                'text' => count($at_risk) . ' learners are behind on course progress and may need follow-up.',
            ];
        }

        if ((int) ($kpis['courses_draft'] ?? 0) > 0) {
            $insights[] = [
                'type' => 'info',
                'text' => (int) $kpis['courses_draft'] . ' courses remain in draft — publish to start tracking analytics.',
            ];
        }

        if ((int) ($kpis['completion_rate'] ?? 0) < 50 && (int) ($kpis['completion_rate'] ?? 0) > 0) {
            $insights[] = [
                'type' => 'warning',
                'text' => 'Completion rate is below 50% — review struggling courses and learner support.',
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'type' => 'success',
                'text' => 'Platform metrics look healthy. Keep publishing courses to grow learning analytics.',
            ];
        }

        return $insights;
    }
}

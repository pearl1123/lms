<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Computes integrity analytics metrics for the admin/teacher dashboard.
 */
class Assessment_integrity_service {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Assessment_integrity_model', 'assessment_integrity_model');
        $this->CI->load->helper('ka_format');
    }

    /**
     * @param int[] $assessment_ids optional filter
     * @return array
     */
    public function build_dashboard(array $assessment_ids = [])
    {
        $overview_rows = $this->CI->assessment_integrity_model->get_assessment_overview_rows($assessment_ids);

        $overviews = [];
        foreach ($overview_rows as $row) {
            $aid = (int) $row->id;
            $overviews[$aid] = [
                'assessment'       => $row,
                'metrics'          => $this->_overview_metrics($row),
                'questions'        => $this->_question_analytics_for_assessment($aid),
                'health'           => null,
            ];
            $overviews[$aid]['health'] = $this->compute_health_badge($overviews[$aid]);
        }

        return [
            'overviews'        => $overviews,
            'most_missed'      => $this->CI->assessment_integrity_model->get_most_missed_questions(10, $assessment_ids),
            'discrimination'   => $this->_discrimination_index_rows($assessment_ids),
            'generated_at'     => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param object $row from integrity model
     * @return array
     */
    private function _overview_metrics($row)
    {
        $started    = (int) ($row->started_count ?? 0);
        $submitted  = (int) ($row->submitted_count ?? 0);
        $retakes    = (int) ($row->retake_count ?? 0);
        $completion = $started > 0 ? round(($submitted / $started) * 100, 1) : 0.0;

        return [
            'started'          => $started,
            'submitted'        => $submitted,
            'retakes'          => $retakes,
            'completion_rate'  => $completion,
            'average_score'    => round((float) ($row->average_score ?? 0), 1),
            'randomize_enabled'=> (int) ($row->randomize_questions ?? 0) === 1,
            'content_version'  => max(1, (int) ($row->content_version ?? 1)),
            'legacy_attempts'  => (int) ($row->legacy_attempt_count ?? 0),
        ];
    }

    /**
     * @return array<int,array>
     */
    private function _question_analytics_for_assessment($assessment_id)
    {
        $stats = $this->CI->assessment_integrity_model->get_question_stats_for_assessment($assessment_id);
        $out   = [];

        foreach ($stats as $s) {
            $presented = (int) ($s->times_presented ?? 0);
            $correct   = (int) ($s->correct_count ?? 0);
            $pct       = $presented > 0 ? round(($correct / $presented) * 100, 1) : 0.0;
            $incorrect = $presented > 0 ? round(100 - $pct, 1) : 0.0;

            $out[] = [
                'question_id'     => (int) $s->question_id,
                'question_text'   => (string) ($s->question_text ?? ''),
                'question_type'   => (string) ($s->question_type ?? ''),
                'times_presented' => $presented,
                'correct_pct'     => $pct,
                'incorrect_pct'   => $incorrect,
                'difficulty'      => $this->classify_difficulty($pct),
            ];
        }

        return $out;
    }

    public function classify_difficulty($correct_pct)
    {
        $p = (float) $correct_pct;
        if ($p > 80) {
            return 'Easy';
        }
        if ($p >= 40) {
            return 'Moderate';
        }

        return 'Hard';
    }

    /**
     * @param array $bundle single overview entry from build_dashboard
     * @return array{label:string,class:string,score:int}
     */
    public function compute_health_badge(array $bundle)
    {
        $m         = $bundle['metrics'] ?? [];
        $questions = $bundle['questions'] ?? [];

        $completion = (float) ($m['completion_rate'] ?? 0);
        $avg        = (float) ($m['average_score'] ?? 0);
        $retakes    = (int) ($m['retakes'] ?? 0);
        $started    = max(1, (int) ($m['started'] ?? 0));
        $retake_pct = ($retakes / $started) * 100;

        $easy = $moderate = $hard = 0;
        foreach ($questions as $q) {
            $d = $q['difficulty'] ?? 'Moderate';
            if ($d === 'Easy') {
                $easy++;
            } elseif ($d === 'Hard') {
                $hard++;
            } else {
                $moderate++;
            }
        }
        $q_total = max(1, count($questions));
        $balance = 100 - (abs($easy - $moderate) + abs($moderate - $hard)) / $q_total * 10;

        $score = (int) round(
            min(100, $completion * 0.3)
            + min(100, $avg * 0.35)
            + min(100, max(0, $balance)) * 0.2
            + max(0, 100 - $retake_pct * 2) * 0.15
        );

        if ($score >= 85) {
            return ['label' => 'Excellent', 'class' => 'aint-health--excellent', 'score' => $score];
        }
        if ($score >= 70) {
            return ['label' => 'Good', 'class' => 'aint-health--good', 'score' => $score];
        }
        if ($score >= 50) {
            return ['label' => 'Needs Review', 'class' => 'aint-health--review', 'score' => $score];
        }

        return ['label' => 'Critical', 'class' => 'aint-health--critical', 'score' => $score];
    }

    public function classify_discrimination($index)
    {
        $i = (float) $index;
        if ($i >= 30) {
            return 'Excellent';
        }
        if ($i >= 20) {
            return 'Good';
        }
        if ($i >= 10) {
            return 'Acceptable';
        }

        return 'Poor';
    }

    /**
     * @return array<int,array>
     */
    private function _discrimination_index_rows(array $assessment_ids)
    {
        $rows = $this->CI->assessment_integrity_model->get_discrimination_raw($assessment_ids);
        $out  = [];

        foreach ($rows as $r) {
            $high = (float) ($r->high_correct_pct ?? 0);
            $low  = (float) ($r->low_correct_pct ?? 0);
            $idx  = round($high - $low, 1);

            $out[] = [
                'question_id'    => (int) $r->question_id,
                'question_text'  => (string) ($r->question_text ?? ''),
                'assessment_id'  => (int) $r->assessment_id,
                'assessment_name'=> (string) ($r->assessment_title ?? ''),
                'high_pct'       => round($high, 1),
                'low_pct'        => round($low, 1),
                'index'          => $idx,
                'rating'         => $this->classify_discrimination($idx),
            ];
        }

        usort($out, static function ($a, $b) {
            return ($b['index'] <=> $a['index']);
        });

        return $out;
    }
}

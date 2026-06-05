<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stream report exports (CSV, Excel XML, PDF).
 */
class Reports_export {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('report_export');
    }

    /**
     * @param array<string,mixed> $bundle
     */
    public function stream_csv(array $bundle)
    {
        $meta    = $bundle['meta'] ?? [];
        $section = $meta['section'] ?? 'all';
        $filename = $this->build_filename('LMS_Report', $section, 'csv');

        $this->_send_headers('text/csv; charset=UTF-8', $filename);
        $out = fopen('php://output', 'w');
        if ($out === false) {
            show_error('Unable to start export.', 500);
        }

        fwrite($out, "\xEF\xBB\xBF");
        $this->_csv_meta_block($out, $meta);

        if ($this->_section_includes($section, 'overview')) {
            $this->_csv_section($out, 'Overview', $bundle['overview'] ?? []);
        }
        if ($this->_section_includes($section, 'courses')) {
            $this->_csv_section($out, 'Courses', $bundle['courses'] ?? []);
        }
        if ($this->_section_includes($section, 'learners')) {
            $learners = $bundle['learners'] ?? [];
            foreach (['active' => 'Active learners', 'at_risk' => 'At-risk learners', 'ready' => 'Certificate-ready'] as $key => $title) {
                if ( ! empty($learners[$key])) {
                    $this->_csv_section($out, $title, $learners[$key]);
                }
            }
        }
        if ($this->_section_includes($section, 'certificates')) {
            $this->_csv_section($out, 'Certificates', $bundle['certificates'] ?? []);
        }

        fclose($out);
        exit;
    }

    /**
     * Excel 2003 XML (multi-sheet) — no PhpSpreadsheet required.
     *
     * @param array<string,mixed> $bundle
     */
    public function stream_excel(array $bundle)
    {
        $meta     = $bundle['meta'] ?? [];
        $section  = $meta['section'] ?? 'all';
        $filename = $this->build_filename('LMS_Report', $section, 'xls');

        $this->_send_headers('application/vnd.ms-excel; charset=UTF-8', $filename);

        $sheets = [];
        if ($this->_section_includes($section, 'overview')) {
            $sheets['Overview'] = $bundle['overview'] ?? [];
        }
        if ($this->_section_includes($section, 'courses')) {
            $sheets['Courses'] = $bundle['courses'] ?? [];
        }
        if ($this->_section_includes($section, 'learners')) {
            $learners = $bundle['learners'] ?? [];
            if ( ! empty($learners['active'])) {
                $sheets['Active learners'] = $learners['active'];
            }
            if ( ! empty($learners['at_risk'])) {
                $sheets['At-risk'] = $learners['at_risk'];
            }
            if ( ! empty($learners['ready'])) {
                $sheets['Cert ready'] = $learners['ready'];
            }
        }
        if ($this->_section_includes($section, 'certificates')) {
            $sheets['Certificates'] = $bundle['certificates'] ?? [];
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
        echo 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        echo '<Styles>';
        echo '<Style ss:ID="hdr"><Font ss:Bold="1"/><Interior ss:Color="#E8F4FD" ss:Pattern="Solid"/></Style>';
        echo '<Style ss:ID="meta"><Font ss:Italic="1" ss:Color="#64748B"/></Style>';
        echo '</Styles>';

        echo '<Worksheet ss:Name="Export info"><Table>';
        $meta_rows = [
            ['Exported by', $meta['exported_by'] ?? ''],
            ['Employee ID', $meta['employee_id'] ?? ''],
            ['Export date', $meta['export_date'] ?? ''],
            ['Date range', $meta['range_label'] ?? ''],
        ];
        foreach ($meta_rows as $row) {
            echo '<Row>';
            foreach ($row as $cell) {
                echo '<Cell ss:StyleID="meta"><Data ss:Type="String">' . $this->_xml($cell) . '</Data></Cell>';
            }
            echo '</Row>';
        }
        echo '</Table></Worksheet>';

        foreach ($sheets as $name => $rows) {
            $this->_excel_sheet($name, $rows);
        }

        echo '</Workbook>';
        exit;
    }

    /**
     * @param array<string,mixed> $bundle
     */
    public function stream_pdf(array $bundle)
    {
        $meta     = $bundle['meta'] ?? [];
        $filename = $this->build_filename('LMS_Report_Executive', 'summary', 'pdf');

        report_export_prepare_response();

        if ( ! is_file(FCPATH . 'vendor/autoload.php')
            && ! is_file(APPPATH . 'third_party/dompdf/autoload.inc.php')) {
            report_export_log('pdf_dompdf_missing', [
                'vendor'  => FCPATH . 'vendor/autoload.php',
                'manual'  => APPPATH . 'third_party/dompdf/autoload.inc.php',
            ]);
            show_error('PDF export library (DOMPDF) is not installed on this server.', 500);
        }

        $this->CI->load->library('pdf');
        $html = $this->CI->load->view('reports/export_pdf', [
            'bundle' => $bundle,
            'meta'   => $meta,
        ], true);

        if (trim($html) === '') {
            report_export_log('pdf_empty_html', ['filename' => $filename]);
            show_error('Report PDF template rendered empty content.', 500);
        }

        report_export_log('pdf_render_start', [
            'filename'   => $filename,
            'html_bytes' => strlen($html),
            'temp_dir'   => function_exists('report_export_dompdf_temp_dir') ? report_export_dompdf_temp_dir() : '',
        ]);

        $this->CI->pdf->load_html($html);
        $this->CI->pdf->set_paper('A4', 'portrait');
        $this->CI->pdf->render();

        $output = $this->CI->pdf->output();
        if ($output === '' || strlen($output) < 500) {
            report_export_log('pdf_render_empty', [
                'filename' => $filename,
                'bytes'    => strlen((string) $output),
            ]);
            show_error('Report PDF could not be generated on this server.', 500);
        }

        report_export_log('pdf_render_ok', [
            'filename' => $filename,
            'bytes'    => strlen($output),
        ]);

        $this->_send_headers('application/pdf', $filename);
        echo $output;
        exit;
    }

    /**
     * @param string $prefix
     * @param string $section
     * @param string $ext
     * @return string
     */
    public function build_filename($prefix, $section, $ext)
    {
        $section = preg_replace('/[^a-z0-9_-]/i', '', $section) ?: 'all';

        return $prefix . '_' . ucfirst($section) . '_' . date('Y-m-d') . '.' . $ext;
    }

    /**
     * @param resource            $out
     * @param array<string,string> $meta
     */
    protected function _csv_meta_block($out, array $meta)
    {
        fputcsv($out, ['Exported by', $meta['exported_by'] ?? '']);
        fputcsv($out, ['Employee ID', $meta['employee_id'] ?? '']);
        fputcsv($out, ['Export date', $meta['export_date'] ?? '']);
        fputcsv($out, ['Date range', $meta['range_label'] ?? '']);
        fputcsv($out, []);
    }

    /**
     * @param resource              $out
     * @param string                $title
     * @param array<int,array>      $rows
     */
    protected function _csv_section($out, $title, array $rows)
    {
        fputcsv($out, ['--- ' . $title . ' ---']);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fputcsv($out, []);
    }

    /**
     * @param string           $name
     * @param array<int,array> $rows
     */
    protected function _excel_sheet($name, array $rows)
    {
        $safe = htmlspecialchars(substr($name, 0, 31), ENT_QUOTES, 'UTF-8');
        echo '<Worksheet ss:Name="' . $safe . '"><Table>';

        $first = true;
        foreach ($rows as $row) {
            echo '<Row>';
            foreach ($row as $cell) {
                $style = $first ? ' ss:StyleID="hdr"' : '';
                $type  = is_numeric($cell) && $first === false ? 'Number' : 'String';
                if ($type === 'Number' && ! is_int($cell) && ! is_float($cell)) {
                    $type = 'String';
                }
                echo '<Cell' . $style . '><Data ss:Type="' . $type . '">' . $this->_xml($cell) . '</Data></Cell>';
            }
            echo '</Row>';
            $first = false;
        }

        echo '</Table></Worksheet>';
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function _xml($value)
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param string $mime
     * @param string $filename
     */
    protected function _send_headers($mime, $filename)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Cache-Control: max-age=0, no-cache, must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
    }

    /**
     * @param string $selected
     * @param string $part
     * @return bool
     */
    protected function _section_includes($selected, $part)
    {
        return $selected === 'all' || $selected === $part;
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Pdf Library — CI3 wrapper for DOMPDF
 *
 * Usage in controller:
 *   $this->load->library('pdf');
 *   $html = $this->load->view('my_template', $data, true);
 *   $this->pdf->load_html($html);
 *   $this->pdf->set_paper('A4', 'landscape');
 *   $this->pdf->render();
 *   $this->pdf->stream('filename.pdf');       // send to browser
 *   $this->pdf->save('/full/path/file.pdf');  // save to disk
 *
 * Install DOMPDF via Composer:
 *   cd [your_project_root]
 *   composer require dompdf/dompdf
 *
 * Or manually place the dompdf folder in:
 *   application/third_party/dompdf/
 * and ensure autoload.inc.php exists inside it.
 */
class Pdf {

    /** @var \Dompdf\Dompdf */
    protected $dompdf;

    /** @var \Dompdf\Options */
    protected $options;

    public function __construct()
    {
        $this->_load_dompdf();

        $this->options = new \Dompdf\Options();
        $this->options->set('isHtml5ParserEnabled', true);
        $this->options->set('isRemoteEnabled',      false);
        $this->options->set('defaultFont',          'DejaVu Sans');
        $this->options->set('chroot',               FCPATH);

        $this->dompdf = new \Dompdf\Dompdf($this->options);
    }

    /**
     * Load HTML string into DOMPDF.
     */
    public function load_html($html, $encoding = 'UTF-8')
    {
        $this->dompdf->loadHtml($html, $encoding);
        return $this;
    }

    /**
     * Set paper size and orientation.
     *
     * @param  string $size        'A4', 'letter', etc.
     * @param  string $orientation 'portrait' | 'landscape'
     */
    public function set_paper($size = 'A4', $orientation = 'portrait')
    {
        $this->dompdf->setPaper($size, $orientation);
        return $this;
    }

    /**
     * Render the PDF (must call before stream or save).
     */
    public function render()
    {
        $this->dompdf->render();
        return $this;
    }

    /**
     * Stream PDF to browser (forces download or inline display).
     *
     * @param  string $filename  Download filename
     * @param  bool   $inline    true = open in browser, false = download
     */
    public function stream($filename = 'document.pdf', $inline = false)
    {
        $this->dompdf->stream($filename, ['Attachment' => ! $inline]);
        exit;
    }

    /**
     * Save PDF to a file on disk.
     *
     * @param  string $path  Full absolute path
     * @return bool
     */
    public function save($path)
    {
        $output = $this->dompdf->output();
        if (empty($output)) return false;

        $dir = dirname($path);
        if ( ! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($path, $output) !== false;
    }

    /**
     * Get raw PDF string output.
     *
     * @return string
     */
    public function output()
    {
        return $this->dompdf->output();
    }

    /**
     * Load DOMPDF — tries Composer autoload first,
     * then falls back to third_party manual install.
     */
    private function _load_dompdf()
    {
        // Option A: Composer (recommended)
        $composer = FCPATH . 'vendor/autoload.php';
        if (file_exists($composer)) {
            require_once $composer;
            return;
        }

        // Option B: Manual install in application/third_party/dompdf/
        $manual = APPPATH . 'third_party/dompdf/autoload.inc.php';
        if (file_exists($manual)) {
            require_once $manual;
            return;
        }

        show_error(
            'DOMPDF not found.<br><br>'
            . 'Install via Composer: <code>composer require dompdf/dompdf</code><br>'
            . 'Or place dompdf folder in: <code>application/third_party/dompdf/</code>',
            500,
            'PDF Library Error'
        );
    }
}
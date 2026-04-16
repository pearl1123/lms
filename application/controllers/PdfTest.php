<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once FCPATH . 'vendor/autoload.php';
use Dompdf\Dompdf;

class PdfTest extends CI_Controller {

    public function index() {
        $dompdf = new Dompdf();
        $dompdf->loadHtml('<h1>Hello Dompdf</h1>');   // your HTML content
        $dompdf->setPaper('A4', 'portrait');          // paper size & orientation
        $dompdf->render();
        $dompdf->stream("test.pdf", ["Attachment" => false]);  // output PDF in browser
    }
}
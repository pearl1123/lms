<?php

class CertificateTest extends PHPUnit\Framework\TestCase
{
    public function test_signatory_upload_service_exists()
    {
        $this->assertFileExists(APPPATH . 'services/Signatory_upload_service.php');
    }

    public function test_template_pdf_renders_signature_images()
    {
        $html = file_get_contents(APPPATH . 'views/certificates/template_pdf.php');
        $this->assertStringContainsString('sig[\'image\']', $html);
        $this->assertStringContainsString('ka_cert_sig_image_src', $html);
    }
}

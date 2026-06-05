<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Legacy view entry — delegates to the active certificate PDF template.
 *
 * @see ka_cert_resolve_template()
 * @see application/views/certificates/templates/
 */
if ( ! function_exists('ka_cert_resolve_template')) {
    $CI =& get_instance();
    $CI->load->helper('certificate_pdf');
}

$__cert_tpl = ka_cert_resolve_template($pdf_template ?? null);

if (empty($cert_css)) {
    $cert_css = ka_cert_inline_css($__cert_tpl);
}

include APPPATH . 'views/certificates/templates/' . $__cert_tpl . '.php';

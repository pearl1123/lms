<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Branded outbound email (SMTP from lms_settings).
 */
class Email_service {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Settings_model', 'settings_model');
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $html_body
     * @return array{ok:bool,message:string}
     */
    public function send($to, $subject, $html_body)
    {
        $to = trim((string) $to);
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Invalid recipient email.'];
        }

        if ( ! $this->CI->settings_model->table_ready()) {
            return ['ok' => false, 'message' => 'Settings table not installed.'];
        }

        $settings = $this->CI->settings_model->get_all_settings();
        $n        = is_array($settings['notifications'] ?? null) ? $settings['notifications'] : [];
        $g        = is_array($settings['general'] ?? null) ? $settings['general'] : [];
        $b        = is_array($settings['branding'] ?? null) ? $settings['branding'] : [];

        $host = trim((string) ($n['smtp_host'] ?? ''));
        if ($host === '') {
            log_message('debug', 'Email_service: SMTP host not configured');

            return ['ok' => false, 'message' => 'SMTP is not configured in Settings → Notifications.'];
        }

        $this->CI->load->library('email');
        $port = (int) ($n['smtp_port'] ?? 587);
        $enc  = strtolower(trim((string) ($n['smtp_encryption'] ?? 'tls')));

        $config = [
            'protocol'  => 'smtp',
            'smtp_host' => $host,
            'smtp_port' => $port,
            'smtp_user' => (string) ($n['smtp_user'] ?? ''),
            'smtp_pass' => (string) ($n['smtp_pass'] ?? ''),
            'mailtype'  => 'html',
            'charset'   => 'utf-8',
            'newline'   => "\r\n",
            'crlf'      => "\r\n",
        ];
        if ($enc === 'ssl') {
            $config['smtp_crypto'] = 'ssl';
        } elseif ($enc === 'tls') {
            $config['smtp_crypto'] = 'tls';
        }

        $this->CI->email->initialize($config);
        $from_email = trim((string) ($n['from_email'] ?? ''));
        if ($from_email === '') {
            $from_email = trim((string) ($g['support_email'] ?? ''));
        }
        if ($from_email === '') {
            $from_email = 'noreply@localhost';
        }
        $from_name = trim((string) ($n['from_name'] ?? ($g['lms_name'] ?? 'kaBAGA Academy')));

        $wrapped = $this->_wrap_template($subject, $html_body, $b, $g);
        $this->CI->email->from($from_email, $from_name);
        $this->CI->email->to($to);
        $this->CI->email->subject($subject);
        $this->CI->email->message($wrapped);

        if ( ! $this->CI->email->send(false)) {
            $err = $this->CI->email->print_debugger(['headers']);
            log_message('error', 'Email_service send failed: ' . $err);

            return ['ok' => false, 'message' => 'Email could not be sent. Check SMTP settings.'];
        }

        log_message('info', 'Email_service: sent to ' . $to . ' subject=' . $subject);

        return ['ok' => true, 'message' => 'Email sent.'];
    }

    /**
     * @param string $template_key invite|approval|certificate|reminder|reassessment
     * @param array  $vars
     */
    public function send_template($to, $template_key, array $vars = [])
    {
        $subject = (string) ($vars['subject'] ?? 'kaBAGA Academy notification');
        $body    = (string) ($vars['body'] ?? '');
        if ($body === '') {
            $body = $this->_default_body($template_key, $vars);
        }

        return $this->send($to, $subject, $body);
    }

    private function _default_body($template_key, array $vars)
    {
        $name   = htmlspecialchars((string) ($vars['learner_name'] ?? 'Learner'), ENT_QUOTES, 'UTF-8');
        $course = htmlspecialchars((string) ($vars['course_title'] ?? 'your course'), ENT_QUOTES, 'UTF-8');
        $link   = htmlspecialchars((string) ($vars['action_url'] ?? base_url()), ENT_QUOTES, 'UTF-8');

        switch ($template_key) {
            case 'invite':
                return '<p>Hello ' . $name . ',</p><p>You have been invited to <strong>' . $course . '</strong>.</p><p><a href="' . $link . '">Open invitation</a></p>';
            case 'approval':
                return '<p>Hello ' . $name . ',</p><p>Your enrollment in <strong>' . $course . '</strong> has been approved.</p><p><a href="' . $link . '">Start learning</a></p>';
            case 'certificate':
                return '<p>Hello ' . $name . ',</p><p>Your certificate for <strong>' . $course . '</strong> is ready.</p><p><a href="' . $link . '">View certificate</a></p>';
            case 'reassessment':
                return '<p>Hello ' . $name . ',</p><p>Please retake the assessment for <strong>' . $course . '</strong>.</p><p><a href="' . $link . '">Go to assessment</a></p>';
            case 'f2f':
                $venue = htmlspecialchars((string) ($vars['venue'] ?? ''), ENT_QUOTES, 'UTF-8');
                $schedule = htmlspecialchars((string) ($vars['schedule'] ?? ''), ENT_QUOTES, 'UTF-8');
                $extra = '';
                if ($schedule !== '') {
                    $extra .= '<p><strong>Schedule:</strong> ' . $schedule . '</p>';
                }
                if ($venue !== '') {
                    $extra .= '<p><strong>Venue:</strong> ' . $venue . '</p>';
                }
                return '<p>Hello ' . $name . ',</p><p>You are enrolled in the face-to-face course <strong>' . $course . '</strong>.</p>'
                    . $extra . '<p><a href="' . $link . '">View course details</a></p>';
            case 'reminder':
                return '<p>Hello ' . $name . ',</p><p>Reminder for <strong>' . $course . '</strong>.</p><p><a href="' . $link . '">View details</a></p>';
            default:
                return '<p>Hello ' . $name . ',</p><p>' . htmlspecialchars((string) ($vars['message'] ?? 'You have a new notification.'), ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }

    private function _wrap_template($subject, $body, array $branding, array $general)
    {
        $lms  = htmlspecialchars((string) ($general['lms_name'] ?? 'kaBAGA Academy'), ENT_QUOTES, 'UTF-8');
        $org  = htmlspecialchars((string) ($general['organization_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $accent = htmlspecialchars((string) ($branding['accent_color'] ?? '#6dabcf'), ENT_QUOTES, 'UTF-8');
        $sub  = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="margin:0;background:#f8fafc;font-family:DM Sans,Arial,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px;">'
            . '<table width="600" style="max-width:600px;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,.08);">'
            . '<tr><td style="background:' . $accent . ';padding:20px 24px;color:#fff;"><strong>' . $lms . '</strong>'
            . ($org !== '' ? '<div style="font-size:12px;opacity:.9;margin-top:4px;">' . $org . '</div>' : '')
            . '</td></tr><tr><td style="padding:24px;color:#1e293b;font-size:15px;line-height:1.6;">'
            . '<h1 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . $sub . '</h1>'
            . $body
            . '</td></tr><tr><td style="padding:16px 24px;background:#f1f5f9;font-size:12px;color:#64748b;">'
            . 'This message was sent by ' . $lms . '. Please do not reply to this automated email.'
            . '</td></tr></table></td></tr></table></body></html>';
    }
}

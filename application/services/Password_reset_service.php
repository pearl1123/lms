<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Self-service password reset — token generation, validation, notification.
 *
 * @property User_model $user_model
 */
class Password_reset_service {

    public const TOKEN_TTL_MINUTES = 60;

    public const GENERIC_REQUEST_MESSAGE =
        'If your Employee ID is registered with kaBAGA Academy, password reset instructions will be sent when available.';

    /** @var CI_Controller&object{user_model: User_model} */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('User_model', 'user_model');
    }

    /**
     * Initiate password reset for an employee ID (always returns generic outward result).
     *
     * @param  string $employee_id
     * @return array{ok:bool,message:string,dev_reset_url:string,email_sent:bool}
     */
    public function request_reset($employee_id)
    {
        $employee_id = normalize_employee_id($employee_id);
        $out = [
            'ok'            => true,
            'message'       => self::GENERIC_REQUEST_MESSAGE,
            'dev_reset_url' => '',
            'email_sent'    => false,
        ];

        if ($employee_id === '') {
            return $out;
        }

        $user = $this->CI->user_model->get_active_user_by_employee_id($employee_id);
        if ( ! $user) {
            log_message('debug', 'Password reset requested for unknown/inactive employee (no user detail exposed).');

            return $out;
        }

        $plain_token = $this->_generate_token();
        $token_hash  = $this->_hash_token($plain_token);
        $start       = date('Y-m-d H:i:s');
        $end         = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_TTL_MINUTES . ' minutes'));

        if ( ! $this->CI->user_model->save_password_reset_token((int) $user->id, $token_hash, $start, $end)) {
            log_message('error', 'Password reset token save failed for user_id=' . (int) $user->id);

            return $out;
        }

        $reset_url = site_url('auth/reset-password/' . $plain_token);
        $email_sent = $this->_send_reset_email($user, $reset_url);

        $out['email_sent'] = $email_sent;

        if ( ! $email_sent && $this->_show_dev_reset_link()) {
            $out['dev_reset_url'] = $reset_url;
            $out['message'] = 'Email is not configured. Use the reset link below (expires in '
                . self::TOKEN_TTL_MINUTES . ' minutes).';
        }

        return $out;
    }

    /**
     * @param  string $plain_token
     * @return object|null Active user row when token is valid
     */
    public function validate_reset_token($plain_token)
    {
        $plain_token = trim((string) $plain_token);
        if ($plain_token === '' || strlen($plain_token) < 32) {
            return null;
        }

        return $this->CI->user_model->get_user_by_password_reset_token($this->_hash_token($plain_token));
    }

    /**
     * @param  string $plain_token
     * @param  string $password
     * @return array{ok:bool,message:string}
     */
    public function complete_reset($plain_token, $password)
    {
        $user = $this->validate_reset_token($plain_token);
        if ( ! $user) {
            return [
                'ok'      => false,
                'message' => 'This reset link is invalid or has expired. Please request a new one.',
            ];
        }

        if (strlen($password) < 8) {
            return [
                'ok'      => false,
                'message' => 'Password must be at least 8 characters.',
            ];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            return [
                'ok'      => false,
                'message' => 'Unable to update password. Please try again.',
            ];
        }

        if ( ! $this->CI->user_model->update_password_and_clear_reset_token((int) $user->id, $hash)) {
            return [
                'ok'      => false,
                'message' => 'Unable to update password. Please try again.',
            ];
        }

        log_message('info', 'Password reset completed for user_id=' . (int) $user->id);

        return [
            'ok'      => true,
            'message' => 'Your password has been reset. You can sign in now.',
        ];
    }

    private function _generate_token()
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (Exception $e) {
            return bin2hex(openssl_random_pseudo_bytes(32));
        }
    }

    private function _hash_token($plain_token)
    {
        return hash('sha256', (string) $plain_token);
    }

    private function _show_dev_reset_link()
    {
        return defined('ENVIRONMENT') && ENVIRONMENT !== 'production';
    }

    /**
     * @param  object $user
     * @param  string $reset_url
     * @return bool
     */
    private function _send_reset_email($user, $reset_url)
    {
        $email = trim((string) ($user->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            log_message('debug', 'Password reset email skipped: no valid email for user_id=' . (int) ($user->id ?? 0));

            return false;
        }

        if (strpos($email, '@lms.local') !== false) {
            log_message('debug', 'Password reset email skipped: placeholder address for user_id=' . (int) ($user->id ?? 0));

            return false;
        }

        $this->CI->load->library('email_service');
        /** @var Email_service $email_service */
        $email_service = $this->CI->{'email_service'};
        if ( ! $email_service) {
            return false;
        }

        $name = trim((string) ($user->fullname ?? 'Learner'));
        $subject = 'kaBAGA Academy — Password reset';
        $body = '<p>Hello ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>We received a request to reset your kaBAGA Academy password.</p>'
            . '<p><a href="' . htmlspecialchars($reset_url, ENT_QUOTES, 'UTF-8') . '">Reset your password</a></p>'
            . '<p>This link expires in ' . (int) self::TOKEN_TTL_MINUTES . ' minutes and can only be used once.</p>'
            . '<p>If you did not request this, you can ignore this email.</p>';

        $result = $email_service->send($email, $subject, $body);

        return ! empty($result['ok']);
    }
}

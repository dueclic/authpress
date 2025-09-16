<?php

namespace AuthPress\Providers;
class TOTP_Provider extends Abstract_Provider
{
    const SECRET_LENGTH = 32;
    const CODE_LENGTH = 6;
    const TIME_STEP = 30;
    const TIME_DRIFT = 1;

    /**
     * Generate a secure random secret key
     * @return string Base32 encoded secret
     */
    private function generate_secret()
    {
        $secret = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

        for ($i = 0; $i < self::SECRET_LENGTH; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $secret;
    }

    /**
     * Base32 decode function
     * @param string $data Base32 encoded data
     * @return string Binary data
     */
    private function base32_decode($data)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        $data = strtoupper($data);

        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            if ($char === '=') break;

            $pos = strpos($alphabet, $char);
            if ($pos === false) continue;

            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $result = '';
        for ($i = 0; $i < strlen($binary); $i += 8) {
            $byte = substr($binary, $i, 8);
            if (strlen($byte) === 8) {
                $result .= chr(bindec($byte));
            }
        }

        return $result;
    }

    /**
     * Normalize code by removing spaces and ensuring it's the correct length
     * @param string $code The code to normalize
     * @return string Normalized code
     */
    protected function normalize_code($code)
    {
        // First apply parent normalization
        $code = parent::normalize_code($code);

        // Then pad with leading zeros if needed for TOTP
        return str_pad($code, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Generate TOTP code for a given secret and timestamp
     * @param string $secret Base32 encoded secret
     * @param int|null $timestamp Unix timestamp (null for current time)
     * @return string 6-digit TOTP code
     */
    private function generate_totp($secret, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        $time_counter = intval($timestamp / self::TIME_STEP);
        $binary_secret = $this->base32_decode($secret);
        $time_bytes = pack('N*', 0) . pack('N*', $time_counter);

        $hash = hash_hmac('sha1', $time_bytes, $binary_secret, true);
        $offset = ord($hash[19]) & 0xf;

        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, self::CODE_LENGTH);

        return str_pad($code, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Verify TOTP code against a secret
     * @param string $code 6-digit code to verify
     * @param string $secret Base32 encoded secret
     * @param int|null $timestamp Unix timestamp (null for current time)
     * @return bool True if code is valid
     */
    private function verify_totp($code, $secret, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        $code = $this->normalize_code($code);

        // Check current time and drift windows
        for ($drift = -self::TIME_DRIFT; $drift <= self::TIME_DRIFT; $drift++) {
            $check_time = $timestamp + ($drift * self::TIME_STEP);
            $expected_code = $this->generate_totp($secret, $check_time);

            if (hash_equals($code, $expected_code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a QR code data URL for TOTP setup
     * @param string $secret Base32 encoded secret
     * @param string $account_name Account name (usually email or username)
     * @param string $issuer Issuer name (site name)
     * @return string QR code data URL (base64 encoded PNG)
     */
    public function get_qr_code_url($secret, $account_name, $issuer = null)
    {
        if (!$issuer) {
            $issuer = get_bloginfo('name');
        }

        $issuer = rawurlencode($issuer);
        $account_name = rawurlencode($account_name);

        $otpauth_url = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=%d&period=%d',
            $issuer,
            $account_name,
            $secret,
            $issuer,
            self::CODE_LENGTH,
            self::TIME_STEP
        );

        // Use PHPQrCode library for QR generation
        try {
            ob_start();
            \QRcode::png($otpauth_url, false, QR_ECLEVEL_M, 6, 2);
            $qr_data = ob_get_contents();
            ob_end_clean();

            return 'data:image/png;base64,' . base64_encode($qr_data);
        } catch (\Exception $e) {
            // Fallback to external service
            return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($otpauth_url);
        }
    }

    /**
     * Get or create TOTP secret for a user
     * @param int $user_id User ID
     * @return string Base32 encoded secret
     */
    public function get_user_secret($user_id)
    {
        $secret = get_user_meta($user_id, 'authpress_totp_secret', true);

        if (empty($secret)) {
            $secret = $this->generate_secret();
            update_user_meta($user_id, 'authpress_totp_secret', $secret);
        }

        return $secret;
    }

    /**
     * Check if user has TOTP enabled
     * @param int $user_id User ID
     * @return bool True if enabled
     */
    public function is_user_totp_enabled($user_id)
    {
        return get_user_meta($user_id, 'authpress_totp_enabled', true) === '1';
    }

    public function is_user_configured($user_id)
    {
        return $this->is_user_totp_enabled($user_id) && !empty(get_user_meta($user_id, 'authpress_totp_secret', true));
    }


    /**
     * Validate a code for a specific user
     * @param string $code The code to validate
     * @param int $user_id The user ID
     * @return bool True if valid, false otherwise
     */
    public function validate_code($code, $user_id)
    {
        if (!$this->is_user_totp_enabled($user_id)) {
            return false;
        }

        $secret = $this->get_user_secret($user_id);
        if (empty($secret)) {
            return false;
        }

        return $this->verify_totp($code, $secret);
    }

    /**
     * Verify TOTP code during setup (bypasses enabled check)
     * @param string $code The code to validate
     * @param int $user_id The user ID
     * @return bool True if valid, false otherwise
     */
    public function verify_setup_code($code, $user_id)
    {
        $secret = $this->get_user_secret($user_id);
        if (empty($secret)) {
            return false;
        }

        return $this->verify_totp($code, $secret);
    }

    /**
     * Generate new codes for a user (returns QR code and secret)
     * @param int $user_id The user ID
     * @param array $options Additional options
     * @return array The secret and QR code info
     */
    public function generate_codes($user_id, $options = [])
    {
        $secret = $this->get_user_secret($user_id);
        $user = get_userdata($user_id);

        if (!$user) {
            return [];
        }

        $account_name = $user->user_email;
        $qr_url = $this->get_qr_code_url($secret, $account_name);

        return [
            'secret' => $secret,
            'qr_code_url' => $qr_url,
            'account_name' => $account_name
        ];
    }

    /**
     * Check if user has active codes (TOTP setup)
     * @param int $user_id The user ID
     * @return bool True if user has TOTP setup, false otherwise
     */
    public function has_codes($user_id)
    {
        return $this->is_user_totp_enabled($user_id) && !empty($this->get_user_secret($user_id));
    }

    public function get_key()
    {
        return 'authenticator';
    }

    public function get_name()
    {
        return __("Authenticator App", "two-factor-login-telegram");
    }

    public function get_description()
    {
        return __("Google Authenticator, Authy, Microsoft Authenticator and other TOTP-compatible apps", "two-factor-login-telegram");
    }

    public function is_configured()
    {
        if (!$this->is_enabled()) {
            return false;
        }

        return true; // TOTP doesn't need external configuration
    }

    public function disable_user_method($user_id)
    {
        delete_user_meta($user_id, 'authpress_totp_enabled');
        delete_user_meta($user_id, 'authpress_totp_secret');
    }


    public function enable_user_method($user_id){
        return update_user_meta($user_id, 'authpress_totp_enabled', '1');
    }

}

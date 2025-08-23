<?php

namespace AuthPress\Providers;

use WP_User;

class Email_Provider extends Abstract_Provider implements Provider_Otp_Interface
{
    /**
     * Send authentication code to user via email
     * @param WP_User $user The user to send the code to
     * @return string|false The generated code or false on failure
     */
    public function send_email_otp($user)
    {
        if (!$user) {
            return false;
        }

        $auth_email = get_user_meta($user->ID, 'authpress_authentication_email', true);
        $recipient_email = !empty($auth_email) && is_email($auth_email) ? $auth_email : $user->user_email;

        if (empty($recipient_email)) {
            return false;
        }

        $auth_code = $this->generate_auth_code(6);
        $normalized_for_storage = $this->normalize_code($auth_code);
        $hashed_code = $this->hash_code($normalized_for_storage);

        global $wpdb;
        $table_name = $wpdb->prefix . 'telegram_auth_codes';

        // Clean up old codes for this user
        $wpdb->delete(
            $table_name,
            array('user_id' => $user->ID),
            array('%d')
        );

        // Insert new code
        $result = $wpdb->insert(
            $table_name,
            array(
                'auth_code' => $hashed_code,
                'user_id' => $user->ID,
                'creation_date' => current_time('mysql'),
                'expiration_date' => date('Y-m-d H:i:s', current_time('timestamp') + get_auth_token_duration())
            ),
            array('%s', '%d', '%s', '%s')
        );

        if ($result === false) {
            return false;
        }

        // Send email
        $subject = sprintf(__('[%s] Two-Factor Authentication Code', 'two-factor-login-telegram'), get_bloginfo('name'));

        $message = sprintf(
            __("Hello %s,\n\nHere's your verification code for logging into %s:\n\n%s\n\nThis code will expire in %d minutes.\n\nIf you didn't request this code, please ignore this email.\n\nBest regards,\n%s Team", 'two-factor-login-telegram'),
            $user->display_name,
            get_bloginfo('name'),
            $auth_code,
            get_auth_token_duration() / 60,
            get_bloginfo('name')
        );

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        $sent = wp_mail($recipient_email, $subject, $message, $headers);

        return $sent ? $auth_code : false;
    }

    /**
     * Validate email OTP code
     * @param string $code The code to validate
     * @param int $user_id The user ID
     * @return bool True if valid, false otherwise
     */
    public function validate_code($code, $user_id)
    {
        if (empty($code) || empty($user_id)) {
            return false;
        }

        $normalized_code = $this->normalize_code($code);
        $hashed_code = $this->hash_code($normalized_code);

        global $wpdb;
        $table_name = $wpdb->prefix . 'telegram_auth_codes';

        $stored_code = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND auth_code = %s AND expiration_date > %s ORDER BY creation_date DESC LIMIT 1",
            $user_id,
            $hashed_code,
            current_time('mysql')
        ));

        if ($stored_code) {
            // Delete the used code
            $wpdb->delete(
                $table_name,
                array('id' => $stored_code->id),
                array('%d')
            );
            return true;
        }

        return false;
    }

    /**
     * Check if code is expired or invalid
     * @param string $code The code to validate
     * @param int $user_id The user ID
     * @return string Status: 'valid', 'expired', or 'invalid'
     */
    public function validate_authcode($code, $user_id)
    {
        if (empty($code) || empty($user_id)) {
            return 'invalid';
        }

        $normalized_code = $this->normalize_code($code);
        $hashed_code = $this->hash_code($normalized_code);

        global $wpdb;
        $table_name = $wpdb->prefix . 'telegram_auth_codes';

        // Check if code exists and is valid
        $stored_code = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND auth_code = %s ORDER BY creation_date DESC LIMIT 1",
            $user_id,
            $hashed_code
        ));

        if (!$stored_code) {
            return 'invalid';
        }

        // Check if expired
        if (strtotime($stored_code->expiration_date) < current_time('timestamp')) {
            return 'expired';
        }

        return 'valid';
    }

    /**
     * Save authentication code for user (used during login)
     * @param WP_User $user The user
     * @return string|false The generated code or false on failure
     */
    public function save_authcode($user)
    {
        return $this->send_email_otp($user);
    }

    /**
     * Generate new codes for a user (email doesn't need pre-generated codes)
     * @param int $user_id The user ID
     * @param array $options Additional options
     * @return array Empty array (email sends codes on demand)
     */
    public function generate_codes($user_id, $options = [])
    {
        // Email OTP doesn't need pre-generated codes
        return array();
    }

    /**
     * Check if user has active codes (for email, this means having an email address)
     * @param int $user_id The user ID
     * @return bool True if user has an email address
     */
    public function has_codes($user_id)
    {
        $user = get_userdata($user_id);
        return $user && !empty($user->user_email);
    }

    /**
     * Delete all codes for a user
     * @param int $user_id The user ID
     * @return bool True on success, false on failure
     */
    public function delete_user_codes($user_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'telegram_auth_codes';

        $result = $wpdb->delete(
            $table_name,
            array('user_id' => $user_id),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Send an OTP code to the user via Email
     * @param WP_User|int $user The user object or user ID
     * @param string $code The OTP code to send
     * @return bool True on success, false on failure
     */
    public function send_otp($user, $code)
    {
        if (is_int($user)) {
            $user = get_userdata($user);
        }

        if (!$user) {
            return false;
        }

        $auth_email = get_user_meta($user->ID, 'authpress_authentication_email', true);
        $recipient_email = !empty($auth_email) && is_email($auth_email) ? $auth_email : $user->user_email;

        if (empty($recipient_email)) {
            return false;
        }

        $subject = sprintf(__('[%s] Two-Factor Authentication Code', 'two-factor-login-telegram'), get_bloginfo('name'));

        $message = sprintf(
            __("Hello %s,\n\nHere's your verification code for logging into %s:\n\n%s\n\nThis code will expire in %d minutes.\n\nIf you didn't request this code, please ignore this email.\n\nBest regards,\n%s Team", 'two-factor-login-telegram'),
            $user->display_name,
            get_bloginfo('name'),
            $code,
            get_auth_token_duration() / 60,
            get_bloginfo('name')
        );

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        return wp_mail($recipient_email, $subject, $message, $headers);
    }

    /**
     * Save and send an authentication code for a user
     * @param WP_User|int $user User object or user ID
     * @param int $code_length Length of the code to generate
     * @return string|false The generated code on success, false on failure
     */
    public function save_and_send_authcode($user, $code_length = 6)
    {
        if (is_int($user)) {
            $user = get_userdata($user);
        }

        if (!$user) {
            return false;
        }

        $auth_email = get_user_meta($user->ID, 'authpress_authentication_email', true);
        $recipient_email = !empty($auth_email) && is_email($auth_email) ? $auth_email : $user->user_email;

        if (empty($recipient_email)) {
            return false;
        }

        $auth_code = $this->generate_auth_code($code_length);
        $hashed_code = $this->hash_code($auth_code);

        global $wpdb;
        $table_name = $wpdb->prefix . 'telegram_auth_codes';

        $creation_date = current_time('mysql');
        $expiration_date = date('Y-m-d H:i:s', strtotime($creation_date) + get_auth_token_duration());

        $result = $wpdb->insert(
            $table_name,
            array(
                'auth_code' => $hashed_code,
                'user_id' => $user->ID,
                'creation_date' => $creation_date,
                'expiration_date' => $expiration_date
            ),
            array('%s', '%d', '%s', '%s')
        );

        if ($result && $this->send_otp($user, $auth_code)) {
            return $auth_code;
        }

        return false;
    }

    /**
     * Resend the last authentication code for a user
     * @param WP_User|int $user User object or user ID
     * @return bool True on success, false on failure
     */
    public function resend_authcode($user)
    {
        // For email, we generate a new code each time
        return $this->save_and_send_authcode($user) !== false;
    }

    /**
     * Check if the user can receive OTP via this provider
     * @param int $user_id The user ID
     * @return bool True if user can receive OTP, false otherwise
     */
    public function can_send_otp($user_id)
    {
        return \Authpress\AuthPress_User_Manager::user_has_email($user_id);
    }

    /**
     * Get the display name for this OTP provider
     * @return string The display name
     */
    public function get_provider_name()
    {
        return __('Email', 'two-factor-login-telegram');
    }

    /**
     * Get the icon URL for this provider
     * @return string PNG icon URL
     */
    public function get_icon()
    {
        $logo = plugin_dir_url(WP_FACTOR_TG_FILE) . '/assets/images/providers/email-icon.png';
        return apply_filters('authpress_provider_logo', $logo, 'email');
    }

    public function get_key()
    {
        return 'email';
    }

    public function get_name()
    {
        return __("Email", "two-factor-login-telegram");
    }

    public function get_description()
    {
        return __("Receive authentication codes via email messages", "two-factor-login-telegram");
    }
}

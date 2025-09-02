<?php

namespace AuthPress\Providers;

use \Authpress\WP_Telegram;
use WP_User;

class Telegram_Provider extends Abstract_Provider implements Provider_Otp_Interface
{
    private $telegram;

    public function __construct()
    {
        $this->telegram = new WP_Telegram();
    }
    /**
     * Generate a unique authentication code that doesn't exist in database
     * @param int $length Code length
     * @return string
     */
    private function get_unique_auth_code($length = 5)
    {
        do {
            $token = $this->generate_auth_code($length);
        } while ($this->token_exists($token));

        return $token;
    }

    /**
     * Check if a token exists in the database
     * @param string $token The token to check
     * @return bool True if exists, false otherwise
     */
    private function token_exists($token)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'authpress_telegram_auth_codes';
        $current_datetime = current_time('mysql');
        $hashed_token = $this->hash_code($this->normalize_code($token));

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE auth_code = %s 
             AND expiration_date > %s",
            $hashed_token,
            $current_datetime
        ));

        return ($result > 0);
    }

    /**
     * Invalidate all existing auth codes for a user
     * @param int $user_id The user ID
     * @return bool True on success, false on failure
     */
    private function invalidate_existing_auth_codes($user_id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'authpress_telegram_auth_codes';

        $result = $wpdb->update(
            $table_name,
            array('expiration_date' => current_time('mysql')),
            array('user_id' => $user_id),
            array('%s'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Cleanup old auth codes for a user (keep only 5 most recent)
     * @param int $user_id The user ID
     * @return bool True on success, false on failure
     */
    private function cleanup_old_auth_codes($user_id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'authpress_telegram_auth_codes';

        // Count existing codes for the user
        $auth_codes_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        ));

        // If more than 5 codes exist, delete the oldest ones
        if ($auth_codes_count > 5) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_name 
                 WHERE user_id = %d 
                 AND id NOT IN (
                     SELECT id FROM (
                         SELECT id FROM $table_name 
                         WHERE user_id = %d 
                         ORDER BY creation_date DESC 
                         LIMIT 5
                     ) AS recent_codes
                 )",
                $user_id,
                $user_id
            ));
        }

        return true;
    }

    /**
     * Save an authentication code for a user
     * @param mixed $user User object or user ID
     * @param int $authcode_length Length of the code to generate
     * @return string|false The generated code on success, false on failure
     */
    public function save_authcode($user, $authcode_length = 5)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'authpress_telegram_auth_codes';
        $auth_code = $this->get_unique_auth_code($authcode_length);
        $user_id = is_object($user) ? $user->ID : intval($user);

        $creation_date = current_time('mysql');
        $expiration_date = date('Y-m-d H:i:s', strtotime($creation_date) + get_auth_token_duration());

        $this->invalidate_existing_auth_codes($user_id);

        $result = $wpdb->insert(
            $table_name,
            array(
                'auth_code' => $this->hash_code($this->normalize_code($auth_code)),
                'user_id' => $user_id,
                'creation_date' => $creation_date,
                'expiration_date' => $expiration_date
            ),
            array('%s', '%d', '%s', '%s')
        );

        if ($wpdb->insert_id) {
            $this->cleanup_old_auth_codes($user_id);
            return $auth_code;
        }

        return false;
    }

    /**
     * Validate an authentication code for a user
     * @param string $authcode The code to validate
     * @param int $user_id The user ID
     * @return string 'valid', 'invalid', or 'expired'
     */
    public function validate_authcode($authcode, $user_id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'authpress_telegram_auth_codes';
        $normalized_auth_code = $this->normalize_code($authcode);
        $hashed_auth_code = $this->hash_code($normalized_auth_code);
        $current_datetime = current_time('mysql');

        // Check if token exists for this user
        $token_exists_query = $wpdb->prepare(
            "SELECT COUNT(*) 
            FROM $table_name 
            WHERE auth_code = %s 
            AND user_id = %d",
            $hashed_auth_code,
            $user_id
        );

        $token_exists = ($wpdb->get_var($token_exists_query) > 0);

        if (!$token_exists) {
            return 'invalid';
        }

        // Check if token is not expired
        $valid_token_query = $wpdb->prepare(
            "SELECT COUNT(*) 
            FROM $table_name 
            WHERE auth_code = %s 
            AND user_id = %d 
            AND expiration_date > %s",
            $hashed_auth_code,
            $user_id,
            $current_datetime
        );

        $is_valid = ($wpdb->get_var($valid_token_query) > 0);

        if (!$is_valid) {
            return 'expired';
        }

        return 'valid';
    }

    /**
     * Validate code for tokencheck (different validation method)
     * @param string $authcode The code to validate
     * @param string $chat_id The Telegram chat ID
     * @return bool True if valid, false otherwise
     */
    public function validate_tokencheck_authcode($authcode, $chat_id)
    {
        return $this->hash_code($authcode) === get_transient("authpress_telegram_authcode_" . $chat_id);
    }

    /**
     * Save user's 2FA settings
     *
     * @param int $user_id User ID
     * @param string $chat_id Telegram Chat ID
     * @param bool $enabled Whether 2FA is enabled
     * @return bool Success status
     */

    public function save_user_2fa_settings($user_id, $chat_id, $enabled = true){
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        if (empty($chat_id)) {
            return false;
        }

        update_user_meta($user_id, 'authpress_telegram_chat_id', sanitize_text_field($chat_id));
        update_user_meta($user_id, 'authpress_telegram_enabled', $enabled ? '1' : '0');

        return true;
    }

    // Implementation of abstract methods

    /**
     * Validate a code for a specific user
     * @param string $code The code to validate
     * @param int $user_id The user ID
     * @return bool True if valid, false otherwise
     */
    public function validate_code($code, $user_id)
    {
        $normalized_code = $this->normalize_code($code);
        $result = $this->validate_authcode($normalized_code, $user_id);
        return $result === 'valid';
    }

    /**
     * Generate new codes for a user (creates one OTP code)
     * @param int $user_id The user ID
     * @param array $options Additional options (length, etc.)
     * @return array The generated codes
     */
    public function generate_codes($user_id, $options = [])
    {
        $length = isset($options['length']) ? $options['length'] : 5;
        $code = $this->save_authcode($user_id, $length);
        return $code ? [$code] : [];
    }

    /**
     * Check if user has active codes
     * @param int $user_id The user ID
     * @return bool True if user has codes, false otherwise
     */
    public function has_codes($user_id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'authpress_telegram_auth_codes';
        $current_datetime = current_time('mysql');

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE user_id = %d 
             AND expiration_date > %s",
            $user_id,
            $current_datetime
        ));

        return ($count > 0);
    }

    /**
     * Delete all codes for a user
     * @param int $user_id The user ID
     * @return bool True on success, false on failure
     */
    public function delete_user_codes($user_id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'authpress_telegram_auth_codes';

        $result = $wpdb->delete(
            $table_name,
            array('user_id' => $user_id),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Send an OTP code to the user via Telegram
     * @param WP_User|int $user The user object or user ID
     * @param string $code The OTP code to send
     * @return bool True on success, false on failure
     */
    public function send_otp($user, $code)
    {
        $user_id = is_object($user) ? $user->ID : intval($user);
        $chat_id = \Authpress\AuthPress_User_Manager::get_user_chat_id($user_id);

        if (!$chat_id) {
            return false;
        }

        return $this->telegram->send_tg_token($code, $chat_id, $user_id) !== false;
    }

    /**
     * Save and send an authentication code for a user
     * @param WP_User|int $user User object or user ID
     * @param int $code_length Length of the code to generate
     * @return string|false The generated code on success, false on failure
     */
    public function save_and_send_authcode($user, $code_length = 5)
    {
        $auth_code = $this->save_authcode($user, $code_length);

        if ($auth_code && $this->send_otp($user, $auth_code)) {
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
        // Generate a new code and send it
        $auth_code = $this->save_authcode($user);

        if ($auth_code) {
            return $this->send_otp($user, $auth_code);
        }

        return false;
    }

    /**
     * Check if the user can receive OTP via this provider
     * @param int $user_id The user ID
     * @return bool True if user can receive OTP, false otherwise
     */
    public function can_send_otp($user_id)
    {
        return \Authpress\AuthPress_User_Manager::user_has_telegram($user_id);
    }

    /**
     * Get the display name for this OTP provider
     * @return string The display name
     */
    public function get_provider_name()
    {
        return __('Telegram', 'two-factor-login-telegram');
    }

    public function get_key()
    {
        return 'telegram';
    }

    public function get_name()
    {
        return __("Telegram", "two-factor-login-telegram");
    }

    public function get_description()
    {
        return __("Receive authentication codes via Telegram messages", "two-factor-login-telegram");
    }

    public function is_user_configured($user_id){
        return !empty(get_user_meta($user_id, 'authpress_telegram_chat_id', true));
    }

    public function is_configured()
    {
        if (!$this->is_enabled()) {
            return false;
        }

        $providers = authpress_providers();
        $bot_token = $providers['telegram']['bot_token'] ?? '';

        if (empty($bot_token)) {
            return false;
        }

        // Create a temporary WP_Telegram instance to validate this specific bot token
        $telegram = new WP_Telegram();
        $old_token = $telegram->get_bot_token();

        $telegram->set_bot_token($bot_token);
        $is_valid = $telegram->get_me() !== false;

        // Restore the original token
        $telegram->set_bot_token($old_token);

        return $is_valid;
    }


    public function is_valid_bot()
    {
        $valid_bot_transient = WP_FACTOR_TG_GETME_TRANSIENT;

        if (($is_valid_bot = get_transient($valid_bot_transient)) === false) {
            $is_valid_bot = $this->telegram->get_me() !== false;
            set_transient($valid_bot_transient, $is_valid_bot, 60 * 60 * 24);
        }

        return boolval($is_valid_bot);
    }

    public function disable_user_method($user_id){
        delete_user_meta($user_id, 'authpress_telegram_enabled');
        delete_user_meta($user_id, 'authpress_telegram_chat_id');
    }

    public function enable_user_method($user_id){
        update_user_meta($user_id, 'authpress_telegram_enabled', '1');
    }

}

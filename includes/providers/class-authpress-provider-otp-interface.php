<?php

namespace AuthPress\Providers;

interface OTP_Provider_Interface
{
    /**
     * Send an OTP code to the user
     * @param WP_User|int $user The user object or user ID
     * @param string $code The OTP code to send
     * @return bool True on success, false on failure
     */
    public function send_otp($user, $code);

    /**
     * Save and send an authentication code for a user
     * @param WP_User|int $user User object or user ID
     * @param int $code_length Length of the code to generate
     * @return string|false The generated code on success, false on failure
     */
    public function save_and_send_authcode($user, $code_length = 5);

    /**
     * Resend the last authentication code for a user
     * @param WP_User|int $user User object or user ID
     * @return bool True on success, false on failure
     */
    public function resend_authcode($user);

    /**
     * Check if the user can receive OTP via this provider
     * @param int $user_id The user ID
     * @return bool True if user can receive OTP, false otherwise
     */
    public function can_send_otp($user_id);

    /**
     * Get the display name for this OTP provider
     * @return string The display name
     */
    public function get_provider_name();
}
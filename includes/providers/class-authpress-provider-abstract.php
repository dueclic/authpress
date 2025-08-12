<?php

namespace AuthPress\Providers;

abstract class Abstract_Provider
{
    /**
     * Generate a random authentication code
     * @param int $length Code length
     * @return string
     */
    protected function generate_auth_code($length = 10)
    {
        $pool = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
        $key = "";

        for ($i = 0; $i < $length; $i++) {
            $key .= $pool[random_int(0, count($pool) - 1)];
        }

        return $key;
    }

    /**
     * Hash a code using SHA256
     * @param string $code The code to hash
     * @return string
     */
    protected function hash_code($code)
    {
        return hash('sha256', $code);
    }

    /**
     * Normalize a code by removing spaces, dashes and converting to uppercase
     * @param string $code The code to normalize
     * @return string
     */
    protected function normalize_code($code)
    {
        return strtoupper(str_replace([' ', '-'], '', trim($code)));
    }

    /**
     * Validate a code for a specific user
     * @param string $code The code to validate
     * @param int $user_id The user ID
     * @return bool True if valid, false otherwise
     */
    abstract public function validate_code($code, $user_id);

    /**
     * Generate new codes for a user
     * @param int $user_id The user ID
     * @param array $options Additional options
     * @return array The generated codes
     */
    abstract public function generate_codes($user_id, $options = []);

    /**
     * Check if user has active codes
     * @param int $user_id The user ID
     * @return bool True if user has codes, false otherwise
     */
    abstract public function has_codes($user_id);

    /**
     * Delete all codes for a user
     * @param int $user_id The user ID
     * @return bool True on success, false on failure
     */
    abstract public function delete_user_codes($user_id);

    /**
     * Get the icon URL for this provider
     * @return string PNG icon URL
     */
    abstract public function get_icon();

    /**
     * Get the provider key/identifier
     * @return string Provider key
     */
    abstract public function get_key();

    /**
     * Get the provider display name
     * @return string Provider name
     */
    abstract public function get_name();

    /**
     * Get the provider description
     * @return string Provider description
     */
    abstract public function get_description();

    /**
     * Check if provider is enabled
     * @return bool True if enabled, false otherwise
     */
    public function is_enabled()
    {
        $providers = authpress_providers();
        return isset($providers[$this->get_key()]['enabled']) && $providers[$this->get_key()]['enabled'];
    }

    /**
     * Check if provider is configured properly
     * @return bool True if configured, false otherwise
     */
    public function is_configured()
    {
        return $this->is_enabled();
    }

    /**
     * Check if provider is available for use
     * @return bool True if available, false otherwise
     */
    public function is_available()
    {
        return $this->is_enabled() && $this->is_configured();
    }
}
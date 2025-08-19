<?php

namespace AuthPress\Providers;

abstract class Abstract_Provider
{
    /**
     * @var string Provider key
     */
    protected $key;

    /**
     * @var string Provider name
     */
    protected $name;

    /**
     * @var string Provider description
     */
    protected $description;

    /**
     * @var string Provider icon URL
     */
    protected $icon_url;

    /**
     * Constructor for external providers
     * @param string $key Provider key
     * @param string $name Provider name
     * @param string $description Provider description
     * @param string $icon_url Provider icon URL
     */
    public function __construct($key = null, $name = null, $description = null, $icon_url = null)
    {
        if ($key !== null) {
            $this->key = $key;
            $this->name = $name;
            $this->description = $description;
            $this->icon_url = $icon_url;
        }
    }
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
     * Get the icon URL for this provider
     * @return string PNG icon URL
     */
    public function get_icon()
    {
        if (!empty($this->icon_url)) {
            return $this->icon_url;
        }

        // Fallback for providers that don't override this method
        return plugin_dir_url(WP_FACTOR_TG_FILE) . 'assets/icons/default-provider.png';
    }

    /**
     * Get the provider key/identifier
     * @return string Provider key
     */
    public function get_key()
    {
        return $this->key ?? static::class;
    }

    /**
     * Get the provider display name
     * @return string Provider name
     */
    public function get_name()
    {
        return $this->name ?? 'Unknown Provider';
    }

    /**
     * Get the provider description
     * @return string Provider description
     */
    public function get_description()
    {
        return $this->description ?? 'No description available';
    }

    /**
     * Get the path to provider's configuration template
     * @return string|null Template path or null if no template
     */
    public function get_config_template_path()
    {
        // Default: look for template in AuthPress main plugin directory
        $default_path = dirname(WP_FACTOR_TG_FILE) . "/templates/provider-configs/{$this->get_key()}.php";

        if (file_exists($default_path)) {
            return $default_path;
        }

        return null;
    }

    /**
     * Get the path to provider's user settings template
     * @return string|null Template path or null if no template
     */
    public function get_user_template_path()
    {
        $template_path = dirname(WP_FACTOR_TG_FILE) . "/templates/provider-templates/{$this->get_key()}.php";

        if (file_exists($template_path)) {
            return $template_path;
        }

        return dirname(WP_FACTOR_TG_FILE) . "/templates/provider-templates/{$this->get_key()}.php";
    }

    /**
     * Get the path to provider's features template
     * @return string|null Template path or null if no template
     */
    public function get_features_template_path()
    {
        // Default: look for template in AuthPress main plugin directory
        $default_path = dirname(WP_FACTOR_TG_FILE) . "/templates/provider-features/{$this->get_key()}.php";

        if (file_exists($default_path)) {
            return $default_path;
        }

        return null;
    }

    /**
     * Get the path to provider's features template
     * @return string|null Template path or null if no template
     */
    public function get_login_template_path()
    {
        // Default: look for template in AuthPress main plugin directory
        $default_path = dirname(WP_FACTOR_TG_FILE) . "/templates/provider-login/{$this->get_key()}.php";

        if (file_exists($default_path)) {
            return $default_path;
        }

        return dirname(WP_FACTOR_TG_FILE) . "/templates/provider-login.php";
    }

    /**
     * Check if provider is enabled
     * Uses WordPress filters to allow customization
     * @return bool True if enabled, false otherwise
     */
    public function is_enabled()
    {
        // Check if provider is enabled in AuthPress settings
        $providers = authpress_providers();
        $enabled = isset($providers[$this->get_key()]['enabled']) && $providers[$this->get_key()]['enabled'];

        // Allow filtering of provider enabled state
        $enabled = apply_filters("authpress_{$this->get_key()}_provider_enabled", $enabled);
        $enabled = apply_filters('authpress_provider_enabled', $enabled, $this->get_key());

        return $enabled;
    }

    /**
     * Check if provider is configured properly
     * Uses WordPress filters to allow customization
     * @return bool True if configured, false otherwise
     */
    public function is_configured()
    {
        // Allow filtering of provider configuration state
        $configured = apply_filters("authpress_{$this->get_key()}_provider_configured", $this->is_enabled());
        $configured = apply_filters('authpress_provider_configured', $configured, $this->get_key());

        return $configured;
    }

    /**
     * Check if provider is available for use
     * @return bool True if available, false otherwise
     */
    public function is_available()
    {
        return $this->is_enabled() && $this->is_configured();
    }

    /**
     * Send a code to the user
     * Override this method to implement code sending logic
     * @param string $code The code to send
     * @param int $user_id The user ID
     * @param array $options Additional options
     * @return bool True on success, false on failure
     */
    public function send_code($code, $user_id, $options = [])
    {
        // Allow filtering of code sending
        $result = apply_filters("authpress_{$this->get_key()}_send_code", false, $code, $user_id, $options);
        $result = apply_filters('authpress_provider_send_code', $result, $this->get_key(), $code, $user_id, $options);

        return $result;
    }

    /**
     * Get user meta key for storing codes
     * @return string
     */
    protected function get_user_meta_key()
    {
        return "authpress_{$this->get_key()}_codes";
    }

    /**
     * Get user meta key for storing provider settings
     * @return string
     */
    protected function get_user_settings_meta_key()
    {
        return "authpress_{$this->get_key()}_settings";
    }

    /**
     * Store codes for a user
     * @param int $user_id The user ID
     * @param array $codes The codes to store (will be hashed)
     * @param array $options Additional options
     * @return bool True on success, false on failure
     */
    protected function store_user_codes($user_id, $codes, $options = [])
    {
        if (!is_array($codes)) {
            return false;
        }

        // Hash all codes
        $hashed_codes = [];
        foreach ($codes as $code) {
            $hashed_codes[] = [
                'code' => $this->hash_code($code),
                'created' => time(),
                'used' => false
            ];
        }

        $data = [
            'codes' => $hashed_codes,
            'created_at' => time(),
            'options' => $options
        ];

        return update_user_meta($user_id, $this->get_user_meta_key(), $data);
    }

    /**
     * Get stored codes for a user
     * @param int $user_id The user ID
     * @return array|false Array of codes or false if none found
     */
    protected function get_user_codes($user_id)
    {
        $data = get_user_meta($user_id, $this->get_user_meta_key(), true);
        return is_array($data) ? $data : false;
    }

    /**
     * Store user settings
     * @param int $user_id The user ID
     * @param array $settings The settings to store
     * @return bool True on success, false on failure
     */
    protected function store_user_settings($user_id, $settings)
    {
        return update_user_meta($user_id, $this->get_user_settings_meta_key(), $settings);
    }

    /**
     * Get user settings
     * @param int $user_id The user ID
     * @return array User settings or empty array if none found
     */
    protected function get_user_settings($user_id)
    {
        $settings = get_user_meta($user_id, $this->get_user_settings_meta_key(), true);
        return is_array($settings) ? $settings : [];
    }

    /**
     * Default implementation of validate_code
     * Validates against stored codes
     * @param string $code The code to validate
     * @param int $user_id The user ID
     * @return bool True if valid, false otherwise
     */
    public function validate_code($code, $user_id)
    {
        $code = $this->normalize_code($code);
        $code_hash = $this->hash_code($code);

        $data = $this->get_user_codes($user_id);
        if (!$data || !isset($data['codes'])) {
            return false;
        }

        // Check each stored code
        foreach ($data['codes'] as $index => $stored_code) {
            if (!$stored_code['used'] && hash_equals($stored_code['code'], $code_hash)) {
                // Mark as used
                $data['codes'][$index]['used'] = true;
                $data['codes'][$index]['used_at'] = time();
                update_user_meta($user_id, $this->get_user_meta_key(), $data);

                return true;
            }
        }

        return false;
    }

    /**
     * Default implementation of generate_codes
     * Generates numeric codes and stores them
     * @param int $user_id The user ID
     * @param array $options Additional options
     * @return array The generated codes
     */
    public function generate_codes($user_id, $options = [])
    {
        $count = $options['count'] ?? 1;
        $length = $options['length'] ?? 6;
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->generate_numeric_code($length);
        }

        $this->store_user_codes($user_id, $codes, $options);

        return $codes;
    }

    /**
     * Generate a numeric code
     * @param int $length Code length
     * @return string
     */
    protected function generate_numeric_code($length = 6)
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= random_int(0, 9);
        }
        return $code;
    }

    /**
     * Check if user has active codes
     * @param int $user_id The user ID
     * @return bool True if user has codes, false otherwise
     */
    public function has_codes($user_id)
    {
        $data = $this->get_user_codes($user_id);
        if (!$data || !isset($data['codes'])) {
            return false;
        }

        // Check if there are any unused codes
        foreach ($data['codes'] as $code) {
            if (!$code['used']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Delete all codes for a user
     * @param int $user_id The user ID
     * @return bool True on success, false on failure
     */
    public function delete_user_codes($user_id)
    {
        $result1 = delete_user_meta($user_id, $this->get_user_meta_key());
        $result2 = delete_user_meta($user_id, $this->get_user_settings_meta_key());

        return $result1 !== false || $result2 !== false;
    }
}

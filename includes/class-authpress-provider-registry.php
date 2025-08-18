<?php

namespace Authpress;

use AuthPress\Providers\Abstract_Provider;

class AuthPress_Provider_Registry
{
    /**
     * @var array Registered provider instances
     */
    private static $providers = [];

    /**
     * @var array Provider class mappings
     */
    private static $provider_classes = [
        'telegram' => 'AuthPress\Providers\Telegram_Provider',
        'email' => 'AuthPress\Providers\Email_Provider',
        'authenticator' => 'AuthPress\Providers\TOTP_Provider',
        'recovery_codes' => 'AuthPress\Providers\Recovery_Codes_Provider',
    ];

    /**
     * @var bool Whether external providers have been loaded
     */
    private static $external_providers_loaded = false;

    /**
     * @var int Last external provider count to detect new registrations
     */
    private static $last_external_count = 0;

    /**
     * Register a provider class
     * @param string $key Provider key
     * @param string $class_name Provider class name
     */
    public static function register($key, $class_name)
    {
        self::$provider_classes[$key] = $class_name;
    }

    /**
     * Load external providers using WordPress filters
     */
    private static function load_external_providers()
    {
        // Always check for new external providers to handle timing issues
        $external_providers = apply_filters('authpress_register_providers', []);
        $current_count = count($external_providers);

        // Skip if we've already loaded and no new providers
        if (self::$external_providers_loaded && $current_count === self::$last_external_count) {
            return;
        }

        if (is_array($external_providers)) {
            foreach ($external_providers as $key => $class_name) {
                if (is_string($key) && is_string($class_name) && !empty($key) && !empty($class_name)) {
                    self::register($key, $class_name);
                }
            }
        }

        self::$external_providers_loaded = true;
        self::$last_external_count = $current_count;
    }

    /**
     * Get a provider instance
     * @param string $key Provider key
     * @return Abstract_Provider|null
     */
    public static function get($key)
    {
        self::load_external_providers();

        if (isset(self::$providers[$key])) {
            return self::$providers[$key];
        }

        if (!isset(self::$provider_classes[$key])) {
            return null;
        }

        $class_name = self::$provider_classes[$key];
        if (!class_exists($class_name)) {
            return null;
        }

        $provider = new $class_name();
        if (!($provider instanceof Abstract_Provider)) {
            return null;
        }

        self::$providers[$key] = $provider;
        return $provider;
    }

    /**
     * Get all available providers
     * @return Abstract_Provider[]
     */
    public static function get_all()
    {
        self::load_external_providers();

        $providers = [];
        foreach (array_keys(self::$provider_classes) as $key) {
            $provider = self::get($key);
            if ($provider) {
                $providers[$key] = $provider;
            }
        }
        return $providers;
    }

    /**
     * Get all enabled providers
     * @return Abstract_Provider[]
     */
    public static function get_enabled()
    {
        $enabled = [];
        foreach (self::get_all() as $key => $provider) {
            if ($provider->is_enabled()) {
                $enabled[$key] = $provider;
            }
        }
        return $enabled;
    }

    /**
     * Get all available providers (enabled and configured)
     * @return Abstract_Provider[]
     */
    public static function get_available()
    {
        $available = [];
        foreach (self::get_all() as $key => $provider) {
            if ($provider->is_available()) {
                $available[$key] = $provider;
            }
        }
        return $available;
    }

    /**
     * Get provider by login method
     * @param string $login_method Login method
     * @return Abstract_Provider|null
     */
    public static function get_by_login_method($login_method)
    {
        $mappings = [
            'recovery' => 'recovery_codes',
            'totp' => 'authenticator',
            'authenticator' => 'authenticator',
            'email' => 'email',
            'telegram' => 'telegram',
        ];

        $key = $mappings[$login_method] ?? $login_method;
        return self::get($key);
    }

    /**
     * Check if any providers are available
     * @return bool
     */
    public static function has_available_providers()
    {
        return !empty(self::get_available());
    }

    /**
     * Force reload of external providers (for timing issues)
     * @return void
     */
    public static function force_reload_external_providers()
    {
        self::$external_providers_loaded = false;
        self::$last_external_count = 0;
        self::$providers = [];
    }
}

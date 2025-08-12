<?php

namespace Authpress;

/**
 * Centralized user authentication management for AuthPress
 * 
 * This class handles all user authentication logic, provider availability checks,
 * and configuration validation in a single, consistent place.
 */
final class AuthPress_User_Manager
{
    /**
     * Cached provider settings
     * @var array|null
     */
    private static $providers_cache = null;

    /**
     * Cached bot validation status
     * @var bool|null
     */
    private static $bot_valid_cache = null;

    /**
     * Get providers configuration with caching
     * 
     * @return array
     */
    private static function get_providers()
    {
        if (self::$providers_cache === null) {
            self::$providers_cache = authpress_providers();
        }
        return self::$providers_cache;
    }

    /**
     * Check if Telegram provider is enabled
     * 
     * @return bool
     * @deprecated Use AuthPress_Provider_Registry::get('telegram')->is_enabled() instead
     */
    public static function is_telegram_provider_enabled()
    {
        $provider = AuthPress_Provider_Registry::get('telegram');
        return $provider && $provider->is_enabled();
    }

    /**
     * Check if Email provider is enabled
     * 
     * @return bool
     * @deprecated Use AuthPress_Provider_Registry::get('email')->is_enabled() instead
     */
    public static function is_email_provider_enabled()
    {
        $provider = AuthPress_Provider_Registry::get('email');
        return $provider && $provider->is_enabled();
    }

    /**
     * Check if Authenticator provider is enabled
     * 
     * @return bool
     * @deprecated Use AuthPress_Provider_Registry::get('authenticator')->is_enabled() instead
     */
    public static function is_authenticator_provider_enabled()
    {
        $provider = AuthPress_Provider_Registry::get('authenticator');
        return $provider && $provider->is_enabled();
    }

    /**
     * Check if Telegram bot is valid with caching
     * 
     * @return bool
     */
    public static function is_telegram_bot_valid()
    {
        if (self::$bot_valid_cache === null) {
            $plugin = AuthPress_Plugin::get_instance();
            self::$bot_valid_cache = $plugin->is_valid_bot();
        }
        return self::$bot_valid_cache;
    }

    /**
     * Check if user has Telegram configured and available
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function user_has_telegram($user_id)
    {
        if (!self::is_telegram_provider_enabled() || !self::is_telegram_bot_valid()) {
            return false;
        }

        $enabled = get_the_author_meta("tg_wp_factor_enabled", $user_id) === "1";
        $chat_id = get_user_meta($user_id, "tg_wp_factor_chat_id", true);
        
        // More strict validation: chat_id must be a non-empty string and enabled must be exactly "1"
        $has_valid_config = $enabled && is_string($chat_id) && trim($chat_id) !== '';
        
        // Clean up orphaned configuration: if enabled but no valid chat_id, disable it
        if ($enabled && (!is_string($chat_id) || trim($chat_id) === '')) {
            update_user_meta($user_id, 'tg_wp_factor_enabled', '0');
            delete_user_meta($user_id, 'tg_wp_factor_chat_id');
            return false;
        }
        
        return $has_valid_config;
    }

    /**
     * Check if user has Email configured and available
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function user_has_email($user_id)
    {
        if (!self::is_email_provider_enabled()) {
            return false;
        }

        // Check if user has email 2FA enabled
        $enabled = get_user_meta($user_id, "wp_factor_email_enabled", true) === "1";
        if (!$enabled) {
            return false;
        }

        $user = get_userdata($user_id);
        return $user && !empty($user->user_email);
    }

    /**
     * Check if user has TOTP configured and available
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function user_has_totp($user_id)
    {
        if (!self::is_authenticator_provider_enabled()) {
            return false;
        }

        $totp = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_TOTP);
        if (!$totp) {
            return false;
        }

        $enabled = $totp->is_user_totp_enabled($user_id);
        $secret = $totp->get_user_secret($user_id);
        
        return $enabled && !empty($secret);
    }

    /**
     * Check if user has any 2FA method configured
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function user_has_2fa($user_id)
    {
        $methods = self::get_user_available_methods($user_id);
        foreach ($methods as $method_available) {
            if ($method_available) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get available 2FA methods for a user
     * 
     * @param int $user_id User ID
     * @return array Available methods ['telegram' => bool, 'email' => bool, 'totp' => bool]
     */
    public static function get_user_available_methods($user_id)
    {
        $methods = [
            'telegram' => self::user_has_telegram($user_id),
            'email' => self::user_has_email($user_id),
            'totp' => self::user_has_totp($user_id)
        ];
        
        // Allow external providers to add their methods
        $methods = apply_filters('authpress_user_available_methods', $methods, $user_id);
        
        return $methods;
    }

    /**
     * Get system default provider
     * 
     * @return string
     */
    public static function get_system_default_provider()
    {
        $providers = self::get_providers();
        return isset($providers['default_provider']) ? $providers['default_provider'] : 'telegram';
    }

    /**
     * Get effective default provider for a user
     * 
     * @param int $user_id User ID
     * @return string|null The effective provider or null if no methods available
     */
    public static function get_user_effective_provider($user_id)
    {
        // Get user preference
        $user_preference = get_user_meta($user_id, 'wp_factor_user_default_provider', true);
        
        // Get system default
        $system_default = self::get_system_default_provider();
        
        // Determine preferred provider
        $preferred = !empty($user_preference) ? $user_preference : $system_default;
        
        // Normalize authenticator -> totp
        if ($preferred === 'authenticator') {
            $preferred = 'totp';
        }

        // Get available methods
        $available = self::get_user_available_methods($user_id);

        // Check if preferred method is available (includes custom providers)
        if (isset($available[$preferred]) && $available[$preferred]) {
            return $preferred;
        }

        // Fallback to system default if available
        $normalized_default = ($system_default === 'authenticator') ? 'totp' : $system_default;
        if (isset($available[$normalized_default]) && $available[$normalized_default]) {
            return $normalized_default;
        }

        // Final fallback to any available method (priority order: Telegram, Email, TOTP, then any other)
        if ($available['telegram'] ?? false) {
            return 'telegram';
        }
        if ($available['email'] ?? false) {
            return 'email';
        }
        if ($available['totp'] ?? false) {
            return 'totp';
        }
        
        // Return the first available custom provider
        foreach ($available as $provider_key => $is_available) {
            if ($is_available && !in_array($provider_key, ['telegram', 'email', 'totp'])) {
                return $provider_key;
            }
        }

        return null;
    }

    /**
     * Check if user has email available for 2FA (regardless of enabled status)
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function user_email_available($user_id)
    {
        if (!self::is_email_provider_enabled()) {
            return false;
        }

        $user = get_userdata($user_id);
        return $user && !empty($user->user_email);
    }

    /**
     * Enable email 2FA for user
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function enable_user_email($user_id)
    {
        if (!self::user_email_available($user_id)) {
            return false;
        }
        return update_user_meta($user_id, 'wp_factor_email_enabled', '1');
    }

    /**
     * Disable email 2FA for user
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function disable_user_email($user_id)
    {
        return delete_user_meta($user_id, 'wp_factor_email_enabled');
    }

    /**
     * Get user's Telegram chat ID
     * 
     * @param int $user_id User ID
     * @return string|false Chat ID or false if not set
     */
    public static function get_user_chat_id($user_id)
    {
        return get_user_meta($user_id, "tg_wp_factor_chat_id", true) ?: false;
    }

    /**
     * Check if user has completed the 2FA setup wizard
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function user_has_completed_setup($user_id)
    {
        return get_user_meta($user_id, 'authpress_already_setup', true) === '1';
    }

    /**
     * Mark user as having completed the 2FA setup
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function mark_user_setup_completed($user_id)
    {
        return update_user_meta($user_id, 'authpress_already_setup', '1');
    }

    /**
     * Reset user setup flag (for forcing them to go through wizard again)
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function reset_user_setup($user_id)
    {
        return delete_user_meta($user_id, 'authpress_already_setup');
    }

    /**
     * Check if user needs 2FA for login
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function user_requires_2fa($user_id)
    {
        // Check if any providers are enabled
        if (!self::is_telegram_provider_enabled() && !self::is_email_provider_enabled() && !self::is_authenticator_provider_enabled()) {
            // Check legacy settings for backward compatibility
            $legacy_enabled = get_option('tg_col')['enabled'] === '1';
            if (!$legacy_enabled) {
                return false;
            }
        }

        return self::user_has_2fa($user_id);
    }

    /**
     * Check if user should see the setup wizard on login
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function user_should_see_wizard($user_id)
    {
        // Skip wizard for administrators
        if (user_can($user_id, 'manage_options')) {
            return false;
        }

        // Check if any providers are enabled for non-admin users
        if (!self::is_telegram_provider_enabled() && !self::is_email_provider_enabled() && !self::is_authenticator_provider_enabled()) {
            return false;
        }

        // Check if user has already completed setup
        if (self::user_has_completed_setup($user_id)) {
            return false;
        }

        // Show wizard if user doesn't have any 2FA method configured
        return !self::user_has_2fa($user_id);
    }

    /**
     * Get user's 2FA configuration summary
     * 
     * @param int $user_id User ID
     * @return array Configuration summary
     */
    public static function get_user_2fa_config($user_id)
    {
        $available = self::get_user_available_methods($user_id);
        $effective_provider = self::get_user_effective_provider($user_id);
        
        return [
            'has_2fa' => self::user_has_2fa($user_id),
            'requires_2fa' => self::user_requires_2fa($user_id),
            'available_methods' => $available,
            'effective_provider' => $effective_provider,
            'chat_id' => $available['telegram'] ? self::get_user_chat_id($user_id) : false,
            'providers_enabled' => [
                'telegram' => self::is_telegram_provider_enabled(),
                'email' => self::is_email_provider_enabled(),
                'authenticator' => self::is_authenticator_provider_enabled()
            ]
        ];
    }

    /**
     * Clear caches (useful for testing or when settings change)
     */
    public static function clear_cache()
    {
        self::$providers_cache = null;
        self::$bot_valid_cache = null;
    }

    /**
     * Helper: Get all users with 2FA enabled
     * 
     * @param string $method Filter by specific method ('telegram', 'totp', 'any')
     * @return array Array of user IDs
     */
    public static function get_users_with_2fa($method = 'any')
    {
        global $wpdb;
        
        $user_ids = [];
        
        if ($method === 'telegram' || $method === 'any') {
            // Get users with Telegram enabled
            $telegram_users = $wpdb->get_col("
                SELECT DISTINCT user_id 
                FROM {$wpdb->usermeta} 
                WHERE meta_key = 'tg_wp_factor_enabled' 
                AND meta_value = '1'
            ");
            
            foreach ($telegram_users as $user_id) {
                if (self::user_has_telegram($user_id)) {
                    $user_ids[] = (int)$user_id;
                }
            }
        }
        
        if ($method === 'totp' || $method === 'any') {
            // Get users with TOTP enabled
            $totp_users = $wpdb->get_col("
                SELECT DISTINCT user_id 
                FROM {$wpdb->usermeta} 
                WHERE meta_key = 'wp_factor_totp_enabled' 
                AND meta_value = '1'
            ");
            
            foreach ($totp_users as $user_id) {
                if (self::user_has_totp($user_id)) {
                    $user_ids[] = (int)$user_id;
                }
            }
        }
        
        return array_unique($user_ids);
    }

    /**
     * Helper: Quick check if 2FA should be enforced for login
     * 
     * @param WP_User $user The user object
     * @return bool
     */
    public static function should_enforce_2fa_for_user($user)
    {
        if (!$user || !$user->exists()) {
            return false;
        }
        
        return self::user_requires_2fa($user->ID);
    }

    /**
     * Helper: Get human-readable status for user 2FA
     * 
     * @param int $user_id User ID
     * @return string Status description
     */
    public static function get_user_2fa_status_text($user_id)
    {
        $config = self::get_user_2fa_config($user_id);
        
        if (!$config['has_2fa']) {
            return __('Disabled', 'two-factor-login-telegram');
        }
        
        $methods = [];
        $method_names = [];
        
        // Build list of available methods and their display names
        foreach ($config['available_methods'] as $method_key => $is_available) {
            if ($is_available) {
                switch ($method_key) {
                    case 'telegram':
                        $methods[] = __('Telegram', 'two-factor-login-telegram');
                        $method_names[$method_key] = __('Telegram', 'two-factor-login-telegram');
                        break;
                    case 'email':
                        $methods[] = __('Email', 'two-factor-login-telegram');
                        $method_names[$method_key] = __('Email', 'two-factor-login-telegram');
                        break;
                    case 'totp':
                        $methods[] = __('Authenticator', 'two-factor-login-telegram');
                        $method_names[$method_key] = __('Authenticator', 'two-factor-login-telegram');
                        break;
                    default:
                        // Handle custom providers
                        $provider = AuthPress_Provider_Registry::get($method_key);
                        if ($provider) {
                            $provider_name = $provider->get_name();
                            $methods[] = $provider_name;
                            $method_names[$method_key] = $provider_name;
                        }
                        break;
                }
            }
        }
        
        if (empty($methods)) {
            return __('Misconfigured', 'two-factor-login-telegram');
        }
        
        $methods_text = implode(' + ', $methods);
        $default_method = $config['effective_provider'];
        
        if ($default_method && isset($method_names[$default_method])) {
            return sprintf(
                __('Enabled (%s, default: %s)', 'two-factor-login-telegram'),
                $methods_text,
                $method_names[$default_method]
            );
        }
        
        return sprintf(__('Enabled (%s)', 'two-factor-login-telegram'), $methods_text);
    }
}
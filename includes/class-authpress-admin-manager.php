<?php

namespace Authpress;

use AuthPress\Providers\Abstract_Provider;

class AuthPress_Admin_Manager
{
    /**
     * @var $telegram WP_Telegram
     */
    private $telegram;
    /**
     * @var $logger AuthPress_Logger
     */
    private $logger;

    public function __construct($telegram, $logger)
    {
        $this->telegram = $telegram;
        $this->logger = $logger;
    }

    public function configure_admin_page()
    {
        $providers = authpress_providers();
        require_once(dirname(WP_FACTOR_TG_FILE) . "/templates/admin/configuration.php");
    }

    public function show_user_2fa_page()
    {
        require_once(dirname(WP_FACTOR_TG_FILE) . "/templates/admin/user-2fa-settings-page.php");
    }

    public function handle_2fa_settings_forms()
    {
        if (!isset($_POST['wp_factor_action']) || !is_user_logged_in()) {
            return;
        }

        $action = sanitize_text_field($_POST['wp_factor_action']);
        $current_user_id = get_current_user_id();

        switch ($action) {

            case 'save_telegram':
                $this->handle_save_telegram($current_user_id);
                break;

            case 'setup_telegram':
                $this->handle_setup_telegram();
                break;

            case 'generate_recovery':
                $this->handle_generate_recovery($current_user_id);
                break;

            case 'regenerate_recovery':
                $this->handle_regenerate_recovery($current_user_id);
                break;

            case 'set_default_provider':
                $this->handle_set_default_provider($current_user_id);
                break;

            default:
                // Handle generic provider disable/enable actions
                if (strpos($action, 'disable_') === 0) {
                    $provider_key = substr($action, 8); // Remove "disable_" prefix
                    $this->handle_generic_disable_provider($current_user_id, $provider_key);
                } elseif (strpos($action, 'enable_') === 0) {
                    $provider_key = substr($action, 7); // Remove "enable_" prefix
                    $this->handle_generic_enable_provider($current_user_id, $provider_key);
                }
                break;
        }
    }

    /**
     * Apply different col class according to provider
     * @param string $cls
     * @param Abstract_Provider $provider
     * @param string $provider_key
     * @param string $category_key
     * @return string
     */

    public function provider_card_col_class($cls, $provider, $provider_key, $category_key){
        if ($provider_key === 'authenticator' || $provider_key === 'totp' || $category_key === 'other'){
            return 'ap-col-12';
        }
        return $cls;
    }

    /**
     * Check if a provider can be disabled (cannot disable user's default method unless it's the last active one)
     *
     * @param int $user_id User ID
     * @param string $provider_key Provider key to check
     * @return bool True if can be disabled, false otherwise
     */
    private function can_disable_provider($user_id, $provider_key)
    {
        $user_default_provider = get_user_meta($user_id, 'authpress_default_provider', true);

        // If no user default is set, any provider can be disabled
        if (empty($user_default_provider)) {
            return true;
        }

        // Check if this provider is the user's default method
        $is_default_provider = false;
        if ($provider_key === 'totp' || $provider_key === 'authenticator') {
            $is_default_provider = ($user_default_provider === 'authenticator' || $user_default_provider === 'totp');
        } else {
            $is_default_provider = ($user_default_provider === $provider_key);
        }

        // If it's not the default provider, it can always be disabled
        if (!$is_default_provider) {
            return true;
        }

        // If it IS the default provider, check if it's the last active method
        // Get all available methods for this user
        $available_methods = AuthPress_User_Manager::get_user_available_methods($user_id);

        // Count enabled methods (excluding recovery codes as they're backup only)
        $enabled_count = 0;
        foreach ($available_methods as $method_key => $is_available) {
            if ($is_available && $method_key !== 'recovery_codes') {
                $enabled_count++;
            }
        }

        // Allow disabling default provider if it's the last active method (user wants to disable all 2FA)
        return $enabled_count <= 1;
    }

    /**
     * Show error notice for attempting to disable the user's default 2FA method
     *
     * @param string $provider_name Human readable provider name
     */
    private function show_provider_disable_error($provider_name)
    {
        add_action('admin_notices', function() use ($provider_name) {
            echo '<div class="notice notice-error is-dismissible"><p>' .
                sprintf(__('Cannot disable %s because it is set as your default 2FA method. Please change your default method first.', 'two-factor-login-telegram'), $provider_name) .
                '</p></div>';
        });
    }


    private function handle_save_telegram($user_id)
    {
        if (wp_verify_nonce($_POST['wp_factor_telegram_save_nonce'], 'wp_factor_save_telegram')) {
            $chat_id = sanitize_text_field($_POST['tg_chat_id']);
            if (!empty($chat_id)) {
                update_user_meta($user_id, 'tg_wp_factor_chat_id', $chat_id);
                update_user_meta($user_id, 'tg_wp_factor_enabled', '1');
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Telegram 2FA has been configured successfully!', 'two-factor-login-telegram') . '</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Invalid Telegram Chat ID.', 'two-factor-login-telegram') . '</p></div>';
                });
            }
        }
    }

    private function handle_setup_telegram()
    {
        if (wp_verify_nonce($_POST['wp_factor_telegram_nonce'], 'wp_factor_setup_telegram')) {
            wp_safe_redirect(admin_url('profile.php#2fa-telegram-setup'));
            exit;
        }
    }

    private function handle_generate_recovery($user_id)
    {
        if (wp_verify_nonce($_POST['wp_factor_recovery_nonce'], 'wp_factor_generate_recovery')) {
            $recovery = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_RECOVERY_CODES);
            $codes = $recovery->regenerate_recovery_codes($user_id);
            if (!empty($codes)) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Recovery codes have been generated successfully!', 'two-factor-login-telegram') . '</p></div>';
                });
            }
        }
    }

    private function handle_regenerate_recovery($user_id)
    {
        if (wp_verify_nonce($_POST['wp_factor_recovery_regenerate_nonce'], 'wp_factor_regenerate_recovery')) {
            $recovery = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_RECOVERY_CODES);
            $codes = $recovery->regenerate_recovery_codes($user_id);
            if (!empty($codes)) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('New recovery codes have been generated successfully!', 'two-factor-login-telegram') . '</p></div>';
                });
            }
        }
    }

    private function handle_set_default_provider($user_id)
    {
        if (wp_verify_nonce($_POST['wp_factor_default_provider_nonce'], 'wp_factor_set_default_provider')) {
            $default_provider = sanitize_text_field($_POST['default_provider']);

            // Get all available methods for this user (includes custom providers)
            $available_methods = AuthPress_User_Manager::get_user_available_methods($user_id);
            $valid_providers = [];

            // Build list of valid providers from available methods
            foreach ($available_methods as $method_key => $is_available) {
                if ($is_available) {
                    // Handle special case for totp -> authenticator mapping
                    if ($method_key === 'totp') {
                        $valid_providers[] = 'authenticator';
                    } else {
                        $valid_providers[] = $method_key;
                    }
                }
            }

            if (in_array($default_provider, $valid_providers)) {
                update_user_meta($user_id, 'authpress_default_provider', $default_provider);

                // Get provider display name dynamically
                $provider_name = $this->get_provider_display_name($default_provider);

                add_action('admin_notices', function() use ($provider_name) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Default 2FA method set to %s successfully!', 'two-factor-login-telegram'), $provider_name) . '</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Invalid default provider selected.', 'two-factor-login-telegram') . '</p></div>';
                });
            }
        }
    }

    /**
     * Get display name for a provider
     * @param string $provider_key Provider key
     * @return string Display name
     */
    private function get_provider_display_name($provider_key)
    {
        switch ($provider_key) {
            case 'telegram':
                return __('Telegram', 'two-factor-login-telegram');
            case 'email':
                return __('Email', 'two-factor-login-telegram');
            case 'authenticator':
                return __('Authenticator App', 'two-factor-login-telegram');
            default:
                $provider = AuthPress_Provider_Registry::get($provider_key);
                return $provider ? $provider->get_name() : ucfirst($provider_key);
        }
    }

    public function load_menu()
    {
        add_options_page(
            __("AuthPress", "two-factor-login-telegram"),
            __("AuthPress", "two-factor-login-telegram"),
            "manage_options",
            "authpress-conf",
            array($this, "configure_admin_page")
        );

        add_users_page(
            __("My 2FA Settings", "two-factor-login-telegram"),
            __("My 2FA Settings", "two-factor-login-telegram"),
            "read",
            "my-2fa-settings",
            array($this, "show_user_2fa_page")
        );
    }

    public function register_providers_settings()
    {
        register_setting('authpress_providers', 'authpress_providers', array(
            'sanitize_callback' => array($this, 'sanitize_providers_settings')
        ));
    }

    public function sanitize_providers_settings($input)
    {

        $sanitized = array();

        $sanitized['authenticator'] = array(
            'enabled' => isset($input['authenticator']['enabled']) ? true : false
        );

        $sanitized['telegram'] = array(
            'enabled' => isset($input['telegram']['enabled']) ? true : false,
            'bot_token' => isset($input['telegram']['bot_token']) ? sanitize_text_field($input['telegram']['bot_token']) : '',
            'failed_login_reports' => isset($input['telegram']['failed_login_reports']) &&  $input['telegram']['failed_login_reports'],
            'report_chat_id' => isset($input['telegram']['report_chat_id']) ? sanitize_text_field($input['telegram']['report_chat_id']) : ''
        );

        if (empty($sanitized['telegram']['report_chat_id'])){
            $sanitized['telegram']['failed_login_reports'] = false;
        }

        $sanitized['email'] = array(
            'enabled' => isset($input['email']['enabled']) ? true : false,
            'token_duration' => isset($input['email']['token_duration']) ? absint($input['email']['token_duration']) : 20
        );

        if ($sanitized['telegram']['enabled'] && !empty($sanitized['telegram']['bot_token'])) {
            $is_valid_bot = $this->telegram->get_me() !== false;
            set_transient(WP_FACTOR_TG_GETME_TRANSIENT, $is_valid_bot, 60 * 60 * 24);

            if ($is_valid_bot) {
                $webhook_url = rest_url('telegram/v1/webhook');
                $this->telegram->set_bot_token($sanitized['telegram']['bot_token'])->set_webhook($webhook_url);
            }
        }

        return apply_filters('authpress_providers_sanitize_before_save', $sanitized, $input);
    }

    public function action_links($links)
    {
        $plugin_links = array(
            '<a href="' . admin_url('options-general.php?page=authpress-conf') . '">' . __('Settings', 'two-factor-login-telegram') . '</a>',
        );

        return array_merge($plugin_links, $links);
    }

    public function is_valid_bot_for_providers($bot_token)
    {
        if (empty($bot_token)) {
            return false;
        }

        $telegram = new WP_Telegram();
        $old_token = $telegram->get_bot_token();

        $telegram->set_bot_token($bot_token);
        $is_valid = $telegram->get_me() !== false;

        $telegram->set_bot_token($old_token);

        return $is_valid;
    }

    /**
     * Handle generic provider disable action
     *
     * @param int $user_id User ID
     * @param string $provider_key Provider key
     */
    private function handle_generic_disable_provider($user_id, $provider_key)
    {
        // Verify nonce
        $nonce_field = 'wp_factor_' . $provider_key . '_disable_nonce';
        $nonce_action = 'wp_factor_disable_' . $provider_key;

        if (!wp_verify_nonce($_POST[$nonce_field], $nonce_action)) {
            return;
        }

        if (!$this->can_disable_provider($user_id, $provider_key)) {
            $provider_name = $this->get_provider_display_name($provider_key);
            $this->show_provider_disable_error($provider_name);
            return;
        }

        // Get provider from registry
        $provider = AuthPress_Provider_Registry::get($provider_key);
        if (!$provider) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Provider not found.', 'two-factor-login-telegram') . '</p></div>';
            });
            return;
        }

        $success = $provider->disable_user_method($user_id);
        if ($success) {
            $provider_name = $provider->get_name();
            add_action('admin_notices', function() use ($provider_name) {
                echo '<div class="notice notice-success is-dismissible"><p>' .
                    sprintf(__('%s 2FA has been disabled successfully.', 'two-factor-login-telegram'), $provider_name) .
                    '</p></div>';
            });
        }
        // Redirect back to My 2FA Settings page to prevent white page
        wp_safe_redirect(admin_url('users.php?page=my-2fa-settings'));
        exit;
    }

    /**
     * Handle generic provider enable action
     *
     * @param int $user_id User ID
     * @param string $provider_key Provider key
     */
    private function handle_generic_enable_provider($user_id, $provider_key)
    {
        // Verify nonce
        $nonce_field = 'wp_factor_' . $provider_key . '_enable_nonce';
        $nonce_action = 'wp_factor_enable_' . $provider_key;

        if (!wp_verify_nonce($_POST[$nonce_field], $nonce_action)) {
            return;
        }


        // Get provider from registry
        $provider = AuthPress_Provider_Registry::get($provider_key);
        if (!$provider) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Provider not found.', 'two-factor-login-telegram') . '</p></div>';
            });
            return;
        }

        $success = $provider->enable_user_method($user_id);
        $provider_name = $provider->get_name();

        if ($success) {
            add_action('admin_notices', function() use ($provider_name) {
                echo '<div class="notice notice-success is-dismissible"><p>' .
                    sprintf(__('%s 2FA has been enabled successfully.', 'two-factor-login-telegram'), $provider_name) .
                    '</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($provider_name) {
                echo '<div class="notice notice-error is-dismissible"><p>' .
                    sprintf(__('Failed to enable %s 2FA.', 'two-factor-login-telegram'), $provider_name) .
                    '</p></div>';
            });
        }
        // Redirect back to My 2FA Settings page to prevent white page
        wp_safe_redirect(admin_url('users.php?page=my-2fa-settings'));
        exit;
    }
}

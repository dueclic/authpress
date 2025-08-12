<?php

namespace Authpress;

class AuthPress_Admin_Manager
{
    private $namespace = "tg_col";
    private $telegram;
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
            case 'disable_totp':
                $this->handle_disable_totp($current_user_id);
                break;

            case 'disable_telegram':
                $this->handle_disable_telegram($current_user_id);
                break;

            case 'enable_email':
                $this->handle_enable_email($current_user_id);
                break;

            case 'disable_email':
                $this->handle_disable_email($current_user_id);
                break;

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
     * Check if a provider can be disabled (not default method)
     * 
     * @param int $user_id User ID
     * @param string $provider_key Provider key to check
     * @return bool True if can be disabled, false otherwise
     */
    private function can_disable_provider($user_id, $provider_key)
    {
        $user_default_provider = get_user_meta($user_id, 'wp_factor_user_default_provider', true);
        if (empty($user_default_provider)) {
            $plugin = AuthPress_Plugin::get_instance();
            $user_default_provider = $plugin->get_default_provider();
        }
        
        // Handle authenticator/totp mapping
        if ($provider_key === 'totp' || $provider_key === 'authenticator') {
            return !($user_default_provider === 'authenticator' || $user_default_provider === 'totp');
        }
        
        return $user_default_provider !== $provider_key;
    }

    /**
     * Show error notice for attempting to disable default provider
     * 
     * @param string $provider_name Human readable provider name
     */
    private function show_default_provider_disable_error($provider_name)
    {
        add_action('admin_notices', function() use ($provider_name) {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                sprintf(__('Cannot disable %s because it is set as your default 2FA method. Please change your default method first.', 'two-factor-login-telegram'), $provider_name) . 
                '</p></div>';
        });
    }

    private function handle_disable_totp($user_id)
    {
        if (wp_verify_nonce($_POST['wp_factor_totp_disable_nonce'], 'wp_factor_disable_totp')) {
            if (!$this->can_disable_provider($user_id, 'totp')) {
                $this->show_default_provider_disable_error(__('Authenticator app', 'two-factor-login-telegram'));
                return;
            }
            
            $totp = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_TOTP);
            if ($totp->disable_user_totp($user_id)) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Authenticator app has been disabled successfully.', 'two-factor-login-telegram') . '</p></div>';
                });
            }
        }
    }

    private function handle_disable_telegram($user_id)
    {
        if (wp_verify_nonce($_POST['wp_factor_telegram_disable_nonce'], 'wp_factor_disable_telegram')) {
            if (!$this->can_disable_provider($user_id, 'telegram')) {
                $this->show_default_provider_disable_error(__('Telegram', 'two-factor-login-telegram'));
                return;
            }
            
            update_user_meta($user_id, 'tg_wp_factor_enabled', '0');
            delete_user_meta($user_id, 'tg_wp_factor_chat_id');
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Telegram 2FA has been disabled successfully.', 'two-factor-login-telegram') . '</p></div>';
            });
        }
    }

    private function handle_enable_email($user_id)
    {
        if (wp_verify_nonce($_POST['wp_factor_email_enable_nonce'], 'wp_factor_enable_email')) {
            if (AuthPress_User_Manager::enable_user_email($user_id)) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Email 2FA has been enabled successfully.', 'two-factor-login-telegram') . '</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to enable Email 2FA. Please ensure you have a valid email address.', 'two-factor-login-telegram') . '</p></div>';
                });
            }
        }
    }

    private function handle_disable_email($user_id)
    {
        if (wp_verify_nonce($_POST['wp_factor_email_disable_nonce'], 'wp_factor_disable_email')) {
            if (!$this->can_disable_provider($user_id, 'email')) {
                $this->show_default_provider_disable_error(__('Email', 'two-factor-login-telegram'));
                return;
            }
            
            AuthPress_User_Manager::disable_user_email($user_id);
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Email 2FA has been disabled successfully.', 'two-factor-login-telegram') . '</p></div>';
            });
        }
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
                update_user_meta($user_id, 'wp_factor_user_default_provider', $default_provider);
                
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
                // For custom providers, get name from provider registry
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
            "tg-conf",
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

    public function sanitize_settings($input)
    {
        $sanitized = array();

        if (isset($input['bot_token'])) {
            $sanitized['bot_token'] = sanitize_text_field($input['bot_token']);
        }

        if (isset($input['chat_id'])) {
            $sanitized['chat_id'] = sanitize_text_field($input['chat_id']);
        }

        if (isset($input['enabled'])) {
            $sanitized['enabled'] = $input['enabled'] === '1' ? '1' : '0';
        }

        if (isset($input['show_site_name'])) {
            $sanitized['show_site_name'] = $input['show_site_name'] === '1' ? '1' : '0';
        }

        if (isset($input['show_site_url'])) {
            $sanitized['show_site_url'] = $input['show_site_url'] === '1' ? '1' : '0';
        }

        if (isset($input['delete_data_on_deactivation'])) {
            $sanitized['delete_data_on_deactivation'] = $input['delete_data_on_deactivation'] === '1' ? '1' : '0';
        }

        if (!empty($sanitized['bot_token'])) {
            $is_valid_bot = $this->telegram->get_me() !== false;
            set_transient(WP_FACTOR_TG_GETME_TRANSIENT, $is_valid_bot, 60 * 60 * 24);

            if ($is_valid_bot) {
                $webhook_url = rest_url('telegram/v1/webhook');
                $this->telegram->set_bot_token($sanitized['bot_token'])->set_webhook($webhook_url);
            }
        }

        return $sanitized;
    }

    public function register_settings()
    {
        register_setting($this->namespace, $this->namespace, array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));

        $this->add_settings_sections();
        $this->add_settings_fields();
    }

    private function add_settings_sections()
    {
        add_settings_section(
            $this->namespace . '_section',
            __('Telegram Configuration', "two-factor-login-telegram"),
            '',
            $this->namespace . '.php'
        );

        add_settings_section(
            $this->namespace . '_failed_login_section',
            __('Failed Login Report', "two-factor-login-telegram"),
            array($this, 'failed_login_section_callback'),
            $this->namespace . '.php'
        );

        add_settings_section(
            $this->namespace . '_data_management_section',
            __('Data Management', "two-factor-login-telegram"),
            array($this, 'data_management_section_callback'),
            $this->namespace . '.php'
        );
    }

    private function add_settings_fields()
    {
        $this->add_bot_token_field();
        $this->add_chat_id_field();
        $this->add_enabled_field();
        $this->add_site_info_fields();
        $this->add_data_cleanup_field();
    }

    private function add_bot_token_field()
    {
        $field_args = array(
            'type' => 'text',
            'id' => 'bot_token',
            'name' => 'bot_token',
            'desc' => __('Bot Token', "two-factor-login-telegram"),
            'std' => '',
            'label_for' => 'bot_token',
            'class' => 'css_class',
        );

        add_settings_field(
            'bot_token',
            __('Bot Token', "two-factor-login-telegram"),
            array($this, 'display_setting'),
            $this->namespace . '.php',
            $this->namespace . '_section',
            $field_args
        );
    }

    private function add_chat_id_field()
    {
        $field_args = array(
            'type' => 'text',
            'id' => 'chat_id',
            'name' => 'chat_id',
            'desc' => __('Enter your Telegram Chat ID to receive notifications about failed login attempts.', "two-factor-login-telegram"),
            'std' => '',
            'label_for' => 'chat_id',
            'class' => 'css_class',
        );

        add_settings_field(
            'chat_id',
            __('Chat ID for Reports', "two-factor-login-telegram"),
            array($this, 'display_setting'),
            $this->namespace . '.php',
            $this->namespace . '_failed_login_section',
            $field_args
        );
    }

    private function add_enabled_field()
    {
        $field_args = array(
            'type' => 'checkbox',
            'id' => 'enabled',
            'name' => 'enabled',
            'desc' => __('Select this checkbox to enable the plugin.', 'two-factor-login-telegram'),
            'std' => '',
            'label_for' => 'enabled',
            'class' => 'css_class',
        );

        add_settings_field(
            'enabled',
            __('Enable plugin?', 'two-factor-login-telegram'),
            array($this, 'display_setting'),
            $this->namespace . '.php',
            $this->namespace . '_section',
            $field_args
        );
    }

    private function add_site_info_fields()
    {
        $show_site_name_args = array(
            'type' => 'checkbox',
            'id' => 'show_site_name',
            'name' => 'show_site_name',
            'desc' => __('Include site name in failed login notifications.<br>Useful when using the same bot for multiple sites.', 'two-factor-login-telegram'),
            'std' => '',
            'label_for' => 'show_site_name',
            'class' => 'css_class',
        );

        add_settings_field(
            'show_site_name',
            __('Show Site Name', 'two-factor-login-telegram'),
            array($this, 'display_setting'),
            $this->namespace . '.php',
            $this->namespace . '_failed_login_section',
            $show_site_name_args
        );

        $show_site_url_args = array(
            'type' => 'checkbox',
            'id' => 'show_site_url',
            'name' => 'show_site_url',
            'desc' => __('Include site URL in failed login notifications.<br>Useful when using the same bot for multiple sites.', 'two-factor-login-telegram'),
            'std' => '',
            'label_for' => 'show_site_url',
            'class' => 'css_class',
        );

        add_settings_field(
            'show_site_url',
            __('Show Site URL', 'two-factor-login-telegram'),
            array($this, 'display_setting'),
            $this->namespace . '.php',
            $this->namespace . '_failed_login_section',
            $show_site_url_args
        );
    }

    private function add_data_cleanup_field()
    {
        $field_args = array(
            'type' => 'checkbox',
            'id' => 'delete_data_on_deactivation',
            'name' => 'delete_data_on_deactivation',
            'desc' => __('Delete all plugin data when the plugin is deactivated.<br><strong>Warning:</strong> This will permanently remove all settings, user configurations, authentication codes, and logs.', 'two-factor-login-telegram'),
            'std' => '',
            'label_for' => 'delete_data_on_deactivation',
            'class' => 'css_class',
        );

        add_settings_field(
            'delete_data_on_deactivation',
            __('Delete Data on Deactivation', 'two-factor-login-telegram'),
            array($this, 'display_setting'),
            $this->namespace . '.php',
            $this->namespace . '_data_management_section',
            $field_args
        );
    }

    public function display_setting($args)
    {
        extract($args);

        $option_name = $this->namespace;
        $options = get_option($option_name);

        switch ($type) {
            case 'text':
                $options[$id] = stripslashes($options[$id]);
                $options[$id] = esc_attr($options[$id]);
                echo "<input class='regular-text $class' type='text' id='$id' name='" . $option_name . "[$id]' value='$options[$id]' />";

                if ($id == "bot_token") {
                    ?>
                    <button id="checkbot" class="button-secondary" type="button"><?php
                    echo __("Check", "two-factor-login-telegram") ?></button>
                    <?php
                }

                echo ($desc != '') ? '<br /><p class="wft-settings-description" id="' . $id . '_desc">' . $desc . '</p>' : "";
                break;

            case 'checkbox':
                $options[$id] = stripslashes($options[$id]);
                $options[$id] = esc_attr($options[$id]);
                ?>
                <label for="<?php echo esc_attr($id); ?>">
                    <input class="regular-text <?php echo esc_attr($class); ?>" type="checkbox" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($id); ?>]" value="1" <?php echo checked(1, $options[$id]); ?> />
                    <?php _e($desc); ?>
                </label>
                <?php
                break;
        }
    }

    public function register_providers_settings()
    {
        register_setting('wp_factor_providers', 'wp_factor_providers', array(
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
            'failed_login_reports' => isset($input['telegram']['failed_login_reports']) ? true : false,
            'report_chat_id' => isset($input['telegram']['report_chat_id']) ? sanitize_text_field($input['telegram']['report_chat_id']) : ''
        );

        $sanitized['email'] = array(
            'enabled' => isset($input['email']['enabled']) ? true : false
        );

        // Allow any valid provider as default, not just hardcoded ones
        $valid_provider_keys = [];
        $all_providers = AuthPress_Provider_Registry::get_all();
        foreach ($all_providers as $key => $provider) {
            if ($provider && $provider->is_enabled()) {
                $valid_provider_keys[] = $key;
                // Handle totp -> authenticator mapping
                if ($key === 'totp') {
                    $valid_provider_keys[] = 'authenticator';
                }
            }
        }
        
        $sanitized['default_provider'] = isset($input['default_provider']) && in_array($input['default_provider'], $valid_provider_keys) ? $input['default_provider'] : 'telegram';

        if ($sanitized['telegram']['enabled'] && !empty($sanitized['telegram']['bot_token'])) {
            $legacy_settings = get_option('tg_col', array());
            $legacy_settings['bot_token'] = $sanitized['telegram']['bot_token'];
            $legacy_settings['enabled'] = '1';

            if ($sanitized['telegram']['failed_login_reports']) {
                $legacy_settings['chat_id'] = $sanitized['telegram']['report_chat_id'];
            }

            update_option('tg_col', $legacy_settings);
        }

        return apply_filters('authpress_providers_sanitize_before_save', $sanitized, $input);
    }

    public function failed_login_section_callback()
    {
        echo '<p>' . __('Configure how to receive notifications when someone fails to log in to your site.', 'two-factor-login-telegram') . '</p>';
    }

    public function data_management_section_callback()
    {
        echo '<p>' . __('Manage plugin data and cleanup options.', 'two-factor-login-telegram') . '</p>';
    }

    public function action_links($links)
    {
        $plugin_links = array(
            '<a href="' . admin_url('options-general.php?page=tg-conf') . '">' . __('Settings', 'two-factor-login-telegram') . '</a>',
        );

        return array_merge($plugin_links, $links);
    }

    public function is_valid_bot()
    {
        $plugin = AuthPress_Plugin::get_instance();
        return $plugin->is_valid_bot();
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
        
        // Skip handled providers (they have specific handlers)
        if (in_array($provider_key, ['telegram', 'email', 'totp', 'authenticator'])) {
            return;
        }
        
        // Check if provider can be disabled
        if (!$this->can_disable_provider($user_id, $provider_key)) {
            $provider_name = $this->get_provider_display_name($provider_key);
            $this->show_default_provider_disable_error($provider_name);
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
        
        // Call provider's disable method if it exists
        if (method_exists($provider, 'disable_user_method')) {
            $success = $provider->disable_user_method($user_id);
            if ($success) {
                $provider_name = $provider->get_name();
                add_action('admin_notices', function() use ($provider_name) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . 
                        sprintf(__('%s 2FA has been disabled successfully.', 'two-factor-login-telegram'), $provider_name) . 
                        '</p></div>';
                });
            }
        }
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
        
        // Skip handled providers (they have specific handlers)
        if (in_array($provider_key, ['telegram', 'email', 'totp', 'authenticator'])) {
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
        
        // Call provider's enable method if it exists
        if (method_exists($provider, 'enable_user_method')) {
            $success = $provider->enable_user_method($user_id);
            if ($success) {
                $provider_name = $provider->get_name();
                add_action('admin_notices', function() use ($provider_name) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . 
                        sprintf(__('%s 2FA has been enabled successfully.', 'two-factor-login-telegram'), $provider_name) . 
                        '</p></div>';
                });
            } else {
                $provider_name = $provider->get_name();
                add_action('admin_notices', function() use ($provider_name) {
                    echo '<div class="notice notice-error is-dismissible"><p>' . 
                        sprintf(__('Failed to enable %s 2FA.', 'two-factor-login-telegram'), $provider_name) . 
                        '</p></div>';
                });
            }
        }
    }
}

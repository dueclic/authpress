<?php

namespace Authpress;

final class AuthPress_Plugin
{
    private static $instance;
    private $namespace = "tg_col";
    private $telegram;
    private $authentication_handler;
    private $admin_manager;
    private $ajax_handler;
    private $hooks_manager;
    private $logger;

    public static function get_instance()
    {
        if (
            empty(self::$instance)
            && !(self::$instance instanceof AuthPress_Plugin)
        ) {
            self::$instance = new AuthPress_Plugin;
            self::$instance->init();
        }

        return self::$instance;
    }

    private function init()
    {
        $this->includes();
        $this->setup_dependencies();
        $this->migrate_legacy_settings_on_init();
        $this->hooks_manager->add_hooks();

        do_action("wp_factor_telegram_loaded");
    }

    public function includes()
    {
        $base_path = dirname(WP_FACTOR_TG_FILE) . "/includes/";

        // Core classes
        require_once($base_path . "class-wp-telegram.php");
        require_once($base_path . "class-authpress-logs-list-table.php");
        require_once($base_path . "class-authpress-user-manager.php");

        // Provider interface and abstract class
        require_once($base_path . "providers/class-authpress-provider-otp-interface.php");
        require_once($base_path . "providers/class-authpress-provider-abstract.php");

        // Concrete providers
        require_once($base_path . "providers/class-authpress-provider-telegram.php");
        require_once($base_path . "providers/class-authpress-provider-email.php");
        require_once($base_path . "providers/class-authpress-provider-totp.php");
        require_once($base_path . "providers/class-authpress-provider-recovery-codes.php");

        // Factory
        require_once($base_path . "class-authpress-auth-factory.php");

        // New refactored classes
        require_once($base_path . "class-authpress-logger.php");
        require_once($base_path . "class-authpress-authentication-handler.php");
        require_once($base_path . "class-authpress-admin-manager.php");
        require_once($base_path . "class-authpress-ajax-handler.php");
        require_once($base_path . "class-authpress-hooks-manager.php");
    }

    private function setup_dependencies()
    {
        // Initialize core dependencies
        $this->telegram = new WP_Telegram();
        $this->logger = new AuthPress_Logger();

        // Initialize handlers with dependencies
        $this->authentication_handler = new AuthPress_Authentication_Handler($this->telegram, $this->logger);
        $this->admin_manager = new AuthPress_Admin_Manager($this->telegram, $this->logger);
        $this->ajax_handler = new AuthPress_AJAX_Handler($this->telegram, $this->logger);
        $this->hooks_manager = new AuthPress_Hooks_Manager(
            $this->authentication_handler,
            $this->admin_manager,
            $this->ajax_handler,
            $this->telegram,
            $this->logger
        );
    }

    public function migrate_legacy_settings_on_init()
    {
        $legacy_settings = get_option($this->namespace, array());
        $providers = get_option('wp_factor_providers');

        if (isset($legacy_settings['enabled']) && $legacy_settings['enabled'] === '1') {
            $legacy_bot_token = isset($legacy_settings['bot_token']) ? $legacy_settings['bot_token'] : '';

            if ($providers === false || !isset($providers['telegram']['enabled']) || !$providers['telegram']['enabled']) {
                if (!empty($legacy_bot_token)) {
                    $new_providers = array(
                        'authenticator' => array('enabled' => false),
                        'telegram' => array(
                            'enabled' => true,
                            'bot_token' => $legacy_bot_token,
                            'failed_login_reports' => false,
                            'report_chat_id' => ''
                        ),
                        'email' => array('enabled' => true),
                        'default_provider' => 'telegram'
                    );

                    if (isset($legacy_settings['chat_id']) && !empty($legacy_settings['chat_id'])) {
                        $new_providers['telegram']['report_chat_id'] = $legacy_settings['chat_id'];
                        $new_providers['telegram']['failed_login_reports'] = true;
                    }

                    update_option('wp_factor_providers', $new_providers);

                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success is-dismissible">';
                        echo '<p>' . __('AuthPress: Legacy settings have been automatically migrated to the new provider system. You can now configure additional 2FA methods.', 'two-factor-login-telegram') . '</p>';
                        echo '</div>';
                    });
                }
            }
        }
    }

    // Proxy methods for backward compatibility
    public function tg_login($user_login, $user)
    {
        return $this->authentication_handler->handle_login($user_login, $user);
    }

    public function validate_tg()
    {
        return $this->authentication_handler->validate_authentication();
    }

    public function configure_tg()
    {
        return $this->admin_manager->configure_admin_page();
    }

    public function show_user_2fa_page()
    {
        return $this->admin_manager->show_user_2fa_page();
    }

    public function handle_2fa_settings_forms()
    {
        return $this->admin_manager->handle_2fa_settings_forms();
    }

    public function tg_load_menu()
    {
        return $this->admin_manager->load_menu();
    }

    public function sanitize_settings($input)
    {
        return $this->admin_manager->sanitize_settings($input);
    }

    public function tg_register_settings()
    {
        return $this->admin_manager->register_settings();
    }

    public function register_providers_settings()
    {
        return $this->admin_manager->register_providers_settings();
    }

    public function sanitize_providers_settings($input)
    {
        return $this->admin_manager->sanitize_providers_settings($input);
    }

    // Utility methods
    public function is_telegram_enabled()
    {
        return AuthPress_User_Manager::is_telegram_provider_enabled();
    }

    public function get_default_provider()
    {
        return AuthPress_User_Manager::get_system_default_provider();
    }

    public function get_effective_default_provider($user_id)
    {
        return AuthPress_User_Manager::get_user_effective_provider($user_id);
    }

    public function has_active_integration($user_id = false)
    {
        if ($user_id === false) {
            $user_id = get_current_user_id();
        }
        return AuthPress_User_Manager::user_has_2fa($user_id);
    }

    public function get_user_chatid($user_id = false)
    {
        if ($user_id === false) {
            $user_id = get_current_user_id();
        }
        return AuthPress_User_Manager::get_user_chat_id($user_id);
    }

    public function is_setup_chatid($user_id = false)
    {
        $chat_id = $this->get_user_chatid($user_id);
        return $chat_id !== false;
    }

    public function action_links($links)
    {
        return $this->admin_manager->action_links($links);
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
}

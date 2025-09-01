<?php

namespace Authpress;

final class AuthPress_Plugin
{
    private static $instance;
    private $telegram;
    private $authentication_handler;
    private $admin_manager;
    private $ajax_handler;
    private $hooks_manager;
    private $logger;

    private $namespace = 'tg_col';

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

        do_action_deprecated(
            'wp_factor_telegram_loaded',
            array(),
            '3.6.0',
            'authpress_loaded',
            __('The action wp_factor_telegram_loaded is deprecated. Use authpress_loaded instead.', 'two-factor-login-telegram')
        );
        do_action('authpress_loaded');

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

        // Ui components
        require_once($base_path . "ui/class-authpress-ui-modal.php");
        require_once($base_path . "ui/modals/class-authpress-ui-modal-recovery-codes.php");
        // Provider registry and factory
        require_once($base_path . "class-authpress-provider-registry.php");
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
        $providers = authpress_providers();

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
                        'email' => array('enabled' => true)
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

                    delete_option($this->namespace);

                }
            }
        }
    }
	/**
	 * Get the configured default provider
	 *
	 * @return string The default provider ('telegram' or 'authenticator')
	 */
	public function get_default_provider()
	{
		$providers = authpress_providers();
		return isset($providers['default_provider']) ? $providers['default_provider'] : 'telegram';
	}
}


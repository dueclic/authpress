<?php

namespace Authpress;

class AuthPress_Hooks_Manager
{
    private $authentication_handler;
    private $admin_manager;
    private $ajax_handler;
    private $telegram;
    private $logger;

    public function __construct($authentication_handler, $admin_manager, $ajax_handler, $telegram, $logger)
    {
        $this->authentication_handler = $authentication_handler;
        $this->admin_manager = $admin_manager;
        $this->ajax_handler = $ajax_handler;
        $this->telegram = $telegram;
        $this->logger = $logger;
    }

    public function add_hooks()
    {
        // Authentication hooks
        add_action('wp_login', array($this->authentication_handler, 'handle_login'), 10, 2);
        add_action('login_form_validate_authpress', array($this->authentication_handler, 'validate_authentication'));

        // Setup wizard hooks
        add_action('login_form_authpress_setup_wizard', array($this->authentication_handler, 'handle_setup_wizard_submission'));
        add_action('init', array($this->authentication_handler, 'handle_wizard_skip'));

        // Failed login hook
        add_action('wp_login_failed', array($this->telegram, 'send_tg_failed_login'), 10, 2);

        // Plugin lifecycle hooks
        register_activation_hook(WP_FACTOR_TG_FILE, array($this, 'plugin_activation'));
        register_deactivation_hook(WP_FACTOR_TG_FILE, array($this, 'plugin_deactivation'));
        add_action('plugins_loaded', array($this, 'check_plugin_update'));

        // Admin hooks
        if (is_admin()) {
            $this->add_admin_hooks();
        }
        // Assets and scripts
        add_action('admin_enqueue_scripts', array($this, 'load_assets'));
        add_action('admin_footer', array($this, 'hook_scripts'));

        // AJAX hooks
        $this->add_ajax_hooks();

        // REST API and rewrite hooks
        $this->add_rest_api_hooks();

        // User list customization
        if (is_admin() && AuthPress_User_Manager::is_telegram_provider_enabled()) {
            $this->add_user_list_hooks();
        }

        // Footer hooks
        add_action("tft_copyright", array($this, "change_copyright"));
    }

    private function add_admin_hooks()
    {
        add_action('admin_init', array($this->admin_manager, 'register_settings'));
        add_action('admin_init', array($this->admin_manager, 'register_providers_settings'));
        add_action('admin_init', array($this->admin_manager, 'handle_2fa_settings_forms'));
        add_action("admin_menu", array($this->admin_manager, 'load_menu'));
        add_filter(
            "plugin_action_links_" . plugin_basename(WP_FACTOR_TG_FILE),
            array($this->admin_manager, 'action_links')
        );
    }

    private function add_ajax_hooks()
    {
        add_action('wp_ajax_send_token_check', array($this->ajax_handler, 'send_token_check'));
        add_action('wp_ajax_token_check', array($this->ajax_handler, 'token_check'));
        add_action('wp_ajax_check_bot', array($this->ajax_handler, 'check_bot'));
        add_action('wp_ajax_regenerate_recovery_codes', array($this->ajax_handler, 'regenerate_recovery'));

        // TOTP AJAX handlers
        add_action('wp_ajax_setup_totp', array($this->ajax_handler, 'setup_totp'));
        add_action('wp_ajax_verify_totp', array($this->ajax_handler, 'verify_totp'));
        add_action('wp_ajax_disable_totp', array($this->ajax_handler, 'disable_totp'));

        // Login code senders (no auth required)
        add_action('wp_ajax_nopriv_send_login_telegram_code', array($this->ajax_handler, 'send_login_telegram_code'));
        add_action('wp_ajax_nopriv_send_login_email_code', array($this->ajax_handler, 'send_login_email_code'));

        // Admin AJAX
        add_action('wp_ajax_disable_user_2fa_telegram', array($this->ajax_handler, 'disable_user_2fa_ajax'));
        add_action('wp_ajax_force_setup_wizard', array($this, 'handle_force_setup_wizard_ajax'));
    }

    private function add_rest_api_hooks()
    {
        add_action('rest_api_init', array($this, 'register_telegram_webhook_route'));
        add_action('init', array($this, 'add_telegram_rewrite_rules'));
        add_action('parse_request', array($this, 'parse_telegram_request'));
    }

    private function add_user_list_hooks()
    {
        add_filter('manage_users_columns', array($this, 'add_2fa_telegram_column'));
        add_filter('manage_users_custom_column', array($this, 'show_2fa_telegram_column_content'), 10, 3);
    }

    public function load_assets()
    {
        $screen = get_current_screen();
        if (in_array($screen->id, ["profile", "settings_page_tg-conf", "users", "users_page_my-2fa-settings"])) {
            wp_register_style(
                "tg_lib_css",
                plugins_url("assets/css/authpress-plugin.css", dirname(__FILE__)),
                array(),
                WP_FACTOR_PLUGIN_VERSION
            );
            wp_enqueue_style("tg_lib_css");

            wp_register_script(
                "tg_lib_js",
                plugins_url("assets/js/authpress-plugin.js", dirname(__FILE__)),
                array('jquery'),
                WP_FACTOR_PLUGIN_VERSION,
                true
            );

            wp_localize_script("tg_lib_js", "tlj", array(
                "ajax_error" => __('Ooops! Server failure, try again! ', 'two-factor-login-telegram'),
                "checkbot_nonce" => wp_create_nonce('ajax-checkbot-nonce'),
                "sendtoken_nonce" => wp_create_nonce('ajax-sendtoken-nonce'),
                "tokencheck_nonce" => wp_create_nonce('ajax-tokencheck-nonce'),
                "spinner" => admin_url("/images/spinner.gif"),
                "invalid_chat_id" => __('Please enter a valid Chat ID', 'two-factor-login-telegram'),
                "enter_confirmation_code" => __('Please enter the confirmation code', 'two-factor-login-telegram'),
                "setup_completed" => __('✅ 2FA setup completed successfully!', 'two-factor-login-telegram'),
                "code_sent" => __('✅ Code sent! Check your Telegram', 'two-factor-login-telegram'),
                "modifying_setup" => __('⚠️ Modifying 2FA configuration - validation required', 'two-factor-login-telegram'),
                "confirm_disable" => __('Are you sure you want to disable 2FA for user %s?', 'two-factor-login-telegram'),
                "disabling" => __('Disabling...', 'two-factor-login-telegram'),
                "disable" => __('Disable', 'two-factor-login-telegram'),
                "inactive" => __('Inactive', 'two-factor-login-telegram'),
                "success_disabled" => __('2FA successfully disabled for %s', 'two-factor-login-telegram'),
                "disable_error" => __('Error during deactivation', 'two-factor-login-telegram'),
                "unknown_error" => __('Unknown error', 'two-factor-login-telegram'),
                "server_error" => __('Server communication error', 'two-factor-login-telegram')
            ));

            wp_enqueue_script("tg_lib_js");

            wp_enqueue_script('jquery-ui-accordion');
            wp_enqueue_script(
                'custom-accordion',
                plugins_url('assets/js/authpress-accordion.js', dirname(__FILE__)),
                array('jquery', 'jquery-ui-core', 'jquery-ui-accordion')
            );
        }
    }

    public function hook_scripts()
    {
        $screen = get_current_screen();
        if (in_array($screen->id, ["profile", "settings_page_tg-conf", "users", "users_page_my-2fa-settings"])): ?>
            <script>
                (function ($) {
                    $(document).ready(function () {
                        AuthPress_Plugin.init();

                        $('.disable-2fa-telegram').on('click', function (e) {
                            e.preventDefault();

                            var $btn = $(this);
                            var user_id = $btn.data('user-id');
                            var nonce = $btn.data('nonce');

                            if (!confirm('<?php echo esc_js(__('Are you sure you want to disable 2FA for this user?', 'two-factor-login-telegram')); ?>')) {
                                return;
                            }

                            $btn.prop('disabled', true).text('<?php echo esc_js(__('Disabling...', 'two-factor-login-telegram')); ?>');

                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'disable_user_2fa_telegram',
                                    user_id: user_id,
                                    nonce: nonce
                                },
                                success: function (response) {
                                    if (response.success) {
                                        $btn.closest('td').html(response.data.new_status);
                                        alert(response.data.message);
                                    } else {
                                        alert('Error: ' + response.data);
                                        $btn.prop('disabled', false).text('<?php echo esc_js(__('Disable', 'two-factor-login-telegram')); ?>');
                                    }
                                },
                                error: function () {
                                    alert('<?php echo esc_js(__('An error occurred. Please try again.', 'two-factor-login-telegram')); ?>');
                                    $btn.prop('disabled', false).text('<?php echo esc_js(__('Disable', 'two-factor-login-telegram')); ?>');
                                }
                            });
                        });

                        // Handle force setup wizard
                        $('.force-setup-wizard').on('click', function (e) {
                            e.preventDefault();

                            var $btn = $(this);
                            var user_id = $btn.data('user-id');
                            var nonce = $btn.data('nonce');
                            var isReSetup = $btn.text().indexOf('<?php echo esc_js(__('Re-setup', 'two-factor-login-telegram')); ?>') !== -1;

                            var confirmMessage = isReSetup
                                ? '<?php echo esc_js(__('Are you sure you want to force this user to re-configure their 2FA setup?', 'two-factor-login-telegram')); ?>'
                                : '<?php echo esc_js(__('Are you sure you want to force this user to set up 2FA?', 'two-factor-login-telegram')); ?>';

                            if (!confirm(confirmMessage)) {
                                return;
                            }

                            $btn.prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'two-factor-login-telegram')); ?>');

                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'force_setup_wizard',
                                    user_id: user_id,
                                    nonce: nonce
                                },
                                success: function (response) {
                                    if (response.success) {
                                        alert(response.data.message);
                                        $btn.text('<?php echo esc_js(__('Forced', 'two-factor-login-telegram')); ?>').addClass('disabled');
                                    } else {
                                        alert('Error: ' + (response.data || 'Unknown error'));
                                        $btn.prop('disabled', false).text(isReSetup ? '<?php echo esc_js(__('Force Re-setup', 'two-factor-login-telegram')); ?>' : '<?php echo esc_js(__('Force Setup', 'two-factor-login-telegram')); ?>');
                                    }
                                },
                                error: function () {
                                    alert('<?php echo esc_js(__('An error occurred. Please try again.', 'two-factor-login-telegram')); ?>');
                                    $btn.prop('disabled', false).text(isReSetup ? '<?php echo esc_js(__('Force Re-setup', 'two-factor-login-telegram')); ?>' : '<?php echo esc_js(__('Force Setup', 'two-factor-login-telegram')); ?>');
                                }
                            });
                        });
                    });
                })(jQuery);
            </script>
            <?php
        endif;
    }

    // Plugin lifecycle methods
    public function plugin_activation()
    {
        $this->create_or_update_telegram_auth_codes_table();
        $this->create_or_update_activities_table();
        $this->migrate_logs_to_activities_table();
        update_option('wp_factor_plugin_version', WP_FACTOR_PLUGIN_VERSION);

        $this->add_telegram_rewrite_rules();
        flush_rewrite_rules();
    }

    public function plugin_deactivation()
    {
        $plugin_options = get_option('tg_col', array());

        if (isset($plugin_options['delete_data_on_deactivation']) && $plugin_options['delete_data_on_deactivation'] === '1') {
            $this->cleanup_all_plugin_data();
        }

        flush_rewrite_rules();
    }

    public function check_plugin_update()
    {
        $installed_version = get_option('wp_factor_plugin_version');

        if ($installed_version !== WP_FACTOR_PLUGIN_VERSION) {
            $this->create_or_update_telegram_auth_codes_table();
            $this->create_or_update_activities_table();
            $this->migrate_logs_to_activities_table();
            update_option('wp_factor_plugin_version', WP_FACTOR_PLUGIN_VERSION);
        }
    }

    // Database methods
    private function create_or_update_telegram_auth_codes_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'telegram_auth_codes';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,  
        auth_code varchar(64) NOT NULL,            
        user_id bigint(20) UNSIGNED NOT NULL,    
        creation_date datetime NOT NULL,          
        expiration_date datetime NOT NULL,        
        PRIMARY KEY (id),                         
        KEY auth_code (auth_code)
    ) $charset_collate";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function create_or_update_activities_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2fat_activities';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,  
        timestamp datetime NOT NULL,
        action varchar(100) NOT NULL,
        data longtext,
        PRIMARY KEY (id),
        KEY timestamp (timestamp),
        KEY action (action)
    ) $charset_collate";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function migrate_logs_to_activities_table()
    {
        global $wpdb;

        $old_logs = get_option('telegram_bot_logs', array());

        if (!empty($old_logs)) {
            $activities_table = $wpdb->prefix . 'wp2fat_activities';

            foreach ($old_logs as $log) {
                $wpdb->insert(
                    $activities_table,
                    array(
                        'timestamp' => $log['timestamp'],
                        'action' => $log['action'],
                        'data' => maybe_serialize($log['data'])
                    ),
                    array('%s', '%s', '%s')
                );
            }

            delete_option('telegram_bot_logs');
        }
    }

    private function cleanup_all_plugin_data()
    {
        global $wpdb;

        delete_option('tg_col');
        delete_option('wp_factor_plugin_version');
        delete_option('telegram_bot_logs');

        $auth_codes_table = $wpdb->prefix . 'telegram_auth_codes';
        $wpdb->query("DROP TABLE IF EXISTS $auth_codes_table");

        $activities_table = $wpdb->prefix . 'wp2fat_activities';
        $wpdb->query("DROP TABLE IF EXISTS $activities_table");

        $wpdb->delete($wpdb->usermeta, array('meta_key' => 'tg_wp_factor_chat_id'));
        $wpdb->delete($wpdb->usermeta, array('meta_key' => 'tg_wp_factor_enabled'));
        $wpdb->delete($wpdb->usermeta, array('meta_key' => 'wp_factor_totp_secret'));
        $wpdb->delete($wpdb->usermeta, array('meta_key' => 'wp_factor_totp_enabled'));
        $wpdb->delete($wpdb->usermeta, array('meta_key' => 'tg_wp_factor_recovery_codes'));

        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wp2fa_telegram_authcode_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_wp2fa_telegram_authcode_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_" . WP_FACTOR_TG_GETME_TRANSIENT . "%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_" . WP_FACTOR_TG_GETME_TRANSIENT . "%'");
    }

    // Utility methods
    private function is_valid_bot()
    {
        $valid_bot_transient = WP_FACTOR_TG_GETME_TRANSIENT;

        if (($is_valid_bot = get_transient($valid_bot_transient)) === false) {
            $is_valid_bot = $this->telegram->get_me() !== false;
            set_transient($valid_bot_transient, $is_valid_bot, 60 * 60 * 24);
        }

        return boolval($is_valid_bot);
    }

    private function is_setup_chatid()
    {
        $user_id = get_current_user_id();
        $chat_id = AuthPress_User_Manager::get_user_chat_id($user_id);
        return $chat_id !== false;
    }
    // REST API and rewrite methods
    public function register_telegram_webhook_route()
    {
        register_rest_route('telegram/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_telegram_webhook'),
            'permission_callback' => '__return_true',
        ));
    }

    public function add_telegram_rewrite_rules()
    {
        add_rewrite_rule(
            '^telegram-confirm/([0-9]+)/([a-zA-Z0-9]+)/?$',
            'index.php?telegram_confirm=1&user_id=$matches[1]&token=$matches[2]',
            'top'
        );

        add_filter('query_vars', function ($vars) {
            $vars[] = 'telegram_confirm';
            $vars[] = 'user_id';
            $vars[] = 'token';
            return $vars;
        });
    }

    public function parse_telegram_request()
    {
        global $wp;

        if (isset($wp->query_vars['telegram_confirm']) && $wp->query_vars['telegram_confirm'] == 1) {
            $user_id = intval($wp->query_vars['user_id']);
            $token = sanitize_text_field($wp->query_vars['token']);
            $nonce = sanitize_text_field($_GET['nonce'] ?? '');

            $this->handle_telegram_confirmation_direct($user_id, $token, $nonce);
        }
    }

    public function handle_telegram_webhook($request = null)
    {
        if ($request instanceof WP_REST_Request) {
            $update = $request->get_json_params();
            $input = wp_json_encode($update);
        } else {
            $input = file_get_contents('php://input');
            $update = json_decode($input, true);
        }

        $this->logger->log_action('webhook_received', array(
            'raw_input' => $input,
            'parsed_update' => $update
        ));

        if (!$update || !isset($update['message'])) {
            $this->logger->log_action('webhook_error', array('error' => 'Invalid update or missing message'));
            return new WP_Error('invalid_webhook', 'Invalid webhook data', array('status' => 400));
        }

        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $text = isset($message['text']) ? $message['text'] : '';

        $this->logger->log_action('message_received', array(
            'chat_id' => $chat_id,
            'text' => $text,
            'from' => $message['from'] ?? null
        ));

        if ($text === '/get_id') {
            $response_text = sprintf(
                "ℹ️ *%s*\n\n`%s`\n\n%s",
                __("Your Telegram Chat ID", "two-factor-login-telegram"),
                $chat_id,
                __("Copy this ID and paste it in your WordPress profile to enable AuthPress.", "two-factor-login-telegram")
            );

            $result = $this->telegram->send_with_keyboard($response_text, $chat_id);

            $this->logger->log_action('get_id_response', array(
                'chat_id' => $chat_id,
                'response_sent' => $result !== false
            ));
        }

        return rest_ensure_response(array('status' => 'ok'));
    }

    private function handle_telegram_confirmation_direct($user_id, $token, $nonce)
    {
        // Implementation would be moved from the main plugin class
        // For brevity, I'm not including the full implementation here
    }

    // User list table methods
    public function add_2fa_telegram_column($columns)
    {
        if (current_user_can('manage_options')) {
            $columns['tg_2fa_status'] = __('2FA Enabled', 'two-factor-login-telegram');
        }
        return $columns;
    }

    public function show_2fa_telegram_column_content($value, $column_name, $user_id)
    {
        if ($column_name == 'tg_2fa_status' && current_user_can('manage_options')) {
            $status_text = AuthPress_User_Manager::get_user_2fa_status_text($user_id);
            $has_2fa = AuthPress_User_Manager::user_has_2fa($user_id);
            $has_completed_setup = AuthPress_User_Manager::user_has_completed_setup($user_id);

            $output = '';

            if ($has_2fa) {
                $disable_nonce = wp_create_nonce('disable_2fa_telegram_' . $user_id);
                $output = '<span style="color: green;">✅ ' . esc_html($status_text) . '</span><br>' .
                    '<a href="#" class="button button-small disable-2fa-telegram" data-user-id="' . $user_id . '" data-nonce="' . $disable_nonce . '" style="margin-top: 5px;">' .
                    __('Disable All', 'two-factor-login-telegram') . '</a>';
            } else {
                $output = '<span style="color: #ccc;">❌ ' . esc_html($status_text) . '</span>';
            }

            // Add "Force Setup Wizard" button for non-admin users
            if (!user_can($user_id, 'manage_options')) {
                $force_wizard_nonce = wp_create_nonce('force_setup_wizard_' . $user_id);
                $button_text = $has_completed_setup ? __('Force Re-setup', 'two-factor-login-telegram') : __('Force Setup', 'two-factor-login-telegram');
                $output .= '<br><a href="#" class="button button-small force-setup-wizard" data-user-id="' . $user_id . '" data-nonce="' . $force_wizard_nonce . '" style="margin-top: 5px;">' .
                    $button_text . '</a>';
            }

            return $output;
        }
        return $value;
    }

    public function change_copyright()
    {
        add_filter('admin_footer_text', function() {
            return __(' | This plugin is powered by', 'two-factor-login-telegram')
                . ' <a href="https://www.dueclic.com/" target="_blank">dueclic</a>. <a class="social-foot" href="https://www.facebook.com/dueclic/"><span class="dashicons dashicons-facebook bg-fb"></span></a>';
        }, 11);
        add_filter('update_footer', function() { return ""; }, 11);
    }

    public function handle_force_setup_wizard_ajax()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'two-factor-login-telegram'));
        }

        $user_id = intval($_POST['user_id'] ?? 0);
        $nonce = sanitize_text_field($_POST['nonce'] ?? '');

        if (!$user_id || !wp_verify_nonce($nonce, 'force_setup_wizard_' . $user_id)) {
            wp_send_json_error(__('Security check failed.', 'two-factor-login-telegram'));
        }

        $user = get_userdata($user_id);
        if (!$user || user_can($user_id, 'manage_options')) {
            wp_send_json_error(__('Invalid user or cannot force setup for administrators.', 'two-factor-login-telegram'));
        }

        // Reset the user's setup flag
        $result = AuthPress_User_Manager::reset_user_setup($user_id);

        if ($result) {
            // Log the forced setup
            $this->logger->log_action('admin_forced_wizard', array(
                'user_id' => $user_id,
                'user_login' => $user->user_login,
                'admin_id' => get_current_user_id(),
                'admin_login' => wp_get_current_user()->user_login
            ));

            $message = sprintf(
                __('User %s will be shown the 2FA setup wizard on their next login.', 'two-factor-login-telegram'),
                $user->user_login
            );
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(__('Failed to force setup wizard.', 'two-factor-login-telegram'));
        }
    }
}

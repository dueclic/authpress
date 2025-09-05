<?php

namespace Authpress;

use AuthPress\Providers\Telegram_Provider;

class AuthPress_Authentication_Handler
{

    private $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function handle_login($user_login, $user)
    {
        // Check if user should see the setup wizard
        if (AuthPress_User_Manager::user_should_see_wizard($user->ID)) {
            wp_clear_auth_cookie();
            $this->show_setup_wizard($user);
            exit;
        }

        if (!AuthPress_User_Manager::user_requires_2fa($user->ID)) {
            return;
        }

        if (AuthPress_User_Manager::user_has_2fa($user->ID)) {
            wp_clear_auth_cookie();
            $this->show_two_factor_login($user);
            exit;
        }
    }

    private function show_two_factor_login($user)
    {
        $user_config = AuthPress_User_Manager::get_user_2fa_config($user->ID);
        $default_method = $user_config['effective_provider'];

        // Handle built-in providers
        if ($default_method === 'telegram' && $user_config['available_methods']['telegram']) {
            /**
             * @var $telegram_otp Telegram_Provider
             */
            $telegram_otp = AuthPress_Provider_Registry::get('telegram');
            $auth_code = $telegram_otp->save_authcode($user);

            $result = $telegram_otp->send_otp($user->ID, $auth_code);

            $this->logger->log_action('telegram_auth_code_sent', array(
                'user_id' => $user->ID,
                'user_login' => $user->user_login,
                'chat_id' => $user_config['chat_id'],
                'success' => $result !== false,
                'reason' => 'default_method_telegram'
            ));
        } elseif ($default_method === 'email' && $user_config['available_methods']['email']) {
            $email_otp = AuthPress_Provider_Registry::get('email');
            $auth_code = $email_otp->save_authcode($user);

            $this->logger->log_action('email_code_sent', array(
                'user_id' => $user->ID,
                'user_login' => $user->user_login,
                'email' => $user->user_email,
                'success' => $auth_code !== false,
                'reason' => 'default_method_email'
            ));
        } else {
            // Handle external providers
            $provider = AuthPress_Provider_Registry::get($default_method);
            if ($provider && isset($user_config['available_methods'][$default_method]) && $user_config['available_methods'][$default_method]) {
                $codes = $provider->generate_codes($user->ID);
                if (!empty($codes)) {
                    $this->logger->log_action('external_code_sent', array(
                        'user_id' => $user->ID,
                        'user_login' => $user->user_login,
                        'provider' => $default_method,
                        'provider_name' => $provider->get_name(),
                        'success' => true,
                        'reason' => 'default_method_external'
                    ));
                } else {
                    $this->logger->log_action('external_code_failed', array(
                        'user_id' => $user->ID,
                        'user_login' => $user->user_login,
                        'provider' => $default_method,
                        'provider_name' => $provider->get_name(),
                        'success' => false,
                        'reason' => 'code_generation_failed'
                    ));
                }
            }
        }

        $redirect_to = isset($_REQUEST['redirect_to'])
            ? wp_sanitize_redirect($_REQUEST['redirect_to']) : wp_unslash($_SERVER['REQUEST_URI']);

        $this->login_html($user, $redirect_to);
    }

    private function login_html($user, $redirect_to, $error_msg = '', $failed_method = null)
    {
        $rememberme = 0;
        if (isset($_REQUEST['rememberme']) && $_REQUEST['rememberme']) {
            $rememberme = 1;
        }

        $plugin_logo = authpress_logo();

        $user_config = AuthPress_User_Manager::get_user_2fa_config($user->ID);
        $default_method = $user_config['effective_provider'];

        // If validation failed, set the failed method as the active method
        if ($failed_method !== null && $failed_method !== 'recovery') {
            $default_method = $failed_method;
        }

        require_once(ABSPATH . '/wp-admin/includes/template.php');
        require_once(dirname(AUTHPRESS_PLUGIN_FILE) . "/templates/login-form.php");
    }

    public function validate_authentication()
    {
        if (!isset($_POST['wp-auth-id'])) {
            return;
        }

        $user = get_userdata($_POST['wp-auth-id']);
        if (!$user) {
            return;
        }

        if (!wp_verify_nonce($_POST['authpress_auth_nonce'], 'authpress_auth_nonce_' . $user->ID)) {
            return;
        }

        $login_method = $_POST['login_method'] ?? 'telegram';

        $has_code = apply_filters('authpress_login_provider_has_code', true, $login_method, $_POST, $user);

        $login_successful = false;
        $error_message = '';

        if (!$has_code) {
            $validation_result = apply_filters('authpress_validate_codeless_authentication', false, $login_method, $_POST, $user);
            if ($validation_result) {
                $login_successful = true;
                $this->log_successful_login($login_method, $user, '');
            } else {
                $error_message = apply_filters('authpress_get_codeless_error_message', __('Authentication failed. Please try again.', 'two-factor-login-telegram'), $login_method);
                $this->get_generic_failed_message($login_method, $user, '');
            }
        } else {
            $code = $this->get_code_from_post($login_method);

            if (empty($code)) {
                $error_message = $this->get_empty_code_error_message($login_method);
            } else {
                $validation_result = AuthPress_Provider_Registry::validate_by_method($code, $user->ID, $login_method);

                if ($validation_result) {
                    $login_successful = true;
                    $this->log_successful_login($login_method, $user, $code);
                } else {
                    $error_message = $this->handle_failed_validation($login_method, $user, $code);
                }
            }
        }

        if (!$login_successful) {
            $this->login_html($user, $_REQUEST['redirect_to'], $error_message, $login_method);
            exit;
        }

        $this->complete_login($user);
    }

    private function get_code_from_post($login_method)
    {
        switch ($login_method) {
            case 'recovery':
                return $_POST['recovery_code'] ?? '';
            case 'totp':
                return $_POST['totp_code'] ?? '';
            case 'email':
                return $_POST['email_code'] ?? '';
            case 'telegram':
                return $_POST['authcode'] ?? '';
            default:
                // For external providers, look for {method}_code field
                return $_POST[$login_method . '_code'] ?? '';
        }
    }

    private function get_empty_code_error_message($login_method)
    {
        switch ($login_method) {
            case 'recovery':
                return __('Please enter a recovery code.', 'two-factor-login-telegram');
            case 'totp':
                return __('Please enter the authenticator code.', 'two-factor-login-telegram');
            case 'email':
                return __('Please enter the email verification code.', 'two-factor-login-telegram');
            default:
                return __('Please enter the verification code.', 'two-factor-login-telegram');
        }
    }

    private function log_successful_login($login_method, $user, $code)
    {
        $log_action = ($login_method === 'recovery') ? 'recovery_code_login_success' :
            ($login_method === 'totp' ? 'totp_code_login_success' :
            ($login_method === 'email' ? 'email_code_login_success' : 'telegram_code_login_success'));

        $this->logger->log_action($log_action, array(
            'user_id' => $user->ID,
            'user_login' => $user->user_login,
            'method' => $login_method,
            'code_used' => substr($code, 0, 4) . '****'
        ));
    }


    private function handle_failed_validation($login_method, $user, $code)
    {
        do_action_deprecated(
            'wp_factor_telegram_failed',
            array($user->user_login),
            '4.0.0',
            'authpress_login_failed',
            __('The action wp_factor_telegram_failed is deprecated. Use authpress_login_failed instead.', 'two-factor-login-telegram')
        );

        do_action('authpress_login_failed', $user->user_login, $login_method, substr($code, 0, 4) . '****');

        if ($login_method === 'telegram') {
            return $this->handle_telegram_failed_validation($user, $code);
        } elseif ($login_method === 'email') {
            return $this->handle_email_failed_validation($user, $code);
        }

        return $this->get_generic_failed_message($login_method, $user, $code);
    }

    private function handle_telegram_failed_validation($user, $code)
    {
        /**
         * @var $telegram_otp Telegram_Provider
         */

        $telegram_otp = AuthPress_Provider_Registry::get('telegram');
        $authcode_validation = $telegram_otp->validate_authcode($code, $user->ID);

        if (AuthPress_User_Manager::user_has_telegram($user->ID)) {
            $chat_id = AuthPress_User_Manager::get_user_chat_id($user->ID);
            $auth_code = $telegram_otp->save_authcode($user);
            $result = $telegram_otp->send_otp($user->ID, $auth_code);

            $this->logger->log_action('telegram_auth_code_resent', array(
                'user_id' => $user->ID,
                'user_login' => $user->user_login,
                'chat_id' => $chat_id,
                'success' => $result !== false,
                'reason' => ($authcode_validation === 'expired') ? 'expired_verification_code' : 'wrong_verification_code'
            ));
        }

        return ($authcode_validation === 'expired')
            ? __('The verification code has expired. We just sent you a new code, please try again!', 'two-factor-login-telegram')
            : __('Wrong verification code, we just sent a new code, please try again!', 'two-factor-login-telegram');
    }

    private function handle_email_failed_validation($user, $code)
    {
        $email_otp = AuthPress_Provider_Registry::get('email');
        $authcode_validation = $email_otp->validate_authcode($code, $user->ID);

        if (AuthPress_User_Manager::user_has_email($user->ID)) {
            $auth_code = $email_otp->save_authcode($user);

            $this->logger->log_action('email_code_resent', array(
                'user_id' => $user->ID,
                'user_login' => $user->user_login,
                'email' => $user->user_email,
                'success' => $auth_code !== false,
                'reason' => ($authcode_validation === 'expired') ? 'expired_verification_code' : 'wrong_verification_code'
            ));
        }

        return ($authcode_validation === 'expired')
            ? __('The verification code has expired. We just sent you a new code via email, please check your inbox!', 'two-factor-login-telegram')
            : __('Wrong verification code, we just sent a new code via email, please check your inbox!', 'two-factor-login-telegram');
    }

    private function get_generic_failed_message($login_method, $user, $code)
    {
        $log_data = array(
            'user_id' => $user->ID,
            'user_login' => $user->user_login,
            'attempted_code' => substr($code, 0, 4) . '****'
        );

        switch ($login_method) {
            case 'recovery':
                $this->logger->log_action('recovery_code_login_failed', $log_data);
                return __('Invalid recovery code. Please check and try again.', 'two-factor-login-telegram');
            case 'totp':
                $this->logger->log_action('totp_code_login_failed', $log_data);
                return __('Invalid authenticator code. Please check and try again.', 'two-factor-login-telegram');
            case 'email':
                $this->logger->log_action('email_code_login_failed', $log_data);
                return __('Invalid email code. Please check your email and try again.', 'two-factor-login-telegram');
            default:
                $this->logger->log_action($login_method.'_code_login_failed', $log_data);
                return __('Invalid verification code. Please try again.', 'two-factor-login-telegram');
        }
    }

    private function complete_login($user)
    {
        $rememberme = false;
        if (isset($_REQUEST['rememberme']) && $_REQUEST['rememberme']) {
            $rememberme = true;
        }

        wp_set_auth_cookie($user->ID, $rememberme);

        $redirect_to = apply_filters(
            'login_redirect',
            $_REQUEST['redirect_to'],
            $_REQUEST['redirect_to'],
            $user
        );
        wp_safe_redirect($redirect_to);
        exit;
    }

    private function show_setup_wizard($user)
    {
        $redirect_to = isset($_REQUEST['redirect_to'])
            ? wp_sanitize_redirect($_REQUEST['redirect_to']) : wp_unslash($_SERVER['REQUEST_URI']);

        $plugin_logo = authpress_logo();

        require_once(ABSPATH . '/wp-admin/includes/template.php');
        require_once(dirname(AUTHPRESS_PLUGIN_FILE) . "/templates/setup-wizard.php");
    }

    public function handle_setup_wizard_submission()
    {
        if (!isset($_POST['setup_wizard_nonce']) || !isset($_POST['user_id'])) {
            return;
        }

        $user_id = intval($_POST['user_id']);
        $user = get_userdata($user_id);

        if (!$user || !wp_verify_nonce($_POST['setup_wizard_nonce'], 'authpress_setup_wizard_' . $user_id)) {
            wp_die(__('Security check failed. Please try again.', 'two-factor-login-telegram'));
        }

        $setup_method = sanitize_text_field($_POST['setup_method'] ?? '');
        $redirect_to = wp_sanitize_redirect($_POST['redirect_to'] ?? admin_url());

        if (empty($setup_method)) {
            wp_die(__('Please select a 2FA method.', 'two-factor-login-telegram'));
        }

        // Log the setup attempt
        $this->logger->log_action('wizard_method_selected', array(
            'user_id' => $user_id,
            'user_login' => $user->user_login,
            'method' => $setup_method
        ));

        // Handle different setup methods
        switch ($setup_method) {
            case 'email':
                if (AuthPress_User_Manager::user_email_available($user_id)) {
                    AuthPress_User_Manager::enable_user_email($user_id);
                    AuthPress_User_Manager::mark_user_setup_completed($user_id);
                    $this->complete_wizard_login($user, $redirect_to);
                }
                break;

            case 'telegram':
            case 'authenticator':
                // For Telegram and Authenticator, redirect to settings page for full setup
                AuthPress_User_Manager::mark_user_setup_completed($user_id);
                wp_set_auth_cookie($user_id, false);

                $settings_url = add_query_arg(array(
                    'page' => 'my-2fa-settings',
                    'setup_method' => $setup_method,
                    'redirect_to' => urlencode($redirect_to)
                ), admin_url('users.php'));

                wp_safe_redirect($settings_url);
                exit;

            default:
                wp_die(__('Invalid setup method selected.', 'two-factor-login-telegram'));
        }
    }

    public function handle_wizard_skip()
    {
        if (!isset($_GET['authpress_skip_wizard'])) {
            return;
        }

        // Extract user info from login session or current context
        $redirect_to = wp_sanitize_redirect($_GET['redirect_to'] ?? admin_url());

        // Check if we have a pending login context
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->exists()) {
            // Mark as setup completed (skipped)
            AuthPress_User_Manager::mark_user_setup_completed($current_user->ID);

            $this->logger->log_action('wizard_skipped', array(
                'user_id' => $current_user->ID,
                'user_login' => $current_user->user_login
            ));
        }

        wp_safe_redirect($redirect_to);
        exit;
    }

    private function complete_wizard_login($user, $redirect_to)
    {
        wp_set_auth_cookie($user->ID, false);

        $this->logger->log_action('wizard_completed', array(
            'user_id' => $user->ID,
            'user_login' => $user->user_login
        ));

        wp_safe_redirect($redirect_to);
        exit;
    }
}

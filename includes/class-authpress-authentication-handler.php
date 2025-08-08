<?php

namespace Authpress;

class AuthPress_Authentication_Handler
{
    private $telegram;
    private $logger;

    public function __construct($telegram, $logger)
    {
        $this->telegram = $telegram;
        $this->logger = $logger;
    }

    public function handle_login($user_login, $user)
    {
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

        if ($default_method === 'telegram' && $user_config['available_methods']['telegram']) {
            $telegram_otp = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_TELEGRAM_OTP);
            $auth_code = $telegram_otp->save_authcode($user);

            $result = $this->telegram->send_tg_token($auth_code, $user_config['chat_id'], $user->ID);

            $this->logger->log_telegram_action('auth_code_sent', array(
                'user_id' => $user->ID,
                'user_login' => $user->user_login,
                'chat_id' => $user_config['chat_id'],
                'success' => $result !== false,
                'reason' => 'default_method_telegram'
            ));
        } elseif ($default_method === 'email' && $user_config['available_methods']['email']) {
            $email_otp = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_EMAIL_OTP);
            $auth_code = $email_otp->save_authcode($user);

            $this->logger->log_telegram_action('email_code_sent', array(
                'user_id' => $user->ID,
                'user_login' => $user->user_login,
                'email' => $user->user_email,
                'success' => $auth_code !== false,
                'reason' => 'default_method_email'
            ));
        }

        $redirect_to = isset($_REQUEST['redirect_to'])
            ? wp_sanitize_redirect($_REQUEST['redirect_to']) : wp_unslash($_SERVER['REQUEST_URI']);

        $this->login_html($user, $redirect_to);
    }

    private function login_html($user, $redirect_to, $error_msg = '')
    {
        $rememberme = 0;
        if (isset($_REQUEST['rememberme']) && $_REQUEST['rememberme']) {
            $rememberme = 1;
        }

        $plugin_logo = apply_filters(
            'two_factor_login_telegram_logo',
            plugins_url('assets/img/plugin_logo.png', WP_FACTOR_TG_FILE)
        );

        $user_config = AuthPress_User_Manager::get_user_2fa_config($user->ID);
        $user_has_telegram = $user_config['available_methods']['telegram'];
        $user_has_email = $user_config['available_methods']['email'];
        $user_has_totp = $user_config['available_methods']['totp'];
        $default_method = $user_config['effective_provider'];

        require_once(ABSPATH . '/wp-admin/includes/template.php');
        require_once(dirname(WP_FACTOR_TG_FILE) . "/templates/login-form.php");
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

        if (!wp_verify_nonce($_POST['nonce'], 'wp2fa_telegram_auth_nonce_' . $user->ID)) {
            return;
        }

        $login_method = isset($_POST['login_method']) ? $_POST['login_method'] : 'telegram';
        $login_successful = false;
        $error_message = '';

        $code = $this->get_code_from_post($login_method);

        if (empty($code)) {
            $error_message = $this->get_empty_code_error_message($login_method);
        } else {
            $validation_result = AuthPress_Auth_Factory::validateByMethod($code, $user->ID, $login_method);

            if ($validation_result) {
                $login_successful = true;
                $this->log_successful_login($login_method, $user, $code);
            } else {
                $error_message = $this->handle_failed_validation($login_method, $user, $code);
            }
        }

        if (!$login_successful) {
            $this->login_html($user, $_REQUEST['redirect_to'], $error_message);
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
            default:
                return $_POST['authcode'] ?? '';
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
        
        $this->logger->log_telegram_action($log_action, array(
            'user_id' => $user->ID,
            'user_login' => $user->user_login,
            'method' => $login_method,
            'code_used' => substr($code, 0, 4) . '****'
        ));
    }

    private function handle_failed_validation($login_method, $user, $code)
    {
        do_action('wp_factor_telegram_failed', $user->user_login);

        if ($login_method === 'telegram') {
            return $this->handle_telegram_failed_validation($user, $code);
        }

        return $this->get_generic_failed_message($login_method, $user, $code);
    }

    private function handle_telegram_failed_validation($user, $code)
    {
        $telegram_otp = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_TELEGRAM_OTP);
        $authcode_validation = $telegram_otp->validate_authcode($code, $user->ID);

        if (AuthPress_User_Manager::user_has_telegram($user->ID)) {
            $chat_id = AuthPress_User_Manager::get_user_chat_id($user->ID);
            $auth_code = $telegram_otp->save_authcode($user);
            $result = $this->telegram->send_tg_token($auth_code, $chat_id, $user->ID);

            $this->logger->log_telegram_action('auth_code_resent', array(
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

    private function get_generic_failed_message($login_method, $user, $code)
    {
        $log_data = array(
            'user_id' => $user->ID,
            'user_login' => $user->user_login,
            'attempted_code' => substr($code, 0, 4) . '****'
        );

        switch ($login_method) {
            case 'recovery':
                $this->logger->log_telegram_action('recovery_code_login_failed', $log_data);
                return __('Invalid recovery code. Please check and try again.', 'two-factor-login-telegram');
            case 'totp':
                $this->logger->log_telegram_action('totp_code_login_failed', $log_data);
                return __('Invalid authenticator code. Please check and try again.', 'two-factor-login-telegram');
            case 'email':
                $this->logger->log_telegram_action('email_code_login_failed', $log_data);
                return __('Invalid email code. Please check your email and try again.', 'two-factor-login-telegram');
            default:
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

        $recovery_codes = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_RECOVERY_CODES);
        if (!$recovery_codes->has_recovery_codes($user->ID)) {
            $codes = $recovery_codes->regenerate_recovery_codes($user->ID);
            $plugin_logo = apply_filters('two_factor_login_telegram_logo', plugins_url('assets/img/plugin_logo.png', WP_FACTOR_TG_FILE));
            $redirect_to = apply_filters('login_redirect', $_REQUEST['redirect_to'], $_REQUEST['redirect_to'], $user);
            require_once(dirname(WP_FACTOR_TG_FILE) . '/templates/recovery-codes-wizard.php');
            exit;
        }

        $redirect_to = apply_filters(
            'login_redirect',
            $_REQUEST['redirect_to'],
            $_REQUEST['redirect_to'],
            $user
        );
        wp_safe_redirect($redirect_to);
        exit;
    }
}
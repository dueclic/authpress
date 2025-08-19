<?php

namespace Authpress;

class AuthPress_AJAX_Handler
{
    private $telegram;
    private $logger;

    public function __construct($telegram, $logger)
    {
        $this->telegram = $telegram;
        $this->logger = $logger;
    }

    public function send_token_check()
    {
        $response = array('type' => 'error');

        if (!wp_verify_nonce($_POST['nonce'], 'ajax-tokencheck-nonce')) {
            $response['msg'] = __('Security check error', 'two-factor-login-telegram');
            die(json_encode($response));
        }

        if (!isset($_POST['chat_id']) || $_POST['chat_id'] == "") {
            $response['msg'] = __('Please fill Chat ID field.', 'two-factor-login-telegram');
            die(json_encode($response));
        }

        $telegram_otp = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_TELEGRAM_OTP);
        $codes = $telegram_otp->generate_codes(0, ['length' => 5]);
        $auth_code = !empty($codes) ? $codes[0] : '';

        set_transient('wp2fa_telegram_authcode_' . $_POST['chat_id'], hash('sha256', $auth_code), WP_FACTOR_AUTHCODE_EXPIRE_SECONDS);

        $current_user_id = get_current_user_id();

        $validation_message = sprintf(
            "🔐 *%s*\n\n`%s`\n\n%s",
            __("WordPress 2FA Validation Code", "two-factor-login-telegram"),
            $auth_code,
            __("Use this code to complete your 2FA setup in WordPress, or click the button below:", "two-factor-login-telegram")
        );

        $reply_markup = null;
        if ($current_user_id) {
            $nonce = wp_create_nonce('telegram_validate_' . $current_user_id . '_' . $auth_code);
            $validation_url = admin_url('profile.php?action=telegram_validate&chat_id=' . $_POST['chat_id'] . '&user_id=' . $current_user_id . '&token=' . $auth_code . '&nonce=' . $nonce);

            $reply_markup = array(
                'inline_keyboard' => array(
                    array(
                        array(
                            'text' => '✅ ' . __('Validate Setup', 'two-factor-login-telegram'),
                            'url' => $validation_url
                        )
                    )
                )
            );
        }

        $send = $this->telegram->send_with_keyboard($validation_message, $_POST['chat_id'], $reply_markup);

        $this->logger->log_action('validation_code_sent', array(
            'chat_id' => $_POST['chat_id'],
            'success' => $send !== false,
            'error' => $send === false ? $this->telegram->lastError : null
        ));

        if (!$send) {
            $response['msg'] = sprintf(__("Error (%s): validation code was not sent, try again!", 'two-factor-login-telegram'), $this->telegram->lastError);
        } else {
            $response['type'] = "success";
            $response['msg'] = __("Validation code was successfully sent", 'two-factor-login-telegram');
        }

        die(json_encode($response));
    }

    public function check_bot()
    {
        $response = array('type' => 'error');

        if (!wp_verify_nonce($_POST['nonce'], 'ajax-checkbot-nonce')) {
            $response['msg'] = __('Security check error', 'two-factor-login-telegram');
            die(json_encode($response));
        }

        if (!isset($_POST['bot_token']) || $_POST['bot_token'] == "") {
            $response['msg'] = __('This bot does not exists.', 'two-factor-login-telegram');
            die(json_encode($response));
        }

        $me = $this->telegram->set_bot_token($_POST['bot_token'])->get_me();

        if ($me === false) {
            $response['msg'] = __('Unable to get Bot infos, please retry.', 'two-factor-login-telegram');
            die(json_encode($response));
        }

        $response = array(
            'type' => 'success',
            'msg' => __('This bot exists.', 'two-factor-login-telegram'),
            'args' => array(
                'id' => $me->id,
                'first_name' => $me->first_name,
                'username' => $me->username,
            ),
        );

        die(json_encode($response));
    }

    public function token_check()
    {
        $response = array('type' => 'error');

        if (!wp_verify_nonce($_POST['nonce'], 'ajax-sendtoken-nonce')) {
            $response['msg'] = __('Security check error', 'two-factor-login-telegram');
            die(json_encode($response));
        }

        $messages = [
            "token_wrong" => __('The token entered is wrong.', 'two-factor-login-telegram'),
            "chat_id_wrong" => __('Chat ID is wrong.', 'two-factor-login-telegram')
        ];

        if (!isset($_POST['token']) || $_POST['token'] == "") {
            $response['msg'] = $messages["token_wrong"];
            die(json_encode($response));
        }

        if (!isset($_POST['chat_id']) || $_POST['chat_id'] == "") {
            $response['msg'] = $messages["chat_id_wrong"];
            die(json_encode($response));
        }

        $telegram_otp = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_TELEGRAM_OTP);
        if (!$telegram_otp->validate_tokencheck_authcode($_POST['token'], $_POST['chat_id'])) {
            $response['msg'] = __('Validation code entered is wrong.', 'two-factor-login-telegram');
        } else {
            $response['type'] = "success";
            $response['msg'] = __("Validation code is correct.", 'two-factor-login-telegram');
        }

        die(json_encode($response));
    }

    public function regenerate_recovery()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized.', 'two-factor-login-telegram')]);
        }
        $user_id = get_current_user_id();
        if (!wp_verify_nonce($_POST['_wpnonce'], 'tg_regenerate_recovery_codes_' . $user_id)) {
            wp_send_json_error(['message' => __('Invalid request.', 'two-factor-login-telegram')]);
        }
        $recovery_codes = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_RECOVERY_CODES);
        $codes = $recovery_codes->regenerate_recovery_codes($user_id, 8, 10);

        $plugin_logo = authpress_logo();
        $redirect_to = $_POST['redirect_to'] ?? admin_url('profile.php');
        ob_start();
        define('IS_PROFILE_PAGE', true);
        require(dirname(WP_FACTOR_TG_FILE) . '/templates/recovery-codes-wizard.php');
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public function setup_totp()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized.', 'two-factor-login-telegram')]);
        }

        $user_id = get_current_user_id();

        if (!wp_verify_nonce($_POST['_wpnonce'], 'setup_totp_' . $user_id)) {
            wp_send_json_error(['message' => __('Invalid request.', 'two-factor-login-telegram')]);
        }

        $totp = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_TOTP);
        $totp_data = $totp->generate_codes($user_id);

        if (empty($totp_data)) {
            wp_send_json_error(['message' => __('Failed to generate TOTP secret.', 'two-factor-login-telegram')]);
        }

        $this->logger->log_action('totp_setup_requested', array(
            'user_id' => $user_id,
            'user_login' => wp_get_current_user()->user_login
        ));

        wp_send_json_success($totp_data);
    }

    public function verify_totp()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized.', 'two-factor-login-telegram')]);
        }

        $user_id = get_current_user_id();

        if (!wp_verify_nonce($_POST['_wpnonce'], 'verify_totp_' . $user_id)) {
            wp_send_json_error(['message' => __('Invalid request.', 'two-factor-login-telegram')]);
        }

        if (empty($_POST['code'])) {
            wp_send_json_error(['message' => __('Please enter a verification code.', 'two-factor-login-telegram')]);
        }

        $code = sanitize_text_field($_POST['code']);
        $totp = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_TOTP);

        if ($totp->verify_setup_code($code, $user_id)) {
            $totp->enable_user_totp($user_id);

            $this->logger->log_action('totp_enabled', array(
                'user_id' => $user_id,
                'user_login' => wp_get_current_user()->user_login
            ));

            wp_send_json_success(['message' => __('Authenticator app enabled successfully!', 'two-factor-login-telegram')]);
        } else {
            $this->logger->log_action('totp_verification_failed', array(
                'user_id' => $user_id,
                'user_login' => wp_get_current_user()->user_login,
                'attempted_code' => substr($code, 0, 2) . '****'
            ));

            wp_send_json_error(['message' => __('Invalid verification code. Please try again.', 'two-factor-login-telegram')]);
        }
    }

    public function disable_totp()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized.', 'two-factor-login-telegram')]);
        }

        $user_id = get_current_user_id();

        if (!wp_verify_nonce($_POST['_wpnonce'], 'disable_totp_' . $user_id)) {
            wp_send_json_error(['message' => __('Invalid request.', 'two-factor-login-telegram')]);
        }

        $totp = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_TOTP);

        if ($totp->disable_user_totp($user_id)) {
            $this->logger->log_action('totp_disabled', array(
                'user_id' => $user_id,
                'user_login' => wp_get_current_user()->user_login
            ));

            wp_send_json_success(['message' => __('Authenticator app disabled successfully.', 'two-factor-login-telegram')]);
        } else {
            wp_send_json_error(['message' => __('Failed to disable authenticator app.', 'two-factor-login-telegram')]);
        }
    }

    public function send_login_telegram_code()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'authpress_auth_nonce_' . $_POST['user_id'])) {
            wp_send_json_error(['message' => __('Security verification failed', 'two-factor-login-telegram')]);
        }

        $user_id = intval($_POST['user_id']);
        $user = get_userdata($user_id);

        if (!$user) {
            wp_send_json_error(['message' => __('Invalid user.', 'two-factor-login-telegram')]);
        }

        if (!AuthPress_User_Manager::user_has_telegram($user_id)) {
            wp_send_json_error(['message' => __('Telegram is not configured for this user.', 'two-factor-login-telegram')]);
        }

        $telegram_otp = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_TELEGRAM_OTP);
        $auth_code = $telegram_otp->save_authcode($user);
        $chat_id = AuthPress_User_Manager::get_user_chat_id($user_id);

        $result = $this->telegram->send_tg_token($auth_code, $chat_id, $user_id);

        $this->logger->log_action('auth_code_sent_on_request', array(
            'user_id' => $user_id,
            'user_login' => $user->user_login,
            'chat_id' => $chat_id,
            'success' => $result !== false,
            'reason' => 'user_switched_to_telegram'
        ));

        if ($result !== false) {
            wp_send_json_success(['message' => __('Telegram code sent successfully!', 'two-factor-login-telegram')]);
        } else {
            wp_send_json_error(['message' => __('Failed to send Telegram code. Please try again.', 'two-factor-login-telegram')]);
        }
    }

    public function send_login_email_code()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'authpress_auth_nonce_' . $_POST['user_id'])) {
            wp_send_json_error(['message' => __('Security verification failed', 'two-factor-login-telegram')]);
        }

        $user_id = intval($_POST['user_id']);
        $user = get_userdata($user_id);

        if (!$user) {
            wp_send_json_error(['message' => __('Invalid user.', 'two-factor-login-telegram')]);
        }

        if (!AuthPress_User_Manager::user_has_email($user_id)) {
            wp_send_json_error(['message' => __('Email is not configured for this user.', 'two-factor-login-telegram')]);
        }

        $email_otp = AuthPress_Auth_Factory::create(AuthPress_Auth_Factory::METHOD_EMAIL_OTP);
        $auth_code = $email_otp->save_authcode($user);

        $this->logger->log_action('email_code_sent_on_request', array(
            'user_id' => $user_id,
            'user_login' => $user->user_login,
            'email' => $user->user_email,
            'success' => $auth_code !== false,
            'reason' => 'user_switched_to_email'
        ));

        if ($auth_code !== false) {
            wp_send_json_success(['message' => __('Email code sent successfully!', 'two-factor-login-telegram')]);
        } else {
            wp_send_json_error(['message' => __('Failed to send email code. Please try again.', 'two-factor-login-telegram')]);
        }
    }

    public function disable_user_2fa_ajax()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'two-factor-login-telegram'));
        }

        $user_id = intval($_POST['user_id']);
        $nonce = sanitize_text_field($_POST['nonce']);

        if (!wp_verify_nonce($nonce, 'disable_2fa_telegram_' . $user_id)) {
            wp_die(__('Security verification failed', 'two-factor-login-telegram'));
        }

        update_user_meta($user_id, 'tg_wp_factor_enabled', '0');
        delete_user_meta($user_id, 'tg_wp_factor_chat_id');

        $user = get_userdata($user_id);
        $current_user = wp_get_current_user();

        $this->logger->log_action('admin_disabled_2fa', array(
            'disabled_user_id' => $user_id,
            'disabled_user_login' => $user ? $user->user_login : 'unknown',
            'admin_user_id' => $current_user->ID,
            'admin_user_login' => $current_user->user_login
        ));

        wp_send_json_success(array(
            'message' => __('2FA has been disabled for this user.', 'two-factor-login-telegram'),
            'new_status' => '<span style="color: #ccc;">❌ ' . __('Inactive', 'two-factor-login-telegram') . '</span>'
        ));
    }
}

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

        set_transient('wp2fa_telegram_authcode_' . $_POST['chat_id'], hash('sha256', $auth_code), get_auth_token_duration());

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
	// For testin email service
	public function handle_test_email()
	{
		if (!is_user_logged_in() || !current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Not authorized.', 'two-factor-login-telegram')]);
		}

		if (!wp_verify_nonce($_POST['_wpnonce'], 'authpress_test_email_nonce')) {
			wp_send_json_error(['message' => __('Invalid request.', 'two-factor-login-telegram')]);
		}

		$current_user = wp_get_current_user();
        $auth_email = get_user_meta($current_user->ID, 'authpress_authentication_email', true);
        $email = !empty($auth_email) && is_email($auth_email) ? $auth_email : $current_user->user_email;


		$subject = sprintf(__('[%s] Test Email from AuthPress', 'two-factor-login-telegram'), get_bloginfo('name'));
		$message = __("Hello,\n\nThis is a test email sent from the AuthPress plugin to confirm that your WordPress mail configuration is working correctly.\n\nRegards,\nThe AuthPress Plugin", 'two-factor-login-telegram');
		$headers = ['Content-Type: text/plain; charset=UTF-8'];

		$sent = wp_mail($email, $subject, $message, $headers);

		if ($sent) {
			wp_send_json_success(['message' => sprintf(__('Test email successfully sent to %s.', 'two-factor-login-telegram'), $email)]);
		} else {
			wp_send_json_error(['message' => __('Failed to send the test email. Please check your WordPress mail configuration or SMTP plugin settings.', 'two-factor-login-telegram')]);
		}
	}

    public function send_auth_email_verification()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized.', 'two-factor-login-telegram')]);
        }

        $user_id = get_current_user_id();

        if (!wp_verify_nonce($_POST['_wpnonce'], 'authpress_save_auth_email_' . $user_id)) {
            wp_send_json_error(['message' => __('Invalid request.', 'two-factor-login-telegram')]);
        }

        $new_email = isset($_POST['authpress_auth_email']) ? sanitize_email($_POST['authpress_auth_email']) : '';

        if (is_email($new_email)) {
            $verification_code = strval(rand(100000, 999999));
            update_user_meta($user_id, 'authpress_pending_email', $new_email);
            update_user_meta($user_id, 'authpress_pending_email_code', $verification_code);

            $subject = __('Verify your new authentication email', 'two-factor-login-telegram');
            $message = sprintf(__('Your verification code is: %s', 'two-factor-login-telegram'), $verification_code);
            $sent = wp_mail($new_email, $subject, $message);

            if ($sent) {
                wp_send_json_success(['message' => __('A verification code has been sent to the new email address. Please enter the code to confirm the change.', 'two-factor-login-telegram')]);
            } else {
                wp_send_json_error(['message' => __('Failed to send the verification email. Please check your WordPress mail configuration.', 'two-factor-login-telegram')]);
            }
        } else {
            wp_send_json_error(['message' => __('Invalid email address provided.', 'two-factor-login-telegram')]);
        }
    }

    public function verify_auth_email()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized.', 'two-factor-login-telegram')]);
        }

        $user_id = get_current_user_id();

        if (!wp_verify_nonce($_POST['_wpnonce'], 'authpress_verify_auth_email_' . $user_id)) {
            wp_send_json_error(['message' => __('Invalid request.', 'two-factor-login-telegram')]);
        }

        $verification_code = isset($_POST['authpress_verification_code']) ? sanitize_text_field($_POST['authpress_verification_code']) : '';
        $pending_email = get_user_meta($user_id, 'authpress_pending_email', true);
        $stored_code = get_user_meta($user_id, 'authpress_pending_email_code', true);

        if ($verification_code === $stored_code && is_email($pending_email)) {
            update_user_meta($user_id, 'authpress_authentication_email', $pending_email);
            AuthPress_User_Manager::enable_user_email($user_id);
            delete_user_meta($user_id, 'authpress_pending_email');
            delete_user_meta($user_id, 'authpress_pending_email_code');

            wp_send_json_success(['message' => __('Authentication email address changed successfully.', 'two-factor-login-telegram')]);
        } else {
            wp_send_json_error(['message' => __('Invalid verification code.', 'two-factor-login-telegram')]);
        }
    }

    public function reset_auth_email()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized.', 'two-factor-login-telegram')]);
        }

        $user_id = get_current_user_id();

        if (!wp_verify_nonce($_POST['_wpnonce'], 'authpress_save_auth_email_' . $user_id)) {
            wp_send_json_error(['message' => __('Invalid request.', 'two-factor-login-telegram')]);
        }

        delete_user_meta($user_id, 'authpress_authentication_email');

        wp_send_json_success(['message' => __('Authentication email has been reset to your default WordPress email address.', 'two-factor-login-telegram')]);
    }

    public function update_user_provider_status()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized.', 'two-factor-login-telegram')]);
        }

        $user_id = get_current_user_id();
        $provider_key = sanitize_key($_POST['provider_key'] ?? '');
        $nonce = sanitize_text_field($_POST['nonce'] ?? '');

        if (empty($provider_key) || !wp_verify_nonce($nonce, 'authpress_update_user_provider_status_' . $provider_key)) {
            wp_send_json_error(['message' => __('Invalid request or security check failed.', 'two-factor-login-telegram')]);
        }

        if ($user_id != intval($_POST['user_id'])) {
            wp_send_json_error(['message' => __('You can only change your own settings.', 'two-factor-login-telegram')]);
        }

        $is_enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1';
        $result = false;

        // Only allow toggling if the method is already configured.
        $user_config = \Authpress\AuthPress_User_Manager::get_user_2fa_config($user_id);
        $user_method_key = $provider_key === 'authenticator' ? 'totp' : $provider_key;
        $is_configured = $user_config['available_methods'][$user_method_key] ?? false;

        // If enabling, it must be configured. If disabling, it's fine.
        if ($is_enabled && !$is_configured) {
             wp_send_json_error(['message' => __('This method must be configured before it can be enabled.', 'two-factor-login-telegram')]);
        }

        switch ($provider_key) {
            case 'authenticator':
                $totp_provider = \Authpress\AuthPress_Auth_Factory::create(\Authpress\AuthPress_Auth_Factory::METHOD_TOTP);
                if ($is_enabled) {
                    $result = $totp_provider->enable_user_totp($user_id);
                } else {
                    $result = $totp_provider->disable_user_totp($user_id);
                }
                break;
            case 'telegram':
                update_user_meta($user_id, 'tg_wp_factor_enabled', $is_enabled ? '1' : '0');
                $result = true;
                break;
            case 'email':
                if ($is_enabled) {
                    \Authpress\AuthPress_User_Manager::enable_user_email($user_id);
                    $result = true;
                } else {
                    // Assuming 'wp_factor_email_enabled' is the meta key.
                    update_user_meta($user_id, 'wp_factor_email_enabled', false);
                    $result = true;
                }
                break;
        }

        if ($result) {
            $this->logger->log_action('user_provider_status_changed', [
                'user_id' => $user_id,
                'provider' => $provider_key,
                'enabled' => $is_enabled,
                'changed_by' => 'user'
            ]);
            wp_send_json_success(['message' => __('Settings saved.', 'two-factor-login-telegram')]);
        } else {
            wp_send_json_error(['message' => __('Could not save settings.', 'two-factor-login-telegram')]);
        }
    }
}

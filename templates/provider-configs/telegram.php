<?php
/**
 * Telegram provider configuration template
 * @var \AuthPress\Providers\Abstract_Provider $provider
 * @var array $providers Legacy provider settings
 */

$telegram_settings = $providers['telegram'] ?? [];
?>

<div class="ap-form">
    <div class="ap-form__group">
        <label class="ap-label" for="telegram_bot_token"><?php _e("Bot Token", "two-factor-login-telegram"); ?></label>
        <input id="telegram_bot_token" class="ap-input"
               name="wp_factor_providers[telegram][bot_token]"
               type="text"
               placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11"
               value="<?php echo esc_attr($telegram_settings['bot_token'] ?? ''); ?>"/>
        <?php if (!empty($telegram_settings['bot_token']) && $this->is_valid_bot_for_providers($telegram_settings['bot_token'])) { ?>
            <div class="ap-notice ap-notice--success mt-8">
                <span>✔</span>
                <span><?php _e("Bot token is valid", "two-factor-login-telegram"); ?></span>
            </div>
        <?php } elseif (!empty($telegram_settings['bot_token'])) { ?>
            <div class="ap-notice ap-notice--error mt-8">
                <span>✖</span>
                <span><?php _e("Bot token is invalid", "two-factor-login-telegram"); ?></span>
            </div>
        <?php } ?>
    </div>
    <div class="ap-form__group">
        <label class="ap-label"
               for="telegram_failed_login_reports"><?php _e("Failed Login Reports", "two-factor-login-telegram"); ?></label>
        <select id="telegram_failed_login_reports" class="ap-select__control"
                name="wp_factor_providers[telegram][failed_login_reports]">
            <option value="0" <?php selected($telegram_settings['failed_login_reports'] ?? false, false); ?>><?php _e("Disabled", "two-factor-login-telegram"); ?></option>
            <option value="1" <?php selected($telegram_settings['failed_login_reports'] ?? false, true); ?>><?php _e("Send notifications about failed login attempts", "two-factor-login-telegram"); ?></option>
        </select>
    </div>
    <?php if ($telegram_settings['failed_login_reports'] ?? false) { ?>
        <div class="ap-form__group">
            <label class="ap-label"
                   for="telegram_report_chat_id"><?php _e("Report Chat ID", "two-factor-login-telegram"); ?></label>
            <input id="telegram_report_chat_id" class="ap-input"
                   name="wp_factor_providers[telegram][report_chat_id]"
                   type="text"
                   placeholder="<?php _e("Enter your Telegram Chat ID", "two-factor-login-telegram"); ?>"
                   value="<?php echo esc_attr($telegram_settings['report_chat_id'] ?? ''); ?>"/>
        </div>
    <?php } ?>
</div>

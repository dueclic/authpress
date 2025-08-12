<?php
/**
 * Telegram provider configuration template
 * @var \AuthPress\Providers\Abstract_Provider $provider
 * @var array $providers Legacy provider settings
 */

$telegram_settings = $providers['telegram'] ?? [];
?>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="telegram_bot_token"><?php _e("Bot Token", "two-factor-login-telegram"); ?></label>
        </th>
        <td>
            <input type="text" id="telegram_bot_token"
                   name="wp_factor_providers[telegram][bot_token]"
                   value="<?php echo esc_attr($telegram_settings['bot_token'] ?? ''); ?>"
                   class="regular-text" />
            <p class="description">
                <?php _e("Enter your Telegram Bot Token. Required for Telegram 2FA to work.", "two-factor-login-telegram"); ?>
            </p>
            <?php if (!empty($telegram_settings['bot_token']) && $this->is_valid_bot_for_providers($telegram_settings['bot_token'])) { ?>
                <p class="description success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e("Bot token is valid", "two-factor-login-telegram"); ?>
                </p>
            <?php } elseif (!empty($telegram_settings['bot_token'])) { ?>
                <p class="description error">
                    <span class="dashicons dashicons-no-alt"></span>
                    <?php _e("Bot token is invalid", "two-factor-login-telegram"); ?>
                </p>
            <?php } ?>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="telegram_failed_login_reports"><?php _e("Failed Login Reports", "two-factor-login-telegram"); ?></label>
        </th>
        <td>
            <label>
                <input type="checkbox" id="telegram_failed_login_reports"
                       name="wp_factor_providers[telegram][failed_login_reports]"
                       value="1" <?php checked($telegram_settings['failed_login_reports'] ?? false, true); ?> />
                <?php _e("Send notifications about failed login attempts", "two-factor-login-telegram"); ?>
            </label>
            <p class="description">
                <?php _e("When enabled, you'll receive Telegram notifications about failed login attempts.", "two-factor-login-telegram"); ?>
            </p>
        </td>
    </tr>

    <?php if ($telegram_settings['failed_login_reports'] ?? false) { ?>
        <tr>
            <th scope="row">
                <label for="telegram_report_chat_id"><?php _e("Report Chat ID", "two-factor-login-telegram"); ?></label>
            </th>
            <td>
                <input type="text" id="telegram_report_chat_id"
                       name="wp_factor_providers[telegram][report_chat_id]"
                       value="<?php echo esc_attr($telegram_settings['report_chat_id'] ?? ''); ?>"
                       class="regular-text" />
                <p class="description">
                    <?php _e("Enter your Telegram Chat ID to receive failed login reports.", "two-factor-login-telegram"); ?>
                </p>
            </td>
        </tr>
    <?php } ?>
</table>
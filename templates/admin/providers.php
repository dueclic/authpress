<?php
/**
 * Providers tab view
 * @var array $providers
 */
?>

<h2><?php _e("2FA Methods Configuration", "two-factor-login-telegram"); ?></h2>

<div class="providers-category">
    <h3><?php _e("TOTP Providers", "two-factor-login-telegram"); ?></h3>
    <p class="providers-description"><?php _e("Time-based One-Time Password providers that send codes directly to you.", "two-factor-login-telegram"); ?></p>

<form method="post" action="options.php">
    <?php settings_fields('wp_factor_providers'); ?>

    <div class="providers-container">

        <!-- Telegram Provider -->
        <div class="provider-card <?php echo $providers['telegram']['enabled'] ? 'enabled' : 'disabled'; ?>">
            <div class="provider-header">
                <div class="provider-icon">
                    <span class="dashicons dashicons-format-chat"></span>
                </div>
                <div class="provider-info">
                    <h3><?php _e("Telegram", "two-factor-login-telegram"); ?></h3>
                    <p><?php _e("Receive authentication codes via Telegram messages", "two-factor-login-telegram"); ?></p>
                </div>
                <div class="provider-toggle">
                    <label class="switch">
                        <input type="checkbox" name="wp_factor_providers[telegram][enabled]"
                               value="1" <?php checked($providers['telegram']['enabled'], true); ?>>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>

            <div class="provider-content">
                <div class="provider-config">
                    <h4><?php _e("Configuration:", "two-factor-login-telegram"); ?></h4>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="telegram_bot_token"><?php _e("Bot Token", "two-factor-login-telegram"); ?></label>
                            </th>
                            <td>
                                <input type="text" id="telegram_bot_token"
                                       name="wp_factor_providers[telegram][bot_token]"
                                       value="<?php echo esc_attr($providers['telegram']['bot_token']); ?>"
                                       class="regular-text" />
                                <p class="description">
                                    <?php _e("Enter your Telegram Bot Token. Required for Telegram 2FA to work.", "two-factor-login-telegram"); ?>
                                </p>
                                <?php if (!empty($providers['telegram']['bot_token']) && $this->is_valid_bot_for_providers($providers['telegram']['bot_token'])) { ?>
                                    <p class="description success">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php _e("Bot token is valid", "two-factor-login-telegram"); ?>
                                    </p>
                                <?php } elseif (!empty($providers['telegram']['bot_token'])) { ?>
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
                                           value="1" <?php checked($providers['telegram']['failed_login_reports'], true); ?> />
                                    <?php _e("Send notifications about failed login attempts", "two-factor-login-telegram"); ?>
                                </label>
                                <p class="description">
                                    <?php _e("When enabled, you'll receive Telegram notifications about failed login attempts.", "two-factor-login-telegram"); ?>
                                </p>
                            </td>
                        </tr>

                        <?php if ($providers['telegram']['failed_login_reports']) { ?>
                            <tr>
                                <th scope="row">
                                    <label for="telegram_report_chat_id"><?php _e("Report Chat ID", "two-factor-login-telegram"); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="telegram_report_chat_id"
                                           name="wp_factor_providers[telegram][report_chat_id]"
                                           value="<?php echo esc_attr($providers['telegram']['report_chat_id'] ?? ''); ?>"
                                           class="regular-text" />
                                    <p class="description">
                                        <?php _e("Enter your Telegram Chat ID to receive failed login reports.", "two-factor-login-telegram"); ?>
                                    </p>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>

                <div class="provider-features">
                    <h4><?php _e("Features:", "two-factor-login-telegram"); ?></h4>
                    <ul>
                        <li>✅ <?php _e("Automatic code delivery", "two-factor-login-telegram"); ?></li>
                        <li>✅ <?php _e("Works on any device", "two-factor-login-telegram"); ?></li>
                        <li>✅ <?php _e("Real-time notifications", "two-factor-login-telegram"); ?></li>
                        <li>✅ <?php _e("Failed login reports", "two-factor-login-telegram"); ?></li>
                    </ul>
                </div>

                <?php if ($providers['telegram']['enabled']) { ?>
                    <div class="provider-status enabled">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e("Telegram provider is active", "two-factor-login-telegram"); ?>
                    </div>
                <?php } else { ?>
                    <div class="provider-status disabled">
                        <span class="dashicons dashicons-no-alt"></span>
                        <?php _e("Telegram provider is disabled", "two-factor-login-telegram"); ?>
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- Email Provider -->
        <div class="provider-card <?php echo $providers['email']['enabled'] ? 'enabled' : 'disabled'; ?>">
            <div class="provider-header">
                <div class="provider-icon">
                    <span class="dashicons dashicons-email"></span>
                </div>
                <div class="provider-info">
                    <h3><?php _e("Email", "two-factor-login-telegram"); ?></h3>
                    <p><?php _e("Receive authentication codes via email messages", "two-factor-login-telegram"); ?></p>
                </div>
                <div class="provider-toggle">
                    <label class="switch">
                        <input type="checkbox" name="wp_factor_providers[email][enabled]"
                               value="1" <?php checked($providers['email']['enabled'], true); ?>>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>

            <div class="provider-content">
                <div class="provider-features">
                    <h4><?php _e("Features:", "two-factor-login-telegram"); ?></h4>
                    <ul>
                        <li>✅ <?php _e("Universal compatibility", "two-factor-login-telegram"); ?></li>
                        <li>✅ <?php _e("Works with any email client", "two-factor-login-telegram"); ?></li>
                        <li>✅ <?php _e("No additional apps required", "two-factor-login-telegram"); ?></li>
                        <li>✅ <?php _e("Automatic availability for all users", "two-factor-login-telegram"); ?></li>
                    </ul>
                </div>

                <?php if ($providers['email']['enabled']) { ?>
                    <div class="provider-status enabled">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e("Email provider is active", "two-factor-login-telegram"); ?>
                    </div>
                <?php } else { ?>
                    <div class="provider-status disabled">
                        <span class="dashicons dashicons-no-alt"></span>
                        <?php _e("Email provider is disabled", "two-factor-login-telegram"); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="providers-category">
        <h3><?php _e("Authenticator Apps", "two-factor-login-telegram"); ?></h3>
        <p class="providers-description"><?php _e("Time-based One-Time Password apps that generate codes offline.", "two-factor-login-telegram"); ?></p>

        <!-- Authenticator Provider -->
        <div class="provider-card <?php echo $providers['authenticator']['enabled'] ? 'enabled' : 'disabled'; ?>">
            <div class="provider-header">
                <div class="provider-icon">
                    <span class="dashicons dashicons-smartphone"></span>
                </div>
                <div class="provider-info">
                    <h3><?php _e("Authenticator App", "two-factor-login-telegram"); ?></h3>
                    <p><?php _e("Google Authenticator, Authy, Microsoft Authenticator and other TOTP-compatible apps", "two-factor-login-telegram"); ?></p>
                </div>
                <div class="provider-toggle">
                    <label class="switch">
                        <input type="checkbox" name="wp_factor_providers[authenticator][enabled]"
                               value="1" <?php checked($providers['authenticator']['enabled'], true); ?>>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>

            <div class="provider-content">
                <div class="provider-features">
                    <h4><?php _e("Features:", "two-factor-login-telegram"); ?></h4>
                    <ul>
                        <li>✅ <?php _e("Works offline", "two-factor-login-telegram"); ?></li>
                        <li>✅ <?php _e("Industry standard TOTP", "two-factor-login-telegram"); ?></li>
                        <li>✅ <?php _e("No internet required", "two-factor-login-telegram"); ?></li>
                        <li>✅ <?php _e("Compatible with all major apps", "two-factor-login-telegram"); ?></li>
                    </ul>
                </div>

                <?php if ($providers['authenticator']['enabled']) { ?>
                    <div class="provider-status enabled">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e("Authenticator provider is active", "two-factor-login-telegram"); ?>
                    </div>
                <?php } else { ?>
                    <div class="provider-status disabled">
                        <span class="dashicons dashicons-no-alt"></span>
                        <?php _e("Authenticator provider is disabled", "two-factor-login-telegram"); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="default-provider-section">
        <h3><?php _e("Default Provider Settings", "two-factor-login-telegram"); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="default_provider"><?php _e("Default 2FA Method", "two-factor-login-telegram"); ?></label>
                </th>
                <td>
                    <select name="wp_factor_providers[default_provider]" id="default_provider">
                        <option value="telegram" <?php selected($providers['default_provider'] ?? 'telegram', 'telegram'); ?>>
                            <?php _e("Telegram", "two-factor-login-telegram"); ?>
                        </option>
                        <option value="email" <?php selected($providers['default_provider'] ?? 'telegram', 'email'); ?>>
                            <?php _e("Email", "two-factor-login-telegram"); ?>
                        </option>
                        <option value="authenticator" <?php selected($providers['default_provider'] ?? 'telegram', 'authenticator'); ?>>
                            <?php _e("Authenticator App", "two-factor-login-telegram"); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e("Choose which 2FA method will be selected by default during login. Users can still switch between available methods.", "two-factor-login-telegram"); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e('Save Providers Configuration', 'two-factor-login-telegram'); ?>" />
    </p>
</form>

<div class="providers-info">
    <h3><?php _e("About 2FA Providers", "two-factor-login-telegram"); ?></h3>
    <p>
        <?php _e("You can enable one or both providers. Users will be able to choose which method to use for their 2FA setup.", "two-factor-login-telegram"); ?>
    </p>
    <p>
        <strong><?php _e("Recommendation:", "two-factor-login-telegram"); ?></strong>
        <?php _e("Enable both providers to give users flexibility and backup options.", "two-factor-login-telegram"); ?>
    </p>
</div>
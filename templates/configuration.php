<?php
if (isset($_GET['tab'])) {
    $active_tab = sanitize_text_field($_GET['tab']);
} else {
    $active_tab = 'providers';
}

// Get current provider settings
$providers = authpress_providers();
?>

<div id="wft-wrap" class="wrap">

    <div class="heading-top">
        <div class="cover-tg-plugin">
        </div>
        <h1><?php _e("AuthPress", "two-factor-login-telegram"); ?> - <?php _e("Configuration", "two-factor-login-telegram"); ?></h1>
    </div>

    <h2 class="wpft-tab-wrapper nav-tab-wrapper">
        <a href="<?php echo admin_url('options-general.php?page=tg-conf&tab=providers'); ?>"
           class="nav-tab <?php echo $active_tab == 'providers' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-plugins"></span> <?php _e("Providers", "two-factor-login-telegram"); ?>
        </a>
        <a href="<?php echo admin_url('options-general.php?page=tg-conf&tab=howto'); ?>"
           class="nav-tab <?php echo $active_tab == 'howto' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-editor-help"></span> <?php _e("FAQ", "two-factor-login-telegram"); ?>
        </a>

        <?php if ($this->is_valid_bot() && $providers['telegram']['enabled']) { ?>
            <a href="<?php echo admin_url('options-general.php?page=tg-conf&tab=logs'); ?>"
               class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-list-view"></span> <?php _e("Bot Logs", "two-factor-login-telegram"); ?>
            </a>
        <?php } ?>

        <?php if ($this->is_valid_bot() && $providers['telegram']['enabled'] && get_the_author_meta("tg_wp_factor_chat_id", get_current_user_id()) !== false) { ?>
            <a href="<?php echo admin_url('options-general.php?page=tg-conf&tab=suggestions'); ?>"
               class="nav-tab <?php echo $active_tab == 'suggestions' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-heart"></span> <?php _e("Suggestions", "two-factor-login-telegram"); ?>
            </a>
        <?php } ?>
    </h2>

    <div class="wpft-container">

        <?php if ($active_tab == "providers") { ?>

            <h2><?php _e("2FA Providers Configuration", "two-factor-login-telegram"); ?></h2>

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

        <?php } else if ($active_tab == "logs") {
            global $wpdb;

            $activities_table = $wpdb->prefix . 'wp2fat_activities';

            // Create an instance of our package class
            $logs_list_table = new Telegram_Logs_List_Table();

            // Handle clear logs action
            if (isset($_POST['clear_logs']) && wp_verify_nonce($_POST['_wpnonce'], 'clear_telegram_logs')) {
                $wpdb->query("DELETE FROM $activities_table");
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Logs cleared successfully.', 'two-factor-login-telegram') . '</p></div>';
            }

            // Process bulk actions
            $logs_list_table->process_bulk_action();

            // Prepare the table
            $logs_list_table->prepare_items();
            ?>

            <h2><?php _e("Bot Logs", "two-factor-login-telegram"); ?></h2>

            <form method="post">
                <?php wp_nonce_field('clear_telegram_logs'); ?>
                <input type="submit" name="clear_logs" class="tg-action-button" value="<?php _e('Clear Logs', 'two-factor-login-telegram'); ?>" onclick="return confirm('<?php _e('Are you sure you want to clear all logs?', 'two-factor-login-telegram'); ?>')">
            </form>

            <br>

            <form method="get">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
                <input type="hidden" name="tab" value="logs" />
                <?php $logs_list_table->display(); ?>
            </form>

        <?php } else if ($active_tab == "howto") { ?>

            <h2><?php _e("FAQ", "two-factor-login-telegram"); ?></h2>

            <div id="wpft-howto">
                <!-- Bot Token Section -->
                <h3 id="first"><?php _e("Bot token", "two-factor-login-telegram"); ?></h3>
                <div class="faq-content">
                    <p>
                        <?php _e('If you want to enable <strong>Telegram provider</strong> you need to provide a valid token for a Telegram Bot.',
                            "two-factor-login-telegram"); ?>
                    </p>
                    <p>
                        <?php _e('Have you ever created a bot in Telegram? It\'s so easy!',
                            "two-factor-login-telegram"); ?>
                    </p>

                    <ol>
                        <li>
                            <strong><?php _e('Open Telegram', 'two-factor-login-telegram'); ?></strong><br>
                            <?php
                            printf(__('Start a conversation with %s',
                                "two-factor-login-telegram"),
                                '<a href="https://telegram.me/botfather" target="_blank" class="external-link">@BotFather</a>');
                            ?>
                        </li>

                        <li>
                            <strong><?php _e('Create new bot', 'two-factor-login-telegram'); ?></strong><br>
                            <?php
                            printf(__('Type command %s to create a new bot',
                                "two-factor-login-telegram"), '<code class="command">/newbot</code>');
                            ?>
                        </li>

                        <li>
                            <strong><?php _e('Configure bot', 'two-factor-login-telegram'); ?></strong><br>
                            <?php _e('Provide username and name for the new bot.',
                                'two-factor-login-telegram'); ?>
                        </li>

                        <li>
                            <strong><?php _e('Get your Bot Token', 'two-factor-login-telegram'); ?></strong><br>
                            <?php _e('In the answer will be your <strong>Bot Token</strong>',
                                'two-factor-login-telegram'); ?>

                            <div class="screenshot-container">
                                <img class="help-screenshot"
                                     src="<?php echo plugins_url("/assets/img/help-api-token.png", WP_FACTOR_TG_FILE); ?>"
                                     alt="<?php _e('Bot token example', 'two-factor-login-telegram'); ?>">
                            </div>
                        </li>
                    </ol>
                </div>

                <!-- Chat ID Section -->
                <h3><?php _e("Get Chat ID for Telegram user", "two-factor-login-telegram"); ?></h3>
                <div class="faq-content">
                    <p>
                        <?php _e("Chat ID identifies your user profile in Telegram.",
                            "two-factor-login-telegram"); ?>
                    </p>
                    <p>
                        <?php _e("You have no idea what is your Chat ID? Follow these simple steps:",
                            "two-factor-login-telegram"); ?>
                    </p>

                    <ol>
                        <?php
                        $bot_username = null;
                        if ($this->is_valid_bot()) {
                            $me = $this->telegram->get_me();
                            if ($me && isset($me->username)) {
                                $bot_username = $me->username;
                            }
                        }

                        if ($bot_username): ?>
                            <li>
                                <strong><?php _e('Contact your bot', 'two-factor-login-telegram'); ?></strong><br>
                                <?php
                                printf(__('Open Telegram and start a conversation with your configured bot %s and press on <strong>Start</strong>',
                                    "two-factor-login-telegram"),
                                    '<a href="https://telegram.me/' . $bot_username . '" target="_blank" class="external-link">@' . $bot_username . '</a>');
                                ?>
                            </li>
                            <li>
                                <strong><?php _e('Get your ID', 'two-factor-login-telegram'); ?></strong><br>
                                <?php
                                printf(__('Type command %s to obtain your Chat ID.',
                                    "two-factor-login-telegram"), '<code class="command">/get_id</code>');
                                ?>
                            </li>
                            <li>
                                <strong><?php _e('Copy the ID', 'two-factor-login-telegram'); ?></strong><br>
                                <?php _e("The bot will reply with your <strong>Chat ID</strong> number",
                                    'two-factor-login-telegram'); ?>
                            </li>
                        <?php else: ?>
                            <li>
                                <strong><?php _e('Configure bot first', 'two-factor-login-telegram'); ?></strong><br>
                                <?php _e('First configure your bot token in the Providers tab, then return here for specific instructions.',
                                    "two-factor-login-telegram"); ?>
                            </li>
                            <li>
                                <strong><?php _e('Alternative method', 'two-factor-login-telegram'); ?></strong><br>
                                <?php _e('Alternatively, you can use a generic bot like',
                                    "two-factor-login-telegram"); ?>
                                <?php
                                printf(__(' %s and type %s to get your Chat ID.',
                                    "two-factor-login-telegram"),
                                    '<a href="https://telegram.me/myidbot" target="_blank" class="external-link">@MyIDBot</a>',
                                    '<code class="command">/getid</code>');
                                ?>
                            </li>
                        <?php endif; ?>
                    </ol>
                </div>

                <!-- Activation Section -->
                <h3><?php _e("Activation of service", "two-factor-login-telegram"); ?></h3>
                <div class="faq-content">
                    <p>
                        <?php _e('Open a conversation with the created bot that you provided for the plugin and push <strong>Start</strong>',
                            'two-factor-login-telegram'); ?>.
                    </p>
                    <div class="notice notice-info">
                        <p>
                            <?php _e('This step is crucial for the bot to be able to send you messages!', 'two-factor-login-telegram'); ?>
                        </p>
                    </div>
                </div>

                <!-- Authenticator App Section -->
                <h3><?php _e("Authenticator App (Alternative to Telegram)", "two-factor-login-telegram"); ?></h3>
                <div class="faq-content">
                    <p>
                        <?php _e('<strong>AuthPress</strong> supports authenticator apps as an alternative or additional 2FA method. You can use apps like:', "two-factor-login-telegram"); ?>
                    </p>
                    <ul>
                        <li><?php _e('Google Authenticator', 'two-factor-login-telegram'); ?></li>
                        <li><?php _e('Microsoft Authenticator', 'two-factor-login-telegram'); ?></li>
                        <li><?php _e('Authy', 'two-factor-login-telegram'); ?></li>
                        <li><?php _e('Any TOTP-compatible app', 'two-factor-login-telegram'); ?></li>
                    </ul>

                    <h4><?php _e('How to set up:', 'two-factor-login-telegram'); ?></h4>
                    <ol>
                        <li><?php _e('Go to your <strong>User Profile</strong> page', 'two-factor-login-telegram'); ?></li>
                        <li><?php _e('Enable 2FA and choose <strong>"Setup Authenticator App"</strong>', 'two-factor-login-telegram'); ?></li>
                        <li><?php _e('Scan the QR code with your authenticator app', 'two-factor-login-telegram'); ?></li>
                        <li><?php _e('Enter the 6-digit code to verify and activate', 'two-factor-login-telegram'); ?></li>
                    </ol>

                    <div class="notice notice-success">
                        <p>
                            <strong><?php _e('Pro tip:', 'two-factor-login-telegram'); ?></strong>
                            <?php _e('You can use both Telegram and Authenticator app simultaneously for maximum security!', 'two-factor-login-telegram'); ?>
                        </p>
                    </div>
                </div>

                <!-- 2FA Methods Comparison -->
                <h3><?php _e("2FA Methods Comparison", "two-factor-login-telegram"); ?></h3>
                <div class="faq-content">
                    <table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
                        <thead>
                            <tr>
                                <th><?php _e('Feature', 'two-factor-login-telegram'); ?></th>
                                <th><?php _e('Telegram', 'two-factor-login-telegram'); ?></th>
                                <th><?php _e('Authenticator App', 'two-factor-login-telegram'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong><?php _e('Internet Required', 'two-factor-login-telegram'); ?></strong></td>
                                <td>✅ <?php _e('Yes', 'two-factor-login-telegram'); ?></td>
                                <td>❌ <?php _e('No (offline)', 'two-factor-login-telegram'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Automatic Code Delivery', 'two-factor-login-telegram'); ?></strong></td>
                                <td>✅ <?php _e('Yes', 'two-factor-login-telegram'); ?></td>
                                <td>❌ <?php _e('Manual', 'two-factor-login-telegram'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Device Independence', 'two-factor-login-telegram'); ?></strong></td>
                                <td>✅ <?php _e('Any device', 'two-factor-login-telegram'); ?></td>
                                <td>📱 <?php _e('Specific device', 'two-factor-login-telegram'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Security Standard', 'two-factor-login-telegram'); ?></strong></td>
                                <td>🔒 <?php _e('High', 'two-factor-login-telegram'); ?></td>
                                <td>🔐 <?php _e('Industry Standard', 'two-factor-login-telegram'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php } else if ($active_tab == 'suggestions') { ?>
            <h2><?php _e("Suggestions", "two-factor-login-telegram"); ?></h2>

            <div id="wpft-suggestions">
                <p>
                    <?php _e("We would love to hear your feedback and suggestions! You can share them with us in three ways:", "two-factor-login-telegram"); ?>
                </p>
                <ol>
                    <li>
                        <?php _e('Send us an email at <a href="mailto:info@dueclic.com">info@dueclic.com</a>.',
                            "two-factor-login-telegram"); ?>
                    </li>
                    <li>
                        <?php
                        printf(__('Visit the <a href="%s" target="_blank">support section on WordPress.org</a>.',
                            "two-factor-login-telegram"),
                            'https://wordpress.org/support/plugin/two-factor-login-telegram/');
                        ?>
                    </li>
                    <li>
                        <?php
                        printf(__('Submit your issues or ideas on our <a href="%s" target="_blank">GitHub project page</a>.',
                            "two-factor-login-telegram"),
                            'https://github.com/debba/wp-two-factor-authentication-with-telegram/issues');
                        ?>
                    </li>
                </ol>
            </div>
        <?php } ?>

    </div>

</div>

<style>
.providers-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.provider-card {
    border: 2px solid #e1e1e1;
    border-radius: 8px;
    padding: 20px;
    background: #fff;
    transition: all 0.3s ease;
}

.provider-card.enabled {
    border-color: #46b450;
    box-shadow: 0 2px 8px rgba(70, 180, 80, 0.1);
}

.provider-card.disabled {
    border-color: #e1e1e1;
    opacity: 0.8;
}

.provider-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.provider-icon {
    font-size: 2em;
    margin-right: 15px;
    color: #0073aa;
}

.provider-info {
    flex: 1;
}

.provider-info h3 {
    margin: 0 0 5px 0;
    color: #23282d;
}

.provider-info p {
    margin: 0;
    color: #666;
    font-size: 0.9em;
}

.provider-toggle {
    margin-left: 15px;
}

.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
}

input:checked + .slider {
    background-color: #46b450;
}

input:focus + .slider {
    box-shadow: 0 0 1px #46b450;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.slider.round {
    border-radius: 34px;
}

.slider.round:before {
    border-radius: 50%;
}

.provider-content {
    margin-top: 15px;
}

.provider-features {
    margin-bottom: 15px;
}

.provider-features h4 {
    margin: 0 0 10px 0;
    color: #23282d;
}

.provider-features ul {
    margin: 0;
    padding-left: 20px;
}

.provider-features li {
    margin-bottom: 5px;
    color: #666;
}

.provider-status {
    padding: 10px;
    border-radius: 4px;
    font-weight: 500;
}

.provider-status.enabled {
    background: #dff0d8;
    color: #3c763d;
    border: 1px solid #d6e9c6;
}

.provider-status.disabled {
    background: #f2dede;
    color: #a94442;
    border: 1px solid #ebccd1;
}

.provider-config {
    margin-bottom: 20px;
}

.provider-config h4 {
    margin: 0 0 15px 0;
    color: #23282d;
}

.description.success {
    color: #46b450;
}

.description.error {
    color: #dc3232;
}

.providers-info {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    margin-top: 30px;
}

.providers-info h3 {
    margin: 0 0 15px 0;
    color: #23282d;
}

.providers-info p {
    margin: 0 0 10px 0;
    color: #666;
}

.default-provider-section {
    background: #f0f6fc;
    border: 1px solid #c7d2fe;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.default-provider-section h3 {
    margin: 0 0 20px 0;
    color: #1e40af;
    display: flex;
    align-items: center;
}

.default-provider-section h3:before {
    content: "⚙️";
    margin-right: 8px;
    font-size: 1.2em;
}

.default-provider-section .form-table th {
    padding: 15px 10px 15px 0;
    color: #1f2937;
    font-weight: 600;
}

.default-provider-section select {
    min-width: 200px;
    padding: 6px 12px;
    border-radius: 4px;
    border: 1px solid #d1d5db;
}

.default-provider-section .description {
    font-style: italic;
    color: #6b7280;
    margin-top: 8px;
}

@media (max-width: 768px) {
    .providers-container {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const telegramToggle = document.querySelector('input[name="wp_factor_providers[telegram][enabled]"]');
    const authenticatorToggle = document.querySelector('input[name="wp_factor_providers[authenticator][enabled]"]');
    const defaultProviderSection = document.querySelector('.default-provider-section');
    const defaultProviderSelect = document.querySelector('#default_provider');

    function updateDefaultProviderVisibility() {
        const telegramEnabled = telegramToggle.checked;
        const authenticatorEnabled = authenticatorToggle.checked;

        if (telegramEnabled && authenticatorEnabled) {
            defaultProviderSection.style.display = 'block';
        } else {
            defaultProviderSection.style.display = 'none';

            // Set default based on which provider is enabled
            if (telegramEnabled && !authenticatorEnabled) {
                defaultProviderSelect.value = 'telegram';
            } else if (!telegramEnabled && authenticatorEnabled) {
                defaultProviderSelect.value = 'authenticator';
            }
        }
    }

    // Initial check
    updateDefaultProviderVisibility();

    // Listen for changes
    telegramToggle.addEventListener('change', updateDefaultProviderVisibility);
    authenticatorToggle.addEventListener('change', updateDefaultProviderVisibility);
});
</script>

<?php do_action("tft_copyright"); ?>

<?php
/**
 * FAQ tab view
 * @var array $providers
 */
?>

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
            if ($this->telegram->is_valid_bot()) {
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

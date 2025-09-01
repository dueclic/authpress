<?php
/**
 * FAQ tab view - Redesigned with category-based structure
 * @var array $providers
 */
?>

<div class="providers-page howto-page">
    <?php do_action('authpress_howto_page_header'); ?>

    <?php
    $page_title = apply_filters('authpress_howto_page_title', __("Setup Guide", "two-factor-login-telegram"));
    $page_subtitle = apply_filters('authpress_howto_page_subtitle', __("Complete step-by-step instructions to configure your two-factor authentication", "two-factor-login-telegram"));
    ?>

    <div class="ap-topbar">
        <div>
            <h1 class="ap-title m-0"><?php echo esc_html($page_title); ?></h1>
            <?php if ($page_subtitle): ?>
                <p class="ap-subtitle"><?php echo esc_html($page_subtitle); ?></p>
            <?php endif; ?>
        </div>
        <?php do_action('authpress_howto_page_topbar_nav'); ?>
    </div>

    <?php do_action('authpress_howto_page_notices'); ?>

    <!-- Telegram Setup Category -->
    <h3 class="ap-heading"><?php _e("Telegram Setup", "two-factor-login-telegram"); ?></h3>
    <p class="ap-text mb-16"><?php _e("Complete step-by-step guide to configure Telegram bot for 2FA authentication.", "two-factor-login-telegram"); ?></p>

    <div class="ap-grid mb-24">
        <!-- Bot Creation Card -->
        <section class="provider-card setup-card ap-col-6">
            <header class="provider-card__header">
                <div class="provider-card__title">
                    <span class="icon-circle" aria-hidden="true">🤖</span>
                    <span><?php _e("Create Telegram Bot", "two-factor-login-telegram"); ?></span>
                </div>
            </header>

            <div class="provider-card__body">
                <p class="ap-text mb-16"><?php _e('First, create a Telegram bot to handle authentication messages.', "two-factor-login-telegram"); ?></p>

                <button type="button" class="provider-card__toggle" aria-expanded="false" aria-controls="bot-creation-steps">
                    <span class="provider-card__arrow">▼</span>
                    <span><?php _e('Step-by-step Instructions', 'two-factor-login-telegram'); ?></span>
                </button>

                <div class="provider-config" id="bot-creation-steps" style="display: none;">
                    <div class="instruction-grid">
                        <div class="instruction-item">
                            <div class="instruction-icon">📱</div>
                            <div class="instruction-text">
                                <strong><?php _e('Open Telegram', 'two-factor-login-telegram'); ?></strong>
                                <p><?php printf(__('Start a conversation with %s', "two-factor-login-telegram"), 
                                    '<a href="https://telegram.me/botfather" target="_blank" class="external-link">@BotFather</a>'); ?></p>
                            </div>
                        </div>
                        
                        <div class="instruction-item">
                            <div class="instruction-icon">⚡</div>
                            <div class="instruction-text">
                                <strong><?php _e('Create new bot', 'two-factor-login-telegram'); ?></strong>
                                <p><?php printf(__('Type command %s to start bot creation', "two-factor-login-telegram"), 
                                    '<code class="command">/newbot</code>'); ?></p>
                            </div>
                        </div>
                        
                        <div class="instruction-item">
                            <div class="instruction-icon">⚙️</div>
                            <div class="instruction-text">
                                <strong><?php _e('Configure bot', 'two-factor-login-telegram'); ?></strong>
                                <p><?php _e('Follow the prompts to set a name and username for your bot', 'two-factor-login-telegram'); ?></p>
                            </div>
                        </div>
                        
                        <div class="instruction-item">
                            <div class="instruction-icon">🔑</div>
                            <div class="instruction-text">
                                <strong><?php _e('Save Bot Token', 'two-factor-login-telegram'); ?></strong>
                                <p><?php _e('Copy the Bot Token from BotFather\'s response - you\'ll need this for the plugin configuration', 'two-factor-login-telegram'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="visual-helper">
                        <div class="screenshot-container">
                            <img class="help-screenshot"
                                 src="<?php echo plugins_url("/assets/img/help-api-token.png", AUTHPRESS_PLUGIN_FILE); ?>"
                                 alt="<?php _e('Bot token example', 'two-factor-login-telegram'); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Chat ID Card -->
        <section class="provider-card setup-card ap-col-6">
            <header class="provider-card__header">
                <div class="provider-card__title">
                    <span class="icon-circle" aria-hidden="true">🆔</span>
                    <span><?php _e("Get Your Chat ID", "two-factor-login-telegram"); ?></span>
                </div>
            </header>

            <div class="provider-card__body">
                <p class="ap-text mb-16"><?php _e("Your Chat ID identifies your Telegram account to the bot.", "two-factor-login-telegram"); ?></p>

                <button type="button" class="provider-card__toggle" aria-expanded="false" aria-controls="chat-id-steps">
                    <span class="provider-card__arrow">▼</span>
                    <span><?php _e('Get Chat ID Instructions', 'two-factor-login-telegram'); ?></span>
                </button>

                <div class="provider-config" id="chat-id-steps" style="display: none;">
                    <?php
                    $bot_username = null;
                    $is_valid_bot = $this->telegram->get_me() !== false;
                    if ($is_valid_bot) {
                        $me = $this->telegram->get_me();
                        if ($me && isset($me->username)) {
                            $bot_username = $me->username;
                        }
                    }
                    ?>

                    <?php if ($bot_username): ?>
                        <div class="instruction-grid">
                            <div class="instruction-item">
                                <div class="instruction-icon">💬</div>
                                <div class="instruction-text">
                                    <strong><?php _e('Contact your bot', 'two-factor-login-telegram'); ?></strong>
                                    <p><?php printf(__('Open %s and press <strong>Start</strong>', "two-factor-login-telegram"),
                                        '<a href="https://telegram.me/' . $bot_username . '" target="_blank" class="external-link">@' . $bot_username . '</a>'); ?></p>
                                </div>
                            </div>
                            
                            <div class="instruction-item">
                                <div class="instruction-icon">📱</div>
                                <div class="instruction-text">
                                    <strong><?php _e('Get your ID', 'two-factor-login-telegram'); ?></strong>
                                    <p><?php printf(__('Type %s to get your Chat ID', "two-factor-login-telegram"), 
                                        '<code class="command">/get_id</code>'); ?></p>
                                </div>
                            </div>
                            
                            <div class="instruction-item">
                                <div class="instruction-icon">📋</div>
                                <div class="instruction-text">
                                    <strong><?php _e('Copy the ID', 'two-factor-login-telegram'); ?></strong>
                                    <p><?php _e("Copy the Chat ID number from the bot's response", 'two-factor-login-telegram'); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="notice notice-warning">
                            <p><strong><?php _e('Bot not configured yet', 'two-factor-login-telegram'); ?></strong></p>
                            <p><?php _e('Configure your bot token in the Providers tab first, then return here for personalized instructions.', "two-factor-login-telegram"); ?></p>
                        </div>
                        
                        <div class="alternative-method">
                            <h4><?php _e('Alternative method', 'two-factor-login-telegram'); ?></h4>
                            <p><?php printf(__('Use %s and type %s to get your Chat ID.', "two-factor-login-telegram"),
                                '<a href="https://telegram.me/myidbot" target="_blank" class="external-link">@MyIDBot</a>',
                                '<code class="command">/getid</code>'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Bot Activation Card -->
        <section class="provider-card setup-card ap-col-6">
            <header class="provider-card__header">
                <div class="provider-card__title">
                    <span class="icon-circle" aria-hidden="true">⚠️</span>
                    <span><?php _e("Activate Your Bot", "two-factor-login-telegram"); ?></span>
                </div>
            </header>

            <div class="provider-card__body">
                <p class="ap-text mb-16"><?php _e('Final step to enable message delivery from your bot.', 'two-factor-login-telegram'); ?></p>

                <div class="critical-step">
                    <div class="critical-content">
                        <p><?php _e('Open a conversation with your configured bot and press <strong>Start</strong>.', 'two-factor-login-telegram'); ?></p>
                        <div class="notice notice-error">
                            <p><strong><?php _e('Important:', 'two-factor-login-telegram'); ?></strong>
                            <?php _e('This step is crucial - without it, the bot cannot send you authentication codes!', 'two-factor-login-telegram'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Email Setup Category -->
    <h3 class="ap-heading"><?php _e("Email Setup", "two-factor-login-telegram"); ?></h3>
    <p class="ap-text mb-16"><?php _e("Receive authentication codes directly in your email inbox for secure 2FA.", "two-factor-login-telegram"); ?></p>

    <div class="ap-grid mb-24">
        <section class="provider-card setup-card ap-col-6">
            <header class="provider-card__header">
                <div class="provider-card__title">
                    <span class="icon-circle" aria-hidden="true">📧</span>
                    <span><?php _e("Email Configuration", "two-factor-login-telegram"); ?></span>
                </div>
            </header>

            <div class="provider-card__body">
                <p class="ap-text mb-16"><?php _e('Email authentication works automatically with your WordPress account email.', "two-factor-login-telegram"); ?></p>

                <div class="instruction-grid">
                    <div class="instruction-item">
                        <div class="instruction-icon">✅</div>
                        <div class="instruction-text">
                            <strong><?php _e('Automatic Setup', 'two-factor-login-telegram'); ?></strong>
                            <p><?php _e('No configuration required - uses your account email automatically', 'two-factor-login-telegram'); ?></p>
                        </div>
                    </div>
                    
                    <div class="instruction-item">
                        <div class="instruction-icon">⚡</div>
                        <div class="instruction-text">
                            <strong><?php _e('Fast Delivery', 'two-factor-login-telegram'); ?></strong>
                            <p><?php _e('Codes are sent instantly via WordPress mail system', 'two-factor-login-telegram'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="provider-card setup-card ap-col-6">
            <header class="provider-card__header">
                <div class="provider-card__title">
                    <span class="icon-circle" aria-hidden="true">🔧</span>
                    <span><?php _e("Email Settings", "two-factor-login-telegram"); ?></span>
                </div>
            </header>

            <div class="provider-card__body">
                <p class="ap-text mb-16"><?php _e('Optional settings to customize email authentication behavior.', "two-factor-login-telegram"); ?></p>

                <button type="button" class="provider-card__toggle" aria-expanded="false" aria-controls="email-settings-info">
                    <span class="provider-card__arrow">▼</span>
                    <span><?php _e('Configuration Options', 'two-factor-login-telegram'); ?></span>
                </button>

                <div class="provider-config" id="email-settings-info" style="display: none;">
                    <div class="instruction-grid">
                        <div class="instruction-item">
                            <div class="instruction-icon">⏱️</div>
                            <div class="instruction-text">
                                <strong><?php _e('Token Duration', 'two-factor-login-telegram'); ?></strong>
                                <p><?php _e('Configure how long email codes remain valid (default: 20 minutes)', 'two-factor-login-telegram'); ?></p>
                            </div>
                        </div>
                        
                        <div class="instruction-item">
                            <div class="instruction-icon">📬</div>
                            <div class="instruction-text">
                                <strong><?php _e('Custom Email Address', 'two-factor-login-telegram'); ?></strong>
                                <p><?php _e('Optionally set a different email address for 2FA in your profile', 'two-factor-login-telegram'); ?></p>
                            </div>
                        </div>
                        
                        <div class="instruction-item">
                            <div class="instruction-icon">🧪</div>
                            <div class="instruction-text">
                                <strong><?php _e('Test Email Function', 'two-factor-login-telegram'); ?></strong>
                                <p><?php _e('Use the test email button in Providers tab to verify mail delivery', 'two-factor-login-telegram'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Authenticator App Category -->
    <h3 class="ap-heading"><?php _e("Authenticator App Setup", "two-factor-login-telegram"); ?></h3>
    <p class="ap-text mb-16"><?php _e("Use TOTP-compatible authenticator apps as an alternative or additional 2FA method.", "two-factor-login-telegram"); ?></p>

    <div class="ap-grid mb-24">
        <section class="provider-card setup-card ap-col-6">
            <header class="provider-card__header">
                <div class="provider-card__title">
                    <span class="icon-circle" aria-hidden="true">📱</span>
                    <span><?php _e("Supported Apps", "two-factor-login-telegram"); ?></span>
                </div>
            </header>

            <div class="provider-card__body">
                <p class="ap-text mb-16"><?php _e('Any TOTP-compatible authenticator app works with AuthPress.', "two-factor-login-telegram"); ?></p>

                <div class="app-grid">
                    <div class="app-item">
                        <div class="app-icon">🔵</div>
                        <span><?php _e('Google Authenticator', 'two-factor-login-telegram'); ?></span>
                    </div>
                    <div class="app-item">
                        <div class="app-icon">🟦</div>
                        <span><?php _e('Microsoft Authenticator', 'two-factor-login-telegram'); ?></span>
                    </div>
                    <div class="app-item">
                        <div class="app-icon">🔴</div>
                        <span><?php _e('Authy', 'two-factor-login-telegram'); ?></span>
                    </div>
                    <div class="app-item">
                        <div class="app-icon">⚙️</div>
                        <span><?php _e('Any TOTP app', 'two-factor-login-telegram'); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="provider-card setup-card ap-col-6">
            <header class="provider-card__header">
                <div class="provider-card__title">
                    <span class="icon-circle" aria-hidden="true">🔧</span>
                    <span><?php _e("Setup Process", "two-factor-login-telegram"); ?></span>
                </div>
            </header>

            <div class="provider-card__body">
                <p class="ap-text mb-16"><?php _e('Quick 4-step setup process for authenticator apps.', "two-factor-login-telegram"); ?></p>

                <button type="button" class="provider-card__toggle" aria-expanded="false" aria-controls="app-setup-steps">
                    <span class="provider-card__arrow">▼</span>
                    <span><?php _e('Setup Instructions', 'two-factor-login-telegram'); ?></span>
                </button>

                <div class="provider-config" id="app-setup-steps" style="display: none;">
                    <div class="flow-steps">
                        <div class="flow-step">
                            <span class="flow-number">1</span>
                            <p><?php _e('Go to your <strong>User Profile</strong> page', 'two-factor-login-telegram'); ?></p>
                        </div>
                        <div class="flow-step">
                            <span class="flow-number">2</span>
                            <p><?php _e('Enable 2FA and choose <strong>"Setup Authenticator App"</strong>', 'two-factor-login-telegram'); ?></p>
                        </div>
                        <div class="flow-step">
                            <span class="flow-number">3</span>
                            <p><?php _e('Scan the QR code with your authenticator app', 'two-factor-login-telegram'); ?></p>
                        </div>
                        <div class="flow-step">
                            <span class="flow-number">4</span>
                            <p><?php _e('Enter the 6-digit code to verify and activate', 'two-factor-login-telegram'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Methods Comparison Category -->
    <h3 class="ap-heading"><?php _e("Methods Comparison", "two-factor-login-telegram"); ?></h3>
    <p class="ap-text mb-16"><?php _e("Compare features to decide which authentication method works best for you.", "two-factor-login-telegram"); ?></p>

    <div class="comparison-section">
        <div class="comparison-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="feature-col"><?php _e('Feature', 'two-factor-login-telegram'); ?></th>
                        <th class="method-col telegram-col"><?php _e('Telegram', 'two-factor-login-telegram'); ?></th>
                        <th class="method-col email-col"><?php _e('Email', 'two-factor-login-telegram'); ?></th>
                        <th class="method-col app-col"><?php _e('Authenticator App', 'two-factor-login-telegram'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php _e('Internet Required', 'two-factor-login-telegram'); ?></strong></td>
                        <td class="pro">✅ <?php _e('Yes', 'two-factor-login-telegram'); ?></td>
                        <td class="pro">✅ <?php _e('Yes', 'two-factor-login-telegram'); ?></td>
                        <td class="con">❌ <?php _e('No (offline)', 'two-factor-login-telegram'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Setup Complexity', 'two-factor-login-telegram'); ?></strong></td>
                        <td class="neutral">⚙️ <?php _e('Bot setup required', 'two-factor-login-telegram'); ?></td>
                        <td class="pro">✅ <?php _e('No setup needed', 'two-factor-login-telegram'); ?></td>
                        <td class="neutral">📱 <?php _e('App installation', 'two-factor-login-telegram'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Automatic Delivery', 'two-factor-login-telegram'); ?></strong></td>
                        <td class="pro">✅ <?php _e('Push notifications', 'two-factor-login-telegram'); ?></td>
                        <td class="pro">✅ <?php _e('Email notifications', 'two-factor-login-telegram'); ?></td>
                        <td class="neutral">📱 <?php _e('Manual check', 'two-factor-login-telegram'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Device Flexibility', 'two-factor-login-telegram'); ?></strong></td>
                        <td class="pro">✅ <?php _e('Any device', 'two-factor-login-telegram'); ?></td>
                        <td class="pro">✅ <?php _e('Any device', 'two-factor-login-telegram'); ?></td>
                        <td class="con">📱 <?php _e('Specific device only', 'two-factor-login-telegram'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Reliability', 'two-factor-login-telegram'); ?></strong></td>
                        <td class="neutral">🔒 <?php _e('Depends on Telegram', 'two-factor-login-telegram'); ?></td>
                        <td class="neutral">📧 <?php _e('Depends on email server', 'two-factor-login-telegram'); ?></td>
                        <td class="pro">🔐 <?php _e('Offline capable', 'two-factor-login-telegram'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Security Standard', 'two-factor-login-telegram'); ?></strong></td>
                        <td class="neutral">🔒 <?php _e('High security', 'two-factor-login-telegram'); ?></td>
                        <td class="neutral">🔒 <?php _e('Standard security', 'two-factor-login-telegram'); ?></td>
                        <td class="pro">🔐 <?php _e('Industry standard', 'two-factor-login-telegram'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="ap-grid mb-24">
            <div class="recommendation-card telegram-card ap-col-4">
                <h4><?php _e('Choose Telegram if:', 'two-factor-login-telegram'); ?></h4>
                <ul>
                    <li><?php _e('You want push notifications', 'two-factor-login-telegram'); ?></li>
                    <li><?php _e('You use multiple devices', 'two-factor-login-telegram'); ?></li>
                    <li><?php _e('You prefer instant messaging', 'two-factor-login-telegram'); ?></li>
                </ul>
            </div>
            <div class="recommendation-card email-card ap-col-4">
                <h4><?php _e('Choose Email if:', 'two-factor-login-telegram'); ?></h4>
                <ul>
                    <li><?php _e('You want zero configuration', 'two-factor-login-telegram'); ?></li>
                    <li><?php _e('You check email regularly', 'two-factor-login-telegram'); ?></li>
                    <li><?php _e('You prefer familiar methods', 'two-factor-login-telegram'); ?></li>
                </ul>
            </div>
            <div class="recommendation-card app-card ap-col-4">
                <h4><?php _e('Choose Authenticator App if:', 'two-factor-login-telegram'); ?></h4>
                <ul>
                    <li><?php _e('You need offline access', 'two-factor-login-telegram'); ?></li>
                    <li><?php _e('You want industry-standard TOTP', 'two-factor-login-telegram'); ?></li>
                    <li><?php _e('You prefer dedicated security apps', 'two-factor-login-telegram'); ?></li>
                </ul>
            </div>
        </div>

        <div class="notice notice-success">
            <p><strong><?php _e('Pro tip:', 'two-factor-login-telegram'); ?></strong>
            <?php _e('You can use multiple methods simultaneously for maximum security and redundancy!', 'two-factor-login-telegram'); ?></p>
        </div>
    </div>

    <?php do_action('authpress_howto_page_footer'); ?>
</div>

<style>
/* Setup Guide Specific Styles */
.howto-page .setup-card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.instruction-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.instruction-item {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #0073aa;
}

.instruction-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.instruction-text strong {
    display: block;
    color: #2c3e50;
    margin-bottom: 6px;
    font-size: 14px;
    font-weight: 600;
}

.instruction-text p {
    margin: 0;
    color: #666;
    line-height: 1.4;
    font-size: 13px;
}

.command {
    background: #2c3e50;
    color: #fff;
    padding: 3px 6px;
    border-radius: 3px;
    font-family: monospace;
    font-weight: normal;
    font-size: 12px;
}

.external-link {
    color: #0073aa;
    text-decoration: none;
    font-weight: 500;
}

.external-link:hover {
    text-decoration: underline;
}

.visual-helper {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    text-align: center;
    margin-top: 20px;
}

.screenshot-container {
    display: inline-block;
    max-width: 100%;
}

.help-screenshot {
    max-width: 100%;
    height: auto;
    border-radius: 6px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.critical-step {
    background: #fff3cd;
    padding: 20px;
    border-radius: 6px;
    border-left: 4px solid #ffc107;
}

.critical-content {
    flex: 1;
}

.alternative-method {
    background: #e3f2fd;
    padding: 20px;
    border-radius: 6px;
    margin-top: 20px;
}

.alternative-method h4 {
    margin: 0 0 10px 0;
    color: #1976d2;
}

.app-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    margin: 15px 0;
}

.app-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e1e1e1;
    font-size: 13px;
}

.app-icon {
    font-size: 16px;
}

.flow-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.flow-step {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 6px;
}

.flow-number {
    background: #0073aa;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 12px;
    flex-shrink: 0;
}

.flow-step p {
    margin: 0;
    line-height: 1.4;
    font-size: 13px;
}

.comparison-section {
    margin-top: 30px;
}

.comparison-table {
    margin-bottom: 30px;
}

.comparison-table table {
    border-collapse: collapse;
    width: 100%;
}

.comparison-table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    text-align: center;
    font-weight: 600;
}

.comparison-table td {
    padding: 15px;
    text-align: center;
    vertical-align: middle;
}

.feature-col {
    text-align: left !important;
    width: 40%;
}

.method-col {
    width: 30%;
}

.pro {
    background: #d4edda;
    color: #155724;
}

.con {
    background: #f8d7da;
    color: #721c24;
}

.neutral {
    background: #fff3cd;
    color: #856404;
}

.recommendation-card {
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid;
    background: #f8f9fa;
}

.telegram-card {
    background: #e3f2fd;
    border-color: #2196f3;
}

.email-card {
    background: #fff8e1;
    border-color: #ff9800;
}

.app-card {
    background: #f3e5f5;
    border-color: #9c27b0;
}

.recommendation-card h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
    font-size: 16px;
}

.recommendation-card ul {
    margin: 0;
    padding-left: 20px;
}

.recommendation-card li {
    margin-bottom: 8px;
    line-height: 1.5;
    font-size: 14px;
}

@media (max-width: 768px) {
    .instruction-grid,
    .app-grid,
    .flow-steps {
        grid-template-columns: 1fr;
    }
    
    .comparison-table {
        overflow-x: auto;
    }
    
    .ap-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.provider-card__toggle');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('aria-controls');
            const targetElement = document.getElementById(targetId);
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            const arrow = this.querySelector('.provider-card__arrow');

            if (isExpanded) {
                targetElement.style.display = 'none';
                this.setAttribute('aria-expanded', 'false');
                arrow.style.transform = 'rotate(0deg)';
            } else {
                targetElement.style.display = 'block';
                this.setAttribute('aria-expanded', 'true');
                arrow.style.transform = 'rotate(180deg)';
            }
        });
    });
});
</script>

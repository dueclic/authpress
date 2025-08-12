<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$plugin = \Authpress\AuthPress_Plugin::get_instance();

// Get centralized user configuration
$user_config = \Authpress\AuthPress_User_Manager::get_user_2fa_config($current_user_id);


// Extract simplified variables for template compatibility
$user_has_telegram = $user_config['available_methods']['telegram'];
$user_has_email = $user_config['available_methods']['email'];
$user_has_totp = $user_config['available_methods']['totp'];
$user_has_active_methods = $user_config['has_2fa'];
$telegram_available = $user_config['providers_enabled']['telegram'];
$email_available = $user_config['providers_enabled']['email'];
$authenticator_enabled = $user_config['providers_enabled']['authenticator'];
$has_providers = $telegram_available || $email_available || $authenticator_enabled;
$totp_enabled = $user_has_totp;
$telegram_chat_id = $user_config['chat_id'] ?: '';
$telegram_user_enabled = $user_has_telegram;

// Check if email is available but not necessarily enabled
$email_user_available = \Authpress\AuthPress_User_Manager::user_email_available($current_user_id);
$email_user_enabled = $user_has_email;

// Check if user has recovery codes (but don't load them for display)
$has_recovery_codes = false;
$recovery = \Authpress\AuthPress_Auth_Factory::create(\Authpress\AuthPress_Auth_Factory::METHOD_RECOVERY_CODES);
if ($recovery) {
    $has_recovery_codes = $recovery->has_recovery_codes($current_user_id);
}

// Get user's preferred default provider
$user_default_provider = get_user_meta($current_user_id, 'wp_factor_user_default_provider', true);
if (empty($user_default_provider)) {
    // Fall back to system default if user hasn't set one
    $user_default_provider = $plugin->get_default_provider();
}

wp_enqueue_script('authpress-plugin');
wp_enqueue_style('authpress-plugin');
?>

<div class="wrap">
    <h1><?php _e('My 2FA Settings', 'two-factor-login-telegram'); ?></h1>

    <?php if (!$has_providers): ?>
        <div class="notice notice-warning">
            <p><?php _e('No 2FA providers are currently enabled by the administrator.', 'two-factor-login-telegram'); ?></p>
        </div>
    <?php else: ?>

        <div class="authpress-2fa-settings">


            <?php if ($telegram_available): ?>
                <!-- Telegram Section -->
                <div class="authpress-section">
                    <h2><?php _e('Telegram', 'two-factor-login-telegram'); ?></h2>

                    <?php if ($telegram_user_enabled): ?>
                        <div class="notice notice-success inline">
                            <p>
                                <?php _e('✅ Telegram is configured and active.', 'two-factor-login-telegram'); ?>
                                <br>
                                <strong><?php _e('Chat ID:', 'two-factor-login-telegram'); ?></strong> <?php echo esc_html($telegram_chat_id); ?>
                            </p>
                        </div>

                        <div class="authpress-actions">
                            <button type="button" class="button button-primary" id="reconfigure-telegram">
                                <?php _e('Change Chat ID', 'two-factor-login-telegram'); ?>
                            </button>

                            <form method="post" action="" class="authpress-disable-form" style="display: inline-block; margin-left: 10px;">
                                <?php wp_nonce_field('wp_factor_disable_telegram', 'wp_factor_telegram_disable_nonce'); ?>
                                <input type="hidden" name="wp_factor_action" value="disable_telegram">
                                <button type="submit" class="button button-secondary" onclick="return confirm('<?php _e('Are you sure you want to disable Telegram 2FA? This will remove your Chat ID and disable Telegram authentication.', 'two-factor-login-telegram'); ?>')">
                                    <?php _e('Disable Telegram 2FA', 'two-factor-login-telegram'); ?>
                                </button>
                            </form>
                        </div>

                        <!-- Hidden reconfiguration section -->
                        <div class="authpress-reconfig" id="telegram-reconfig-section" style="display: none; margin-top: 20px;">
                            <h4><?php _e('Reconfigure Telegram', 'two-factor-login-telegram'); ?></h4>
                            <p><?php _e('Follow the steps below to change your Telegram Chat ID:', 'two-factor-login-telegram'); ?></p>

                            <div class="tg-setup-steps">
                                <ol>
                                    <li>
                                        <?php
                                        $username = $this->telegram->get_me()->username;
                                        printf(__('Open a conversation with %s and make sure it\'s still active',
                                                'two-factor-login-telegram'),
                                                '<a href="https://telegram.me/' . $username
                                                . '" target="_blank">@' . $username . '</a>');
                                        ?>
                                    </li>
                                    <li>
                                        <?php
                                        printf(__('Type command %s to get your current Chat ID.',
                                                "two-factor-login-telegram"),
                                                '<code>/get_id</code>');
                                        ?>
                                    </li>
                                    <li><?php _e('Copy the new Chat ID and paste it below', 'two-factor-login-telegram'); ?></li>
                                </ol>
                            </div>

                            <table class="form-table">
                                <tr>
                                    <th>
                                        <label for="tg_wp_factor_chat_id_reconfig"><?php _e('New Telegram Chat ID', 'two-factor-login-telegram'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="tg_wp_factor_chat_id_reconfig" id="tg_wp_factor_chat_id_reconfig"
                                               value="" class="regular-text" placeholder="<?php echo esc_attr($telegram_chat_id); ?>"/>
                                        <p class="description"><?php _e('Enter your new Telegram Chat ID', 'two-factor-login-telegram'); ?></p>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-primary tg-action-button" id="tg_wp_factor_reconfig_send">
                                            <?php _e("Send Test Code", "two-factor-login-telegram"); ?>
                                        </button>
                                        <div id="reconfig-status" class="tg-status" style="display: none;"></div>
                                    </td>
                                </tr>

                                <tr id="factor-reconfig-confirm" style="display: none;">
                                    <th>
                                        <label for="tg_wp_factor_reconfig_confirm"><?php _e('Confirmation code', 'two-factor-login-telegram'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="tg_wp_factor_reconfig_confirm" id="tg_wp_factor_reconfig_confirm"
                                               value="" class="regular-text"/>
                                        <p class="description"><?php _e('Enter the confirmation code you received on Telegram', 'two-factor-login-telegram'); ?></p>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-primary tg-action-button" id="tg_wp_factor_reconfig_validate">
                                            <?php _e("Validate & Save", "two-factor-login-telegram"); ?>
                                        </button>
                                        <div id="reconfig-validation-status" class="tg-status" style="display: none;"></div>
                                    </td>
                                </tr>
                            </table>

                            <p>
                                <button type="button" class="button button-secondary" id="cancel-reconfigure">
                                    <?php _e('Cancel', 'two-factor-login-telegram'); ?>
                                </button>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="notice notice-info inline">
                            <p><?php _e('Configure Telegram to enable this 2FA method.', 'two-factor-login-telegram'); ?></p>
                        </div>

                        <div class="authpress-setup">
                            <h3><?php _e('Setup Telegram', 'two-factor-login-telegram'); ?></h3>

                            <?php
                            $username = $this->telegram->get_me()->username;
                            ?>

                            <div class="tg-setup-steps">
                                <h4><?php _e('🚀 Setup Steps', 'two-factor-login-telegram'); ?></h4>
                                <ol>
                                    <li>
                                        <?php
                                        printf(__('Open a conversation with %s and press on <strong>Start</strong>',
                                                'two-factor-login-telegram'),
                                                '<a href="https://telegram.me/' . $username
                                                . '" target="_blank">@' . $username . '</a>');
                                        ?>
                                    </li>
                                    <li>
                                        <?php
                                        printf(__('Type command %s to obtain your Chat ID.',
                                                "two-factor-login-telegram"),
                                                '<code>/get_id</code>');
                                        ?>
                                    </li>
                                    <li>
                                        <?php
                                        _e("The bot will reply with your <strong>Chat ID</strong> number",
                                                'two-factor-login-telegram');
                                        ?>
                                    </li>
                                    <li><?php
                                        _e('Copy your Chat ID and paste it below, then press <strong>Submit code</strong>',
                                                'two-factor-login-telegram'); ?></li>
                                </ol>
                            </div>

                            <div class="tg-progress">
                                <div class="tg-progress-bar" id="tg-progress-bar"></div>
                            </div>

                            <div class="authpress-config">
                                <table class="form-table">
                                    <tr>
                                        <th>
                                            <label for="tg_wp_factor_chat_id"><?php
                                                _e('Telegram Chat ID',
                                                        'two-factor-login-telegram'); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <input type="text" name="tg_wp_factor_chat_id"
                                                   id="tg_wp_factor_chat_id" value="<?php echo $telegram_chat_id; ?>"
                                                   class="regular-text"/><br/>
                                            <span class="description"><?php
                                                _e('Put your Telegram Chat ID',
                                                        'two-factor-login-telegram'); ?></span>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-primary tg-action-button" id="tg_wp_factor_chat_id_send"><?php
                                                _e("Submit code",
                                                        "two-factor-login-telegram"); ?></button>
                                            <div id="chat-id-status" class="tg-status" style="display: none;"></div>
                                        </td>
                                    </tr>

                                    <tr id="factor-chat-confirm" style="display: none;">
                                        <th>
                                            <label for="tg_wp_factor_chat_id_confirm"><?php
                                                _e('Confirmation code',
                                                        'two-factor-login-telegram'); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <input type="text" name="tg_wp_factor_chat_id_confirm"
                                                   id="tg_wp_factor_chat_id_confirm" value=""
                                                   class="regular-text"/><br/>
                                            <span class="description"><?php
                                                _e('Please enter the confirmation code you received on Telegram',
                                                        'two-factor-login-telegram'); ?></span>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-primary tg-action-button" id="tg_wp_factor_chat_id_check"><?php
                                                _e("Validate",
                                                        "two-factor-login-telegram"); ?></button>
                                            <div id="validation-status" class="tg-status" style="display: none;"></div>
                                        </td>
                                    </tr>
                                    <tr id="factor-chat-response" style="display: none;">
                                        <td colspan="3">
                                            <div class="wpft-notice wpft-notice-warning">
                                                <p></p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr id="factor-chat-save" style="display: none;">
                                        <td colspan="3">
                                            <form method="post" action="" class="authpress-save-form">
                                                <?php wp_nonce_field('wp_factor_save_telegram', 'wp_factor_telegram_save_nonce'); ?>
                                                <input type="hidden" name="wp_factor_action" value="save_telegram">
                                                <input type="hidden" name="tg_chat_id" id="tg_chat_id_hidden" value="">
                                                <p class="submit">
                                                    <button type="submit" class="button button-primary">
                                                        <?php _e('Save Telegram Configuration', 'two-factor-login-telegram'); ?>
                                                    </button>
                                                </p>
                                            </form>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($email_available): ?>
            <!-- Email Section -->
            <div class="authpress-section">
                <h2><?php _e('Email', 'two-factor-login-telegram'); ?></h2>

                <?php if ($email_user_available): ?>
                    <?php if ($email_user_enabled): ?>
                        <div class="notice notice-success inline">
                            <p>
                                <?php _e('✅ Email 2FA is configured and active.', 'two-factor-login-telegram'); ?>
                                <br>
                                <strong><?php _e('Email:', 'two-factor-login-telegram'); ?></strong> <?php echo esc_html(wp_get_current_user()->user_email); ?>
                            </p>
                        </div>
                        <p><?php _e('Authentication codes will be sent to your registered email address when you log in.', 'two-factor-login-telegram'); ?></p>
                        
                        <form method="post" action="" class="authpress-disable-form" style="margin-top: 15px;">
                            <?php wp_nonce_field('wp_factor_disable_email', 'wp_factor_email_disable_nonce'); ?>
                            <input type="hidden" name="wp_factor_action" value="disable_email">
                            <button type="submit" class="button button-secondary" onclick="return confirm('<?php _e('Are you sure you want to disable Email 2FA?', 'two-factor-login-telegram'); ?>')">
                                <?php _e('Disable Email 2FA', 'two-factor-login-telegram'); ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="notice notice-info inline">
                            <p>
                                <?php _e('📧 Email 2FA is available but not enabled.', 'two-factor-login-telegram'); ?>
                                <br>
                                <strong><?php _e('Email:', 'two-factor-login-telegram'); ?></strong> <?php echo esc_html(wp_get_current_user()->user_email); ?>
                            </p>
                        </div>
                        <p><?php _e('Enable email 2FA to receive authentication codes via email when you log in.', 'two-factor-login-telegram'); ?></p>
                        
                        <form method="post" action="" class="authpress-enable-form" style="margin-top: 15px;">
                            <?php wp_nonce_field('wp_factor_enable_email', 'wp_factor_email_enable_nonce'); ?>
                            <input type="hidden" name="wp_factor_action" value="enable_email">
                            <button type="submit" class="button button-primary">
                                <?php _e('Enable Email 2FA', 'two-factor-login-telegram'); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="notice notice-warning inline">
                        <p><?php _e('⚠️ Email 2FA is not available.', 'two-factor-login-telegram'); ?></p>
                    </div>
                    <p><?php _e('You need a valid email address in your profile to use email 2FA. Please update your email address in your user profile.', 'two-factor-login-telegram'); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($authenticator_enabled): ?>
            <!-- Authenticator App Section -->
            <div class="authpress-section">
                <h2><?php _e('Authenticator App', 'two-factor-login-telegram'); ?></h2>

                <?php if ($totp_enabled): ?>
                    <div class="notice notice-success inline">
                        <p><?php _e('✅ Authenticator app is configured and active.', 'two-factor-login-telegram'); ?></p>
                    </div>

                    <form method="post" action="" class="authpress-disable-form">
                        <?php wp_nonce_field('wp_factor_disable_totp', 'wp_factor_totp_disable_nonce'); ?>
                        <input type="hidden" name="wp_factor_action" value="disable_totp">
                        <p>
                            <button type="submit" class="button button-secondary" onclick="return confirm('<?php _e('Are you sure you want to disable the authenticator app?', 'two-factor-login-telegram'); ?>')">
                                <?php _e('Disable Authenticator App', 'two-factor-login-telegram'); ?>
                            </button>
                        </p>
                    </form>
                <?php else: ?>
                    <div class="notice notice-info inline">
                        <p><?php _e('Configure your authenticator app to enable this 2FA method.', 'two-factor-login-telegram'); ?></p>
                    </div>

                    <div class="authpress-totp-setup">
                        <h3><?php _e('Setup Authenticator App', 'two-factor-login-telegram'); ?></h3>

                        <div class="authpress-qr-section">
                            <div class="authpress-qr-code">
                                <img id="wp_factor_qr_code" src="" alt="QR Code" style="display:none;" />
                            </div>
                            <p>
                                <button type="button" id="wp_factor_generate_qr" class="button">
                                    <?php _e('Generate QR Code', 'two-factor-login-telegram'); ?>
                                </button>
                            </p>
                            <input type="hidden" name="wp_factor_totp_setup_nonce" value="<?php echo wp_create_nonce('setup_totp_' . $current_user_id); ?>" />
                        </div>

                        <div class="authpress-verification" id="wp_factor_verification_section" style="display:none;">
                            <h4><?php _e('Verify Setup', 'two-factor-login-telegram'); ?></h4>
                            <p><?php _e('Enter the 6-digit code from your authenticator app to complete setup:', 'two-factor-login-telegram'); ?></p>

                            <form id="wp_factor_verify_form">
                                <?php wp_nonce_field('verify_totp_' . $current_user_id, 'wp_factor_totp_nonce'); ?>
                                <p>
                                    <input type="text" id="wp_factor_totp_code" name="totp_code" maxlength="6" placeholder="000000" required />
                                    <button type="submit" class="button button-primary"><?php _e('Verify & Enable', 'two-factor-login-telegram'); ?></button>
                                </p>
                            </form>

                            <div id="wp_factor_totp_message"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($user_has_active_methods): ?>
            <!-- Default Provider Selection Section -->
            <div class="authpress-section">
                <h2><?php _e('Default 2FA Method', 'two-factor-login-telegram'); ?></h2>
                <p><?php _e('Choose which 2FA method to use by default when you log in. You can always switch to another method during login.', 'two-factor-login-telegram'); ?></p>

                <form method="post" action="" class="authpress-default-provider-form">
                    <?php wp_nonce_field('wp_factor_set_default_provider', 'wp_factor_default_provider_nonce'); ?>
                    <input type="hidden" name="wp_factor_action" value="set_default_provider">

                    <div class="authpress-provider-options">
                        <?php if ($user_has_telegram): ?>
                            <label class="authpress-provider-option">
                                <input type="radio" name="default_provider" value="telegram"
                                       <?php checked($user_default_provider, 'telegram'); ?>>
                                <span class="provider-icon">
                                    <img src="<?php echo esc_url($telegram_provider->get_icon()); ?>" alt="Telegram" style="width: 20px; height: 20px;" />
                                </span>
                                <span class="provider-name"><?php _e('Telegram', 'two-factor-login-telegram'); ?></span>
                                <span class="provider-description"><?php _e('Receive codes via Telegram', 'two-factor-login-telegram'); ?></span>
                            </label>
                        <?php endif; ?>

                        <?php if ($user_has_email): ?>
                            <label class="authpress-provider-option">
                                <input type="radio" name="default_provider" value="email"
                                       <?php checked($user_default_provider, 'email'); ?>>
                                <span class="provider-icon">
                                    <img src="<?php echo esc_url($email_provider->get_icon()); ?>" alt="Email" style="width: 20px; height: 20px;" />
                                </span>
                                <span class="provider-name"><?php _e('Email', 'two-factor-login-telegram'); ?></span>
                                <span class="provider-description"><?php _e('Receive codes via email', 'two-factor-login-telegram'); ?></span>
                            </label>
                        <?php endif; ?>

                        <?php if ($user_has_totp): ?>
                            <label class="authpress-provider-option">
                                <input type="radio" name="default_provider" value="authenticator"
                                       <?php checked($user_default_provider, 'authenticator'); ?>>
                                <span class="provider-icon">
                                    <img src="<?php echo esc_url($totp_provider->get_icon()); ?>" alt="Authenticator" style="width: 20px; height: 20px;" />
                                </span>
                                <span class="provider-name"><?php _e('Authenticator App', 'two-factor-login-telegram'); ?></span>
                                <span class="provider-description"><?php _e('Use codes from your authenticator app', 'two-factor-login-telegram'); ?></span>
                            </label>
                        <?php endif; ?>
                    </div>

                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php _e('Save Default Method', 'two-factor-login-telegram'); ?>
                        </button>
                    </p>
                </form>
            </div>
            <?php endif; ?>

            <!-- Recovery Codes Section -->
            <div class="authpress-section">
                <h2><?php _e('Recovery Codes', 'two-factor-login-telegram'); ?></h2>

                <?php if ($has_recovery_codes): ?>
                    <div class="notice notice-info inline">
                        <p><?php _e('You have recovery codes available. Keep them safe!', 'two-factor-login-telegram'); ?></p>
                    </div>

                    <div class="authpress-recovery-codes">
                        <p><?php _e('Your recovery codes are hidden for security. Regenerate them to view and save new codes.', 'two-factor-login-telegram'); ?></p>
                    </div>

                    <p>
                        <button type="button" id="regenerate_recovery_codes_btn" class="button button-primary" data-user-id="<?php echo $current_user_id; ?>" onclick="if(confirm('<?php _e('Are you sure? This will invalidate your current recovery codes and generate new ones that you must save immediately.', 'two-factor-login-telegram'); ?>')) { regenerateRecoveryCodes(); }">
                            <?php _e('Regenerate Recovery Codes', 'two-factor-login-telegram'); ?>
                        </button>
                        <input type="hidden" id="regenerate_recovery_nonce" value="<?php echo wp_create_nonce('tg_regenerate_recovery_codes_' . $current_user_id); ?>" />
                    </p>

                <?php else: ?>
                    <div class="notice notice-warning inline">
                        <p><?php _e('You don\'t have any recovery codes yet. Generate them now!', 'two-factor-login-telegram'); ?></p>
                    </div>

                    <form method="post" action="">
                        <?php wp_nonce_field('wp_factor_generate_recovery', 'wp_factor_recovery_nonce'); ?>
                        <input type="hidden" name="wp_factor_action" value="generate_recovery">
                        <p>
                            <button type="submit" class="button button-primary">
                                <?php _e('Generate Recovery Codes', 'two-factor-login-telegram'); ?>
                            </button>
                        </p>
                    </form>
                <?php endif; ?>
            </div>

        </div>

    <?php endif; ?>
</div>

<?php if ($has_providers): ?>
<style>

.authpress-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.authpress-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.authpress-qr-code {
    text-align: center;
    margin: 20px 0;
}

.authpress-qr-code img {
    border: 1px solid #ddd;
    padding: 10px;
    background: #fff;
}

.recovery-codes-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin: 15px 0;
    padding: 15px;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.recovery-codes-list code {
    display: block;
    padding: 8px;
    background: #fff;
    border: 1px solid #ddd;
    text-align: center;
    font-weight: bold;
}

@media print {
    .recovery-codes-list {
        break-inside: avoid;
    }
}

.authpress-provider-options {
    margin: 15px 0;
}

.authpress-provider-option {
    display: block;
    padding: 15px;
    margin: 10px 0;
    border: 2px solid #ddd;
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.authpress-provider-option:hover {
    border-color: #0073aa;
    background: #f9f9f9;
}

.authpress-provider-option input[type="radio"] {
    margin: 0 10px 0 0;
    vertical-align: top;
}

.authpress-provider-option input[type="radio"]:checked + .provider-icon {
    background: #0073aa;
    color: white;
}

.authpress-provider-option:has(input[type="radio"]:checked) {
    border-color: #0073aa;
    background: #f0f8ff;
}

.provider-icon {
    font-size: 18px;
    margin-right: 10px;
    padding: 5px;
    border-radius: 3px;
    background: #f0f0f0;
}

.provider-name {
    font-weight: bold;
    display: inline-block;
    margin-right: 10px;
}

.provider-description {
    color: #666;
    font-style: italic;
    display: block;
    margin-top: 5px;
    margin-left: 30px;
}

/* Telegram setup specific styles */
.tg-setup-steps {
    margin: 20px 0;
    padding: 15px;
    background-color: #007cba;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.tg-setup-steps h4 {
    margin: 0 0 10px 0;
    color: #495057;
}

.tg-setup-steps ol {
    margin: 0;
    padding-left: 20px;
}

.tg-setup-steps li {
    margin: 8px 0;
    line-height: 1.5;
}

.tg-progress {
    width: 100%;
    height: 6px;
    background-color: #e9ecef;
    border-radius: 3px;
    margin: 15px 0;
}

.tg-progress-bar {
    height: 100%;
    background-color: #007cba;
    border-radius: 3px;
    width: 0;
    transition: width 0.3s ease;
}

.authpress-config {
    margin-top: 20px;
}

.authpress-config .form-table {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.authpress-config .form-table th {
    background: #f6f7f7;
    border-bottom: 1px solid #c3c4c7;
    padding: 15px;
    font-weight: 600;
}

.authpress-config .form-table td {
    padding: 15px;
    border-bottom: 1px solid #c3c4c7;
}

.tg-status {
    margin-top: 10px;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 14px;
}

.tg-status.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.tg-status.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.tg-action-button {
    min-width: 120px;
}

.wpft-notice {
    padding: 12px;
    border-radius: 4px;
    margin: 10px 0;
}

.wpft-notice-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}
</style>
<?php endif; ?>

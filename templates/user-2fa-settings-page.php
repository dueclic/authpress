<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$plugin = WP_Factor_Telegram_Plugin::get_instance();

// Check if providers are available
$providers = authpress_providers();

function is_bot_token_valid($bot_token)
{
    if (empty($bot_token)) {
        return false;
    }

    // Check if token has the expected Telegram bot token format
    // Telegram bot tokens are typically in format: 123456789:ABCdefGHIjklMNOpqrsTUVwxyz
    return preg_match('/^\d+:[A-Za-z0-9_-]+$/', $bot_token);
}


$bot_token = $providers['telegram']['bot_token'];
$telegram_available = $providers['telegram']['enabled'] && is_bot_token_valid($bot_token);
$authenticator_enabled = $providers['authenticator']['enabled'];

$has_providers = $authenticator_enabled || $telegram_available;

// Get user's current 2FA status
$totp_enabled = false;
$telegram_user_enabled = false;
$recovery_codes = [];

if ($has_providers) {
    $totp = WP_Factor_Auth_Factory::create(WP_Factor_Auth_Factory::METHOD_TOTP);
    $telegram_otp = WP_Factor_Auth_Factory::create(WP_Factor_Auth_Factory::METHOD_TELEGRAM_OTP);
    $recovery = WP_Factor_Auth_Factory::create(WP_Factor_Auth_Factory::METHOD_RECOVERY_CODES);

    $totp_enabled = $totp->has_codes($current_user_id);
    $telegram_user_enabled = $telegram_otp->has_codes($current_user_id);

    // Get existing recovery codes
    if ($recovery) {
        $existing_codes = $recovery->get_user_recovery_codes($current_user_id);
        if (!empty($existing_codes)) {
            $recovery_codes = $existing_codes;
        }
    }
}

// Check which methods are actually available for this user
$user_has_telegram = $telegram_user_enabled && $telegram_available && $plugin->is_valid_bot();
$user_has_totp = $totp_enabled && $authenticator_enabled;
$user_has_active_methods = $user_has_telegram || $user_has_totp;

// Get user's preferred default provider
$user_default_provider = get_user_meta($current_user_id, 'wp_factor_user_default_provider', true);
if (empty($user_default_provider)) {
    // Fall back to system default if user hasn't set one
    $user_default_provider = $plugin->get_default_provider();
}

wp_enqueue_script('wp-factor-telegram-plugin');
wp_enqueue_style('wp-factor-telegram-plugin');
?>

<div class="wrap">
    <h1><?php _e('My 2FA Settings', 'two-factor-login-telegram'); ?></h1>

    <?php if (!$has_providers): ?>
        <div class="notice notice-warning">
            <p><?php _e('No 2FA providers are currently enabled by the administrator.', 'two-factor-login-telegram'); ?></p>
        </div>
    <?php else: ?>

        <div class="wp-factor-2fa-settings">

            <?php if ($authenticator_enabled): ?>
            <!-- Authenticator App Section -->
            <div class="wp-factor-section">
                <h2><?php _e('Authenticator App', 'two-factor-login-telegram'); ?></h2>

                <?php if ($totp_enabled): ?>
                    <div class="notice notice-success inline">
                        <p><?php _e('✅ Authenticator app is configured and active.', 'two-factor-login-telegram'); ?></p>
                    </div>

                    <form method="post" action="" class="wp-factor-disable-form">
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

                    <div class="wp-factor-totp-setup">
                        <h3><?php _e('Setup Authenticator App', 'two-factor-login-telegram'); ?></h3>

                        <div class="wp-factor-qr-section">
                            <div class="wp-factor-qr-code">
                                <img id="wp_factor_qr_code" src="" alt="QR Code" style="display:none;" />
                            </div>
                            <p>
                                <button type="button" id="wp_factor_generate_qr" class="button">
                                    <?php _e('Generate QR Code', 'two-factor-login-telegram'); ?>
                                </button>
                            </p>
                            <input type="hidden" name="wp_factor_totp_setup_nonce" value="<?php echo wp_create_nonce('setup_totp_' . $current_user_id); ?>" />
                        </div>

                        <div class="wp-factor-verification" id="wp_factor_verification_section" style="display:none;">
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

            <?php if ($telegram_available): ?>
            <!-- Telegram Section -->
            <div class="wp-factor-section">
                <h2><?php _e('Telegram', 'two-factor-login-telegram'); ?></h2>

                <?php if ($telegram_user_enabled): ?>
                    <div class="notice notice-success inline">
                        <p><?php _e('✅ Telegram is configured and active.', 'two-factor-login-telegram'); ?></p>
                    </div>

                    <form method="post" action="" class="wp-factor-disable-form">
                        <?php wp_nonce_field('wp_factor_disable_telegram', 'wp_factor_telegram_disable_nonce'); ?>
                        <input type="hidden" name="wp_factor_action" value="disable_telegram">
                        <p>
                            <button type="submit" class="button button-secondary" onclick="return confirm('<?php _e('Are you sure you want to disable Telegram 2FA?', 'two-factor-login-telegram'); ?>')">
                                <?php _e('Disable Telegram 2FA', 'two-factor-login-telegram'); ?>
                            </button>
                        </p>
                    </form>
                <?php else: ?>
                    <div class="notice notice-info inline">
                        <p><?php _e('Configure Telegram to enable this 2FA method.', 'two-factor-login-telegram'); ?></p>
                    </div>

                    <div class="wp-factor-telegram-setup">
                        <h3><?php _e('Setup Telegram', 'two-factor-login-telegram'); ?></h3>
                        <p><?php _e('Click the button below to start the Telegram configuration process.', 'two-factor-login-telegram'); ?></p>

                        <form method="post" action="">
                            <?php wp_nonce_field('wp_factor_setup_telegram', 'wp_factor_telegram_nonce'); ?>
                            <input type="hidden" name="wp_factor_action" value="setup_telegram">
                            <p>
                                <button type="submit" class="button button-primary">
                                    <?php _e('Setup Telegram 2FA', 'two-factor-login-telegram'); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($user_has_active_methods): ?>
            <!-- Default Provider Selection Section -->
            <div class="wp-factor-section">
                <h2><?php _e('Default 2FA Method', 'two-factor-login-telegram'); ?></h2>
                <p><?php _e('Choose which 2FA method to use by default when you log in. You can always switch to another method during login.', 'two-factor-login-telegram'); ?></p>

                <form method="post" action="" class="wp-factor-default-provider-form">
                    <?php wp_nonce_field('wp_factor_set_default_provider', 'wp_factor_default_provider_nonce'); ?>
                    <input type="hidden" name="wp_factor_action" value="set_default_provider">

                    <div class="wp-factor-provider-options">
                        <?php if ($user_has_telegram): ?>
                            <label class="wp-factor-provider-option">
                                <input type="radio" name="default_provider" value="telegram"
                                       <?php checked($user_default_provider, 'telegram'); ?>>
                                <span class="provider-icon">📱</span>
                                <span class="provider-name"><?php _e('Telegram', 'two-factor-login-telegram'); ?></span>
                                <span class="provider-description"><?php _e('Receive codes via Telegram', 'two-factor-login-telegram'); ?></span>
                            </label>
                        <?php endif; ?>

                        <?php if ($user_has_totp): ?>
                            <label class="wp-factor-provider-option">
                                <input type="radio" name="default_provider" value="authenticator"
                                       <?php checked($user_default_provider, 'authenticator'); ?>>
                                <span class="provider-icon">🔐</span>
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
            <div class="wp-factor-section">
                <h2><?php _e('Recovery Codes', 'two-factor-login-telegram'); ?></h2>

                <?php if (!empty($recovery_codes)): ?>
                    <div class="notice notice-info inline">
                        <p><?php _e('You have recovery codes available. Keep them safe!', 'two-factor-login-telegram'); ?></p>
                    </div>

                    <div class="wp-factor-recovery-codes">
                        <h3><?php _e('Your Recovery Codes', 'two-factor-login-telegram'); ?></h3>
                        <p><?php _e('Save these codes in a safe place. Each code can only be used once.', 'two-factor-login-telegram'); ?></p>

                        <div class="recovery-codes-list">
                            <?php foreach ($recovery_codes as $code): ?>
                                <code><?php echo esc_html($code); ?></code>
                            <?php endforeach; ?>
                        </div>

                        <p>
                            <button type="button" id="wp_factor_download_codes" class="button">
                                <?php _e('Download Recovery Codes', 'two-factor-login-telegram'); ?>
                            </button>
                            <button type="button" id="wp_factor_print_codes" class="button">
                                <?php _e('Print Recovery Codes', 'two-factor-login-telegram'); ?>
                            </button>
                        </p>
                    </div>

                    <form method="post" action="" style="margin-top: 20px;">
                        <?php wp_nonce_field('wp_factor_regenerate_recovery', 'wp_factor_recovery_regenerate_nonce'); ?>
                        <input type="hidden" name="wp_factor_action" value="regenerate_recovery">
                        <p>
                            <button type="submit" class="button button-secondary" onclick="return confirm('<?php _e('Are you sure? This will invalidate your current recovery codes.', 'two-factor-login-telegram'); ?>')">
                                <?php _e('Generate New Recovery Codes', 'two-factor-login-telegram'); ?>
                            </button>
                        </p>
                    </form>

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
.wp-factor-2fa-settings {
    max-width: 800px;
}

.wp-factor-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.wp-factor-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.wp-factor-qr-code {
    text-align: center;
    margin: 20px 0;
}

.wp-factor-qr-code img {
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

.wp-factor-provider-options {
    margin: 15px 0;
}

.wp-factor-provider-option {
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

.wp-factor-provider-option:hover {
    border-color: #0073aa;
    background: #f9f9f9;
}

.wp-factor-provider-option input[type="radio"] {
    margin: 0 10px 0 0;
    vertical-align: top;
}

.wp-factor-provider-option input[type="radio"]:checked + .provider-icon {
    background: #0073aa;
    color: white;
}

.wp-factor-provider-option:has(input[type="radio"]:checked) {
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
</style>
<?php endif; ?>

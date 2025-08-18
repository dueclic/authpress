<?php
if (!defined('ABSPATH')) {
    exit;
}

// Variables available from the main template:
// $key - provider key (authenticator)
// $data - provider data array
// $provider - provider instance
// $user_has_method - boolean if user has this method enabled
// $current_user_id - current user ID
?>

<div class="authpress-section">
    <h2>
        <img src="<?php echo esc_url($data['icon']); ?>" alt="Authenticator" style="width: 24px; height: 24px; margin-right: 8px; vertical-align: text-bottom;" />
        <?php _e('Authenticator App', 'two-factor-login-telegram'); ?>
    </h2>

    <?php if ($user_has_method): ?>
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
<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var $current_user_id int
 * @var $user_has_method boolean
 * @var $provider \AuthPress\Providers\Abstract_Provider
 */


?>

<div class="authpress-section">

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

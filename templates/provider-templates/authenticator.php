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

    <div class="authpress-intro">
        <?php if ($user_has_method): ?>
        <p class="ap-text"><?php _e('✅ Authenticator app is configured and active.', 'two-factor-login-telegram'); ?></p>
        <?php else: ?>
            <p class="ap-text"><?php _e('Configure your authenticator app to enable this 2FA method.', 'two-factor-login-telegram'); ?></p>
        <?php endif; ?>
    </div>

    <?php if (!$user_has_method): ?>

        <div class="authpress-totp-setup">

            <div class="authpress-qr-section">
                <div class="authpress-qr-code">
                    <img id="wp_factor_qr_code" src="" alt="QR Code" style="display:none;" />
                </div>
                <p>
                    <button type="button" id="wp_factor_generate_qr" class="ap-button ap-button--primary">
                        <?php _e('Generate QR Code', 'two-factor-login-telegram'); ?>
                    </button>
                </p>
                <input type="hidden" name="wp_factor_totp_setup_nonce" value="<?php echo wp_create_nonce('setup_totp_' . $current_user_id); ?>" />
            </div>

            <div class="authpress-verification" id="wp_factor_verification_section" style="display:none;">
                <h4 class="ap-text"><?php _e('Verify Setup', 'two-factor-login-telegram'); ?></h4>
                <p class="ap-text--small"><?php _e('Enter the 6-digit code from your authenticator app to complete setup:', 'two-factor-login-telegram'); ?></p>

                <form id="wp_factor_verify_form">
                    <?php wp_nonce_field('verify_totp_' . $current_user_id, 'wp_factor_totp_nonce'); ?>

                    <div class="field-row">
                        <div class="input-container">
                        <input type="text" id="authpress_authenticator_totp_code" class="ap-input" name="totp_code" maxlength="6" placeholder="000000" required />
                        </div>
                        <button type="submit"  class="ap-button ap-button--primary"><?php _e('Verify & Enable', 'two-factor-login-telegram'); ?></button>
                    </div>
                </form>

                <div id="wp_factor_totp_message" class="mt-8 ap-notice"></div>
            </div>
        </div>
    <?php endif; ?>
</div>

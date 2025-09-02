<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var $current_user_id int
 * @var $user_has_method boolean
 * @var $provider \AuthPress\Providers\Abstract_Provider
 */

$email_user_available = \Authpress\AuthPress_User_Manager::user_email_available($current_user_id);
$auth_email = get_user_meta($current_user_id, 'authpress_authentication_email', true);
if (empty($auth_email)) {
    $auth_email = wp_get_current_user()->user_email;
}

$pending_email = get_user_meta($current_user_id, 'authpress_pending_email', true);
?>

<div class="authpress-section">


    <p class="ap-text mb-8"><?php _e('Current Email:', 'two-factor-login-telegram'); ?> <?php echo esc_html($auth_email); ?></p>
    <p class="ap-text mb-8"><?php _e('You can specify a different email address for receiving authentication codes. If left blank, your primary WordPress email will be used.', 'two-factor-login-telegram'); ?></p>

    <?php if ($user_has_method): ?>

            <div class="authpress-actions mt-8">
                <button type="button" class="ap-button ap-button--primary" id="reconfigure-email">
                    <?php _e('Change Authentication Email', 'two-factor-login-telegram'); ?>
                </button>
                <?php
                $custom_auth_email = get_user_meta($current_user_id, 'authpress_authentication_email', true);
                if (!empty($custom_auth_email) && $custom_auth_email !== wp_get_current_user()->user_email) : ?>
                    <button type="button" class="ap-button ap-button--secondary" id="authpress_reset_email_btn" style="margin-left: 10px;">
                        <?php _e('Reset to default mail', 'two-factor-login-telegram'); ?>
                    </button>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="authpress-actions">
                 <button type="button" class="ap-button ap-button--primary" id="reconfigure-email">
                    <?php _e('Set Authentication Email', 'two-factor-login-telegram'); ?>
                </button>
                <form method="post" action="" id="authpress-enable-email-form" class="authpress-enable-form" style="display: inline-block; margin-left: 10px;">
                    <?php wp_nonce_field('wp_factor_enable_email', 'wp_factor_email_enable_nonce'); ?>
                    <input type="hidden" name="authpress_action" value="enable_email">
                </form>
            </div>
        <?php endif; ?>

        <!-- Hidden reconfiguration section -->
        <div class="authpress-reconfig" id="email-reconfig-section" style="display: none; margin-top: 20px;">
            <?php wp_nonce_field('authpress_save_auth_email_' . $current_user_id, 'authpress_email_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label class="ap-label" for="authpress_auth_email"><?php _e('New Authentication Email', 'two-factor-login-telegram'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="authpress_auth_email" class="ap-input" name="authpress_auth_email" value="<?php echo esc_attr($auth_email); ?>"/>
                        <p class="ap-label"><?php _e('Enter the email address to receive 2FA codes.', 'two-factor-login-telegram'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="button" id="authpress_send_email_code_btn" class="ap-button ap-button--primary">
                    <?php _e('Send Verification Code', 'two-factor-login-telegram'); ?>
                </button>
                <button type="button" class="ap-button ap-button--secondary" id="cancel-reconfigure-email" style="margin-left: 10px;">
                    <?php _e('Cancel', 'two-factor-login-telegram'); ?>
                </button>
            </p>
            <div id="authpress-email-send-status" class="tg-status" style="display: none;"></div>
        </div>

        <!-- Hidden verification section -->
        <div class="authpress-reconfig" id="email-verify-section" style="display: none; margin-top: 20px;">
            <h4><?php _e('Verify New Email Address', 'two-factor-login-telegram'); ?></h4>
            <p id="email-verify-message"></p>
            <?php wp_nonce_field('authpress_verify_auth_email_' . $current_user_id, 'authpress_email_verification_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label class="ap-label" for="authpress_verification_code"><?php _e('Verification Code', 'two-factor-login-telegram'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="authpress_verification_code" name="authpress_verification_code" value="" class="regular-text" />
                        <p class="ap-label"><?php _e('Enter the verification code you received in your email.', 'two-factor-login-telegram'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="button" id="authpress_verify_email_code_btn" class="ap-button ap-button--primary">
                    <?php _e('Verify & Save', 'two-factor-login-telegram'); ?>
                </button>
                <button type="button" class="ap-button ap-button--secondary" id="cancel-verify-email" style="margin-left: 10px;"><?php _e('Cancel', 'two-factor-login-telegram'); ?></button>
            </p>
            <div id="authpress-email-verify-status" class="tg-status" style="display: none;"></div>
        </div>
</div>

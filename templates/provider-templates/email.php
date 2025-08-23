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
    <h2>
        <img src="<?php echo esc_url($provider->get_icon()); ?>" alt="Email" style="width: 24px; height: 24px; margin-right: 8px; vertical-align: text-bottom;" />
        <?php _e('Email', 'two-factor-login-telegram'); ?>
    </h2>

    <?php if ($email_user_available): ?>
        <?php if ($user_has_method): ?>
            <div class="notice notice-success inline">
                <p class="tex">
                    <?php _e('✅ Email 2FA is configured and active.', 'two-factor-login-telegram'); ?><br>
                    <strong><?php _e('Email:', 'two-factor-login-telegram'); ?></strong> <?php echo esc_html($auth_email); ?>
                </p>
            </div>

            <div class="authpress-actions mt-8">
                <button type="button" class="button button-primary" id="reconfigure-email">
                    <?php _e('Change Authentication Email', 'two-factor-login-telegram'); ?>
                </button>
                <?php
                $custom_auth_email = get_user_meta($current_user_id, 'authpress_authentication_email', true);
                if (!empty($custom_auth_email) && $custom_auth_email !== wp_get_current_user()->user_email) : ?>
                    <button type="button" class="button button-secondary" id="authpress_reset_email_btn" style="margin-left: 10px;">
                        <?php _e('Reset to default mail', 'two-factor-login-telegram'); ?>
                    </button>
                <?php endif; ?>
                <form method="post" action="" class="authpress-disable-form" style="display: inline-block; margin-left: 10px;">
                    <?php wp_nonce_field('wp_factor_disable_email', 'wp_factor_email_disable_nonce'); ?>
                    <input type="hidden" name="wp_factor_action" value="disable_email">
                    <button type="submit" class="button button-secondary" onclick="return confirm('<?php _e('Are you sure you want to disable Email 2FA?', 'two-factor-login-telegram'); ?>')">
                        <?php _e('Disable Email 2FA', 'two-factor-login-telegram'); ?>
                    </button>
                </form>
            </div>

        <?php else: ?>
            <div class="notice notice-info inline">
                <p>
                    <?php _e('📧 Email 2FA is available but not enabled.', 'two-factor-login-telegram'); ?><br>
                    <strong><?php _e('Current Email:', 'two-factor-login-telegram'); ?></strong> <?php echo esc_html($auth_email); ?>
                </p>
            </div>
            <p><?php _e('You can specify a different email address for receiving authentication codes. If left blank, your primary WordPress email will be used.', 'two-factor-login-telegram'); ?></p>

            <div class="authpress-actions">
                 <button type="button" class="button button-primary" id="reconfigure-email">
                    <?php _e('Set Authentication Email', 'two-factor-login-telegram'); ?>
                </button>
                <form method="post" action="" class="authpress-enable-form" style="display: inline-block; margin-left: 10px;">
                    <?php wp_nonce_field('wp_factor_enable_email', 'wp_factor_email_enable_nonce'); ?>
                    <input type="hidden" name="wp_factor_action" value="enable_email">
                    <button type="submit" class="button button-primary">
                        <?php _e('Enable Email 2FA', 'two-factor-login-telegram'); ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Hidden reconfiguration section -->
        <div class="authpress-reconfig" id="email-reconfig-section" style="display: none; margin-top: 20px;">
            <h4><?php _e('Set Authentication Email', 'two-factor-login-telegram'); ?></h4>
            <?php wp_nonce_field('authpress_save_auth_email_' . $current_user_id, 'authpress_email_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="authpress_auth_email"><?php _e('New Authentication Email', 'two-factor-login-telegram'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="authpress_auth_email" name="authpress_auth_email" value="<?php echo esc_attr($auth_email); ?>" class="regular-text" />
                        <p class="description"><?php _e('Enter the email address to receive 2FA codes.', 'two-factor-login-telegram'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="button" id="authpress_send_email_code_btn" class="button button-primary">
                    <?php _e('Send Verification Code', 'two-factor-login-telegram'); ?>
                </button>
                <button type="button" class="button button-secondary" id="cancel-reconfigure-email" style="margin-left: 10px;">
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
                        <label for="authpress_verification_code"><?php _e('Verification Code', 'two-factor-login-telegram'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="authpress_verification_code" name="authpress_verification_code" value="" class="regular-text" />
                        <p class="description"><?php _e('Enter the verification code you received in your email.', 'two-factor-login-telegram'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="button" id="authpress_verify_email_code_btn" class="button button-primary">
                    <?php _e('Verify & Save', 'two-factor-login-telegram'); ?>
                </button>
                <button type="button" class="button button-secondary" id="cancel-verify-email" style="margin-left: 10px;"><?php _e('Cancel', 'two-factor-login-telegram'); ?></button>
            </p>
            <div id="authpress-email-verify-status" class="tg-status" style="display: none;"></div>
        </div>

    <?php else: ?>
        <div class="notice notice-warning inline">
            <p><?php _e('⚠️ Email 2FA is not available.', 'two-factor-login-telegram'); ?></p>
        </div>
        <p><?php _e('You need a valid email address in your profile to use email 2FA. Please update your email address in your user profile.', 'two-factor-login-telegram'); ?></p>
    <?php endif; ?>
</div>

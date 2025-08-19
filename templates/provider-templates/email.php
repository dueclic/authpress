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
?>

<div class="authpress-section">
    <h2>
        <img src="<?php echo esc_url($provider->get_icon()); ?>" alt="Email" style="width: 24px; height: 24px; margin-right: 8px; vertical-align: text-bottom;" />
        <?php _e('Email', 'two-factor-login-telegram'); ?>
    </h2>

    <?php if ($email_user_available): ?>
        <?php if ($user_has_method): ?>
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

<?php

use AuthPress\Providers\Abstract_Provider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var $provider Abstract_Provider
 * @var $user_has_method boolean
 */
?>

<div class="authpress-section">

    <?php if ($user_has_method): ?>
        <div class="notice notice-success inline">
            <p>
                ✅ <?php printf(__('%s is configured and active.', 'two-factor-login-telegram'), $provider->get_name()); ?>
            </p>
        </div>

        <p class="description"><?php echo esc_html($provider->get_description()); ?></p>

        <div class="authpress-actions" style="margin-top: 15px;">
            <form method="post" action="" class="authpress-disable-form" style="display: inline-block;">
                <?php wp_nonce_field('wp_factor_disable_' . $provider->get_key(), 'wp_factor_' . $provider->get_key() . '_disable_nonce'); ?>
                <input type="hidden" name="wp_factor_action" value="disable_<?php echo esc_attr($provider->get_key()); ?>">
                <button type="submit" class="button button-secondary" onclick="return confirm('<?php printf(__('Are you sure you want to disable %s 2FA?', 'two-factor-login-telegram'), $data['name']); ?>')">
                    <?php printf(__('Disable %s 2FA', 'two-factor-login-telegram'), $provider->get_name()); ?>
                </button>
            </form>
        </div>

    <?php else: ?>
        <div class="notice notice-info inline">
            <p><?php printf(__('Configure %s to enable this 2FA method.', 'two-factor-login-telegram'), $provider->get_name()); ?></p>
        </div>

        <p class="description"><?php echo esc_html($provider->get_description()); ?></p>

        <div class="authpress-setup" style="margin-top: 15px;">
            <form method="post" action="" class="authpress-enable-form">
                <?php wp_nonce_field('wp_factor_enable_' . $provider->get_key(), 'wp_factor_' . $provider->get_key() . '_enable_nonce'); ?>
                <input type="hidden" name="wp_factor_action" value="enable_<?php echo esc_attr($provider->get_key()); ?>">
                <button type="submit" class="button button-primary">
                    <?php printf(__('Enable %s 2FA', 'two-factor-login-telegram'), $provider->get_name()); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

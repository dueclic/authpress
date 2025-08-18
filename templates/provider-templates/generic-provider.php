<?php
if (!defined('ABSPATH')) {
    exit;
}

// Variables available from the main template:
// $key - provider key
// $data - provider data array
// $provider - provider instance
// $user_has_method - boolean if user has this method enabled
// $current_user_id - current user ID
?>

<div class="authpress-section">
    <h2>
        <?php if (!empty($data['icon'])): ?>
            <img src="<?php echo esc_url($data['icon']); ?>" alt="<?php echo esc_attr($data['name']); ?>" style="width: 24px; height: 24px; margin-right: 8px; vertical-align: text-bottom;" />
        <?php endif; ?>
        <?php echo esc_html($data['name']); ?>
    </h2>
    
    <?php if ($user_has_method): ?>
        <div class="notice notice-success inline">
            <p>
                ✅ <?php printf(__('%s is configured and active.', 'two-factor-login-telegram'), $data['name']); ?>
            </p>
        </div>
        
        <p class="description"><?php echo esc_html($data['description']); ?></p>
        
        <div class="authpress-actions" style="margin-top: 15px;">
            <form method="post" action="" class="authpress-disable-form" style="display: inline-block;">
                <?php wp_nonce_field('wp_factor_disable_' . $key, 'wp_factor_' . $key . '_disable_nonce'); ?>
                <input type="hidden" name="wp_factor_action" value="disable_<?php echo esc_attr($key); ?>">
                <button type="submit" class="button button-secondary" onclick="return confirm('<?php printf(__('Are you sure you want to disable %s 2FA?', 'two-factor-login-telegram'), $data['name']); ?>')">
                    <?php printf(__('Disable %s 2FA', 'two-factor-login-telegram'), $data['name']); ?>
                </button>
            </form>
        </div>
        
    <?php else: ?>
        <div class="notice notice-info inline">
            <p><?php printf(__('Configure %s to enable this 2FA method.', 'two-factor-login-telegram'), $data['name']); ?></p>
        </div>
        
        <p class="description"><?php echo esc_html($data['description']); ?></p>
        
        <div class="authpress-setup" style="margin-top: 15px;">
            <form method="post" action="" class="authpress-enable-form">
                <?php wp_nonce_field('wp_factor_enable_' . $key, 'wp_factor_' . $key . '_enable_nonce'); ?>
                <input type="hidden" name="wp_factor_action" value="enable_<?php echo esc_attr($key); ?>">
                <button type="submit" class="button button-primary">
                    <?php printf(__('Enable %s 2FA', 'two-factor-login-telegram'), $data['name']); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
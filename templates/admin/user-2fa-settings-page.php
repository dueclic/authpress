<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$plugin = \Authpress\AuthPress_Plugin::get_instance();

// Get centralized user configuration
$user_config = \Authpress\AuthPress_User_Manager::get_user_2fa_config($current_user_id);

// Use registry to get all providers dynamically
$enabled_providers = \Authpress\AuthPress_Provider_Registry::get_enabled();
$available_providers = \Authpress\AuthPress_Provider_Registry::get_available();


$provider_categories = authpress_provider_categories(
        $available_providers
);

$has_providers = !empty($enabled_providers);

// Recovery codes handling
$has_recovery_codes = false;
$recovery_provider = \Authpress\AuthPress_Provider_Registry::get('recovery_codes');
if ($recovery_provider) {
    $has_recovery_codes = $recovery_provider->has_recovery_codes($current_user_id);
}

// Get user's preferred default provider
$user_default_provider = get_user_meta($current_user_id, 'authpress_default_provider', true);
if (empty($user_default_provider)) {
    $user_default_provider = $plugin->get_default_provider();
}

// Check if user has any active 2FA methods
$user_has_active_methods = $user_config['has_2fa'];
?>


<div class="authpress-ui ap-wpbody-content">
    <div class="ap-container">

        <div class="ap-topbar">
            <div class="ap-logo-section">
                <img src="<?php echo plugins_url("assets/img/plugin_logo.png", AUTHPRESS_PLUGIN_FILE); ?>"
                     alt="AuthPress"
                     class="ap-logo"
                     width="120"
                     height="auto">
                <span class="ap-logo-text">AuthPress</span>
            </div>
        </div>
        <div class="providers-page">
            <?php do_action('authpress_user_providers_page_header'); ?>

            <?php
            $page_title = apply_filters('authpress_user_providers_page_title', __("My 2FA Settings", "two-factor-login-telegram"));
            ?>

            <div class="ap-topbar">
                <div>
                    <h1 class="ap-title m-0"><?php echo esc_html($page_title); ?></h1>
                </div>
                <?php do_action('authpress_user_providers_page_topbar_nav'); ?>
            </div>

            <?php do_action('authpress_user_providers_page_notices'); ?>

            <?php if (!$has_providers): ?>
                <div class="ap-notice ap-notice--warning">
                    <p><?php _e('No 2FA providers are currently enabled by the administrator.', 'two-factor-login-telegram'); ?></p>
                </div>
            <?php else: ?>

                <?php foreach ($provider_categories as $category_key => $category): ?>
                    <?php
                    $category_title = apply_filters('authpress_provider_category_title', $category['title'], $category_key, $category);
                    $category_description = apply_filters('authpress_provider_category_description', $category['description'], $category_key, $category);
                    ?>

                    <h3 class="ap-heading"><?php echo esc_html($category_title); ?></h3>
                    <?php if ($category_description): ?>
                        <p class="ap-text mb-16"><?php echo esc_html($category_description); ?></p>
                    <?php endif; ?>

                    <div class="ap-grid mb-24 <?php echo apply_filters('authpress_user_provider_settings_grid_cls', '', $category_key); ?>">
                        <?php foreach ($category['providers'] as $provider_key): ?>
                            <?php
                            $provider = $available_providers[$provider_key] ?? null;
                            if (!$provider) continue;

                            $user_method_key = $provider_key === 'authenticator' ? 'totp' : $provider_key;
                            $user_has_method = $user_config['available_methods'][$user_method_key] ?? false;
                            $col_class = apply_filters('authpress_user_provider_card_col_class', 'ap-col-6', $provider, $provider_key, $category_key);

                            // Build CSS classes based on category and provider
                            $base_classes = 'provider-card';
                            $category_class = $category_key . '-provider';
                            $provider_class = $provider_key . '-provider';

                            $card_classes = apply_filters('authpress_provider_card_classes',
                                    trim($base_classes . ' ' . $category_class . ' ' . $provider_class),
                                    $provider, $provider_key, $category_key
                            );

                            $header_style = apply_filters('authpress_provider_header_style', '', $provider, $provider_key, $category_key);
                            ?>

                            <section
                                    class="<?php echo $card_classes; ?> <?php echo $col_class; ?> <?php echo $user_has_method ? 'enabled' : 'disabled'; ?>"
                                    aria-labelledby="provider-<?php echo esc_attr($provider_key); ?>">

                                <?php do_action('authpress_provider_card_before_header', $provider, $provider_key, $category_key); ?>

                                <header class="provider-card__header" <?php echo $header_style ? 'style="' . esc_attr($header_style) . '"' : ''; ?>>
                                    <div class="provider-card__title"
                                         id="provider-<?php echo esc_attr($provider_key); ?>-title">
                                        <?php
                                        $icon_html = apply_filters('authpress_provider_icon_html',
                                                '<span class="icon-circle" aria-hidden="true">📨</span>',
                                                $provider, $provider_key, $category_key
                                        );
                                        echo $icon_html;
                                        ?>
                                        <span><?php echo esc_html($provider->get_name()); ?></span>

                                    </div>

                                    <?php
                                    $toggle_label = sprintf(
                                            $user_has_method ? __('Disable %s for your account', 'two-factor-login-telegram') : __('Enable %s for your account', 'two-factor-login-telegram'),
                                            $provider->get_name()
                                    );
                                    $toggle_label = apply_filters('authpress_provider_toggle_label', $toggle_label, $provider, $provider_key, 'user-settings');
                                    ?>
                                    <label class="ap-switch" aria-label="<?php echo esc_attr($toggle_label); ?>">
                                        <input type="checkbox"
                                               class="authpress-user-provider-toggle"
                                               data-provider-key="<?php echo esc_attr($provider_key); ?>"
                                               data-user-id="<?php echo esc_attr($current_user_id); ?>"
                                               data-nonce="<?php echo wp_create_nonce('authpress_update_user_provider_status_' . $provider_key); ?>"
                                               value="1"
                                                <?php checked($user_has_method); ?>>
                                        <span class="ap-slider"></span>
                                    </label>
                                </header>

                                <?php do_action('authpress_provider_card_after_header', $provider, $provider_key, $category_key); ?>

                                <div class="provider-card__body"
                                     id="provider-<?php echo esc_attr($provider_key); ?>-body">
                                    <?php
                                    $provider_description = apply_filters('authpress_provider_description',
                                            $provider->get_description(),
                                            $provider, $provider_key, $category_key
                                    );
                                    ?>

                                    <p class="ap-text mb-16"><?php echo esc_html($provider_description); ?></p>

                                    <?php do_action('authpress_user_provider_settings_pre_content', $provider_key, $provider); ?>
                                    <?php do_action('authpress_user_provider_settings_' . $provider_key . '_pre_content', $provider); ?>

                                    <?php
                                    // Check if provider has custom user template path method
                                    $template_path = $provider->get_user_template_path();

                                    ?>

                                    <div class="provider-config ap-form <?php if ($user_has_method) {
                                        echo 'provider-configured';
                                    } ?>"
                                         id="provider-<?php echo esc_attr($provider_key); ?>-config">

                                        <?php

                                        if (file_exists($template_path)) {
                                            include $template_path;
                                        } else {
                                            include dirname(AUTHPRESS_PLUGIN_FILE) . '/templates/provider-templates/generic-provider.php';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <?php do_action('authpress_provider_card_before_footer', $provider, $provider_key, $category_key); ?>

                                <footer class="provider-card__footer">
                                    <?php
                                    $status_text = "";
                                    if ($user_has_method) {
                                        $status_text = '<span class="ap-text--small ap-text--success">' . __("Ready to use", "two-factor-login-telegram") . '</span>';
                                    } else {
                                        $status_text = '<span class="ap-text--small ap-text--muted">' . __("Needs setup", "two-factor-login-telegram") . '</span>';
                                    }

                                    $status_text = apply_filters('authpress_user_provider_status_text', $status_text, $provider, $provider_key, $category_key, $user_has_method);
                                    ?>
                                    <span class="ap-text"><?php echo $status_text; ?></span>

                                    <?php do_action('authpress_user_provider_footer_actions', $provider, $provider_key, $category_key); ?>
                                </footer>

                                <?php do_action('authpress_provider_card_after_footer', $provider, $provider_key, $category_key); ?>

                            </section>
                        <?php endforeach; ?>
                    </div>

                <?php endforeach; ?>

                <?php if ($user_has_active_methods): ?>
                    <!-- Default Provider Selection Section -->
                    <h3 class="ap-heading"><?php _e('Default 2FA Method', 'two-factor-login-telegram'); ?></h3>
                    <p class="ap-text mb-16"><?php _e('Choose which 2FA method to use by default when you log in. You can always switch to another method during login.', 'two-factor-login-telegram'); ?></p>

                    <div class="authpress-section">

                        <form method="post" action="" class="authpress-default-provider-form">
                            <?php wp_nonce_field('wp_factor_set_default_provider', 'wp_factor_default_provider_nonce'); ?>
                            <input type="hidden" name="authpress_action" value="set_default_provider">

                            <div class="authpress-provider-options">
                                <?php foreach ($available_providers as $available_provider):

                                    $provider_key = $available_provider->get_key();
                                    $user_method_key = $provider_key === 'authenticator' ? 'totp' : $provider_key;
                                    $user_has_method = $user_config['available_methods'][$user_method_key] ?? false;

                                    if (!$user_has_method) continue; // Only show enabled methods
                                    ?>
                                    <label class="authpress-provider-option">
                                        <input type="radio" name="default_provider"
                                               value="<?php echo esc_attr($provider_key); ?>"
                                                <?php checked($user_default_provider, $provider_key); ?>>
                                        <span class="provider-icon">
                                    <img src="<?php echo esc_url($available_provider->get_icon()); ?>"
                                         alt="<?php echo esc_attr($available_provider->get_name()); ?>"
                                         style="width: 20px; height: 20px;"/>
                                </span>
                                        <span class="provider-name"><?php echo esc_html($available_provider->get_name()); ?></span>
                                        <span class="provider-description"><?php echo esc_html($available_provider->get_description()); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div class="save-bar">
                                <button type="submit" class="ap-button ap-button--primary">
                                    <?php _e('Save Default Method', 'two-factor-login-telegram'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Recovery Codes Section -->
                <h3 class="ap-heading"><?php _e('Recovery Codes', 'two-factor-login-telegram'); ?></h3>

                <div class="authpress-section">

                    <?php if ($has_recovery_codes): ?>
                        <div class="notice notice-info inline">
                            <p><?php _e('You have recovery codes available. Keep them safe!', 'two-factor-login-telegram'); ?></p>
                        </div>

                        <div class="authpress-recovery-codes">
                            <p><?php _e('Your recovery codes are hidden for security. Regenerate them to view and save new codes.', 'two-factor-login-telegram'); ?></p>
                        </div>

                        <div class="save-bar">
                            <button type="button" id="regenerate_recovery_codes_btn"
                                    class="ap-button ap-button--primary"
                                    data-user-id="<?php echo $current_user_id; ?>"
                            >
                                <?php _e('Regenerate Recovery Codes', 'two-factor-login-telegram'); ?>
                            </button>
                            <input type="hidden" id="regenerate_recovery_nonce"
                                   value="<?php echo wp_create_nonce('tg_regenerate_recovery_codes_' . $current_user_id); ?>"/>
                        </div>

                    <?php else: ?>
                        <div class="notice notice-warning inline">
                            <p><?php _e('You don\'t have any recovery codes yet. Generate them now!', 'two-factor-login-telegram'); ?></p>
                        </div>

                        <form method="post" action="">
                            <?php wp_nonce_field('wp_factor_generate_recovery', 'wp_factor_recovery_nonce'); ?>
                            <input type="hidden" name="authpress_action" value="generate_recovery">
                            <div class="save-bar">
                                <button type="submit" class="ap-button ap-button--primary">
                                    <?php _e('Generate Recovery Codes', 'two-factor-login-telegram'); ?>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </div>
    </div>
    <?php do_action("authpress_copyright"); ?>
</div>

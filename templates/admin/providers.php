<?php
/**
 * Providers tab view - Modular approach using provider registry
 * @var array $providers Legacy providers array for backward compatibility
 */

use Authpress\AuthPress_Provider_Registry;

// Get all available provider instances
$available_providers = AuthPress_Provider_Registry::get_all();

$provider_categories = authpress_provider_categories(
        $available_providers
);


?>

<div class="providers-page">
    <?php do_action('authpress_providers_page_header'); ?>

    <?php
    $page_title = apply_filters('authpress_providers_page_title', __("2FA Methods Configuration", "two-factor-login-telegram"));
    $page_subtitle = apply_filters('authpress_providers_page_subtitle', __("Manage 2FA methods and configure provider delivery settings.", "two-factor-login-telegram"));
    ?>

    <div class="ap-topbar">
        <div>
            <h1 class="ap-title m-0"><?php echo esc_html($page_title); ?></h1>
            <?php if ($page_subtitle): ?>
                <p class="ap-subtitle"><?php echo esc_html($page_subtitle); ?></p>
            <?php endif; ?>
        </div>
        <?php do_action('authpress_providers_page_topbar_nav'); ?>
    </div>

    <?php do_action('authpress_providers_page_notices'); ?>

    <form method="post" action="<?php echo admin_url('options.php'); ?>">
        <?php settings_fields('authpress_providers'); ?>
        <?php do_action('authpress_providers_form_start'); ?>

        <?php foreach ($provider_categories as $category_key => $category): ?>
            <?php
            $category_title = apply_filters('authpress_provider_category_title', $category['title'], $category_key, $category);
            $category_description = apply_filters('authpress_provider_category_description', $category['description'], $category_key, $category);
            ?>

            <h3 class="ap-heading"><?php echo esc_html($category_title); ?></h3>
            <?php if ($category_description): ?>
                <p class="ap-text mb-16"><?php echo esc_html($category_description); ?></p>
            <?php endif; ?>

            <div class="ap-grid mb-24 <?php echo apply_filters('authpress_provider_settings_grid_cls', '', $category_key); ?>">
                <?php foreach ($category['providers'] as $provider_key): ?>
                    <?php
                    $provider = $available_providers[$provider_key] ?? null;
                    if (!$provider) continue;

                    $is_enabled = $provider->is_enabled();
                    $is_configured = $provider->is_configured();

                    $col_class = apply_filters('authpress_provider_card_col_class', 'ap-col-6', $provider, $provider_key, $category_key);

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

                    <section class="<?php echo $card_classes; ?> <?php echo $col_class; ?> <?php echo $is_enabled ? 'enabled' : 'disabled'; ?>"
                             aria-labelledby="provider-<?php echo esc_attr($provider_key); ?>">

                        <?php do_action('authpress_provider_card_before_header', $provider, $provider_key, $category_key); ?>

                        <header class="provider-card__header" <?php echo $header_style ? 'style="' . esc_attr($header_style) . '"' : ''; ?>>
                            <div class="provider-card__title"
                                 id="provider-<?php echo esc_attr($provider_key); ?>-title">
                                <?php
                                $icon_html = apply_filters('authpress_provider_icon_html',
                                        '<span class="icon-circle" aria-hidden="true" style="background-image: url('.esc_attr($provider->get_icon()).');"></span>',
                                        $provider, $provider_key, $category_key
                                );
                                echo $icon_html;
                                ?>
                                <span><?php echo esc_html($provider->get_name()); ?></span>

                            </div>

                            <?php $toggle_label = apply_filters('authpress_provider_toggle_label',
                                sprintf(__('Enable %s', 'two-factor-login-telegram'), $provider->get_name()),
                                $provider, $provider_key, $category_key
                            ); ?>

                            <label class="ap-switch" aria-label="<?php echo esc_attr($toggle_label); ?>">
                                <input type="checkbox"
                                       name="authpress_providers[<?php echo esc_attr($provider->get_key()); ?>][enabled]"
                                       value="1"
                                       <?php checked($is_enabled); ?>>
                                <span class="ap-slider"></span>
                            </label>
                        </header>

                        <?php do_action('authpress_provider_card_after_header', $provider, $provider_key, $category_key); ?>

                        <div class="provider-card__body" id="provider-<?php echo esc_attr($provider_key); ?>-body">
                            <?php
                            $provider_description = apply_filters('authpress_provider_description',
                                $provider->get_description(),
                                $provider, $provider_key, $category_key
                            );
                            ?>

                            <p class="ap-text mb-16"><?php echo esc_html($provider_description); ?></p>

                            <?php do_action('authpress_provider_settings_pre_content', $provider_key, $provider); ?>
                            <?php do_action('authpress_provider_settings_' . $provider_key . '_pre_content', $provider);

                            $features_template = $provider->get_features_template_path();
                            if ($features_template && file_exists($features_template)):
                                ?>
                                <div class="provider-features">
                                    <?php
                                    $features_heading = apply_filters('authpress_provider_features_heading',
                                            __("Features:", "two-factor-login-telegram"),
                                            $provider, $provider_key, $category_key
                                    );
                                    if ($features_heading):
                                        ?>
                                        <h4 class="ap-label"><?php echo esc_html($features_heading); ?></h4>
                                    <?php endif; ?>
                                    <?php include $features_template; ?>
                                </div>
                            <?php endif; ?>


                            <?php
                            $config_template = $provider->get_config_template_path();
                            if ($config_template && file_exists($config_template)):
                                ?>
                                <button type="button" class="provider-card__toggle" aria-expanded="false" aria-controls="provider-<?php echo esc_attr($provider_key); ?>-config">
                                    <span class="provider-card__arrow">▼</span>
                                    <span><?php _e('Configuration', 'two-factor-login-telegram'); ?></span>
                                </button>
                                <div class="provider-config ap-form" id="provider-<?php echo esc_attr($provider_key); ?>-config" style="display: none;">
                                    <?php include $config_template; ?>

                                    <?php
                                    $submit_text = apply_filters('authpress_provider_settings_save_button_text', __('Save', 'two-factor-login-telegram'), $provider_key, $provider);

                                    ?>

                                    <input type="submit" class="ap-button ap-button--primary" value="<?php echo esc_attr($submit_text); ?>"/>
                                </div>
                            <?php endif; ?>
                            <?php
                            do_action('authpress_provider_settings_content_end', $provider_key, $provider);
                            do_action('authpress_provider_settings_' . $provider_key . '_content_end', $provider);
                            ?>
                        </div>

                        <?php do_action('authpress_provider_card_before_footer', $provider, $provider_key, $category_key); ?>

                        <footer class="provider-card__footer">
                            <?php
                            $status_text = "";
                            if ($is_enabled && !$is_configured) {
                                $status_text .= ' <span class="ap-text--small">(' . __("requires configuration", "two-factor-login-telegram") . ')</span>';
                            }

                            $status_text = apply_filters('authpress_provider_status_text', $status_text, $provider, $provider_key, $category_key, $is_enabled, $is_configured);
                            ?>
                            <span class="ap-text"><?php echo $status_text; ?></span>

                            <?php do_action('authpress_provider_footer_actions', $provider, $provider_key, $category_key); ?>
                        </footer>

                        <?php do_action('authpress_provider_card_after_footer', $provider, $provider_key, $category_key); ?>

                        <?php
                        do_action('authpress_provider_settings_post_content', $provider_key, $provider);
                        do_action('authpress_provider_settings_' . $provider_key . '_post_content', $provider);
                        ?>

                    </section>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <?php do_action('authpress_providers_form_end'); ?>
    </form>

    <?php
    $show_info_section = apply_filters('authpress_providers_show_info_section', true);
    if ($show_info_section):
        $info_title = apply_filters('authpress_providers_info_title', __("About 2FA Providers", "two-factor-login-telegram"));
        $info_description = apply_filters('authpress_providers_info_description',
            __("You can enable one or both providers. Users will be able to choose which method to use for their 2FA setup.", "two-factor-login-telegram")
        );
        $info_recommendation = apply_filters('authpress_providers_info_recommendation',
            __("Enable both providers to give users flexibility and backup options.", "two-factor-login-telegram")
        );
    ?>
        <div class="providers-info">
            <h3 class="ap-heading"><?php echo esc_html($info_title); ?></h3>
            <p class="ap-text"><?php echo esc_html($info_description); ?></p>
            <p class="ap-text">
                <strong><?php _e("Recommendation:", "two-factor-login-telegram"); ?></strong>
                <?php echo esc_html($info_recommendation); ?>
            </p>

            <?php do_action('authpress_providers_info_section_content'); ?>
        </div>
    <?php endif; ?>

    <?php do_action('authpress_providers_page_footer'); ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.provider-card__toggle');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('aria-controls');
            const targetElement = document.getElementById(targetId);
            const isExpanded = this.getAttribute('aria-expanded') === 'true';

            if (isExpanded) {
                targetElement.style.display = 'none';
                this.setAttribute('aria-expanded', 'false');
            } else {
                targetElement.style.display = 'block';
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // Auto-submit form when provider checkboxes are toggled
    const providerCheckboxes = document.querySelectorAll('input[type="checkbox"][name*="authpress_providers"][name*="[enabled]"]');

    providerCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
</script>

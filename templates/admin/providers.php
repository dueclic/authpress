<?php
/**
 * Providers tab view - Modular approach using provider registry
 * @var array $providers Legacy providers array for backward compatibility
 */

use Authpress\AuthPress_Provider_Registry;

// Get all available provider instances
$available_providers = AuthPress_Provider_Registry::get_all();

// Handle timing issues where external providers register after initial load
$external_providers = apply_filters('authpress_register_providers', []);
$missing_external_providers = array_diff(array_keys($external_providers), array_keys($available_providers));
if (!empty($missing_external_providers)) {
    // Force reload to catch late-registering external providers
    AuthPress_Provider_Registry::force_reload_external_providers();
    $available_providers = AuthPress_Provider_Registry::get_all();
}

// Group providers by category
$provider_categories = [
    'messaging' => [
        'title' => __("Messaging Providers", "two-factor-login-telegram"),
        'description' => __("Receive authentication codes directly via messaging platforms.", "two-factor-login-telegram"),
        'providers' => ['telegram', 'email']
    ],
    'authenticator' => [
        'title' => __("Authenticator Apps", "two-factor-login-telegram"),
        'description' => __("Time-based One-Time Password apps that generate codes offline.", "two-factor-login-telegram"),
        'providers' => ['authenticator']
    ]
];

// Allow external plugins to add providers to categories
$provider_categories = apply_filters('authpress_provider_categories', $provider_categories);

// Auto-add any uncategorized providers to 'other' category
$categorized_providers = [];
foreach ($provider_categories as $category) {
    $categorized_providers = array_merge($categorized_providers, $category['providers']);
}

$uncategorized_providers = array_diff(array_keys($available_providers), $categorized_providers);
// Exclude recovery_codes from being shown in admin interface - it's a backend-only provider
$uncategorized_providers = array_diff($uncategorized_providers, ['recovery_codes']);
if (!empty($uncategorized_providers)) {
    $provider_categories['other'] = [
        'title' => __("Other Providers", "two-factor-login-telegram"),
        'description' => __("Additional 2FA methods provided by plugins.", "two-factor-login-telegram"),
        'providers' => $uncategorized_providers
    ];
}

?>

<h2><?php _e("2FA Methods Configuration", "two-factor-login-telegram"); ?></h2>

<form method="post" action="options.php">
    <?php settings_fields('wp_factor_providers'); ?>

    <?php foreach ($provider_categories as $category_key => $category): ?>
        <div class="providers-category">
            <h3><?php echo esc_html($category['title']); ?></h3>
            <p class="providers-description"><?php echo esc_html($category['description']); ?></p>

            <div class="providers-container">
                <?php foreach ($category['providers'] as $provider_key): ?>
                    <?php
                    $provider = $available_providers[$provider_key] ?? null;
                    if (!$provider) continue;

                    $is_enabled = $provider->is_enabled();
                    $is_configured = $provider->is_configured();
                    ?>

                    <div class="provider-card <?php echo $is_enabled ? 'enabled' : 'disabled'; ?>">
                        <div class="provider-header">
                            <div class="provider-icon">
                                <img src="<?php echo esc_url($provider->get_icon()); ?>" alt="<?php echo esc_attr($provider->get_name()); ?>" style="width: 32px; height: 32px;">
                            </div>
                            <div class="provider-info">
                                <h3><?php echo esc_html($provider->get_name()); ?></h3>
                                <p><?php echo esc_html($provider->get_description()); ?></p>
                            </div>
                            <div class="provider-toggle">
                                <label class="switch">
                                    <input type="checkbox"
                                           name="wp_factor_providers[<?php echo esc_attr($provider->get_key()); ?>][enabled]"
                                           value="1"
                                           <?php checked($is_enabled); ?>>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="provider-content">
                            <?php
                            // Load provider-specific configuration template
                            $config_template = $provider->get_config_template_path();
                            if ($config_template && file_exists($config_template)):
                            ?>
                                <div class="provider-config">
                                    <h4><?php _e("Configuration:", "two-factor-login-telegram"); ?></h4>
                                    <?php include $config_template; ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            // Load provider-specific features template
                            $features_template = $provider->get_features_template_path();
                            if ($features_template && file_exists($features_template)):
                            ?>
                                <div class="provider-features">
                                    <h4><?php _e("Features:", "two-factor-login-telegram"); ?></h4>
                                    <?php include $features_template; ?>
                                </div>
                            <?php endif; ?>

                            <div class="provider-status <?php echo $is_enabled ? 'enabled' : 'disabled'; ?>">
                                <span class="dashicons <?php echo $is_enabled ? 'dashicons-yes-alt' : 'dashicons-no-alt'; ?>"></span>
                                <?php
                                if ($is_enabled) {
                                    printf(__("%s provider is active", "two-factor-login-telegram"), $provider->get_name());
                                    if (!$is_configured) {
                                        echo ' <span class="warning">' . __("(requires configuration)", "two-factor-login-telegram") . '</span>';
                                    }
                                } else {
                                    printf(__("%s provider is disabled", "two-factor-login-telegram"), $provider->get_name());
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="default-provider-section">
        <h3><?php _e("Default Provider Settings", "two-factor-login-telegram"); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="default_provider"><?php _e("Default 2FA Method", "two-factor-login-telegram"); ?></label>
                </th>
                <td>
                    <select name="wp_factor_providers[default_provider]" id="default_provider">
                        <?php
                        $current_default = $providers['default_provider'] ?? 'telegram';
                        foreach ($available_providers as $key => $provider):
                            if ($key === 'recovery_codes') continue; // Skip recovery codes as default
                            $selected = selected($current_default, $key, false);
                        ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php echo $selected; ?>>
                                <?php echo esc_html($provider->get_name()); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e("Choose which 2FA method will be selected by default during login. Users can still switch between available methods.", "two-factor-login-telegram"); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e('Save Providers Configuration', 'two-factor-login-telegram'); ?>" />
    </p>
</form>

<div class="providers-info">
    <h3><?php _e("About 2FA Providers", "two-factor-login-telegram"); ?></h3>
    <p>
        <?php _e("You can enable one or both providers. Users will be able to choose which method to use for their 2FA setup.", "two-factor-login-telegram"); ?>
    </p>
    <p>
        <strong><?php _e("Recommendation:", "two-factor-login-telegram"); ?></strong>
        <?php _e("Enable both providers to give users flexibility and backup options.", "two-factor-login-telegram"); ?>
    </p>
</div>

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
$has_providers = !empty($enabled_providers);

// Dynamic provider data for rendering
$provider_data = [];
foreach ($enabled_providers as $key => $provider) {
    $user_method_key = $key === 'authenticator' ? 'totp' : $key;
    $user_has_method = $user_config['available_methods'][$user_method_key] ?? false;

    $provider_data[$key] = [
        'provider' => $provider,
        'key' => $key,
        'user_has_method' => $user_has_method,
        'is_enabled' => $provider->is_enabled(),
        'is_available' => $provider->is_available(),
        'icon' => $provider->get_icon(),
        'name' => $provider->get_name(),
        'description' => $provider->get_description()
    ];
}

// Recovery codes handling
$has_recovery_codes = false;
$recovery_provider = \Authpress\AuthPress_Provider_Registry::get('recovery_codes');
if ($recovery_provider) {
    $has_recovery_codes = $recovery_provider->has_recovery_codes($current_user_id);
}

// Get user's preferred default provider
$user_default_provider = get_user_meta($current_user_id, 'wp_factor_user_default_provider', true);
if (empty($user_default_provider)) {
    $user_default_provider = $plugin->get_default_provider();
}

// Check if user has any active 2FA methods
$user_has_active_methods = $user_config['has_2fa'];

wp_enqueue_script('authpress-plugin');
wp_enqueue_style('authpress-plugin');

// Enqueue AuthPress UI Kit
$uikit_path = dirname(WP_FACTOR_TG_FILE) . '/uikit/authpress-ui-kit.css';
$uikit_url = plugin_dir_url(WP_FACTOR_TG_FILE) . 'uikit/authpress-ui-kit.css';
if (file_exists($uikit_path)) {
    wp_enqueue_style('authpress-ui-kit', $uikit_url, array(), filemtime($uikit_path));
}
?>

<div class="wrap">
    <h1><?php _e('My 2FA Settings', 'two-factor-login-telegram'); ?></h1>

    <?php if (!$has_providers): ?>
        <div class="notice notice-warning">
            <p><?php _e('No 2FA providers are currently enabled by the administrator.', 'two-factor-login-telegram'); ?></p>
        </div>
    <?php else: ?>

        <div class="authpress-2fa-settings">
            <?php
            // Dynamic provider sections using registry and template files
            foreach ($provider_data as $key => $data):
                $provider = $data['provider'];
                $user_has_method = $data['user_has_method'];

                // Check if provider has custom user template path method
                $template_path = null;
                if (method_exists($provider, 'get_user_template_path')) {
                    $template_path = $provider->get_user_template_path();
                }

                // Fallback to default template location
                if (!$template_path || !file_exists($template_path)) {
                    $template_file = sprintf('provider-%s.php', $key);
                    $template_path = dirname(WP_FACTOR_TG_FILE) . '/templates/provider-templates/' . $template_file;
                }

                if (file_exists($template_path)) {
                    // Load provider-specific template
                    include $template_path;
                } else {
                    // Fallback to generic provider template
                    include dirname(WP_FACTOR_TG_FILE) .'/templates/provider-templates/generic-provider.php';
                }
            endforeach;
            ?>

            <?php if ($user_has_active_methods): ?>
            <!-- Default Provider Selection Section -->
            <div class="authpress-section">
                <h2><?php _e('Default 2FA Method', 'two-factor-login-telegram'); ?></h2>
                <p><?php _e('Choose which 2FA method to use by default when you log in. You can always switch to another method during login.', 'two-factor-login-telegram'); ?></p>

                <form method="post" action="" class="authpress-default-provider-form">
                    <?php wp_nonce_field('wp_factor_set_default_provider', 'wp_factor_default_provider_nonce'); ?>
                    <input type="hidden" name="wp_factor_action" value="set_default_provider">

                    <div class="authpress-provider-options">
                        <?php foreach ($provider_data as $key => $data):
                            if (!$data['user_has_method']) continue; // Only show enabled methods
                        ?>
                            <label class="authpress-provider-option">
                                <input type="radio" name="default_provider" value="<?php echo esc_attr($key); ?>"
                                       <?php checked($user_default_provider, $key); ?>>
                                <span class="provider-icon">
                                    <img src="<?php echo esc_url($data['icon']); ?>" alt="<?php echo esc_attr($data['name']); ?>" style="width: 20px; height: 20px;" />
                                </span>
                                <span class="provider-name"><?php echo esc_html($data['name']); ?></span>
                                <span class="provider-description"><?php echo esc_html($data['description']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php _e('Save Default Method', 'two-factor-login-telegram'); ?>
                        </button>
                    </p>
                </form>
            </div>
            <?php endif; ?>

            <!-- Recovery Codes Section -->
            <div class="authpress-section">
                <h2><?php _e('Recovery Codes', 'two-factor-login-telegram'); ?></h2>

                <?php if ($has_recovery_codes): ?>
                    <div class="notice notice-info inline">
                        <p><?php _e('You have recovery codes available. Keep them safe!', 'two-factor-login-telegram'); ?></p>
                    </div>

                    <div class="authpress-recovery-codes">
                        <p><?php _e('Your recovery codes are hidden for security. Regenerate them to view and save new codes.', 'two-factor-login-telegram'); ?></p>
                    </div>

                    <p>
                        <button type="button" id="regenerate_recovery_codes_btn" class="button button-primary" data-user-id="<?php echo $current_user_id; ?>" onclick="if(confirm('<?php _e('Are you sure? This will invalidate your current recovery codes and generate new ones that you must save immediately.', 'two-factor-login-telegram'); ?>')) { regenerateRecoveryCodes(); }">
                            <?php _e('Regenerate Recovery Codes', 'two-factor-login-telegram'); ?>
                        </button>
                        <input type="hidden" id="regenerate_recovery_nonce" value="<?php echo wp_create_nonce('tg_regenerate_recovery_codes_' . $current_user_id); ?>" />
                    </p>

                <?php else: ?>
                    <div class="notice notice-warning inline">
                        <p><?php _e('You don\'t have any recovery codes yet. Generate them now!', 'two-factor-login-telegram'); ?></p>
                    </div>

                    <form method="post" action="">
                        <?php wp_nonce_field('wp_factor_generate_recovery', 'wp_factor_recovery_nonce'); ?>
                        <input type="hidden" name="wp_factor_action" value="generate_recovery">
                        <p>
                            <button type="submit" class="button button-primary">
                                <?php _e('Generate Recovery Codes', 'two-factor-login-telegram'); ?>
                            </button>
                        </p>
                    </form>
                <?php endif; ?>
            </div>

        </div>

    <?php endif; ?>
</div>

<?php if ($has_providers): ?>
<style>

.authpress-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.authpress-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.authpress-qr-code {
    text-align: center;
    margin: 20px 0;
}

.authpress-qr-code img {
    border: 1px solid #ddd;
    padding: 10px;
    background: #fff;
}

.recovery-codes-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin: 15px 0;
    padding: 15px;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.recovery-codes-list code {
    display: block;
    padding: 8px;
    background: #fff;
    border: 1px solid #ddd;
    text-align: center;
    font-weight: bold;
}

@media print {
    .recovery-codes-list {
        break-inside: avoid;
    }
}

.authpress-provider-options {
    margin: 15px 0;
}

.authpress-provider-option {
    display: block;
    padding: 15px;
    margin: 10px 0;
    border: 2px solid #ddd;
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.authpress-provider-option:hover {
    border-color: #0073aa;
    background: #f9f9f9;
}

.authpress-provider-option input[type="radio"] {
    margin: 0 10px 0 0;
    vertical-align: top;
}

.authpress-provider-option input[type="radio"]:checked + .provider-icon {
    background: #0073aa;
    color: white;
}

.authpress-provider-option:has(input[type="radio"]:checked) {
    border-color: #0073aa;
    background: #f0f8ff;
}

.provider-icon {
    font-size: 18px;
    margin-right: 10px;
    padding: 5px;
    border-radius: 3px;
    background: #f0f0f0;
}

.provider-name {
    font-weight: bold;
    display: inline-block;
    margin-right: 10px;
}

.provider-description {
    color: #666;
    font-style: italic;
    display: block;
    margin-top: 5px;
    margin-left: 30px;
}

/* Telegram setup specific styles */
.tg-setup-steps {
    margin: 20px 0;
    padding: 15px;
    background-color: #007cba;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.tg-setup-steps h4 {
    margin: 0 0 10px 0;
    color: #495057;
}

.tg-setup-steps ol {
    margin: 0;
    padding-left: 20px;
}

.tg-setup-steps li {
    margin: 8px 0;
    line-height: 1.5;
}

.tg-progress {
    width: 100%;
    height: 6px;
    background-color: #e9ecef;
    border-radius: 3px;
    margin: 15px 0;
}

.tg-progress-bar {
    height: 100%;
    background-color: #007cba;
    border-radius: 3px;
    width: 0;
    transition: width 0.3s ease;
}

.authpress-config {
    margin-top: 20px;
}

.authpress-config .form-table {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.authpress-config .form-table th {
    background: #f6f7f7;
    border-bottom: 1px solid #c3c4c7;
    padding: 15px;
    font-weight: 600;
}

.authpress-config .form-table td {
    padding: 15px;
    border-bottom: 1px solid #c3c4c7;
}

.tg-status {
    margin-top: 10px;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 14px;
}

.tg-status.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.tg-status.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.tg-action-button {
    min-width: 120px;
}

.wpft-notice {
    padding: 12px;
    border-radius: 4px;
    margin: 10px 0;
}

.wpft-notice-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}
</style>
<?php endif; ?>

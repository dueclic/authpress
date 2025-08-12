<?php

/**
 * @var array $providers Legacy providers array for backward compatibility
 */

use Authpress\Authpress_Logs_List_Table;
use Authpress\AuthPress_Provider_Registry;

if (isset($_GET['tab'])) {
    $active_tab = sanitize_text_field($_GET['tab']);
} else {
    $active_tab = 'providers';
}

?>

<div id="wft-wrap" class="wrap">

    <div class="heading-top">
        <div class="cover-tg-plugin">
        </div>
        <h1><?php _e("AuthPress", "two-factor-login-telegram"); ?> - <?php _e("Configuration", "two-factor-login-telegram"); ?></h1>
    </div>

    <h2 class="wpft-tab-wrapper nav-tab-wrapper">
        <a href="<?php echo admin_url('options-general.php?page=tg-conf&tab=providers'); ?>"
           class="nav-tab <?php echo $active_tab == 'providers' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-plugins"></span> <?php _e("Providers", "two-factor-login-telegram"); ?>
        </a>
        <a href="<?php echo admin_url('options-general.php?page=tg-conf&tab=howto'); ?>"
           class="nav-tab <?php echo $active_tab == 'howto' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-editor-help"></span> <?php _e("FAQ", "two-factor-login-telegram"); ?>
        </a>

        <?php 
        // Show logs tab if Telegram provider is available and configured
        $telegram_provider = AuthPress_Provider_Registry::get('telegram');
        if ($telegram_provider && $telegram_provider->is_available()) : 
        ?>
            <a href="<?php echo admin_url('options-general.php?page=tg-conf&tab=logs'); ?>"
               class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-list-view"></span> <?php _e("Logs", "two-factor-login-telegram"); ?>
            </a>
        <?php endif; ?>

        <?php 
        // Show suggestions tab if user has active Telegram integration
        if ($telegram_provider && 
            $telegram_provider->is_available() && 
            get_the_author_meta("tg_wp_factor_chat_id", get_current_user_id()) !== false) : 
        ?>
            <a href="<?php echo admin_url('options-general.php?page=tg-conf&tab=suggestions'); ?>"
               class="nav-tab <?php echo $active_tab == 'suggestions' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-heart"></span> <?php _e("Suggestions", "two-factor-login-telegram"); ?>
            </a>
        <?php endif; ?>
    </h2>

    <div class="wpft-container">

        <?php
        switch($active_tab) {
            case 'providers':
                include dirname(__FILE__) . '/providers.php';
                break;
            case 'logs':
                include dirname(__FILE__) . '/logs.php';
                break;
            case 'howto':
                include dirname(__FILE__) . '/howto.php';
                break;
            case 'suggestions':
                include dirname(__FILE__) . '/suggestions.php';
                break;
            default:
                include dirname(__FILE__) . '/providers.php';
                break;
        }
        ?>

    </div>

</div>

<style>
.providers-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.provider-card {
    border: 2px solid #e1e1e1;
    border-radius: 8px;
    padding: 20px;
    background: #fff;
    transition: all 0.3s ease;
}

.provider-card.enabled {
    border-color: #46b450;
    box-shadow: 0 2px 8px rgba(70, 180, 80, 0.1);
}

.provider-card.disabled {
    border-color: #e1e1e1;
    opacity: 0.8;
}

.provider-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.provider-icon {
    font-size: 2em;
    margin-right: 15px;
    color: #0073aa;
}

.provider-info {
    flex: 1;
}

.provider-info h3 {
    margin: 0 0 5px 0;
    color: #23282d;
}

.provider-info p {
    margin: 0;
    color: #666;
    font-size: 0.9em;
}

.provider-toggle {
    margin-left: 15px;
}

.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
}

input:checked + .slider {
    background-color: #46b450;
}

input:focus + .slider {
    box-shadow: 0 0 1px #46b450;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.slider.round {
    border-radius: 34px;
}

.slider.round:before {
    border-radius: 50%;
}

.provider-content {
    margin-top: 15px;
}

.provider-features {
    margin-bottom: 15px;
}

.provider-features h4 {
    margin: 0 0 10px 0;
    color: #23282d;
}

.provider-features ul {
    margin: 0;
    padding-left: 20px;
}

.provider-features li {
    margin-bottom: 5px;
    color: #666;
}

.provider-status {
    padding: 10px;
    border-radius: 4px;
    font-weight: 500;
}

.provider-status.enabled {
    background: #dff0d8;
    color: #3c763d;
    border: 1px solid #d6e9c6;
}

.provider-status.disabled {
    background: #f2dede;
    color: #a94442;
    border: 1px solid #ebccd1;
}

.provider-config {
    margin-bottom: 20px;
}

.provider-config h4 {
    margin: 0 0 15px 0;
    color: #23282d;
}

.description.success {
    color: #46b450;
}

.description.error {
    color: #dc3232;
}

.providers-info {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    margin-top: 30px;
}

.providers-info h3 {
    margin: 0 0 15px 0;
    color: #23282d;
}

.providers-info p {
    margin: 0 0 10px 0;
    color: #666;
}

.default-provider-section {
    background: #f0f6fc;
    border: 1px solid #c7d2fe;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.default-provider-section h3 {
    margin: 0 0 20px 0;
    color: #1e40af;
    display: flex;
    align-items: center;
}

.default-provider-section h3:before {
    content: "⚙️";
    margin-right: 8px;
    font-size: 1.2em;
}

.default-provider-section .form-table th {
    padding: 15px 10px 15px 0;
    color: #1f2937;
    font-weight: 600;
}

.default-provider-section select {
    min-width: 200px;
    padding: 6px 12px;
    border-radius: 4px;
    border: 1px solid #d1d5db;
}

.default-provider-section .description {
    font-style: italic;
    color: #6b7280;
    margin-top: 8px;
}

.providers-category {
    margin: 30px 0;
}

.providers-category h3 {
    margin: 0 0 10px 0;
    color: #1e40af;
    font-size: 1.3em;
    display: flex;
    align-items: center;
}

.providers-category h3:before {
    content: "📱";
    margin-right: 8px;
    font-size: 1.1em;
}

.providers-category:last-of-type h3:before {
    content: "🔐";
}

.providers-description {
    color: #666;
    font-style: italic;
    margin: 0 0 20px 0;
    background: #f8f9fa;
    padding: 12px 16px;
    border-left: 4px solid #0073aa;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .providers-container {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Find all provider toggles dynamically
    const providerToggles = document.querySelectorAll('input[name*="wp_factor_providers"][name*="[enabled]"]');
    const defaultProviderSection = document.querySelector('.default-provider-section');
    const defaultProviderSelect = document.querySelector('#default_provider');

    if (!defaultProviderSection || !defaultProviderSelect) {
        return;
    }

    function updateDefaultProviderVisibility() {
        // Count enabled providers
        const enabledProviders = [];
        providerToggles.forEach(toggle => {
            if (toggle.checked) {
                // Extract provider key from name attribute
                const matches = toggle.name.match(/wp_factor_providers\[(.+?)\]\[enabled\]/);
                if (matches) {
                    enabledProviders.push(matches[1]);
                }
            }
        });

        if (enabledProviders.length > 1) {
            defaultProviderSection.style.display = 'block';
        } else {
            defaultProviderSection.style.display = 'none';

            // Auto-set default to the only enabled provider
            if (enabledProviders.length === 1) {
                defaultProviderSelect.value = enabledProviders[0];
            }
        }
    }

    // Initial check
    updateDefaultProviderVisibility();

    // Listen for changes on all provider toggles
    providerToggles.forEach(toggle => {
        toggle.addEventListener('change', updateDefaultProviderVisibility);
    });
});
</script>

<?php do_action("tft_copyright"); ?>

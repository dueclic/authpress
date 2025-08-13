<?php

use Authpress\AuthPress_Plugin;

$plugin_options = get_option('tg_col', array());

if (isset($_POST['submit']) && isset($_POST['authpress_settings_nonce']) && wp_verify_nonce($_POST['authpress_settings_nonce'], 'authpress_save_settings')) {
    $new_options = array();
    
    if (isset($_POST['delete_data_on_deactivation'])) {
        $new_options['delete_data_on_deactivation'] = '1';
    } else {
        $new_options['delete_data_on_deactivation'] = '0';
    }
    
    // Merge with existing options to preserve other settings
    $plugin_options = array_merge($plugin_options, $new_options);
    update_option('tg_col', $plugin_options);
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'two-factor-login-telegram') . '</p></div>';
}

?>

<div class="settings-container">
    <form method="post" action="">
        <?php wp_nonce_field('authpress_save_settings', 'authpress_settings_nonce'); ?>
        
        <div class="settings-section">
            <h3><?php _e('Data Management', 'two-factor-login-telegram'); ?></h3>
            <p class="description">
                <?php _e('Configure how AuthPress handles plugin data.', 'two-factor-login-telegram'); ?>
            </p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <?php _e('Data Cleanup', 'two-factor-login-telegram'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e('Data cleanup options', 'two-factor-login-telegram'); ?></span>
                                </legend>
                                <label for="delete_data_on_deactivation">
                                    <input 
                                        type="checkbox" 
                                        id="delete_data_on_deactivation" 
                                        name="delete_data_on_deactivation" 
                                        value="1" 
                                        <?php checked(isset($plugin_options['delete_data_on_deactivation']) ? $plugin_options['delete_data_on_deactivation'] : '0', '1'); ?> 
                                    />
                                    <?php _e('Delete all plugin data when deactivated', 'two-factor-login-telegram'); ?>
                                </label>
                                <p class="description">
                                    <strong><?php _e('Warning:', 'two-factor-login-telegram'); ?></strong>
                                    <?php _e('This will permanently remove all settings, user configurations, authentication codes, logs, and database tables when the plugin is deactivated. This action cannot be undone.', 'two-factor-login-telegram'); ?>
                                </p>
                                
                                <div class="data-cleanup-info" style="background: #f8f9fa; padding: 15px; border-left: 4px solid #dc3545; margin-top: 15px;">
                                    <h4 style="margin: 0 0 10px 0; color: #dc3545;">
                                        <?php _e('Data that will be deleted:', 'two-factor-login-telegram'); ?>
                                    </h4>
                                    <ul style="margin: 0; color: #666;">
                                        <li><?php _e('Plugin settings and configuration', 'two-factor-login-telegram'); ?></li>
                                        <li><?php _e('User 2FA settings (Telegram chat IDs, TOTP secrets, etc.)', 'two-factor-login-telegram'); ?></li>
                                        <li><?php _e('Authentication codes and recovery codes', 'two-factor-login-telegram'); ?></li>
                                        <li><?php _e('Activity logs and security logs', 'two-factor-login-telegram'); ?></li>
                                        <li><?php _e('Database tables created by the plugin', 'two-factor-login-telegram'); ?></li>
                                        <li><?php _e('All cached data and transients', 'two-factor-login-telegram'); ?></li>
                                    </ul>
                                </div>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php submit_button(__('Save Settings', 'two-factor-login-telegram')); ?>
    </form>
</div>

<style>
.settings-container {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
}

.settings-section {
    margin-bottom: 30px;
}

.settings-section h3 {
    margin: 0 0 10px 0;
    color: #1e40af;
    font-size: 1.3em;
    display: flex;
    align-items: center;
}

.settings-section h3:before {
    content: "⚙️";
    margin-right: 8px;
    font-size: 1.2em;
}

.settings-section > .description {
    color: #666;
    font-style: italic;
    margin: 0 0 20px 0;
    background: #f8f9fa;
    padding: 12px 16px;
    border-left: 4px solid #0073aa;
    border-radius: 4px;
}

.form-table th {
    padding: 15px 10px 15px 0;
    color: #1f2937;
    font-weight: 600;
    vertical-align: top;
    width: 200px;
}

.form-table td {
    padding: 15px 10px 15px 0;
}

.form-table label {
    font-weight: 500;
    color: #374151;
}

.form-table input[type="checkbox"] {
    margin-right: 8px;
}

.form-table .description {
    font-style: italic;
    color: #6b7280;
    margin-top: 8px;
    font-size: 0.9em;
}

.data-cleanup-info ul {
    list-style-type: none;
    padding-left: 0;
}

.data-cleanup-info li {
    margin-bottom: 5px;
    position: relative;
    padding-left: 20px;
}

.data-cleanup-info li:before {
    content: "⚠️";
    position: absolute;
    left: 0;
    font-size: 0.8em;
}
</style>
<?php
/**
 * Template for 2FA Login Form - Modular approach using provider registry
 *
 * Available variables:
 * - $user: User object
 * - $redirect_to: Redirect URL after login
 * - $error_msg: Error message to display
 * - $plugin_logo: URL of the plugin logo
 * - $rememberme: Remember me value
 * - $nonce: Security nonce
 * Legacy variables (for backward compatibility):
 * - $user_has_telegram, $user_has_email, $user_has_totp
 * - $default_method
 * - Provider objects
 */

use Authpress\AuthPress_Provider_Registry;
use Authpress\AuthPress_User_Manager;

if (!defined('ABSPATH')) {
    exit;
}

// Call login_header() to display WordPress login page header
login_header();

if (!empty($error_msg)) {
    echo '<div id="login_error"><strong>' . esc_html($error_msg) . '</strong><br /></div>';
}
?>

<style>
    body.login div#login h1 a {
        background-image: url("<?php echo esc_url($plugin_logo); ?>");
    }

    /* Method Selector Dropdown Styles */
    .method-selector-wrapper {
        margin-bottom: 25px;
        text-align: center;
    }

    .method-label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #50575e;
    }

    .method-dropdown-container {
        position: relative;
        max-width: 320px;
        margin: 0 auto;
    }

    .method-dropdown {
        width: 100%;
        padding: 12px 40px 12px 16px;
        font-size: 16px;
        font-weight: 500;
        color: #32373c;
        background: #fff;
        border: 2px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        transition: all 0.3s ease;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    .method-dropdown:hover {
        border-color: #0073aa;
        box-shadow: 0 3px 8px rgba(0, 115, 170, 0.15);
    }

    .method-dropdown:focus {
        outline: none;
        border-color: #005177;
        box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2);
    }

    .dropdown-arrow {
        position: absolute;
        top: 50%;
        right: 16px;
        transform: translateY(-50%);
        font-size: 12px;
        color: #50575e;
        pointer-events: none;
        transition: transform 0.3s ease;
    }

    .method-dropdown:focus + .dropdown-arrow,
    .method-dropdown:hover + .dropdown-arrow {
        transform: translateY(-50%) rotate(180deg);
        color: #0073aa;
    }

    /* Style for dropdown options */
    .method-dropdown option {
        padding: 10px;
        font-size: 16px;
        background: #fff;
    }

    /* Animation for login sections */
    .login-section {
        display: none;
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.3s ease;
    }

    .login-section.active {
        display: block;
        opacity: 1;
        transform: translateY(0);
    }

    /* Enhanced notice styling */
    .notice {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 16px;
        border-left: 4px solid #0073aa;
    }

    .notice-info {
        background: #f0f8ff;
        color: #0073aa;
    }

    .login-section {
        display: none;
    }

    .login-section.active {
        display: block;
    }
</style>

<form name="validate_authpress" id="loginform"
      action="<?php echo esc_url(site_url('wp-login.php?action=validate_authpress', 'login_post')); ?>" method="post"
      autocomplete="off">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp2fa_telegram_auth_nonce_' . $user->ID); ?>">
    <input type="hidden" name="wp2fa_telegram_auth_nonce"
           value="<?php echo wp_create_nonce('wp2fa_telegram_auth_nonce_' . $user->ID); ?>">
    <input type="hidden" name="wp-auth-id" id="wp-auth-id" value="<?php echo esc_attr($user->ID); ?>"/>
    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>"/>
    <input type="hidden" name="rememberme" id="rememberme" value="<?php echo esc_attr($rememberme); ?>"/>

    <!-- Hidden input to track which form is being used -->
    <input type="hidden" name="login_method" id="login_method" value="<?php echo esc_attr($default_method); ?>">

    <?php
    // Get user available methods using the modular system
    $user_available_methods = AuthPress_User_Manager::get_user_available_methods($user->ID);
    $available_count = array_sum($user_available_methods);
    $user_default_method = AuthPress_User_Manager::get_user_effective_provider($user->ID);
    $enabled_providers = AuthPress_Provider_Registry::get_enabled();
    ?>

    <?php
    $context = array(
            'available_count' => $available_count,
            'enabled_providers' => $enabled_providers,
            'user_available_methods' => $user_available_methods,
            'default_method' => $default_method
    );

    $provider_selector_html = authpress_get_template('templates/provider-selector.php', $context);

    echo apply_filters('authpress_provider_selector_html', $provider_selector_html, $available_count, $enabled_providers, $user_available_methods, $default_method);

    foreach ($enabled_providers as $provider_key => $provider):

        $provider_sections_disabled = apply_filters('authpress_provider_login_section_disabled', []);

        if (in_array($provider_key, $provider_sections_disabled)) continue;

        if (!isset($user_available_methods[$provider_key]) || !$user_available_methods[$provider_key]) continue;

        $is_active = ($default_method === $provider_key) ? 'active' : '';

        $context = [
                'provider_key' => $provider_key,
                'provider' => $provider,
                'user_available_methods' => $user_available_methods,
                'enabled_providers' => $enabled_providers,
                'default_method' => $default_method,
                'is_active' => $is_active
        ];

        $authpress_provider_login_section = authpress_get_template(
                $provider->get_login_template_path(),
                $context,
                true
        );

        ?>
        <div id="<?php echo esc_attr($provider_key); ?>-login-section" class="login-section <?php echo $is_active; ?>">
            <?php
            echo apply_filters(
                    'authpress_provider_login_section',
                    $authpress_provider_login_section,
                    $provider_key,
                    $provider,
                    $user_available_methods,
                    $enabled_providers,
                    $default_method
            );
            ?>
        </div>
    <?php

    endforeach;
    ?>

    <!-- Recovery Login Section -->
    <div id="recovery_codes-login-section" class="login-section">
        <p class="notice notice-info">
            <?php _e("Enter one of your recovery codes.", "two-factor-login-telegram"); ?>
        </p>

        <p>
            <label for="recovery_code" style="padding-top:1em">
                <?php _e("Recovery code:", "two-factor-login-telegram"); ?>
            </label>
            <input type="text" name="recovery_code" id="recovery_code" class="input" value="" size="12" placeholder="XXXX-XXXX-XX"/>
        </p>
    </div>

    <p class="submit">
        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large"
               value="<?php esc_attr_e('Login', 'two-factor-login-telegram'); ?>"/>
        <input type="button" id="use-recovery-code" class="button button-secondary"
               value="<?php esc_attr_e('Use Recovery Code', 'two-factor-login-telegram'); ?>"
               style="margin-left: 10px;"/>
    </p>
</form>

<p id="backtoblog">
    <a href="<?php echo esc_url(home_url('/')); ?>"
       title="<?php esc_attr_e("Are you lost?", "two-factor-login-telegram"); ?>">
        <?php echo sprintf(__('&larr; Back to %s', 'two-factor-login-telegram'), get_bloginfo('title', 'display')); ?>
    </a>
</p>

<?php
do_action("authpress_login_footer", $user_default_method, $user_available_methods, $enabled_providers);
?>

<script type="text/javascript">
    // Configuration for AuthPress login form JavaScript modules
    window.authpressConfig = {
        defaultMethod: '<?php echo esc_js($default_method); ?>',
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
        expireSeconds: <?php echo WP_FACTOR_AUTHCODE_EXPIRE_SECONDS; ?>,
        // Text strings
        useRecoveryText: '<?php esc_attr_e('Use Recovery Code', 'two-factor-login-telegram'); ?>',
        backTo2FAText: '<?php esc_attr_e('Back to 2FA', 'two-factor-login-telegram'); ?>',
        sendingTelegramCode: '⏳ <?php echo esc_js(__('Sending Telegram code...', 'two-factor-login-telegram')); ?>',
        telegramCodeSent: '✅ <?php echo esc_js(__('Telegram code sent! Check your phone.', 'two-factor-login-telegram')); ?>',
        sendingEmailCode: '⏳ <?php echo esc_js(__('Sending email code...', 'two-factor-login-telegram')); ?>',
        emailCodeSent: '✅ <?php echo esc_js(__('Email code sent! Check your inbox.', 'two-factor-login-telegram')); ?>',
        errorSendingCode: '❌ <?php echo esc_js(__('Error sending code. Please try again.', 'two-factor-login-telegram')); ?>',
        preparingAuth: '🔐 Preparing authentication...',
        passkeyNotAvailable: '❌ <?php echo esc_js(__('Passkey authentication not available', 'two-factor-login-telegram')); ?>',
        codeExpiredMessage: '<?php echo esc_js(__('The verification code has expired. Please request a new code to login.', 'two-factor-login-telegram')); ?>'
    };
</script>

<?php
do_action('login_footer');
?>
<div class="clear"></div>
</body>
</html>

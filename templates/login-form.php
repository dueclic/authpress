<?php
/**
 * Template for 2FA Login Form
 *
 * Available variables:
 * - $user: User object
 * - $redirect_to: Redirect URL after login
 * - $error_msg: Error message to display
 * - $plugin_logo: URL of the plugin logo
 * - $rememberme: Remember me value
 * - $nonce: Security nonce
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get user's available 2FA methods
$has_telegram = get_the_author_meta("tg_wp_factor_enabled", $user->ID) === "1";
$chat_id = get_user_meta($user->ID, "tg_wp_factor_chat_id", true);
$telegram_configured = !empty($chat_id);

$totp = WP_Factor_Auth_Factory::create(WP_Factor_Auth_Factory::METHOD_TOTP);
$totp_enabled = $totp->is_user_totp_enabled($user->ID);
$totp_secret = $totp->get_user_secret($user->ID);
$totp_configured = $totp_enabled && !empty($totp_secret);

// Get providers settings
$providers = get_option('wp_factor_providers', array());
$authenticator_available = isset($providers['authenticator']['enabled']) ? $providers['authenticator']['enabled'] : false;
$telegram_available = isset($providers['telegram']['enabled']) ? $providers['telegram']['enabled'] : false;

// Check which methods are actually available for this user
$user_has_telegram = $has_telegram && $telegram_configured && $telegram_available;
$user_has_totp = $totp_configured && $authenticator_available;

// Determine default method
$default_method = 'telegram';
if (!$user_has_telegram && $user_has_totp) {
    $default_method = 'totp';
} elseif ($user_has_telegram && !$user_has_totp) {
    $default_method = 'telegram';
} elseif ($user_has_telegram && $user_has_totp) {
    $default_method = 'telegram'; // Default to telegram if both available
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
    
    .method-selector {
        margin-bottom: 20px;
        text-align: center;
    }
    
    .method-button {
        display: inline-block;
        margin: 0 10px;
        padding: 10px 20px;
        border: 2px solid #ddd;
        border-radius: 5px;
        background: #f9f9f9;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .method-button.active {
        border-color: #0073aa;
        background: #0073aa;
        color: white;
    }
    
    .method-button:hover {
        border-color: #0073aa;
        background: #0073aa;
        color: white;
    }
    
    .method-button.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .method-button.disabled:hover {
        border-color: #ddd;
        background: #f9f9f9;
        color: inherit;
    }
    
    .login-section {
        display: none;
    }
    
    .login-section.active {
        display: block;
    }
</style>

<form name="validate_tg" id="loginform" action="<?php echo esc_url(site_url('wp-login.php?action=validate_tg', 'login_post')); ?>" method="post" autocomplete="off">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp2fa_telegram_auth_nonce_' . $user->ID); ?>">
    <input type="hidden" name="wp-auth-id" id="wp-auth-id" value="<?php echo esc_attr($user->ID); ?>"/>
    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>"/>
    <input type="hidden" name="rememberme" id="rememberme" value="<?php echo esc_attr($rememberme); ?>"/>

    <!-- Hidden input to track which form is being used -->
    <input type="hidden" name="login_method" id="login_method" value="<?php echo esc_attr($default_method); ?>">

    <?php if ($user_has_telegram && $user_has_totp): ?>
        <!-- Method Selector -->
        <div class="method-selector">
            <div class="method-button <?php echo $default_method === 'telegram' ? 'active' : ''; ?>" data-method="telegram">
                📱 <?php _e("Telegram", "two-factor-login-telegram"); ?>
            </div>
            <div class="method-button <?php echo $default_method === 'totp' ? 'active' : ''; ?>" data-method="totp">
                🔐 <?php _e("Authenticator", "two-factor-login-telegram"); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Telegram Login Section -->
    <div id="telegram-login-section" class="login-section <?php echo $default_method === 'telegram' ? 'active' : ''; ?>">
        <p class="notice notice-info">
            <?php _e("Enter the code sent to your Telegram account.", "two-factor-login-telegram"); ?>
        </p>

        <p>
            <label for="authcode" style="padding-top:1em">
                <?php _e("Authentication code:", "two-factor-login-telegram"); ?>
            </label>
            <input type="text" name="authcode" id="authcode" class="input" value="" size="5"/>
        </p>
    </div>

    <!-- TOTP Login Section -->
    <div id="totp-login-section" class="login-section <?php echo $default_method === 'totp' ? 'active' : ''; ?>">
        <p class="notice notice-info">
            <?php _e("Enter the 6-digit code from your authenticator app.", "two-factor-login-telegram"); ?>
        </p>

        <p>
            <label for="totp_code" style="padding-top:1em">
                <?php _e("Authenticator code:", "two-factor-login-telegram"); ?>
            </label>
            <input type="text" name="totp_code" id="totp_code" class="input" value="" size="6" maxlength="6" placeholder="123456"/>
        </p>
    </div>

    <!-- Recovery Login Section -->
    <div id="recovery-login-section" class="login-section">
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
        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Login', 'two-factor-login-telegram'); ?>" />
        <input type="button" id="use-recovery-code" class="button button-secondary" value="<?php esc_attr_e('Use Recovery Code', 'two-factor-login-telegram'); ?>" style="margin-left: 10px;" />
    </p>
</form>

<p id="backtoblog">
    <a href="<?php echo esc_url(home_url('/')); ?>" title="<?php esc_attr_e("Are you lost?", "two-factor-login-telegram"); ?>">
        <?php echo sprintf(__('&larr; Back to %s', 'two-factor-login-telegram'), get_bloginfo('title', 'display')); ?>
    </a>
</p>

<script type="text/javascript">
// Handle login method switching
document.addEventListener('DOMContentLoaded', function() {
    var loginMethodInput = document.getElementById('login_method');
    var useRecoveryButton = document.getElementById('use-recovery-code');
    var telegramSection = document.getElementById('telegram-login-section');
    var totpSection = document.getElementById('totp-login-section');
    var recoverySection = document.getElementById('recovery-login-section');
    var authcodeInput = document.getElementById('authcode');
    var totpInput = document.getElementById('totp_code');
    var recoveryInput = document.getElementById('recovery_code');
    var loginButton = document.getElementById('wp-submit');
    var methodButtons = document.querySelectorAll('.method-button');

    // Handle method button clicks
    methodButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var method = this.getAttribute('data-method');
            
            // Update active button
            methodButtons.forEach(function(btn) {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Update hidden input
            loginMethodInput.value = method;
            
            // Show/hide sections
            telegramSection.classList.remove('active');
            totpSection.classList.remove('active');
            recoverySection.classList.remove('active');
            
            if (method === 'telegram') {
                telegramSection.classList.add('active');
                authcodeInput.focus();
            } else if (method === 'totp') {
                totpSection.classList.add('active');
                totpInput.focus();
            }
        });
    });

    // Handle "Use Recovery Code" button click
    useRecoveryButton.addEventListener('click', function() {
        if (loginMethodInput.value === 'recovery') {
            // Switch back to default method
            var defaultMethod = '<?php echo esc_js($default_method); ?>';
            loginMethodInput.value = defaultMethod;
            
            // Update button states
            methodButtons.forEach(function(btn) {
                btn.classList.remove('active');
                if (btn.getAttribute('data-method') === defaultMethod) {
                    btn.classList.add('active');
                }
            });
            
            // Show appropriate section
            telegramSection.classList.remove('active');
            totpSection.classList.remove('active');
            recoverySection.classList.remove('active');
            
            if (defaultMethod === 'telegram') {
                telegramSection.classList.add('active');
                useRecoveryButton.value = '<?php esc_attr_e('Use Recovery Code', 'two-factor-login-telegram'); ?>';
                recoveryInput.value = '';
                authcodeInput.focus();
            } else if (defaultMethod === 'totp') {
                totpSection.classList.add('active');
                useRecoveryButton.value = '<?php esc_attr_e('Use Recovery Code', 'two-factor-login-telegram'); ?>';
                recoveryInput.value = '';
                totpInput.focus();
            }
        } else {
            // Switch to recovery mode
            loginMethodInput.value = 'recovery';
            telegramSection.classList.remove('active');
            totpSection.classList.remove('active');
            recoverySection.classList.add('active');
            
            // Update button states
            methodButtons.forEach(function(btn) {
                btn.classList.remove('active');
            });
            
            useRecoveryButton.value = '<?php esc_attr_e('Back to 2FA', 'two-factor-login-telegram'); ?>';
            authcodeInput.value = '';
            totpInput.value = '';
            recoveryInput.focus();
        }
    });

    // Focus on appropriate input based on default method
    var defaultMethod = '<?php echo esc_js($default_method); ?>';
    if (defaultMethod === 'telegram') {
        authcodeInput.focus();
    } else if (defaultMethod === 'totp') {
        totpInput.focus();
    }
});

// Auto-expire token after timeout period (only for Telegram method)
setTimeout(function() {
    var loginMethodInput = document.getElementById('login_method');
    if (loginMethodInput && loginMethodInput.value === 'telegram') {
        var errorDiv = document.getElementById('login_error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'login_error';
            var loginForm = document.getElementById('loginform');
            if (loginForm) {
                loginForm.parentNode.insertBefore(errorDiv, loginForm);
            }
        }
        errorDiv.innerHTML = '<strong><?php echo esc_js(__('The verification code has expired. Please request a new code to login.', 'two-factor-login-telegram')); ?></strong><br />';
    }
}, <?php echo WP_FACTOR_AUTHCODE_EXPIRE_SECONDS * 1000; ?>);
</script>

<?php
do_action('login_footer');
?>
<div class="clear"></div>
</body>
</html>

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
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: all 0.3s ease;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    .method-dropdown:hover {
        border-color: #0073aa;
        box-shadow: 0 3px 8px rgba(0,115,170,0.15);
    }

    .method-dropdown:focus {
        outline: none;
        border-color: #005177;
        box-shadow: 0 0 0 2px rgba(0,115,170,0.2);
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

<form name="validate_authpress" id="loginform" action="<?php echo esc_url(site_url('wp-login.php?action=validate_authpress', 'login_post')); ?>" method="post" autocomplete="off">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp2fa_telegram_auth_nonce_' . $user->ID); ?>">
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
    ?>
    
    <?php if ($available_count > 1): ?>
        <!-- Method Selector Dropdown -->
        <div class="method-selector-wrapper">
            <label for="method-dropdown" class="method-label">
                <?php _e("Choose your verification method:", "two-factor-login-telegram"); ?>
            </label>
            <div class="method-dropdown-container">
                <select id="method-dropdown" class="method-dropdown">
                    <?php 
                    $enabled_providers = AuthPress_Provider_Registry::get_enabled();
                    foreach ($enabled_providers as $key => $provider):
                        // Map keys for backward compatibility
                        $method_key = ($key === 'authenticator') ? 'totp' : $key;
                        
                        // Skip recovery codes in dropdown
                        if ($key === 'recovery_codes') continue;
                        
                        // Check if user has this method available
                        // Handle both hardcoded providers and external providers
                        $available_key = ($key === 'authenticator') ? 'totp' : $key;
                        if (!isset($user_available_methods[$available_key]) || !$user_available_methods[$available_key]) continue;
                    ?>
                        <option value="<?php echo esc_attr($method_key); ?>" 
                                <?php echo $user_default_method === $method_key ? 'selected' : ''; ?> 
                                data-icon="<?php echo esc_url($provider->get_icon()); ?>">
                            <?php echo esc_html($provider->get_name()); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="dropdown-arrow">▼</div>
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

    <!-- Email Login Section -->
    <div id="email-login-section" class="login-section <?php echo $default_method === 'email' ? 'active' : ''; ?>">
        <p class="notice notice-info">
            <?php _e("Enter the code sent to your email address.", "two-factor-login-telegram"); ?>
        </p>

        <p>
            <label for="email_code" style="padding-top:1em">
                <?php _e("Email code:", "two-factor-login-telegram"); ?>
            </label>
            <input type="text" name="email_code" id="email_code" class="input" value="" size="6" maxlength="6" placeholder="123456"/>
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

    <!-- Dynamic sections for external providers -->
    <?php 
    foreach ($enabled_providers as $key => $provider):
        if (in_array($key, ['telegram', 'email', 'authenticator', 'recovery_codes'])) continue;
        
        $available_key = $key;
        if (!isset($user_available_methods[$available_key]) || !$user_available_methods[$available_key]) continue;
        
        $method_key = $key;
        $is_active = ($default_method === $method_key) ? 'active' : '';
    ?>
        <div id="<?php echo esc_attr($key); ?>-login-section" class="login-section <?php echo $is_active; ?>">
            <p class="notice notice-info">
                <?php echo sprintf(__("Enter the code sent via %s.", "two-factor-login-telegram"), $provider->get_name()); ?>
            </p>

            <p>
                <label for="<?php echo esc_attr($key); ?>_code" style="padding-top:1em">
                    <?php echo sprintf(__("%s code:", "two-factor-login-telegram"), $provider->get_name()); ?>
                </label>
                <input type="text" name="<?php echo esc_attr($key); ?>_code" id="<?php echo esc_attr($key); ?>_code" class="input" value="" size="6" maxlength="6" placeholder="123456"/>
            </p>
        </div>
    <?php endforeach; ?>

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
    var emailSection = document.getElementById('email-login-section');
    var totpSection = document.getElementById('totp-login-section');
    var recoverySection = document.getElementById('recovery-login-section');
    var authcodeInput = document.getElementById('authcode');
    var emailInput = document.getElementById('email_code');
    var totpInput = document.getElementById('totp_code');
    var recoveryInput = document.getElementById('recovery_code');
    var loginButton = document.getElementById('wp-submit');
    var methodDropdown = document.getElementById('method-dropdown');

    // Handle dropdown selection change
    if (methodDropdown) {
        methodDropdown.addEventListener('change', function() {
            var method = this.value;

            // Update hidden input
            loginMethodInput.value = method;

            // Hide all sections
            document.querySelectorAll('.login-section').forEach(function(section) {
                section.classList.remove('active');
            });

            // Small delay to allow for smooth transition
            setTimeout(function() {
                var targetSection = document.getElementById(method + '-login-section');
                if (targetSection) {
                    targetSection.classList.add('active');
                    
                    // Focus on the appropriate input
                    var input = targetSection.querySelector('input[type="text"]');
                    if (input) {
                        setTimeout(function() { input.focus(); }, 100);
                    }
                }

                // Send codes for methods that need them when switching methods
                if (method !== '<?php echo esc_js($default_method); ?>') {
                    if (method === 'telegram') {
                        sendTelegramCode();
                    } else if (method === 'email') {
                        sendEmailCode();
                    } else {
                        // Handle external providers that support code sending
                        sendExternalProviderCode(method);
                    }
                }
            }, 150);
        });
    }

    // Handle "Use Recovery Code" button click
    useRecoveryButton.addEventListener('click', function() {
        if (loginMethodInput.value === 'recovery') {
            // Switch back to default method
            var defaultMethod = '<?php echo esc_js($default_method); ?>';
            loginMethodInput.value = defaultMethod;

            // Update dropdown selection
            if (methodDropdown) {
                methodDropdown.value = defaultMethod;
            }

            // Show appropriate section
            telegramSection.classList.remove('active');
            emailSection.classList.remove('active');
            totpSection.classList.remove('active');
            recoverySection.classList.remove('active');

            if (defaultMethod === 'telegram') {
                telegramSection.classList.add('active');
                useRecoveryButton.value = '<?php esc_attr_e('Use Recovery Code', 'two-factor-login-telegram'); ?>';
                recoveryInput.value = '';
                authcodeInput.focus();
            } else if (defaultMethod === 'email') {
                emailSection.classList.add('active');
                useRecoveryButton.value = '<?php esc_attr_e('Use Recovery Code', 'two-factor-login-telegram'); ?>';
                recoveryInput.value = '';
                emailInput.focus();
            } else if (defaultMethod === 'totp') {
                totpSection.classList.add('active');
                useRecoveryButton.value = '<?php esc_attr_e('Use Recovery Code', 'two-factor-login-telegram'); ?>';
                recoveryInput.value = '';
                totpInput.focus();
            }

            // Show dropdown if it exists
            if (methodDropdown && methodDropdown.parentElement.parentElement) {
                methodDropdown.parentElement.parentElement.style.display = 'block';
            }
        } else {
            // Switch to recovery mode
            loginMethodInput.value = 'recovery';
            telegramSection.classList.remove('active');
            emailSection.classList.remove('active');
            totpSection.classList.remove('active');
            recoverySection.classList.add('active');

            // Hide dropdown when in recovery mode
            if (methodDropdown && methodDropdown.parentElement.parentElement) {
                methodDropdown.parentElement.parentElement.style.display = 'none';
            }

            useRecoveryButton.value = '<?php esc_attr_e('Back to 2FA', 'two-factor-login-telegram'); ?>';
            authcodeInput.value = '';
            emailInput.value = '';
            totpInput.value = '';
            recoveryInput.focus();
        }
    });

    // Focus on appropriate input based on default method
    var defaultMethod = '<?php echo esc_js($default_method); ?>';
    if (defaultMethod === 'telegram') {
        authcodeInput.focus();
    } else if (defaultMethod === 'email') {
        emailInput.focus();
    } else if (defaultMethod === 'totp') {
        totpInput.focus();
    }
});

// Function to send Telegram code via AJAX
function sendTelegramCode() {
    var userId = document.getElementById('wp-auth-id').value;
    var nonce = document.querySelector('input[name="nonce"]').value;

    // Show loading message
    var telegramSection = document.getElementById('telegram-login-section');
    var noticeElement = telegramSection.querySelector('.notice');
    var originalNoticeText = noticeElement.innerHTML;
    noticeElement.innerHTML = '⏳ <?php echo esc_js(__('Sending Telegram code...', 'two-factor-login-telegram')); ?>';

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=send_login_telegram_code&user_id=' + encodeURIComponent(userId) + '&nonce=' + encodeURIComponent(nonce)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            noticeElement.innerHTML = '✅ <?php echo esc_js(__('Telegram code sent! Check your phone.', 'two-factor-login-telegram')); ?>';
        } else {
            noticeElement.innerHTML = '❌ <?php echo esc_js(__('Error sending code. Please try again.', 'two-factor-login-telegram')); ?>';
        }
    })
    .catch(error => {
        noticeElement.innerHTML = '❌ <?php echo esc_js(__('Error sending code. Please try again.', 'two-factor-login-telegram')); ?>';
    });
}

// Function to send Email code via AJAX
function sendEmailCode() {
    var userId = document.getElementById('wp-auth-id').value;
    var nonce = document.querySelector('input[name="nonce"]').value;

    // Show loading message
    var emailSection = document.getElementById('email-login-section');
    var noticeElement = emailSection.querySelector('.notice');
    var originalNoticeText = noticeElement.innerHTML;
    noticeElement.innerHTML = '⏳ <?php echo esc_js(__('Sending email code...', 'two-factor-login-telegram')); ?>';

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=send_login_email_code&user_id=' + encodeURIComponent(userId) + '&nonce=' + encodeURIComponent(nonce)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            noticeElement.innerHTML = '✅ <?php echo esc_js(__('Email code sent! Check your inbox.', 'two-factor-login-telegram')); ?>';
        } else {
            noticeElement.innerHTML = '❌ <?php echo esc_js(__('Error sending code. Please try again.', 'two-factor-login-telegram')); ?>';
        }
    })
    .catch(error => {
        noticeElement.innerHTML = '❌ <?php echo esc_js(__('Error sending code. Please try again.', 'two-factor-login-telegram')); ?>';
    });
}

// Function to send code for external providers
function sendExternalProviderCode(method) {
    // External providers should implement their own code sending logic
    // This is a generic function that will be called for external methods
    var userId = document.getElementById('wp-auth-id').value;
    var nonce = document.querySelector('input[name="nonce"]').value;

    // Find the method section
    var methodSection = document.getElementById(method + '-login-section');
    if (methodSection) {
        var noticeElement = methodSection.querySelector('.notice');
        if (noticeElement) {
            noticeElement.innerHTML = '⏳ Sending ' + method + ' code...';
        }

        // Make a generic AJAX call for external providers
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=send_login_' + method + '_code&user_id=' + encodeURIComponent(userId) + '&nonce=' + encodeURIComponent(nonce)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && noticeElement) {
                noticeElement.innerHTML = '✅ Code sent! Check your device.';
            } else if (noticeElement) {
                noticeElement.innerHTML = '❌ Error sending code. Please try again.';
            }
        })
        .catch(error => {
            if (noticeElement) {
                noticeElement.innerHTML = '❌ Error sending code. Please try again.';
            }
        });
    }
}

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

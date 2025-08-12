<?php
/**
 * Template for 2FA Setup Wizard
 *
 * Available variables:
 * - $user: User object
 * - $redirect_to: Redirect URL after setup
 * - $plugin_logo: URL of the plugin logo
 * - $telegram_available: Whether Telegram provider is available
 * - $email_available: Whether Email provider is available
 * - $authenticator_enabled: Whether Authenticator provider is available
 * - $telegram_bot: Bot info for Telegram setup
 */

if (!defined('ABSPATH')) {
    exit;
}

// Call login_header() to display WordPress login page header
login_header(__('2FA Setup', 'two-factor-login-telegram'), '', '');
?>

<style>

    #login {
        width: 600px !important;
    }

    body.login div#login h1 a {
        background-image: url("<?php echo esc_url($plugin_logo); ?>");
    }

    .authpress-wizard {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 30px;
        max-width: 600px;
        margin: 0 auto 20px;
    }

    .wizard-header {
        text-align: center;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid #007cba;
    }

    .wizard-title {
        font-size: 24px;
        font-weight: 600;
        color: #23282d;
        margin: 0 0 10px 0;
    }

    .wizard-subtitle {
        color: #666;
        font-size: 16px;
        margin: 0;
        line-height: 1.4;
    }

    .wizard-content {
        margin-bottom: 25px;
    }

    .method-options {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin: 20px 0;
    }

    .method-option {
        position: relative;
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        background: #fff;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .method-option:hover {
        border-color: #007cba;
        background: #f8fafc;
    }

    .method-option.selected {
        border-color: #007cba;
        background: #e8f4f8;
    }

    .method-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .method-icon {
        font-size: 24px;
        margin-bottom: 8px;
        display: block;
    }

    .method-name {
        font-size: 18px;
        font-weight: 600;
        color: #23282d;
        margin: 0 0 5px 0;
    }

    .method-description {
        color: #666;
        font-size: 14px;
        margin: 0;
        line-height: 1.4;
    }

    .method-pros {
        margin-top: 8px;
        color: #0073aa;
        font-size: 12px;
        font-weight: 500;
    }

    .wizard-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
    }

    .skip-link {
        color: #666;
        text-decoration: none;
        font-size: 14px;
        padding: 8px 12px;
        border-radius: 4px;
        transition: color 0.3s ease;
    }

    .skip-link:hover {
        color: #007cba;
        text-decoration: underline;
    }

    .continue-button {
        background: #007cba;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s ease;
        min-width: 120px;
    }

    .continue-button:hover {
        background: #005a87;
    }

    .continue-button:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .wizard-notice {
        background: #e8f4f8;
        border: 1px solid #007cba;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 20px;
        color: #0073aa;
        font-size: 14px;
        line-height: 1.5;
    }

    .wizard-notice .dashicons {
        margin-right: 8px;
        vertical-align: middle;
    }

    @media (max-width: 480px) {
        .authpress-wizard {
            padding: 20px;
            margin: 0 10px 20px;
        }

        .wizard-actions {
            flex-direction: column;
            gap: 15px;
        }

        .continue-button,
        .skip-link {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="authpress-wizard">
    <div class="wizard-header">
        <h1 class="wizard-title"><?php _e('Setup AuthPress', 'two-factor-login-telegram'); ?></h1>
        <p class="wizard-subtitle"><?php _e('Choose a method to secure your account with an additional layer of protection', 'two-factor-login-telegram'); ?></p>
    </div>

    <div class="wizard-notice">
        <span class="dashicons dashicons-shield-alt"></span>
        <?php _e('Two-factor authentication adds an extra layer of security to your account by requiring a second form of verification when you log in.', 'two-factor-login-telegram'); ?>
    </div>

    <form id="setup-wizard-form" method="post" action="<?php echo esc_url(site_url('wp-login.php?action=authpress_setup_wizard', 'login_post')); ?>">
        <?php wp_nonce_field('authpress_setup_wizard_' . $user->ID, 'setup_wizard_nonce'); ?>
        <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

        <div class="wizard-content">
            <div class="method-options">
                <?php if ($telegram_available): ?>
                    <label class="method-option" for="method-telegram">
                        <input type="radio" id="method-telegram" name="setup_method" value="telegram">
                        <span class="method-icon">
                            <img src="<?php echo esc_url($telegram_provider->get_icon()); ?>" alt="Telegram" style="width: 24px; height: 24px;" />
                        </span>
                        <h3 class="method-name"><?php _e('Telegram', 'two-factor-login-telegram'); ?></h3>
                        <p class="method-description">
                            <?php _e('Receive verification codes instantly on your phone via Telegram', 'two-factor-login-telegram'); ?>
                        </p>
                        <p class="method-pros">✓ <?php _e('Fast and convenient', 'two-factor-login-telegram'); ?></p>
                    </label>
                <?php endif; ?>

                <?php if ($email_available): ?>
                    <label class="method-option" for="method-email">
                        <input type="radio" id="method-email" name="setup_method" value="email">
                        <span class="method-icon">
                            <img src="<?php echo esc_url($email_provider->get_icon()); ?>" alt="Email" style="width: 24px; height: 24px;" />
                        </span>
                        <h3 class="method-name"><?php _e('Email', 'two-factor-login-telegram'); ?></h3>
                        <p class="method-description">
                            <?php _e('Receive verification codes via email', 'two-factor-login-telegram'); ?>
                        </p>
                        <p class="method-pros">✓ <?php _e('Works with any email client', 'two-factor-login-telegram'); ?></p>
                    </label>
                <?php endif; ?>

                <?php if ($authenticator_enabled): ?>
                    <label class="method-option" for="method-authenticator">
                        <input type="radio" id="method-authenticator" name="setup_method" value="authenticator">
                        <span class="method-icon">
                            <img src="<?php echo esc_url($totp_provider->get_icon()); ?>" alt="Authenticator" style="width: 24px; height: 24px;" />
                        </span>
                        <h3 class="method-name"><?php _e('Authenticator App', 'two-factor-login-telegram'); ?></h3>
                        <p class="method-description">
                            <?php _e('Use apps like Google Authenticator or Authy for offline code generation', 'two-factor-login-telegram'); ?>
                        </p>
                        <p class="method-pros">✓ <?php _e('Works without internet connection', 'two-factor-login-telegram'); ?></p>
                    </label>
                <?php endif; ?>
            </div>
        </div>

        <div class="wizard-actions">
            <a href="<?php echo esc_url(add_query_arg('authpress_skip_wizard', '1', wp_login_url($redirect_to))); ?>" class="skip-link">
                <?php _e('Skip for now', 'two-factor-login-telegram'); ?>
            </a>
            <button type="submit" class="continue-button" id="continue-btn" disabled>
                <?php _e('Continue', 'two-factor-login-telegram'); ?>
            </button>
        </div>
    </form>
</div>

<p id="backtoblog">
    <a href="<?php echo esc_url(home_url('/')); ?>" title="<?php esc_attr_e('Are you lost?', 'two-factor-login-telegram'); ?>">
        <?php echo sprintf(__('&larr; Back to %s', 'two-factor-login-telegram'), get_bloginfo('title', 'display')); ?>
    </a>
</p>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('setup-wizard-form');
    const continueBtn = document.getElementById('continue-btn');
    const methodOptions = document.querySelectorAll('.method-option');
    const radioInputs = document.querySelectorAll('input[name="setup_method"]');

    // Handle method selection
    radioInputs.forEach(function(radio) {
        radio.addEventListener('change', function() {
            // Update visual selection
            methodOptions.forEach(function(option) {
                option.classList.remove('selected');
            });

            if (this.checked) {
                this.closest('.method-option').classList.add('selected');
                continueBtn.disabled = false;
            }
        });
    });

    // Handle clicking on method option labels
    methodOptions.forEach(function(option) {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio && !radio.checked) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
            }
        });
    });

    // Form validation
    form.addEventListener('submit', function(e) {
        const selectedMethod = document.querySelector('input[name="setup_method"]:checked');
        if (!selectedMethod) {
            e.preventDefault();
            alert('<?php echo esc_js(__('Please select a 2FA method to continue.', 'two-factor-login-telegram')); ?>');
            return false;
        }
    });
});
</script>

<?php
do_action('login_footer');
?>
<div class="clear"></div>
</body>
</html>

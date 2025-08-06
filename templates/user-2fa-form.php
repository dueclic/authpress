<?php
/**
 * Template for user 2FA form
 */

// Get enabled providers
$providers = get_option('wp_factor_providers', array(
    'authenticator' => array('enabled' => false),
    'telegram' => array('enabled' => false, 'bot_token' => '', 'failed_login_reports' => false)
));

// Helper function to check if bot token is valid
function is_bot_token_valid($bot_token)
{
    if (empty($bot_token)) {
        return false;
    }

    // Check if token has the expected Telegram bot token format
    // Telegram bot tokens are typically in format: 123456789:ABCdefGHIjklMNOpqrsTUVwxyz
    return preg_match('/^\d+:[A-Za-z0-9_-]+$/', $bot_token);
}

$is_enabled = esc_attr(get_the_author_meta('tg_wp_factor_enabled', $user->ID)) === "1";
$chat_id = esc_attr(get_the_author_meta('tg_wp_factor_chat_id', $user->ID));
$is_configured = !empty($chat_id);


// Check which providers are available
$telegram_available = $providers['telegram']['enabled'] && is_bot_token_valid($providers['telegram']['bot_token']);

$has_any_2fa = $is_configured;
$has_available_providers = $telegram_available;
?>

<h3 id="wptl"><?php _e('AuthPress', 'two-factor-login-telegram'); ?></h3>

<?php if (!$has_available_providers): ?>
    <div class="notice notice-warning">
        <p>
            <span class="dashicons dashicons-warning"></span>
            <?php _e('No 2FA providers are currently enabled. Please contact your administrator to enable at least one provider.', 'two-factor-login-telegram'); ?>
        </p>
    </div>
<?php else: ?>

    <table class="form-table">
        <tr>
            <th>
                <label for="tg_wp_factor_enabled"><?php _e('Enable 2FA', 'two-factor-login-telegram'); ?></label>
            </th>
            <td colspan="2">
                <input type="hidden" name="tg_wp_factor_valid" id="tg_wp_factor_valid"
                    value="<?php echo (int) $is_enabled; ?>">
                <input type="checkbox" name="tg_wp_factor_enabled" id="tg_wp_factor_enabled" value="1" class="regular-text"
                    <?php echo checked($is_enabled, 1); ?>     <?php echo !$has_available_providers ? 'disabled' : ''; ?> />

                <?php if ($has_any_2fa && $is_enabled): ?>
                    <span class="tg-status success" style="display: inline-flex; margin-left: 10px;">
                        ✅ <?php _e('2FA is active', 'two-factor-login-telegram'); ?>
                        <?php
                        if ($is_configured && $telegram_available) {
                            echo '(' . __('Telegram', 'two-factor-login-telegram') . ')';
                        }
                        ?>
                    </span>
                <?php endif; ?>
            </td>
        </tr>
    </table>


    <!-- Available Providers Status -->
    <div class="providers-status-section" style="margin: 20px 0;">
        <h4><?php _e('Your 2FA Methods', 'two-factor-login-telegram'); ?></h4>

        <?php if ($telegram_available): ?>
            <div class="provider-status-card <?php echo $is_configured ? 'configured' : 'not-configured'; ?>">
                <div class="provider-header">
                    <span class="dashicons dashicons-format-chat"></span>
                    <h5><?php _e('Telegram', 'two-factor-login-telegram'); ?></h5>
                    <span class="status-badge <?php echo $is_configured ? 'active' : 'inactive'; ?>">
                        <?php echo $is_configured ? __('Active', 'two-factor-login-telegram') : __('Not Configured', 'two-factor-login-telegram'); ?>
                    </span>
                </div>
                <?php if ($is_configured): ?>
                    <div class="provider-details">
                        <p><strong><?php _e('Chat ID:', 'two-factor-login-telegram'); ?></strong> <?php echo esc_html($chat_id); ?>
                        </p>
                        <button type="button" class="button tg-edit-button" id="tg-edit-chat-id">
                            <?php _e('Change', 'two-factor-login-telegram'); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="provider-details">
                        <p><?php _e('Configure Telegram to receive authentication codes via messages.', 'two-factor-login-telegram'); ?>
                        </p>
                        <button type="button" class="button button-primary" id="setup-telegram-btn">
                            <?php _e('Setup Telegram', 'two-factor-login-telegram'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- 2FA Method Selection for new setup -->
    <?php if (!$has_any_2fa): ?>
        <div id="2fa-method-selection" style="margin: 20px 0;">
            <h4><?php _e('Choose your 2FA method:', 'two-factor-login-telegram'); ?></h4>
            <div style="display: flex; gap: 20px; margin: 15px 0;">
                <?php if ($telegram_available): ?>
                    <button type="button" class="button button-primary" id="setup-telegram-btn">
                        📱 <?php _e('Setup Telegram', 'two-factor-login-telegram'); ?>
                    </button>
                <?php endif; ?>
            </div>
            <p class="description">
                <?php _e('Configure Telegram to enable two-factor authentication.', 'two-factor-login-telegram'); ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Additional Methods Section -->
    <?php if ($has_any_2fa): ?>
        <hr style="margin:32px 0 18px 0;">
        <div class="additional-methods-section">

        </div>
    <?php endif; ?>


<?php endif; ?>

<!-- Telegram Configuration Section -->
<?php if ($telegram_available): ?>
    <div id="tg-2fa-configuration" style="display: none;">
        <table class="form-table">
            <tr>
                <td colspan="3">
                    <?php
                    $username = $this->telegram->get_me()->username;
                    ?>
                    <div class="tg-setup-steps">
                        <h4 style="margin-top: 0;"><?php _e('🚀 Setup Steps', 'two-factor-login-telegram'); ?></h4>
                        <ol>
                            <li>
                                <?php
                                printf(
                                    __(
                                        'Open a conversation with %s and press on <strong>Start</strong>',
                                        'two-factor-login-telegram'
                                    ),
                                    '<a href="https://telegram.me/' . $username
                                    . '" target="_blank">@' . $username . '</a>'
                                );
                                ?>
                            </li>
                            <li>
                                <?php
                                printf(
                                    __(
                                        'Type command %s to obtain your Chat ID.',
                                        "two-factor-login-telegram"
                                    ),
                                    '<code>/get_id</code>'
                                );
                                ?>
                            </li>
                            <li>
                                <?php
                                _e(
                                    "The bot will reply with your <strong>Chat ID</strong> number",
                                    'two-factor-login-telegram'
                                );
                                ?>
                            </li>
                            <li><?php
                            _e(
                                'Copy your Chat ID and paste it below, then press <strong>Submit code</strong>',
                                'two-factor-login-telegram'
                            ); ?></li>
                        </ol>
                    </div>
                    <div class="tg-progress">
                        <div class="tg-progress-bar" id="tg-progress-bar"></div>
                    </div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="tg_wp_factor_chat_id"><?php _e('Telegram Chat ID', 'two-factor-login-telegram'); ?></label>
                </th>
                <td>
                    <input type="text" name="tg_wp_factor_chat_id" id="tg_wp_factor_chat_id" value="<?php
                    echo esc_attr(get_the_author_meta('tg_wp_factor_chat_id', $user->ID)); ?>"
                        class="regular-text" /><br />
                    <span class="description"><?php _e('Put your Telegram Chat ID', 'two-factor-login-telegram'); ?></span>
                </td>
                <td>
                    <button class="tg-action-button" id="tg_wp_factor_chat_id_send"><?php
                    _e("Submit code", "two-factor-login-telegram"); ?></button>
                    <div id="chat-id-status" class="tg-status" style="display: none;"></div>
                </td>
            </tr>
            <tr id="factor-chat-confirm">
                <th>
                    <label
                        for="tg_wp_factor_chat_id_confirm"><?php _e('Confirmation code', 'two-factor-login-telegram'); ?></label>
                </th>
                <td>
                    <input type="text" name="tg_wp_factor_chat_id_confirm" id="tg_wp_factor_chat_id_confirm" value=""
                        class="regular-text" /><br />
                    <span
                        class="description"><?php _e('Please enter the confirmation code you received on Telegram', 'two-factor-login-telegram'); ?></span>
                </td>
                <td>
                    <button class="tg-action-button" id="tg_wp_factor_chat_id_check"><?php
                    _e("Validate", "two-factor-login-telegram"); ?></button>
                    <div id="validation-status" class="tg-status" style="display: none;"></div>
                </td>
            </tr>
            <tr id="factor-chat-response">
                <td colspan="3">
                    <div class="wpft-notice wpft-notice-warning">
                        <p></p>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <button type="button" class="button button-secondary back-to-method-selection"
                        style="margin-top: 10px;">
                        ← <?php _e('Back to Method Selection', 'two-factor-login-telegram'); ?>
                    </button>
                </td>
            </tr>
        </table>
    </div>
<?php endif; ?>


<?php if ($has_any_2fa && $is_enabled): ?>
    <hr style="margin:32px 0 18px 0;">
    <div id="tg-recovery-codes-section" style="margin-bottom:32px;">
        <h4 style="margin-top:0;">
            <?php _e('Recovery Codes', 'two-factor-login-telegram'); ?>
        </h4>
        <?php
        $recovery_codes_plain = isset($GLOBALS['tg_recovery_codes_plain']) ? $GLOBALS['tg_recovery_codes_plain'] : null;
        $recovery_codes = get_user_meta($user->ID, 'tg_wp_factor_recovery_codes', true);
        $just_regenerated = is_array($recovery_codes_plain) && count($recovery_codes_plain) > 0;
        ?>
        <?php if ($just_regenerated): ?>
            <div class="notice-warning" style="margin-bottom:16px;">
                <?php _e('These are your new Recovery Codes. Save them now: they won\'t be visible again!', 'two-factor-login-telegram'); ?>
            </div>
            <div class="recovery-codes-list" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px;">
                <?php foreach ($recovery_codes_plain as $code): ?>
                    <div class="recovery-code-box"
                        style="background:#f9f9f9;border:1px solid #e1e1e1;border-radius:4px;padding:10px 16px;font-family:monospace;font-size:1.1em;letter-spacing:2px;">
                        <?php echo esc_html($code); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="notice-info" style="margin-bottom:12px;">
                <?php _e('You have already generated Recovery Codes. You can regenerate them if needed, but the old ones will be invalidated.', 'two-factor-login-telegram'); ?>
            </div>
        <?php endif; ?>
        <div style="margin-top:10px;">
            <button type="button" class="button tg-action-button" id="tg-recovery-codes-btn"
                data-nonce="<?php echo wp_create_nonce('tg_regenerate_recovery_codes_' . $user->ID); ?>">
                <?php _e('Regenerate Recovery Codes', 'two-factor-login-telegram'); ?>
            </button>
            <span id="tg-recovery-spinner" style="display:none;margin-left:10px;vertical-align:middle;">⌛</span>
            <span id="tg-recovery-msg" style="margin-left:10px;color:#d00;"></span>
        </div>
    </div>
<?php endif; ?>

<script>
    jQuery(document).ready(function ($) {
        // 2FA Enable/Disable functionality
        var $enableCheckbox = $('#tg_wp_factor_enabled');
        var $providersStatus = $('.providers-status-section');
        var $methodSelection = $('#2fa-method-selection');
        var $additionalMethods = $('.additional-methods-section');

        // Show/hide 2FA sections based on checkbox state
        function toggle2FASections() {
            var isEnabled = $enableCheckbox.is(':checked');

            if (isEnabled) {
                $providersStatus.show();
                if ($methodSelection.length) {
                    $methodSelection.show();
                }
                if ($additionalMethods.length) {
                    $additionalMethods.show();
                }
            } else {
                $providersStatus.hide();
                $methodSelection.hide();
                $additionalMethods.hide();
                // Hide setup sections
                $('#tg-2fa-configuration').hide();
                $('#totp-setup-section').hide();
            }
        }

        // Initialize state - handled by wp-factor-telegram-plugin.js

        // Handle checkbox change - handled by wp-factor-telegram-plugin.js

        // Setup buttons functionality - handled by wp-factor-telegram-plugin.js
        // Back buttons functionality - handled by wp-factor-telegram-plugin.js

        // Edit buttons functionality - handled by wp-factor-telegram-plugin.js

        // Disable TOTP button - handled by wp-factor-telegram-plugin.js

        // Recovery codes functionality
        var $btn = $('#tg-recovery-codes-btn');
        var $spinner = $('#tg-recovery-spinner');
        var $msg = $('#tg-recovery-msg');

        $btn.on('click', function (e) {
            e.preventDefault();

            if (!confirm('<?php echo esc_js(__('Are you sure you want to regenerate Recovery Codes? The old ones will no longer be valid.', 'two-factor-login-telegram')); ?>')) return;

            $spinner.show();
            $msg.text('');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'regenerate_recovery_codes',
                    _wpnonce: $btn.data('nonce')
                },
                success: function (res) {
                    $spinner.hide();
                    if (res.success && res.data && res.data.html) {
                        openRecoveryCodesModal(null, window.location.href, res.data.html);
                    } else {
                        $msg.text(res.data && res.data.message ? res.data.message : '<?php echo esc_js(__('Error occurred', 'two-factor-login-telegram')); ?>');
                    }
                },
                error: function (xhr, status, error) {
                    $spinner.hide();
                    $msg.text('<?php echo esc_js(__('Network error', 'two-factor-login-telegram')); ?>: ' + error);
                }
            });
        });
    });
</script>


<style>
    .providers-status-section {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
    }

    .provider-status-card {
        border: 2px solid #e1e1e1;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background: #fff;
        transition: all 0.3s ease;
    }

    .provider-status-card.configured {
        border-color: #46b450;
        box-shadow: 0 2px 8px rgba(70, 180, 80, 0.1);
    }

    .provider-status-card.not-configured {
        border-color: #e1e1e1;
        opacity: 0.8;
    }

    .provider-header {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .provider-header .dashicons {
        font-size: 1.5em;
        margin-right: 10px;
        color: #0073aa;
    }

    .provider-header h5 {
        margin: 0;
        flex: 1;
        color: #23282d;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8em;
        font-weight: 500;
    }

    .status-badge.active {
        background: #dff0d8;
        color: #3c763d;
    }

    .status-badge.inactive {
        background: #f2dede;
        color: #a94442;
    }

    .provider-details {
        margin-top: 10px;
    }

    .provider-details p {
        margin: 0 0 10px 0;
        color: #666;
    }

    .provider-details button {
        margin-right: 10px;
    }
</style>
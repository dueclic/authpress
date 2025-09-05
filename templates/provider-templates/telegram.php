<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var $current_user_id int
 * @var $user_has_method boolean
 * @var $provider \AuthPress\Providers\Telegram_Provider
 * @var $user_config array
 */

$telegram_chat_id = $user_config['chat_id'] ?: '';
$username = $provider->telegram->get_me()->username;
$bot_link = '<a href="https://telegram.me/' . $username . '" target="_blank">@' . $username . '</a>';

?>

<div class="authpress-section">

    <?php if ($user_has_method): ?>

        <div class="authpress-actions">
            <button type="button" class="ap-button ap-button--primary" id="reconfigure-telegram">
                <?php _e('Change Settings', "two-factor-login-telegram"); ?>
            </button>
            <form method="post" action="" class="authpress-disable-form ap-form">
                <?php wp_nonce_field('authpress_disable_telegram', 'wp_factor_telegram_disable_nonce'); ?>
                <input type="hidden" name="authpress_action" value="disable_telegram">
            </form>
        </div>
    <?php else: ?>

        <div class="authpress-intro">
            <p class="ap-text"><?php _e('Configure Telegram to enable this 2FA method.', "two-factor-login-telegram"); ?></p>
        </div>

    <?php endif; ?>

    <div class="authpress-config" id="telegram-config-section">
        <?php
        require(dirname(AUTHPRESS_PLUGIN_FILE) . "/templates/provider-templates/telegram/setup-steps.php");
        ?>
    </div>

</div>

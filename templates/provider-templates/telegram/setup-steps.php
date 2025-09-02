<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var $bot_link string
 * @var $user_has_method boolean
 * @var $provider \AuthPress\Providers\Abstract_Provider
 * @var $user_config array
 * @var $provider_key string
 */

?>

<?php

function render_telegram_instructions($username_link, $is_reconfigure = false) {
    $first_step = $is_reconfigure ?
        __('Open a conversation with %s and make sure it\'s still active', "two-factor-login-telegram") :
        __('Open a conversation with %s and press on <strong>Start</strong>', "two-factor-login-telegram");

    if ($is_reconfigure) {
        echo '<p class="ap-text">' . __('Follow the steps below to change your Telegram Chat ID:', "two-factor-login-telegram") . '</p>';
    } else {
        echo '<h4 class="ap-heading">' . __('🚀 Setup Steps', "two-factor-login-telegram") . '</h4>';
    }
    ?>
    <ol class="tg-instructions-list">
        <li class="ap-text"><?php printf($first_step, $username_link); ?></li>
        <li class="ap-text"><?php printf(__('Type command %s to get your current Chat ID.', "two-factor-login-telegram"), '<code class="tg-code-style">/get_id</code>'); ?></li>
        <?php if (!$is_reconfigure): ?>
            <li class="ap-text"><?php _e("The bot will reply with your <strong>Chat ID</strong> number", "two-factor-login-telegram"); ?></li>
            <li class="ap-text"><?php _e('Copy your Chat ID and paste it below, then press <strong>Submit code</strong>', "two-factor-login-telegram"); ?></li>
        <?php else: ?>
            <li class="ap-text"><?php _e('Copy the new Chat ID and paste it below', "two-factor-login-telegram"); ?></li>
        <?php endif; ?>
    </ol>
    <?php
}

?>

<div class="authpress-setup">

    <div class="setup-steps">
        <?php render_telegram_instructions($bot_link); ?>
    </div>

    <div class="tg-progress">
        <div class="tg-progress-bar" id="tg-progress-bar"></div>
    </div>

    <div class="authpress-config">
        <div class="ap-form">
            <div class="ap-form__group">
                <label class="ap-label" for="authpress_telegram_chat_id"><?php _e('Telegram Chat ID', "two-factor-login-telegram"); ?></label>
                <p class="ap-text ap-text--small mb-8"><?php _e('Put your Telegram Chat ID', "two-factor-login-telegram"); ?></p>
                <div class="field-row">
                    <div class="input-container">
                        <input type="text" name="authpress_telegram_chat_id"
                               id="authpress_telegram_chat_id" value="<?php echo $telegram_chat_id; ?>"
                               class="ap-input"/>
                    </div>
                    <button type="button" class="ap-button ap-button--primary tg-action-button"
                            id="authpress_telegram_chat_id_send"><?php _e("Submit code", "two-factor-login-telegram"); ?></button>
                </div>
                <div id="chat-id-status" class="mt-8 tg-status" style="display: none;"></div>
            </div>

            <div class="ap-form__group" id="factor-chat-confirm" style="display: none;">
                <label class="ap-label" for="authpress_telegram_chat_id_confirm"><?php _e('Confirmation code', "two-factor-login-telegram"); ?></label>
                <p class="ap-text ap-text--small mb-8"><?php _e('Please enter the confirmation code you received on Telegram', "two-factor-login-telegram"); ?></p>
                <div class="field-row">
                    <div class="input-container">
                        <input type="text" name="authpress_telegram_chat_id_confirm"
                               id="authpress_telegram_chat_id_confirm" value=""
                               class="ap-input"/>
                    </div>
                    <button type="button" class="ap-button ap-button--primary tg-action-button"
                            id="authpress_telegram_chat_id_check"><?php _e("Validate", "two-factor-login-telegram"); ?></button>
                </div>
                <div id="validation-status" class="tg-status" style="display: none;"></div>
            </div>

            <div class="ap-form__group" id="factor-chat-response" style="display: none;">
                <div class="ap-notice ap-notice--warning">
                    <p></p>
                </div>
            </div>

            <div class="ap-form__group" id="factor-chat-save" style="display: none;">
                <form method="post" action="" class="authpress-save-form ap-form">
                    <?php wp_nonce_field('wp_factor_save_telegram', 'wp_factor_telegram_save_nonce'); ?>
                    <input type="hidden" name="authpress_action" value="save_telegram">
                    <input type="hidden" name="tg_chat_id" id="tg_chat_id_hidden" value="">
                    <div class="submit">
                        <button type="submit" class="ap-button ap-button--primary">
                            <?php _e('Save Telegram Configuration', "two-factor-login-telegram"); ?>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
    <div class="ap-form__group">
        <?php
        if ($user_has_method):
            ?>

            <button type="button" class="ap-button ap-button--secondary cancel-reconfigure" id="cancel-<?php echo esc_attr($provider_key); ?>-reconfigure">
                <?php _e('Cancel', "two-factor-login-telegram"); ?>
            </button>

        <?php
        endif;
        ?>
    </div>
</div>

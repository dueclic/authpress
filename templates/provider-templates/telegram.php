<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var $current_user_id int
 * @var $user_has_method boolean
 * @var $provider \AuthPress\Providers\Abstract_Provider
 * @var $user_config array
 */


$telegram_chat_id = $user_config['chat_id'] ?: '';
?>

<div class="authpress-section">

    <?php if ($user_has_method): ?>
        <div class="notice notice-success inline">
            <p>
                <?php _e('✅ Telegram is configured and active.', 'two-factor-login-telegram'); ?>
                <br>
                <strong><?php _e('Chat ID:', 'two-factor-login-telegram'); ?></strong> <?php echo esc_html($telegram_chat_id); ?>
            </p>
        </div>

        <div class="authpress-actions">
            <button type="button" class="button button-primary" id="reconfigure-telegram">
                <?php _e('Change Chat ID', 'two-factor-login-telegram'); ?>
            </button>

            <form method="post" action="" class="authpress-disable-form" style="display: inline-block; margin-left: 10px;">
                <?php wp_nonce_field('wp_factor_disable_telegram', 'wp_factor_telegram_disable_nonce'); ?>
                <input type="hidden" name="wp_factor_action" value="disable_telegram">
                <button type="submit" class="button button-secondary" onclick="return confirm('<?php _e('Are you sure you want to disable Telegram 2FA? This will remove your Chat ID and disable Telegram authentication.', 'two-factor-login-telegram'); ?>')">
                    <?php _e('Disable Telegram 2FA', 'two-factor-login-telegram'); ?>
                </button>
            </form>
        </div>

        <!-- Hidden reconfiguration section -->
        <div class="authpress-reconfig" id="telegram-reconfig-section" style="display: none; margin-top: 20px;">
            <h4><?php _e('Reconfigure Telegram', 'two-factor-login-telegram'); ?></h4>
            <p><?php _e('Follow the steps below to change your Telegram Chat ID:', 'two-factor-login-telegram'); ?></p>

            <div class="tg-setup-steps">
                <ol>
                    <li>
                        <?php
                        printf(__('Open a conversation with %s and make sure it\'s still active',
                                'two-factor-login-telegram'),
                                '<a href="https://telegram.me/' . $username
                                . '" target="_blank">@' . $username . '</a>');
                        ?>
                    </li>
                    <li>
                        <?php
                        printf(__('Type command %s to get your current Chat ID.',
                                "two-factor-login-telegram"),
                                '<code>/get_id</code>');
                        ?>
                    </li>
                    <li><?php _e('Copy the new Chat ID and paste it below', 'two-factor-login-telegram'); ?></li>
                </ol>
            </div>

            <table class="form-table">
                <tr>
                    <th>
                        <label for="tg_wp_factor_chat_id_reconfig"><?php _e('New Telegram Chat ID', 'two-factor-login-telegram'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="tg_wp_factor_chat_id_reconfig" id="tg_wp_factor_chat_id_reconfig"
                               value="" class="regular-text" placeholder="<?php echo esc_attr($telegram_chat_id); ?>"/>
                        <p class="description"><?php _e('Enter your new Telegram Chat ID', 'two-factor-login-telegram'); ?></p>
                    </td>
                    <td>
                        <button type="button" class="button button-primary tg-action-button" id="tg_wp_factor_reconfig_send">
                            <?php _e("Send Test Code", "two-factor-login-telegram"); ?>
                        </button>
                        <div id="reconfig-status" class="tg-status" style="display: none;"></div>
                    </td>
                </tr>

                <tr id="factor-reconfig-confirm" style="display: none;">
                    <th>
                        <label for="tg_wp_factor_reconfig_confirm"><?php _e('Confirmation code', 'two-factor-login-telegram'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="tg_wp_factor_reconfig_confirm" id="tg_wp_factor_reconfig_confirm"
                               value="" class="regular-text"/>
                        <p class="description"><?php _e('Enter the confirmation code you received on Telegram', 'two-factor-login-telegram'); ?></p>
                    </td>
                    <td>
                        <button type="button" class="button button-primary tg-action-button" id="tg_wp_factor_reconfig_validate">
                            <?php _e("Validate & Save", "two-factor-login-telegram"); ?>
                        </button>
                        <div id="reconfig-validation-status" class="tg-status" style="display: none;"></div>
                    </td>
                </tr>
            </table>

            <p>
                <button type="button" class="button button-secondary" id="cancel-reconfigure">
                    <?php _e('Cancel', 'two-factor-login-telegram'); ?>
                </button>
            </p>
        </div>
    <?php else: ?>
        <div class="notice notice-info inline">
            <p><?php _e('Configure Telegram to enable this 2FA method.', 'two-factor-login-telegram'); ?></p>
        </div>

        <div class="authpress-setup">
            <h3><?php _e('Setup Telegram', 'two-factor-login-telegram'); ?></h3>

            <?php
            $username = $this->telegram->get_me()->username;
            ?>

            <div class="tg-setup-steps">
                <h4><?php _e('🚀 Setup Steps', 'two-factor-login-telegram'); ?></h4>
                <ol>
                    <li>
                        <?php
                        printf(__('Open a conversation with %s and press on <strong>Start</strong>',
                                'two-factor-login-telegram'),
                                '<a href="https://telegram.me/' . $username
                                . '" target="_blank">@' . $username . '</a>');
                        ?>
                    </li>
                    <li>
                        <?php
                        printf(__('Type command %s to obtain your Chat ID.',
                                "two-factor-login-telegram"),
                                '<code>/get_id</code>');
                        ?>
                    </li>
                    <li>
                        <?php
                        _e("The bot will reply with your <strong>Chat ID</strong> number",
                                'two-factor-login-telegram');
                        ?>
                    </li>
                    <li><?php
                        _e('Copy your Chat ID and paste it below, then press <strong>Submit code</strong>',
                                'two-factor-login-telegram'); ?></li>
                </ol>
            </div>

            <div class="tg-progress">
                <div class="tg-progress-bar" id="tg-progress-bar"></div>
            </div>

            <div class="authpress-config">
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="tg_wp_factor_chat_id"><?php
                                _e('Telegram Chat ID',
                                        'two-factor-login-telegram'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="tg_wp_factor_chat_id"
                                   id="tg_wp_factor_chat_id" value="<?php echo $telegram_chat_id; ?>"
                                   class="regular-text"/><br/>
                            <span class="description"><?php
                                _e('Put your Telegram Chat ID',
                                        'two-factor-login-telegram'); ?></span>
                        </td>
                        <td>
                            <button type="button" class="button button-primary tg-action-button" id="tg_wp_factor_chat_id_send"><?php
                                _e("Submit code",
                                        "two-factor-login-telegram"); ?></button>
                            <div id="chat-id-status" class="tg-status" style="display: none;"></div>
                        </td>
                    </tr>

                    <tr id="factor-chat-confirm" style="display: none;">
                        <th>
                            <label for="tg_wp_factor_chat_id_confirm"><?php
                                _e('Confirmation code',
                                        'two-factor-login-telegram'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="tg_wp_factor_chat_id_confirm"
                                   id="tg_wp_factor_chat_id_confirm" value=""
                                   class="regular-text"/><br/>
                            <span class="description"><?php
                                _e('Please enter the confirmation code you received on Telegram',
                                        'two-factor-login-telegram'); ?></span>
                        </td>
                        <td>
                            <button type="button" class="button button-primary tg-action-button" id="tg_wp_factor_chat_id_check"><?php
                                _e("Validate",
                                        "two-factor-login-telegram"); ?></button>
                            <div id="validation-status" class="tg-status" style="display: none;"></div>
                        </td>
                    </tr>
                    <tr id="factor-chat-response" style="display: none;">
                        <td colspan="3">
                            <div class="wpft-notice wpft-notice-warning">
                                <p></p>
                            </div>
                        </td>
                    </tr>
                    <tr id="factor-chat-save" style="display: none;">
                        <td colspan="3">
                            <form method="post" action="" class="authpress-save-form">
                                <?php wp_nonce_field('wp_factor_save_telegram', 'wp_factor_telegram_save_nonce'); ?>
                                <input type="hidden" name="wp_factor_action" value="save_telegram">
                                <input type="hidden" name="tg_chat_id" id="tg_chat_id_hidden" value="">
                                <p class="submit">
                                    <button type="submit" class="button button-primary">
                                        <?php _e('Save Telegram Configuration', 'two-factor-login-telegram'); ?>
                                    </button>
                                </p>
                            </form>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

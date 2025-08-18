<?php
if (!defined('ABSPATH')) {
    exit;
}

?>
    <p class="notice notice-info">
        <?php _e("Enter the code sent to your Telegram account.", "two-factor-login-telegram"); ?>
    </p>

    <p>
        <label for="authcode" style="padding-top:1em">
            <?php _e("Authentication code:", "two-factor-login-telegram"); ?>
        </label>
        <input type="text" name="authcode" id="authcode" class="input" value="" size="5"/>
    </p>

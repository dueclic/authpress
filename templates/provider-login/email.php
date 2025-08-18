<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var $default_method string
 */
?>
<p class="notice notice-info">
    <?php _e("Enter the code sent to your email address.", "two-factor-login-telegram"); ?>
</p>

<p>
    <label for="email_code" style="padding-top:1em">
        <?php _e("Email code:", "two-factor-login-telegram"); ?>
    </label>
    <input type="text" name="email_code" id="email_code" class="input" value="" size="6" maxlength="6"
           placeholder="123456"/>
</p>

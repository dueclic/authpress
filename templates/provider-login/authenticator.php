<?php

if (!defined('ABSPATH')) {
    exit;
}


?>
<p class="notice notice-info">
    <?php _e("Enter the 6-digit code from your authenticator app.", "two-factor-login-telegram"); ?>
</p>

<p>
    <label for="totp_code" style="padding-top:1em">
        <?php _e("Authenticator code:", "two-factor-login-telegram"); ?>
    </label>
    <input type="text" name="totp_code" id="totp_code" class="input" value="" size="6" maxlength="6"
           placeholder="123456"/>
</p>

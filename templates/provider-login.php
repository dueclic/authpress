<?php

/**
 * @var $provider_key string
 * @var $is_active boolean
 * @var $provider Abstract_Provider
 */

use AuthPress\Providers\Abstract_Provider;

if (!defined('ABSPATH')) {
    exit;
}

?>
<p class="notice notice-info">
    <?php echo sprintf(__("Enter the code sent via %s.", "two-factor-login-telegram"), $provider->get_name()); ?>
</p>

<p>
    <label for="<?php echo esc_attr($provider_key); ?>_code" style="padding-top:1em">
        <?php echo sprintf(__("%s code:", "two-factor-login-telegram"), $provider->get_name()); ?>
    </label>
    <input type="text" name="<?php echo esc_attr($provider_key); ?>_code"
           id="<?php echo esc_attr($provider_key); ?>_code" class="input" value="" size="6" maxlength="6"
           placeholder="123456"/>
</p>

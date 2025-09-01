<?php
/**
 * Email provider configuration template
 * @var \AuthPress\Providers\Abstract_Provider $provider
 * @var array $providers
 */

$email_settings = $providers['email'] ?? [];
$token_duration = $email_settings['token_duration'] ?? 20; // Default to 20 minutes
?>

<div class="ap-form__group">
	<label class="ap-label" for="email_token_duration"><?php _e("Token duration:", "two-factor-login-telegram"); ?></label>
	<p class="ap-provider-config__description">
		<?php _e("Default token duration in minutes", "two-factor-login-telegram"); ?>
	</p>
	<div class="ap-form">
		<div class="ap-form__group">
			<input id="email_token_duration" class="ap-input"
			       name="authpress_providers[email][token_duration]"
			       type="number"
			       value="<?php echo esc_attr($token_duration); ?>"/>
		</div>
	</div>
</div>

<div class="ap-form__group">
	<label class="ap-label"><?php _e("Mail testing", "two-factor-login-telegram"); ?></label>
	<p class="ap-provider-config__description">
		<?php _e("This provider uses the standard WordPress mail function (wp_mail). You can send a test email to your admin account to ensure it's working correctly.", "two-factor-login-telegram"); ?>
	</p>
	<div class="ap-form">
		<div class="ap-form__group">
			<button id="authpress-test-email" class="ap-button ap-button--secondary" type="button">
				<?php _e("Send Test Email", "two-factor-login-telegram"); ?>
			</button>
			<div id="authpress-test-email-status" class="ap-notice mt-8" style="display: none;"></div>
		</div>
	</div>
</div>

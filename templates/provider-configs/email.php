<?php
/**
 * Email provider configuration template
 * @var \AuthPress\Providers\Abstract_Provider $provider
 * @var array $providers
 */
?>

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

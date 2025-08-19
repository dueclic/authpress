/**
 * AuthPress Login Code Sender
 * Handles sending authentication codes for different providers
 * Uses jQuery to respect WordPress standards
 */
(function($) {
    'use strict';

    var AuthPressCodeSender = {
        userId: null,
        nonce: null,

        init: function() {
            this.userId = $('#wp-auth-id').val();
            this.nonce = $('input[name="authpress_auth_nonce"]').val();
        },

        sendTelegramCode: function() {
            var self = this;
            var section = $('#telegram-login-section');
            var noticeElement = section.find('.notice');

            if (!noticeElement.length) return;

            this.showLoadingMessage(noticeElement, window.authpressConfig.sendingTelegramCode);

            $.ajax({
                url: window.authpressConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'send_login_telegram_code',
                    user_id: this.userId,
                    nonce: this.nonce
                },
                success: function(data) {
                    if (data.success) {
                        self.showSuccessMessage(noticeElement, window.authpressConfig.telegramCodeSent);
                    } else {
                        self.showErrorMessage(noticeElement, window.authpressConfig.errorSendingCode);
                    }
                },
                error: function() {
                    self.showErrorMessage(noticeElement, window.authpressConfig.errorSendingCode);
                }
            });
        },

        sendEmailCode: function() {
            var self = this;
            var section = $('#email-login-section');
            var noticeElement = section.find('.notice');

            if (!noticeElement.length) return;

            this.showLoadingMessage(noticeElement, window.authpressConfig.sendingEmailCode);

            $.ajax({
                url: window.authpressConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'send_login_email_code',
                    user_id: this.userId,
                    nonce: this.nonce
                },
                success: function(data) {
                    if (data.success) {
                        self.showSuccessMessage(noticeElement, window.authpressConfig.emailCodeSent);
                    } else {
                        self.showErrorMessage(noticeElement, window.authpressConfig.errorSendingCode);
                    }
                },
                error: function() {
                    self.showErrorMessage(noticeElement, window.authpressConfig.errorSendingCode);
                }
            });
        },

        sendExternalProviderCode: function(method) {
            var self = this;
            var section = $('#' + method + '-login-section');
            if (!section.length) return;

            var noticeElement = section.find('.notice');
            if (!noticeElement.length) return;

            this.showLoadingMessage(noticeElement, '⏳ Sending ' + method + ' code...');

            $.ajax({
                url: window.authpressConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'send_login_' + method + '_code',
                    user_id: this.userId,
                    nonce: this.nonce
                },
                success: function(data) {
                    if (data.success) {
                        self.showSuccessMessage(noticeElement, '✅ Code sent! Check your device.');
                    } else {
                        self.showErrorMessage(noticeElement, '❌ Error sending code. Please try again.');
                    }
                },
                error: function() {
                    self.showErrorMessage(noticeElement, '❌ Error sending code. Please try again.');
                }
            });
        },

        showLoadingMessage: function(element, message) {
            element.html(message);
        },

        showSuccessMessage: function(element, message) {
            element.html(message);
        },

        showErrorMessage: function(element, message) {
            element.html(message);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        window.authpressCodeSender = AuthPressCodeSender;
        AuthPressCodeSender.init();
    });

})(jQuery);

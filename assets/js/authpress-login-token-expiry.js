/**
 * AuthPress Login Token Expiry Handler
 * Handles token expiration for time-sensitive authentication methods
 * Uses jQuery to respect WordPress standards
 */
(function($) {
    'use strict';

    var AuthPressTokenExpiryHandler = {
        loginMethodInput: null,

        init: function() {
            this.loginMethodInput = $('#login_method');
            
            if (window.authpressConfig.expireSeconds) {
                this.startExpiryTimer(window.authpressConfig.expireSeconds * 1000);
            }
        },

        startExpiryTimer: function(timeoutMs) {
            var self = this;
            setTimeout(function() {
                self.handleExpiry();
            }, timeoutMs);
        },

        handleExpiry: function() {
            // Only show expiry message for Telegram method
            if (this.loginMethodInput.length && this.loginMethodInput.val() === 'telegram') {
                this.showExpiryMessage();
            }
        },

        showExpiryMessage: function() {
            var errorDiv = $('#login_error');
            
            if (!errorDiv.length) {
                errorDiv = $('<div id="login_error"></div>');
                var loginForm = $('#loginform');
                if (loginForm.length) {
                    errorDiv.insertBefore(loginForm);
                }
            }
            
            errorDiv.html('<strong>' + window.authpressConfig.codeExpiredMessage + '</strong><br />');
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        window.authpressTokenExpiry = AuthPressTokenExpiryHandler;
        AuthPressTokenExpiryHandler.init();
    });

})(jQuery);
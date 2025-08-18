/**
 * AuthPress Login PassKey Handler
 * Handles PassKey authentication functionality
 * Uses jQuery to respect WordPress standards
 */
(function($) {
    'use strict';

    var AuthPressPasskeyHandler = {
        userId: null,
        statusDiv: null,
        passkeyButton: null,
        authenticatedField: null,
        loginForm: null,

        init: function() {
            this.userId = $('#wp-auth-id').val();
            this.statusDiv = $('#passkey-status');
            this.passkeyButton = $('#passkey-authenticate');
            this.authenticatedField = $('#passkey_authenticated');
            this.loginForm = $('#loginform');
            
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;
            
            if (this.passkeyButton.length) {
                this.passkeyButton.on('click', function() {
                    self.authenticate();
                });
            }
        },

        authenticate: function() {
            var self = this;
            
            this.showStatus(window.authpressConfig.preparingAuth, 'blue');
            this.disableButton();

            if (typeof authpressPasskeyAuthenticate === 'function') {
                authpressPasskeyAuthenticate(this.userId, function(success, message) {
                    self.enableButton();
                    self.handleAuthResult(success, message);
                });
            } else {
                this.showStatus(window.authpressConfig.passkeyNotAvailable, 'red');
                this.enableButton();
            }
        },

        handleAuthResult: function(success, message) {
            var self = this;
            
            if (success) {
                this.showStatus('✅ ' + message, 'green');
                if (this.authenticatedField.length) {
                    this.authenticatedField.val('1');
                }
                // Auto-submit form after successful authentication
                setTimeout(function() {
                    if (self.loginForm.length) {
                        self.loginForm.submit();
                    }
                }, 1000);
            } else {
                this.showStatus('❌ ' + message, 'red');
            }
        },

        showStatus: function(message, color) {
            if (this.statusDiv.length) {
                this.statusDiv.show().html('<span style="color: ' + color + ';">' + message + '</span>');
            }
        },

        disableButton: function() {
            if (this.passkeyButton.length) {
                this.passkeyButton.prop('disabled', true);
            }
        },

        enableButton: function() {
            if (this.passkeyButton.length) {
                this.passkeyButton.prop('disabled', false);
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        window.authpressPasskeyHandler = AuthPressPasskeyHandler;
        AuthPressPasskeyHandler.init();
    });

})(jQuery);
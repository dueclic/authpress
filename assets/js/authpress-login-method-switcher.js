/**
 * AuthPress Login Method Switcher
 * Handles switching between different login methods and managing form sections
 * Uses jQuery to respect WordPress standards
 */
(function($) {
    'use strict';

    var AuthPressLoginMethodSwitcher = {
        loginMethodInput: null,
        methodDropdown: null,
        recoverySection: null,
        useRecoveryButton: null,
        recoveryInput: null,

        init: function() {
            this.loginMethodInput = $('#login_method');
            this.methodDropdown = $('#method-dropdown');
            this.recoverySection = $('#recovery_codes-login-section');
            this.useRecoveryButton = $('#use-recovery-code');
            this.recoveryInput = $('#recovery_code');
            
            this.bindEvents();
            this.focusDefaultInput();
        },

        bindEvents: function() {
            var self = this;
            
            if (this.methodDropdown.length) {
                this.methodDropdown.on('change', function() {
                    self.switchMethod($(this).val());
                });
            }

            if (this.useRecoveryButton.length) {
                this.useRecoveryButton.on('click', function() {
                    self.toggleRecoveryMode();
                });
            }
        },

        switchMethod: function(method) {
            var self = this;
            
            // Update hidden input
            this.loginMethodInput.val(method);

            // Hide all sections
            $('.login-section').removeClass('active');

            // Small delay for smooth transition
            setTimeout(function() {
                var targetSection = $('#' + method + '-login-section');
                if (targetSection.length) {
                    targetSection.addClass('active');
                    self.focusMethodInput(targetSection);
                }

                // Send codes for methods that need them
                if (method !== window.authpressConfig.defaultMethod) {
                    self.sendCodeForMethod(method);
                }
            }, 150);
        },

        focusMethodInput: function(section) {
            var input = section.find('input[type="text"]').first();
            if (input.length) {
                setTimeout(function() {
                    input.focus();
                }, 100);
            }
        },

        sendCodeForMethod: function(method) {
            if (['totp', 'authenticator', 'passkey'].indexOf(method) !== -1) {
                return;
            }

            if (method === 'telegram') {
                window.authpressCodeSender.sendTelegramCode();
            } else if (method === 'email') {
                window.authpressCodeSender.sendEmailCode();
            } else {
                window.authpressCodeSender.sendExternalProviderCode(method);
            }
        },

        toggleRecoveryMode: function() {
            var isInRecoveryMode = this.loginMethodInput.val() === 'recovery';
            
            if (isInRecoveryMode) {
                this.exitRecoveryMode();
            } else {
                this.enterRecoveryMode();
            }
        },

        enterRecoveryMode: function() {
            this.loginMethodInput.val('recovery');
            
            // Hide all sections
            $('.login-section').removeClass('active');

            // Show recovery section
            if (this.recoverySection.length) {
                this.recoverySection.addClass('active');
            }

            // Hide dropdown
            var dropdownContainer = this.methodDropdown.closest('.method-selector-wrapper');
            if (dropdownContainer.length) {
                dropdownContainer.hide();
            }

            // Update button text
            this.useRecoveryButton.val(window.authpressConfig.backTo2FAText);
            
            // Clear form inputs
            this.clearAllInputs();
            
            // Focus recovery input
            if (this.recoveryInput.length) {
                this.recoveryInput.focus();
            }
        },

        exitRecoveryMode: function() {
            var defaultMethod = window.authpressConfig.defaultMethod;
            this.loginMethodInput.val(defaultMethod);

            // Update dropdown selection
            if (this.methodDropdown.length) {
                this.methodDropdown.val(defaultMethod);
            }

            // Show appropriate section
            this.switchToDefaultMethod(defaultMethod);

            // Show dropdown
            var dropdownContainer = this.methodDropdown.closest('.method-selector-wrapper');
            if (dropdownContainer.length) {
                dropdownContainer.show();
            }

            // Update button text
            this.useRecoveryButton.val(window.authpressConfig.useRecoveryText);
            
            // Clear recovery input
            if (this.recoveryInput.length) {
                this.recoveryInput.val('');
            }
        },

        switchToDefaultMethod: function(defaultMethod) {
            // Hide all sections including recovery
            $('.login-section').removeClass('active');

            if (this.recoverySection.length) {
                this.recoverySection.removeClass('active');
            }

            // Show the appropriate method section
            var methodSection = $('#' + defaultMethod + '-login-section');
            if (methodSection.length) {
                methodSection.addClass('active');
                this.focusMethodInput(methodSection);
            }
        },

        clearAllInputs: function() {
            var inputs = ['authcode', 'email_code', 'totp_code'];
            
            $.each(inputs, function(index, inputId) {
                var input = $('#' + inputId);
                if (input.length) {
                    input.val('');
                }
            });
        },

        focusDefaultInput: function() {
            var defaultMethod = window.authpressConfig.defaultMethod;
            var inputMap = {
                'telegram': 'authcode',
                'email': 'email_code',
                'totp': 'totp_code'
            };

            var inputId = inputMap[defaultMethod];
            if (inputId) {
                var input = $('#' + inputId);
                if (input.length) {
                    input.focus();
                }
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        window.authpressMethodSwitcher = AuthPressLoginMethodSwitcher;
        AuthPressLoginMethodSwitcher.init();
    });

})(jQuery);
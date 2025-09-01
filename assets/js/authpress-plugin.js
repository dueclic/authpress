/**
 * WP Factor Telegram Plugin
 */

var AuthPress_Plugin = function ($) {

    var $twfci = $("#authpress_telegram_chat_id");
    var $twfciconf = $("#authpress_telegram_chat_id_confirm");
    var $twbtn = $("#authpress_telegram_chat_id_send");

    var $twfcr = $("#factor-chat-response");
    var $twfconf = $("#factor-chat-confirm");
    var $twfcheck = $("#authpress_telegram_chat_id_check");
    var $twbcheck = $("#checkbot");
    var $twb = $("#bot_token");
    var $twbdesc = $("#bot_token_desc");

    return {
        init: init
    };

    function init() {
        initTwoFASettingsPage();


        // Watch for changes in Chat ID when in edit mode
        $twfci.on("input", function () {
            var currentValue = $(this).val();
            var originalValue = $(this).data('original-value');

            // If value changed from original, require re-validation
            if (currentValue !== originalValue) {
                $(this).removeClass('input-valid');
            }
        });

        // Store original chat ID value for comparison
        if ($twfci.length) {
            $twfci.data('original-value', $twfci.val());
        }

        $twfci.on("change", function (evt) {
            validateChatId($(this).val());
        });

        $twbtn.on("click", function (evt) {
            evt.preventDefault();
            var chat_id = $twfci.val();

            if (!validateChatId(chat_id)) {
                showStatus('#chat-id-status', 'error', tlj.invalid_chat_id);
                return;
            }

            send_tg_token(chat_id);
        });

        $twfcheck.on("click", function (evt) {
            evt.preventDefault();
            var token = $twfciconf.val();
            var chat_id = $twfci.val();

            if (!token.trim()) {
                showStatus('#validation-status', 'error', tlj.enter_confirmation_code);
                return;
            }

            check_tg_token(token, chat_id);
        });

        $twbcheck.on("click", function (evt) {

            evt.preventDefault();
            var bot_token = $twb.val();
            check_tg_bot(bot_token);

        });

        // Handle Test Email button click
        $(document).on('click', '#authpress-test-email', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $status = $('#authpress-test-email-status');

            $btn.prop('disabled', true).text('Sending...');
            $status.removeClass('success error').text('Sending test email...').show();

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'authpress_test_email',
                    _wpnonce: tlj.test_email_nonce
                },
                success: function (response) {
                    if (response.success) {
                        $status.removeClass('error').addClass('success').text(response.data.message);
                    } else {
                        $status.removeClass('success').addClass('error').text(response.data.message);
                    }
                },
                error: function () {
                    $status.removeClass('success').addClass('error').text(tlj.ajax_error || 'A network error occurred.');
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Send Test Email');
                }
            });
        });

        // Handle Failed Login Reports toggle for Telegram provider
        $(document).on('change', '#telegram_failed_login_reports', function () {
            var isEnabled = $(this).val() === '1';
            var $reportChatIdGroup = $(this).closest('.ap-form').find('#telegram_report_chat_id').closest('.ap-form__group');

            if (isEnabled) {
                $reportChatIdGroup.slideDown();
            } else {
                $reportChatIdGroup.slideUp();
            }
        });

        // Initialize Report Chat Id field visibility on page load
        var $failedLoginSelect = $('#telegram_failed_login_reports');
        if ($failedLoginSelect.length) {
            var isEnabled = $failedLoginSelect.val() === '1';
            var $reportChatIdGroup = $failedLoginSelect.closest('.ap-form').find('#telegram_report_chat_id').closest('.ap-form__group');

            if (!isEnabled) {
                $reportChatIdGroup.hide();
            }
        }

    }

    function initTwoFASettingsPage() {
        setupTelegramReconfiguration();
        setupEmailReconfiguration();
        setupAuthenticatorConfiguration();
    }

    function check_tg_bot(bot_token) {

        $.ajax({

            type: "POST",
            url: ajaxurl,
            data: {
                'nonce': tlj.checkbot_nonce,
                'action': 'check_bot',
                'bot_token': bot_token
            },
            beforeSend: function () {
                $twbcheck.addClass('disabled').after('<div class="load-spinner"><img src="' + tlj.spinner + '" /></div>');
            },
            dataType: 'json',
            success: function (response) {

                if (response.type === "success") {
                    $twbdesc.html("Bot info: <span class='success'>" + response.args.first_name + " (@" + response.args.username + ")</span>");
                } else {
                    $twbdesc.html("<span class='error'>" + response.msg + "</span>");
                }

            },
            complete: function () {
                $twbcheck.removeClass('disabled');
                $(".load-spinner").remove();
            }

        })

    }

    function check_tg_token(token, chat_id) {

        $.ajax({

            type: "POST",
            url: ajaxurl,
            data: {
                'action': 'token_check',
                'nonce': tlj.sendtoken_nonce,
                'chat_id': chat_id,
                'token': token
            },
            beforeSend: function () {
                $twfcheck.addClass('disabled').after('<div class="load-spinner"><img src="' + tlj.spinner + '" /></div>');
                $twfcr.hide();
                hideStatus('#validation-status');
            },
            dataType: "json",
            success: function (response) {

                if (response.type === "success") {
                    $twfconf.hide();
                    $twfci.addClass("input-valid");
                    updateProgress(100);
                    showStatus('#validation-status', 'success', tlj.setup_completed);

                    // Show save button and populate hidden chat ID field
                    $('#factor-chat-save').show();
                    $('#tg_chat_id_hidden').val(chat_id);
                } else {
                    showStatus('#validation-status', 'error', response.msg);
                    $twfci.removeClass("input-valid");
                }

            },
            error: function (xhr, ajaxOptions, thrownError) {
                showStatus('#validation-status', 'error', tlj.ajax_error + " " + thrownError + " (" + xhr.state + ")");
                $twfci.removeClass("input-valid");
            },
            complete: function () {
                $twfcheck.removeClass('disabled');
                $(".load-spinner").remove();
            }

        });

    }

    function send_tg_token(chat_id) {

        $.ajax({

            type: "POST",
            url: ajaxurl,
            data: {
                'action': 'send_token_check',
                'nonce': tlj.tokencheck_nonce,
                'chat_id': chat_id
            },
            beforeSend: function () {
                $twbtn.addClass('disabled').after('<div class="load-spinner"><img src="' + tlj.spinner + '" /></div>');
                $twfcr.hide();
                $twfconf.hide();
                hideStatus('#chat-id-status');
            },
            dataType: "json",
            success: function (response) {

                if (response.type === "success") {
                    $twfconf.show();
                    $twfci.removeClass("input-valid");
                    updateProgress(75);
                    showStatus('#chat-id-status', 'success', tlj.code_sent);
                } else {
                    showStatus('#chat-id-status', 'error', response.msg);
                }

            },
            error: function (xhr, ajaxOptions, thrownError) {
                showStatus('#chat-id-status', 'error', tlj.ajax_error + " " + thrownError + " (" + xhr.state + ")");
            },
            complete: function () {
                $twbtn.removeClass('disabled');
                $(".load-spinner").remove();
                $twfci.removeClass("input-valid");
            }

        });

    }

    // Helper functions
    function updateProgress(percentage) {
        $('#tg-progress-bar').css('width', percentage + '%');
    }

    function validateChatId(chatId) {
        // Telegram Chat ID validation: must be numeric (positive for users, negative for groups)
        if (!chatId || typeof chatId !== 'string') {
            return false;
        }

        var trimmedId = chatId.trim();
        if (trimmedId === '') {
            return false;
        }

        // Check if it's a valid number (can be negative for groups)
        var numericId = parseInt(trimmedId, 10);
        return !isNaN(numericId) && trimmedId === numericId.toString();
    }

    function showStatus(selector, type, message) {
        var $status = $(selector);
        $status.removeClass('success error warning')
            .addClass(type)
            .text(message)
            .fadeIn(300);
    }

    function hideStatus(selector) {
        $(selector).fadeOut(300);
    }


    // Telegram reconfiguration functionality
    function setupTelegramReconfiguration() {
        // Show reconfiguration section
        $('#reconfigure-telegram').on('click', function () {
            $('#provider-telegram-config').addClass('expanded');
            $('#telegram-config-section').slideDown();
            $(this).hide();
        });

        $('#cancel-telegram-reconfigure').on('click', function () {
            $('#provider-telegram-config').removeClass('expanded');
            $('#telegram-config-section').slideUp();
            $('#reconfigure-telegram').show();
            resetReconfigurationForm();
        });

    }

    function resetReconfigurationForm() {
        $('#factor-reconfig-confirm').hide();
        $('#reconfig-status, #reconfig-validation-status').hide();
    }

    function setupAuthenticatorConfiguration() {
        $(document).on('click', '#wp_factor_generate_qr', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $qrSection = $('#wp_factor_qr_code');
            var $verificationSection = $('#wp_factor_verification_section');

            $btn.prop('disabled', true).text('Generating...');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'setup_totp',
                    _wpnonce: $('input[name="wp_factor_totp_setup_nonce"]').val()
                },
                success: function (response) {
                    if (response.success) {
                        $qrSection.attr('src', response.data.qr_code_url).show();
                        $verificationSection.show();
                        $btn.hide();
                    } else {
                        alert(response.data.message || 'Failed to generate QR code');
                        $btn.prop('disabled', false).text('Generate QR Code');
                    }
                },
                error: function () {
                    alert('Error generating QR code');
                    $btn.prop('disabled', false).text('Generate QR Code');
                }
            });
        });
        $(document).on('submit', '#wp_factor_verify_form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $messageDiv = $('#wp_factor_totp_message');
            var code = $('#wp_factor_totp_code').val().trim();

            if (code.length !== 6) {
                $messageDiv.removeClass('notice-success').addClass('notice notice-error').html('<p>Please enter a 6-digit code</p>').show();
                return;
            }

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'verify_totp',
                    code: code,
                    _wpnonce: $('input[name="wp_factor_totp_nonce"]').val()
                },
                success: function (response) {
                    if (response.success) {
                        $messageDiv.removeClass('notice-error').addClass('notice notice-success').html('<p>' + response.data.message + '</p>').show();
                        setTimeout(function () {
                            location.reload();
                        }, 2000);
                    } else {
                        $messageDiv.removeClass('notice-success').addClass('notice notice-error').html('<p>' + response.data.message + '</p>').show();
                    }
                },
                error: function () {
                    $messageDiv.removeClass('notice-success').addClass('notice notice-error').html('<p>Network error occurred</p>').show();
                }
            });
        });
    }

    function setupEmailReconfiguration() {
        // Show reconfiguration section
        $('#reconfigure-email').on('click', function () {
            $(this).hide();
            $('#email-reconfig-section').slideDown();
        });

        // Cancel reconfiguration
        $('#cancel-reconfigure-email').on('click', function () {
            $('#reconfigure-email').show();
            $('#email-reconfig-section').slideUp();
        });

        // Send verification code for email
        $('#authpress_send_email_code_btn').on('click', function () {
            var $btn = $(this);
            var email = $('#authpress_auth_email').val().trim();
            if (!email) {
                showStatus('#authpress-email-send-status', 'error', 'Please enter an email address');
                return;
            }

            $btn.prop('disabled', true).text('Sending...');
            showStatus('#authpress-email-send-status', 'info', 'Sending verification code...');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'send_auth_email_verification',
                    authpress_auth_email: email,
                    _wpnonce: $('#authpress_email_nonce').val()
                },
                success: function (response) {
                    $btn.prop('disabled', false).text('Send Verification Code');
                    if (response.success) {
                        showStatus('#authpress-email-send-status', 'success', response.data.message);
                        $('#email-reconfig-section').slideUp();
                        $('#email-verify-section').slideDown();
                        $('#email-verify-message').html('A verification code has been sent to <strong>' + email + '</strong>. Please enter the code below to confirm the change.');
                    } else {
                        showStatus('#authpress-email-send-status', 'error', response.data.message);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('Send Verification Code');
                    showStatus('#authpress-email-send-status', 'error', 'Network error. Please try again.');
                }
            });
        });

        // Verify and save email
        $('#authpress_verify_email_code_btn').on('click', function () {
            var $btn = $(this);
            var verificationCode = $('#authpress_verification_code').val().trim();
            if (!verificationCode) {
                showStatus('#authpress-email-verify-status', 'error', 'Please enter the verification code');
                return;
            }

            $btn.prop('disabled', true).text('Verifying...');
            showStatus('#authpress-email-verify-status', 'info', 'Verifying code...');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'verify_auth_email',
                    authpress_verification_code: verificationCode,
                    _wpnonce: $('#authpress_email_verification_nonce').val()
                },
                success: function (response) {
                    $btn.prop('disabled', false).text('Verify & Save');
                    if (response.success) {
                        showStatus('#authpress-email-verify-status', 'success', response.data.message);
                        setTimeout(function () {
                            location.reload();
                        }, 2000);
                    } else {
                        showStatus('#authpress-email-verify-status', 'error', response.data.message);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('Verify & Save');
                    showStatus('#authpress-email-verify-status', 'error', 'Network error. Please try again.');
                }
            });
        });

        $('#cancel-verify-email').on('click', function () {
            $('#reconfigure-email').show();
            $('#email-verify-section').slideUp();
        });

        // Reset email to default
        $('#authpress_reset_email_btn').on('click', function () {
            var $btn = $(this);
            if (!confirm('Are you sure you want to reset to your default WordPress email address?')) {
                return;
            }

            $btn.prop('disabled', true).text('Resetting...');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'reset_auth_email',
                    _wpnonce: $('#authpress_email_nonce').val() // Re-using the nonce for simplicity
                },
                success: function (response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $btn.prop('disabled', false).text('Reset default mail');
                    }
                },
                error: function () {
                    alert('Network error. Please try again.');
                    $btn.prop('disabled', false).text('Reset default mail');
                }
            });
        });
    }

}(jQuery);

jQuery(function ($) {

    $('.authpress-user-provider-toggle').on('change', function () {
        var $toggle = $(this);
        var providerKey = $toggle.data('provider-key');
        var isEnabled = $toggle.is(':checked');
        var userId = $toggle.data('user-id');
        var nonce = $toggle.data('nonce');

        $toggle.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'authpress_update_user_provider_status',
                provider_key: providerKey,
                user_id: userId,
                enabled: isEnabled ? '1' : '0',
                nonce: nonce
            },
            success: function (result) {
                if (result.success) {
                    window.location.reload();
                } else {
                    alert(result.data.message);
                    $toggle.prop('checked', !isEnabled);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
                $toggle.prop('checked', !isEnabled);
            },
            complete: function () {
                $toggle.prop('disabled', false);
            }
        });
    });

    $("#regenerate_recovery_codes_btn").on("click", function () {

        if (!confirm('Are you sure? This will invalidate your current recovery codes and generate new ones that you must save immediately.')) {
            return;
        }

        var $btn = $('#regenerate_recovery_codes_btn');
        var originalText = $btn.text();
        var nonce = $('#regenerate_recovery_nonce').val();

        $btn.prop('disabled', true).text('Generating...');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'regenerate_recovery_codes',
                _wpnonce: nonce
            },
            success: function (response) {
                $btn.prop('disabled', false).text(originalText);

                if (response.success && response.data.html) {
                    $('body').append(response.data.html);
                } else {
                    alert(response.data && response.data.message ? response.data.message : 'Failed to regenerate recovery codes');
                }
            },
            error: function () {
                $btn.prop('disabled', false).text(originalText);
                alert('Network error occurred while regenerating recovery codes');
            }
        });

    });

});

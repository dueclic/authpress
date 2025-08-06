/**
 * WP Factor Telegram Plugin
 */

var AuthPress_Plugin = function ($) {

    var $twfci = $("#tg_wp_factor_chat_id");
    var $twfciconf = $("#tg_wp_factor_chat_id_confirm");
    var $twbtn = $("#tg_wp_factor_chat_id_send");
    var $twctrl = $("#tg_wp_factor_valid");
    var $twenabled = $("#tg_wp_factor_enabled");
    var $twconfig = $("#tg-2fa-configuration");
    var $tweditbtn = $("#tg-edit-chat-id");
    var $twconfigrow = $(".tg-configured-row");

    var $twfcr = $("#factor-chat-response");
    var $twfconf = $("#factor-chat-confirm");
    var $twfcheck = $("#tg_wp_factor_chat_id_check");
    var $twbcheck = $("#checkbot");
    var $twb = $("#bot_token");
    var $twbdesc = $("#bot_token_desc");

    return {
        init: init
    };

    function init() {
        // Initialize 2FA settings page functionality
        initTwoFASettingsPage();

        // Handle checkbox toggle for 2FA configuration with smooth animation
        $twenabled.on("change", function(evt){
            var isConfigured = $twconfigrow.length > 0;
            var hasAny2FA = $('.provider-status-card.configured').length > 0;

            if ($(this).is(":checked")) {
                // Enable 2FA = 1, so tg_wp_factor_valid = 0
                $twctrl.val(0);
                
                // Show method selection if no 2FA is configured yet
                if (!hasAny2FA) {
                    $('#2fa-method-selection').show();
                    updateProgress(25);
                }
                
                // Hide configuration sections initially
                $twconfig.hide();
                $('#totp-setup-section').hide();
            } else {
                // Enable 2FA = 0, so tg_wp_factor_valid = 1
                $twctrl.val(1);
                $twconfig.removeClass('show').hide();
                $('#totp-setup-section').hide();
                $('#2fa-method-selection').hide();
                setTimeout(function() {
                    $twconfig.hide();
                }, 300);
                updateProgress(0);
                resetStatusIndicators();
            }
        });

        // Handle edit button click (when 2FA is already configured)
        $tweditbtn.on("click", function(evt){
            evt.preventDefault();

            // Hide configured row and show configuration form
            $twconfigrow.hide();
            $twconfig.addClass('show').show();

            // Make the input editable and clear it
            $twfci.prop('readonly', false).removeClass('input-valid').css('background', '').val('');

            // Reset validation state
            $twctrl.val(0);
            updateProgress(25);
            resetStatusIndicators();

            // Show modifying status message
            $('.tg-status.success').removeClass('success').addClass('warning').text(tlj.modifying_setup);
            
            // Smooth scroll to configuration section
            $('html, body').animate({
                scrollTop: $('#tg-2fa-configuration').offset().top - 50
            }, 500);
        });

        // Watch for changes in Chat ID when in edit mode
        $twfci.on("input", function(){
            var currentValue = $(this).val();
            var originalValue = $(this).data('original-value');

            // If value changed from original, require re-validation
            if (currentValue !== originalValue) {
                $twctrl.val(0);
                $(this).removeClass('input-valid');
            }
        });

        // Initialize visibility based on checkbox state and configuration
        var isConfigured = $twconfigrow.length > 0;
        var hasAny2FA = $('.provider-status-card.configured').length > 0;
        
        if ($twenabled.is(":checked")) {
            if (!hasAny2FA) {
                $('#2fa-method-selection').show();
                updateProgress(25);
            }
            // Hide configuration sections initially
            $twconfig.hide();
            $('#totp-setup-section').hide();
        } else {
            $twconfig.removeClass('show').hide();
            $('#totp-setup-section').hide();
            $('#2fa-method-selection').hide();
        }

        // Initialize other sections visibility
        var $providersStatus = $('.providers-status-section');
        var $methodSelection = $('#2fa-method-selection');
        var $additionalMethods = $('.additional-methods-section');

        if ($twenabled.is(":checked")) {
            $providersStatus.show();
            if ($methodSelection.length && !hasAny2FA) {
                $methodSelection.show();
            }
            if ($additionalMethods.length) {
                $additionalMethods.show();
            }
        } else {
            $providersStatus.hide();
            $methodSelection.hide();
            $additionalMethods.hide();
        }

        // Store original chat ID value for comparison
        if ($twfci.length) {
            $twfci.data('original-value', $twfci.val());
        }

        $twfci.on("change", function(evt){
           $twctrl.val(0);
           // Validate Chat ID format (basic validation)
           validateChatId($(this).val());
        });

        $twbtn.on("click", function(evt){
            evt.preventDefault();
            var chat_id = $twfci.val();

            if (!validateChatId(chat_id)) {
                showStatus('#chat-id-status', 'error', tlj.invalid_chat_id);
                return;
            }

            send_tg_token(chat_id);
        });

        $twfcheck.on("click", function(evt){
            evt.preventDefault();
            var token = $twfciconf.val();
            var chat_id = $twfci.val();

            if (!token.trim()) {
                showStatus('#validation-status', 'error', tlj.enter_confirmation_code);
                return;
            }

            check_tg_token(token, chat_id);
        });

        $twbcheck.on("click", function(evt){

            evt.preventDefault();
            var bot_token = $twb.val();
            check_tg_bot(bot_token);

        });

        // Method selection buttons
        $(document).on('click', '#setup-telegram-btn, #add-telegram-btn', function() {
            $('#2fa-method-selection').hide();
            $('#tg-2fa-configuration').show();
            $('#totp-setup-section').hide();
            
            // Smooth scroll to configuration section
            $('html, body').animate({
                scrollTop: $('#tg-2fa-configuration').offset().top - 50
            }, 500);
        });

        $(document).on('click', '#setup-totp-btn, #add-totp-btn', function() {
            $('#2fa-method-selection').hide();
            setupTOTP();
            
            // Smooth scroll to TOTP setup section
            $('html, body').animate({
                scrollTop: $('#totp-setup-section').offset().top - 50
            }, 500);
        });

        // TOTP configuration buttons
        $(document).on('click', '#totp-reconfigure-btn', function() {
            setupTOTP();
            
            // Smooth scroll to TOTP setup section
            $('html, body').animate({
                scrollTop: $('#totp-setup-section').offset().top - 50
            }, 500);
        });

        $(document).on('click', '#totp-disable-btn', function() {
            if (confirm(tlj.confirm_disable_totp || 'Are you sure you want to disable the Authenticator app? You will lose this 2FA method.')) {
                disableTOTP();
            }
        });

        $(document).on('click', '#totp-show-secret-btn', function() {
            $('#totp-secret-manual').toggle();
        });

        $(document).on('click', '#totp-verify-btn', function() {
            verifyTOTP();
        });

        // Back buttons to return to method selection
        $(document).on('click', '.back-to-method-selection', function() {
            $('#2fa-method-selection').show();
            $('#tg-2fa-configuration').hide();
            $('#totp-setup-section').hide();
            
            // Smooth scroll to method selection
            $('html, body').animate({
                scrollTop: $('#2fa-method-selection').offset().top - 50
            }, 500);
        });

        // TOTP verification code input - only allow numbers
        $(document).on('input', '#totp-verification-code', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

    }

    function initTwoFASettingsPage() {
        // Initialize 2FA settings page specific functionality
        
        // Generate QR Code button for TOTP
        $(document).on('click', '#wp_factor_generate_qr', function(e) {
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
                success: function(response) {
                    if (response.success) {
                        $qrSection.attr('src', response.data.qr_code_url).show();
                        $verificationSection.show();
                        $btn.hide();
                    } else {
                        alert(response.data.message || 'Failed to generate QR code');
                        $btn.prop('disabled', false).text('Generate QR Code');
                    }
                },
                error: function() {
                    alert('Error generating QR code');
                    $btn.prop('disabled', false).text('Generate QR Code');
                }
            });
        });
        
        // TOTP verification form submission
        $(document).on('submit', '#wp_factor_verify_form', function(e) {
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
                success: function(response) {
                    if (response.success) {
                        $messageDiv.removeClass('notice-error').addClass('notice notice-success').html('<p>' + response.data.message + '</p>').show();
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $messageDiv.removeClass('notice-success').addClass('notice notice-error').html('<p>' + response.data.message + '</p>').show();
                    }
                },
                error: function() {
                    $messageDiv.removeClass('notice-success').addClass('notice notice-error').html('<p>Network error occurred</p>').show();
                }
            });
        });
        
        // Download recovery codes
        $(document).on('click', '#wp_factor_download_codes', function(e) {
            e.preventDefault();
            downloadRecoveryCodes();
        });
        
        // Print recovery codes
        $(document).on('click', '#wp_factor_print_codes', function(e) {
            e.preventDefault();
            printRecoveryCodes();
        });
    }
    
    function downloadRecoveryCodes() {
        var codes = [];
        $('.recovery-codes-list code').each(function() {
            codes.push($(this).text().trim());
        });
        
        var content = "WordPress 2FA Recovery Codes\n";
        content += "Generated: " + new Date().toLocaleString() + "\n\n";
        content += "Keep these codes in a safe place. Each code can only be used once.\n\n";
        codes.forEach(function(code) {
            content += code + "\n";
        });
        
        var blob = new Blob([content], { type: 'text/plain' });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = 'wp-2fa-recovery-codes.txt';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }
    
    function printRecoveryCodes() {
        var printWindow = window.open('', '_blank');
        var codes = [];
        $('.recovery-codes-list code').each(function() {
            codes.push($(this).text().trim());
        });
        
        var content = `
        <html>
        <head>
            <title>WordPress 2FA Recovery Codes</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h1 { color: #333; }
                .codes-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin: 20px 0; }
                .code { padding: 8px; border: 1px solid #ddd; text-align: center; font-weight: bold; }
                .warning { color: #d63638; margin-top: 20px; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h1>WordPress 2FA Recovery Codes</h1>
            <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
            <p><strong>Site:</strong> ${window.location.hostname}</p>
            <div class="codes-grid">
                ${codes.map(code => `<div class="code">${code}</div>`).join('')}
            </div>
            <div class="warning">
                <p><strong>Important:</strong></p>
                <ul>
                    <li>Keep these codes in a safe place</li>
                    <li>Each code can only be used once</li>
                    <li>Generate new codes if these are lost or compromised</li>
                </ul>
            </div>
        </body>
        </html>
        `;
        
        printWindow.document.write(content);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }

    function check_tg_bot(bot_token){

        $twctrl.val(0);

        $.ajax({

            type:"POST",
            url: ajaxurl,
            data: {
                'nonce': tlj.checkbot_nonce,
                'action' : 'check_bot',
                'bot_token' : bot_token
            },
            beforeSend: function(){
                $twbcheck.addClass('disabled').after('<div class="load-spinner"><img src="'+tlj.spinner+'" /></div>');
            },
            dataType: 'json',
            success: function(response) {

                if (response.type === "success") {
                    $twbdesc.html("Bot info: <span class='success'>"+response.args.first_name+" (@"+response.args.username+")</span>");
                }

                else {
                    $twbdesc.html("<span class='error'>"+response.msg+"</span>");
                }

            },
            complete: function() {
                $twbcheck.removeClass('disabled');
                $(".load-spinner").remove();
            }

        })

    }

    function check_tg_token(token, chat_id){

        $.ajax({

            type: "POST",
            url: ajaxurl,
            data: {
                'action' : 'token_check',
                'nonce': tlj.sendtoken_nonce,
                'chat_id': chat_id,
                'token' : token
            },
            beforeSend: function(){
                $twfcheck.addClass('disabled').after('<div class="load-spinner"><img src="'+tlj.spinner+'" /></div>');
                $twfcr.hide();
                hideStatus('#validation-status');
            },
            dataType: "json",
            success: function(response){

                if (response.type === "success") {
                    $twfconf.hide();
                    $twfci.addClass("input-valid");
                    $twctrl.val(1);
                    updateProgress(100);
                    showStatus('#validation-status', 'success', tlj.setup_completed);
                }
                else {
                    showStatus('#validation-status', 'error', response.msg);
                    $twfci.removeClass("input-valid");
                    $twctrl.val(0);
                }

            },
            error: function(xhr, ajaxOptions, thrownError){
                showStatus('#validation-status', 'error', tlj.ajax_error+" "+thrownError+" ("+xhr.state+")");
                $twfci.removeClass("input-valid");
            },
            complete: function() {
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
                'action' : 'send_token_check',
                'nonce': tlj.tokencheck_nonce,
                'chat_id' : chat_id
            },
            beforeSend: function(){
                $twbtn.addClass('disabled').after('<div class="load-spinner"><img src="'+tlj.spinner+'" /></div>');
                $twfcr.hide();
                $twfconf.hide();
                hideStatus('#chat-id-status');
            },
            dataType: "json",
            success: function(response){

                if (response.type === "success") {
                    $twfconf.show();
                    $twfci.removeClass("input-valid");
                    updateProgress(75);
                    showStatus('#chat-id-status', 'success', tlj.code_sent);
                }
                else {
                    showStatus('#chat-id-status', 'error', response.msg);
                }

            },
            error: function(xhr, ajaxOptions, thrownError){
                showStatus('#chat-id-status', 'error', tlj.ajax_error+" "+thrownError+" ("+xhr.state+")");
            },
            complete: function() {
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

    function resetStatusIndicators() {
        hideStatus('#chat-id-status');
        hideStatus('#validation-status');
    }

    // TOTP Setup functionality
    var totpSecret = '';

    function setupTOTP() {
        $('#totp-setup-section').show();
        $('#tg-2fa-configuration').hide();
        $('#2fa-method-selection').hide();
        
        // Load QR code and secret
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'setup_totp',
                _wpnonce: $('#totp-setup-nonce').val() || ''
            },
            success: function(res) {
                if (res.success && res.data) {
                    totpSecret = res.data.secret;
                    $('#totp-qr-code').html('<img src="' + res.data.qr_code_url + '" alt="QR Code" style="border: 1px solid #ddd; padding: 10px; background: white;">');
                    $('#totp-secret-text').text(res.data.secret);
                } else {
                    showTOTPStatus('error', res.data && res.data.message ? res.data.message : (tlj.qr_generation_failed || 'Failed to generate QR code'));
                }
            },
            error: function() {
                showTOTPStatus('error', tlj.network_error || 'Network error occurred');
            }
        });
    }

    function verifyTOTP() {
        var code = $('#totp-verification-code').val().trim();
        
        if (code.length !== 6) {
            showTOTPStatus('error', tlj.enter_6_digit_code || 'Please enter a 6-digit code');
            return;
        }

        $('#totp-verify-btn').prop('disabled', true).text(tlj.verifying || 'Verifying...');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'verify_totp',
                code: code,
                _wpnonce: $('#totp-verify-nonce').val() || ''
            },
            success: function(res) {
                $('#totp-verify-btn').prop('disabled', false).text(tlj.verify_enable || 'Verify & Enable');
                
                if (res.success) {
                    showTOTPStatus('success', tlj.totp_enabled_success || 'Authenticator app enabled successfully!');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showTOTPStatus('error', res.data && res.data.message ? res.data.message : (tlj.invalid_code || 'Invalid code. Please try again.'));
                }
            },
            error: function() {
                $('#totp-verify-btn').prop('disabled', false).text(tlj.verify_enable || 'Verify & Enable');
                showTOTPStatus('error', tlj.network_error || 'Network error occurred');
            }
        });
    }

    function disableTOTP() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'disable_totp',
                _wpnonce: $('#totp-disable-nonce').val() || ''
            },
            success: function(res) {
                if (res.success) {
                    location.reload();
                } else {
                    alert(res.data && res.data.message ? res.data.message : (tlj.disable_totp_failed || 'Failed to disable authenticator'));
                }
            },
            error: function() {
                alert(tlj.network_error || 'Network error occurred');
            }
        });
    }

    function showTOTPStatus(type, message) {
        var $status = $('#totp-verification-status');
        $status.removeClass('success error').addClass(type);
        $status.text(message).show();
        
        if (type === 'success') {
            setTimeout(function() {
                $status.fadeOut();
            }, 5000);
        }
    }

}(jQuery);

// Functionality to disable 2FA from users list (admin)
jQuery(document).ready(function($) {
    // Handler for 2FA disable buttons in users list
    $('.disable-2fa-btn').on('click', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var userId = $btn.data('user-id');
        var userName = $btn.data('user-name');

        if (!confirm(tlj.confirm_disable.replace('%s', userName))) {
            return;
        }

        // Add loading spinner
        $btn.prop('disabled', true).text(tlj.disabling);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'disable_user_2fa',
                user_id: userId,
                nonce: tlj.admin_nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update icon and button
                    var $cell = $btn.closest('td');
                    $cell.html('<span style="color: #999;">❌ ' + tlj.inactive + '</span>');

                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p>' + tlj.success_disabled.replace('%s', userName) + '</p></div>')
                        .insertAfter('.wp-header-end')
                        .delay(3000)
                        .fadeOut();
                } else {
                    alert(tlj.disable_error + ': ' + (response.data || tlj.unknown_error));
                    $btn.prop('disabled', false).text(tlj.disable);
                }
            },
            error: function() {
                alert(tlj.server_error);
                $btn.prop('disabled', false).text(tlj.disable);
            }
        });
    });
});

window.openRecoveryCodesModal = function(url, redirect_to, html) {
    var oldModal = document.getElementById('tg-modal-recovery');
    if (oldModal) oldModal.remove();
    if (html) {
        var div = document.createElement('div');
        div.innerHTML = html;
        var modalElement = div.querySelector('#tg-modal-recovery') || div.firstElementChild;
        if (modalElement) {
            document.body.appendChild(modalElement);
            var btn = document.getElementById('confirm-recovery-codes');
            if (btn) {
                btn.onclick = function() {
                    window.location.href = redirect_to;
                };
            }
        }
        return;
    }

    fetch(url)
        .then(r => r.text())
        .then(html => {
            var div = document.createElement('div');
            div.innerHTML = html;
            document.body.appendChild(div.firstElementChild);
            var btn = document.getElementById('confirm-recovery-codes');
            if (btn) {
                btn.onclick = function() {
                    window.location.href = redirect_to;
                };
            }
        });

}
window.closeRecoveryModal = function() {
    var modal = document.getElementById('tg-modal-recovery');
    if (modal) modal.remove();
}
window.copyRecoveryCodes = function() {
    let codes = Array.from(document.querySelectorAll('.recovery-code-box')).map(e => e.textContent).join('\n');
    navigator.clipboard.writeText(codes).then(function() {
        alert('Codes copied to clipboard!');
    }).catch(function() {
        alert('Failed to copy codes to clipboard');
    });
}

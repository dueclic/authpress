<?php

namespace AuthPress\Ui\Modals;

use AuthPress\Ui\UI_Modal;

class RecoveryCodesModal extends UI_Modal {


    /**
     * @param array<string $codes
     * @param bool $profile_page
     * @param string $modal_id
     */
    public function __construct($codes, $is_profile_page, $modal_id = 'authpress-modal')
    {
        parent::__construct($modal_id);

        $title = __('Recovery Codes', 'two-factor-login-telegram');

        $warning_text = $is_profile_page
            ? __('You have just regenerated Recovery Codes. Save them now: <b>they will only be shown at this moment</b> and cannot be recovered.', 'two-factor-login-telegram')
            : __('These codes allow you to log in if you don\'t have access to Telegram. <b>Save them in a safe place!</b> They will only be shown now and cannot be recovered.', 'two-factor-login-telegram');

        $codes_html = '<div class="recovery-codes-list" id="recovery-codes-list">';
        foreach ($codes as $code) {
            $codes_html .= '<div class="recovery-code-box">' . esc_html($code) . '</div>';
        }
        $codes_html .= '</div>';

        $content = '
            <div class="plugin-logo">
                <img src="' . authpress_logo() . '" alt="2FA Plugin Logo" style="width: 64px; height: 64px; border-radius: 50%;">
            </div>
            <div class="notice-warning" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 15px 0 20px 0; color: #856404; font-size: 14px; line-height: 1.5;">
                ' . $warning_text . '
            </div>
            <button class="copy-btn" onclick="copyRecoveryCodes()" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 14px; margin-bottom: 20px; transition: all 0.2s ease;">
                ' . __('Copy all codes', 'two-factor-login-telegram') . '
            </button>
            ' . $codes_html;

        $this->set_title($title)
            ->set_content($content)
            ->add_button(
                __('I have saved the codes, continue', 'two-factor-login-telegram'),
                'confirmRecoveryCodes()',
                '',
                ['id' => 'confirm-recovery-codes']
            )
            ->add_custom_css('
                  .recovery-codes-list {
                      background: #f8f9fa;
                      border: 1px solid #e9ecef;
                      border-radius: 8px;
                      padding: 20px;
                      margin: 20px 0;
                      display: grid;
                      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                      gap: 12px;
                  }
                  .recovery-code-box {
                      background: #ffffff;
                      border: 2px solid #dee2e6;
                      border-radius: 6px;
                      padding: 12px 8px;
                      font-family: "Monaco", "Menlo", "Ubuntu Mono", monospace;
                      font-size: 13px;
                      font-weight: 600;
                      text-align: center;
                      color: #495057;
                      transition: all 0.2s ease;
                      cursor: pointer;
                      user-select: all;
                  }
                  .recovery-code-box:hover {
                      border-color: #667eea;
                      background: #f8f9ff;
                      transform: translateY(-1px);
                      box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
                  }
                  .copy-btn:hover {
                      transform: translateY(-1px);
                      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                  }
                  @media (max-width: 768px) {
                      .recovery-codes-list {
                          grid-template-columns: repeat(2, 1fr);
                          gap: 10px;
                          padding: 15px;
                      }
                      .recovery-code-box {
                          font-size: 12px;
                          padding: 10px 6px;
                      }
                  }
              ')
            ->add_custom_js('
                  window.copyRecoveryCodes = function() {
                      let codes = Array.from(document.querySelectorAll(".recovery-code-box")).map(e => e.textContent).join("\\n");
                      navigator.clipboard.writeText(codes).then(function() {
                          alert("Codes copied to clipboard!");
                      }).catch(function() {
                          alert("Failed to copy codes to clipboard");
                      });
                  }
                  
                  window.confirmRecoveryCodes = function() {
                      if (window.recoveryCodesCallback) {
                          window.recoveryCodesCallback();
                      }
                      closeAuthPressModal("authpress-recovery-codes-modal");
                      window.location.reload();
                  }
              ');

    }
}

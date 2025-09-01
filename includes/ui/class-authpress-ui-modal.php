<?php

namespace AuthPress\Ui;

abstract class UI_Modal {

    private $modal_id;
    private $title;
    private $content;
    private $buttons;
    private $size;
    private $closeable;
    private $custom_css;
    private $custom_js;

    public function __construct($modal_id = 'authpress-modal') {
        $this->modal_id = $modal_id;
        $this->title = '';
        $this->content = '';
        $this->buttons = [];
        $this->size = 'medium';
        $this->closeable = true;
        $this->custom_css = '';
        $this->custom_js = '';
    }

    public function set_title($title) {
        $this->title = apply_filters('authpress_ui_modal_title', $title, $this->modal_id);
        return $this;
    }

    public function set_content($content) {
        $this->content = apply_filters('authpress_ui_modal_content', $content, $this->modal_id);
        return $this;
    }

    public function set_size($size) {
        $allowed_sizes = apply_filters('authpress_ui_modal_allowed_sizes', ['small', 'medium', 'large'], $this->modal_id);
        $this->size = in_array($size, $allowed_sizes) ? $size : 'medium';
        return $this;
    }

    public function set_closeable($closeable) {
        $this->closeable = apply_filters('authpress_ui_modal_closeable', $closeable, $this->modal_id);
        return $this;
    }

    public function add_button($text, $action, $class = 'button-primary', $attributes = []) {
        $button = [
            'text' => $text,
            'action' => $action,
            'class' => $class,
            'attributes' => $attributes
        ];

        $this->buttons[] = apply_filters('authpress_ui_modal_button', $button, $this->modal_id);
        return $this;
    }

    public function add_custom_css($css) {
        $this->custom_css .= apply_filters('authpress_ui_modal_custom_css', $css, $this->modal_id);
        return $this;
    }

    public function add_custom_js($js) {
        $this->custom_js .= apply_filters('authpress_ui_modal_custom_js', $js, $this->modal_id);
        return $this;
    }

    public function render() {
        do_action('authpress_ui_modal_before_render', $this->modal_id);

        $modal_classes = apply_filters('authpress_ui_modal_classes', [
            'authpress-modal',
            'authpress-modal-' . $this->size
        ], $this->modal_id);

        $content_classes = apply_filters('authpress_ui_modal_content_classes', [
            'authpress-modal-content'
        ], $this->modal_id);

        ob_start();
        ?>
        <style>
            <?php echo $this->get_default_css(); ?>
            <?php echo $this->custom_css; ?>
        </style>

        <div class="<?php echo esc_attr(implode(' ', $modal_classes)); ?>" id="<?php echo esc_attr($this->modal_id); ?>">
            <div class="authpress-modal-bg" onclick="<?php echo $this->closeable ? 'closeAuthPressModal(\'' . esc_js($this->modal_id) . '\')' : ''; ?>"></div>
            <div class="<?php echo esc_attr(implode(' ', $content_classes)); ?>">
                <?php if ($this->closeable): ?>
                    <button class="authpress-modal-close" onclick="closeAuthPressModal('<?php echo esc_js($this->modal_id); ?>')">&times;</button>
                <?php endif; ?>

                <?php if (!empty($this->title)): ?>
                    <h2 class="authpress-modal-title"><?php echo wp_kses_post($this->title); ?></h2>
                <?php endif; ?>

                <div class="authpress-modal-body">
                    <?php echo $this->content; ?>
                </div>

                <?php if (!empty($this->buttons)): ?>
                    <div class="authpress-modal-footer">
                        <?php foreach ($this->buttons as $button): ?>
                            <button
                                class="authpress-action-button <?php echo esc_attr($button['class']); ?>"
                                onclick="<?php echo esc_attr($button['action']); ?>"
                                <?php foreach ($button['attributes'] as $attr => $value): ?>
                                    <?php echo esc_attr($attr); ?>="<?php echo esc_attr($value); ?>"
                                <?php endforeach; ?>
                            >
                                <?php echo esc_html($button['text']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
            <?php echo $this->get_default_js(); ?>
            <?php echo $this->custom_js; ?>
        </script>
        <?php

        $output = ob_get_clean();
        $output = apply_filters('authpress_ui_modal_output', $output, $this->modal_id);

        do_action('authpress_ui_modal_after_render', $this->modal_id, $output);

        return $output;
    }

    public function display() {
        echo $this->render();
    }

    private function get_default_css() {
        $css = apply_filters('authpress_ui_modal_default_css', '
            .authpress-modal {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                z-index: 99999;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(0,0,0,0.35);
                animation: authpress-modal-fadein 0.2s;
            }
            .authpress-modal-bg {
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.35);
                z-index: 1;
            }
            .authpress-modal-content {
                position: relative;
                z-index: 2;
                box-shadow: 0 4px 24px rgba(0,0,0,0.13);
                border-radius: 8px;
                background: #fff;
                max-width: 480px;
                width: 95vw;
                margin: 0 auto;
                padding: 32px 28px 24px 28px;
                text-align: center;
            }
            .authpress-modal-small .authpress-modal-content {
                max-width: 320px;
            }
            .authpress-modal-large .authpress-modal-content {
                max-width: 640px;
            }
            .authpress-modal-close {
                position: absolute;
                top: 12px;
                right: 16px;
                background: none;
                border: none;
                font-size: 2em;
                color: #888;
                cursor: pointer;
                z-index: 3;
                line-height: 1;
            }
            .authpress-modal-close:hover {
                color: #222;
            }
            @keyframes authpress-modal-fadein {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            .authpress-modal-title {
                color: #333;
                font-size: 24px;
                margin: 0 0 20px 0;
                font-weight: 600;
            }
            .authpress-modal-body {
                margin: 20px 0;
            }
            .authpress-modal-footer {
                margin-top: 20px;
                display: flex;
                gap: 10px;
                justify-content: center;
                flex-wrap: wrap;
            }
            .authpress-action-button {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                color: white;
                padding: 12px 24px;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 500;
                font-size: 16px;
                transition: all 0.2s ease;
                min-width: 120px;
            }
            .authpress-action-button:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            }
            .authpress-action-button.button-secondary {
                background: #f8f9fa;
                color: #333;
                border: 1px solid #dee2e6;
            }
            .authpress-action-button.button-secondary:hover {
                background: #e9ecef;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
            @media (max-width: 768px) {
                .authpress-modal-content {
                    padding: 24px 20px;
                    margin: 20px;
                }
                .authpress-modal-footer {
                    flex-direction: column;
                }
                .authpress-action-button {
                    width: 100%;
                }
            }
        ', $this->modal_id);

        return $css;
    }

    private function get_default_js() {
        $js = apply_filters('authpress_ui_modal_default_js', '
            window.closeAuthPressModal = function(modalId) {
                var modal = document.getElementById(modalId);
                if (modal) {
                    modal.remove();
                }
            }
            
            window.openAuthPressModal = function(modalId, modalHtml) {
                var oldModal = document.getElementById(modalId);
                if (oldModal) oldModal.remove();
                
                if (modalHtml) {
                    var div = document.createElement("div");
                    div.innerHTML = modalHtml;
                    var modalElement = div.querySelector("#" + modalId) || div.firstElementChild;
                    if (modalElement) {
                        document.body.appendChild(modalElement);
                    }
                }
            }
        ', $this->modal_id);

        return $js;
    }

}

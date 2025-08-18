<?php
if (!defined('ABSPATH'))
    exit;

if ($available_count > 1): ?>
    <!-- Method Selector Dropdown -->
    <div class="method-selector-wrapper">
        <label for="method-dropdown" class="method-label">
            <?php _e("Choose your verification method:", "two-factor-login-telegram"); ?>
        </label>
        <div class="method-dropdown-container">
            <select id="method-dropdown" class="method-dropdown">
                <?php
                foreach ($enabled_providers as $key => $provider):
                    // Map keys for backward compatibility
                    $method_key = ($key === 'authenticator') ? 'totp' : $key;

                    // Skip recovery codes in dropdown
                    if ($key === 'recovery_codes') continue;

                    // Check if user has this method available
                    // Handle both hardcoded providers and external providers
                    $available_key = ($key === 'authenticator') ? 'totp' : $key;
                    if (!isset($user_available_methods[$available_key]) || !$user_available_methods[$available_key]) continue;
                ?>
                    <option value="<?php echo esc_attr($method_key); ?>"
                            <?php echo $default_method === $method_key ? 'selected' : ''; ?>
                            data-icon="<?php echo esc_url($provider->get_icon()); ?>">
                        <?php echo esc_html($provider->get_name()); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="dropdown-arrow">▼</div>
        </div>
    </div>
<?php endif; ?>

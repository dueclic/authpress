# AuthPress Custom 2FA Providers Developer Guide

This guide explains how to create custom 2FA providers for AuthPress using the extensibility system with WordPress filters.

## Overview

AuthPress provides an extensible architecture that allows developers to create custom 2FA providers through a simple filter-based system. All external providers use the `authpress_` prefix for consistency.

## Quick Start

### 1. Register Your Provider

Use the `authpress_register_providers` filter to register your provider:

```php
function my_plugin_register_provider($providers) {
    $providers['my_provider'] = 'MyPlugin\\Providers\\My_Provider_Class';
    return $providers;
}
add_filter('authpress_register_providers', 'my_plugin_register_provider');
```

### 2. Create Your Provider Class

Extend the `Abstract_Provider` class:

```php
namespace MyPlugin\\Providers;

use AuthPress\\Providers\\Abstract_Provider;

class My_Provider_Class extends Abstract_Provider
{
    public function __construct()
    {
        parent::__construct(
            'my_provider',              // Unique provider key
            'My Provider',              // Display name
            'Description of provider',   // Description
            'https://example.com/icon.png' // Icon URL (optional)
        );
    }
    
    // Implement required methods...
}
```

### 3. Create Template (Optional)

Create a template file for custom UI:
- File: `templates/provider-templates/provider-my_provider.php`
- The template will be loaded automatically

## Architecture

### Provider Registry

The `AuthPress_Provider_Registry` class manages all providers:

- **Built-in providers**: telegram, email, authenticator, recovery_codes
- **External providers**: Loaded via `authpress_register_providers` filter
- **Auto-loading**: External providers are loaded automatically when accessed

### Filter System

All external provider filters use the `authpress_` prefix:

- `authpress_register_providers` - Register new providers
- `authpress_{provider_key}_provider_enabled` - Check if specific provider is enabled
- `authpress_provider_enabled` - Global provider enabled filter
- `authpress_{provider_key}_provider_configured` - Check if provider is configured
- `authpress_provider_configured` - Global provider configuration filter
- `authpress_{provider_key}_send_code` - Handle code sending
- `authpress_provider_send_code` - Global code sending filter

## Base Classes

### Abstract_Provider

Base class for all providers with built-in helper methods for external providers:

```php
abstract class Abstract_Provider
{
    // Constructor for external providers (built-in providers don't use this)
    public function __construct($key = null, $name = null, $description = null, $icon_url = null);
    
    // Helper methods available to all providers
    protected function store_user_codes($user_id, $codes, $options = []);
    protected function get_user_codes($user_id);
    protected function store_user_settings($user_id, $settings);
    protected function get_user_settings($user_id);
    protected function generate_numeric_code($length = 6);
    
    // Default implementations (can be overridden)
    public function send_code($code, $user_id, $options = []);
    public function validate_code($code, $user_id);
    public function generate_codes($user_id, $options = []);
    public function has_codes($user_id);
    public function delete_user_codes($user_id);
    public function is_enabled();
    public function is_configured();
}
```

### Required Methods

Every provider must implement:

```php
// Validate user-provided code
public function validate_code($code, $user_id);

// Generate new codes for user
public function generate_codes($user_id, $options = []);

// Check if user has active codes
public function has_codes($user_id);

// Delete all user codes
public function delete_user_codes($user_id);

// Provider metadata
public function get_key();
public function get_name();
public function get_description();
public function get_icon();
```

## Complete Example: SMS Provider with Aimon

Here's a complete working example of an SMS provider:

### 1. Main Plugin File (`authpress-sms-aimon.php`)

```php
<?php
/**
 * Plugin Name: AuthPress SMS Provider - Aimon
 * Description: SMS 2FA Provider using Aimon service
 * Version: 1.0.0
 */

class AuthPress_SMS_Aimon_Plugin
{
    public function init()
    {
        // Load provider class
        require_once __DIR__ . '/class-sms-aimon-provider.php';
        
        // Register provider
        add_filter('authpress_register_providers', [$this, 'register_provider']);
    }
    
    public function register_provider($providers)
    {
        $providers['sms_aimon'] = 'MyPlugin\\SMS_Aimon_Provider';
        return $providers;
    }
}

(new AuthPress_SMS_Aimon_Plugin())->init();
```

### 2. Provider Class (`class-sms-aimon-provider.php`)

```php
<?php
namespace MyPlugin;

use AuthPress\\Providers\\Abstract_Provider;

class SMS_Aimon_Provider extends Abstract_Provider
{
    public function __construct()
    {
        parent::__construct(
            'sms_aimon',
            __('SMS via Aimon', 'my-plugin'),
            __('Receive codes via SMS using Aimon service', 'my-plugin'),
            plugin_dir_url(__FILE__) . 'assets/sms-icon.png'
        );
    }
    
    public function is_configured()
    {
        $providers = get_option('wp_factor_providers', []);
        $config = $providers['sms_aimon'] ?? [];
        
        return !empty($config['api_key']) && !empty($config['sender_id']);
    }
    
    public function send_code($code, $user_id, $options = [])
    {
        $phone = $this->get_user_phone($user_id);
        if (!$phone) return false;
        
        return $this->send_sms_via_aimon($phone, "Your code: {$code}");
    }
    
    public function generate_codes($user_id, $options = [])
    {
        // Generate 6-digit code
        $codes = [sprintf('%06d', random_int(0, 999999))];
        
        // Send immediately
        if (!$this->send_code($codes[0], $user_id)) {
            return false;
        }
        
        // Store for validation
        $this->store_user_codes($user_id, $codes);
        
        return $codes;
    }
    
    // ... implement other required methods
}
```

### 3. Template File (`templates/provider-templates/provider-sms_aimon.php`)

```php
<div class="authpress-section">
    <h2>SMS Authentication</h2>
    
    <?php if ($user_has_method): ?>
        <p>✅ SMS 2FA is active</p>
        <button class="button" onclick="disableSMS()">Disable SMS 2FA</button>
    <?php else: ?>
        <p>Configure SMS to enable this 2FA method</p>
        <input type="tel" id="phone-number" placeholder="+1234567890">
        <button class="button button-primary" onclick="enableSMS()">Enable SMS 2FA</button>
    <?php endif; ?>
</div>
```

## Provider Configuration

### Integrated Configuration

Since version 3.6.0, all external providers are configured directly in the AuthPress providers interface. Provider settings are stored in the main `wp_factor_providers` option:

```php
// Retrieve provider configuration from AuthPress settings
public function is_configured()
{
    $providers = get_option('wp_factor_providers', []);
    $config = $providers[$this->get_key()] ?? [];
    
    return !empty($config['api_key']) && !empty($config['other_setting']);
}

public function send_code($code, $user_id, $options = [])
{
    $providers = get_option('wp_factor_providers', []);
    $config = $providers[$this->get_key()] ?? [];
    
    $api_key = $config['api_key'] ?? '';
    $setting = $config['other_setting'] ?? '';
    
    // Use configuration...
}
```

### Configuration Templates

Create a configuration template that will be automatically included in the AuthPress providers page:

**File:** `templates/admin/provider-configs/{provider_key}.php`

```php
<?php
// Configuration template for your provider
$current_config = $providers[$provider->get_key()] ?? [];
$api_key = $current_config['api_key'] ?? '';
?>

<div class="form-group">
    <label for="my_provider_api_key">
        <strong><?php _e('API Key:', 'my-textdomain'); ?></strong>
    </label>
    <input type="password" 
           name="wp_factor_providers[<?php echo esc_attr($provider->get_key()); ?>][api_key]" 
           id="my_provider_api_key"
           value="<?php echo esc_attr($api_key); ?>" 
           class="regular-text" />
    <p class="description">
        <?php _e('Enter your API key from the service provider.', 'my-textdomain'); ?>
    </p>
</div>
```

### User Data Storage

Use the built-in helper methods:

```php
// Store user-specific settings
$this->store_user_settings($user_id, [
    'phone_number' => '+1234567890',
    'preferences' => ['sms_time' => 'business_hours']
]);

// Store verification codes (automatically hashed)
$this->store_user_codes($user_id, ['123456'], ['expires' => time() + 600]);

// Retrieve user data
$settings = $this->get_user_settings($user_id);
$codes_data = $this->get_user_codes($user_id);
```

## Template System

### Template Loading

Templates are loaded automatically based on provider key:

1. `templates/provider-templates/provider-{provider_key}.php` (specific)
2. `templates/provider-templates/generic-provider.php` (fallback)

### Template Variables

Your template receives these variables:

```php
$key            // Provider key
$data           // Provider data array
$provider       // Provider instance
$user_has_method // Boolean - user has method enabled
$current_user_id // Current user ID
$user_config    // User 2FA configuration
```

### Template Example

```php
<?php if (!defined('ABSPATH')) exit; ?>

<div class="authpress-section">
    <h2>
        <img src="<?php echo esc_url($data['icon']); ?>" alt="<?php echo esc_attr($data['name']); ?>" />
        <?php echo esc_html($data['name']); ?>
    </h2>
    
    <?php if ($user_has_method): ?>
        <!-- User has this method enabled -->
        <div class="notice notice-success">
            <p>✅ <?php echo esc_html($data['name']); ?> is configured and active.</p>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('disable_' . $key); ?>
            <input type="hidden" name="action" value="disable_<?php echo esc_attr($key); ?>">
            <button type="submit" class="button button-secondary">
                Disable <?php echo esc_html($data['name']); ?>
            </button>
        </form>
    <?php else: ?>
        <!-- User needs to set up this method -->
        <div class="notice notice-info">
            <p>Configure <?php echo esc_html($data['name']); ?> to enable this 2FA method.</p>
        </div>
        
        <!-- Custom setup UI here -->
        <div class="setup-section">
            <!-- Your provider-specific setup form -->
        </div>
    <?php endif; ?>
</div>
```

## Hooks and Actions

### Available Actions

```php
// Code sending events
do_action('authpress_{provider_key}_code_sent', $user_id, $destination, $result);
do_action('authpress_provider_code_sent', $provider_key, $user_id, $destination, $result);

// Code validation events
do_action('authpress_{provider_key}_code_validated', $user_id, $code);
do_action('authpress_provider_code_validated', $provider_key, $user_id, $code);

// Provider enabled/disabled events
do_action('authpress_{provider_key}_enabled', $user_id);
do_action('authpress_{provider_key}_disabled', $user_id);
```

### Available Filters

```php
// Provider registration
add_filter('authpress_register_providers', function($providers) {
    $providers['my_key'] = 'My_Provider_Class';
    return $providers;
});

// Provider state
add_filter('authpress_{provider_key}_provider_enabled', function($enabled) {
    return $enabled && my_custom_check();
});

// Message customization
add_filter('authpress_{provider_key}_message', function($message, $code, $user_id) {
    return "Custom message: {$code}";
}, 10, 3);

// Code validation
add_filter('authpress_{provider_key}_validate_code', function($valid, $code, $user_id) {
    // Custom validation logic
    return $valid;
}, 10, 3);
```

## Best Practices

### Security

1. **Always hash codes**: Use `$this->hash_code()` for storage
2. **Validate nonces**: Use WordPress nonces for forms
3. **Sanitize input**: Use appropriate sanitization functions
4. **Time limits**: Implement code expiration
5. **Rate limiting**: Prevent code spam

```php
public function validate_code($code, $user_id)
{
    $code = $this->normalize_code($code);
    $code_hash = $this->hash_code($code);
    
    $data = $this->get_user_codes($user_id);
    if (!$data) return false;
    
    foreach ($data['codes'] as $index => $stored) {
        // Check expiration
        if (time() - $stored['created'] > 600) continue; // 10 minutes
        
        if (!$stored['used'] && hash_equals($stored['code'], $code_hash)) {
            // Mark as used
            $data['codes'][$index]['used'] = true;
            $this->store_user_codes($user_id, [], $data);
            return true;
        }
    }
    
    return false;
}
```

### Error Handling

```php
public function send_code($code, $user_id, $options = [])
{
    try {
        $result = $this->external_api_call($code, $user_id);
        
        if ($result['success']) {
            do_action('authpress_sms_code_sent', $user_id, $result);
            return true;
        } else {
            error_log("SMS send failed: " . $result['error']);
            return false;
        }
    } catch (Exception $e) {
        error_log("SMS provider exception: " . $e->getMessage());
        return false;
    }
}
```

### Performance

1. **Cache API responses** when appropriate
2. **Use transients** for temporary data
3. **Lazy load** external dependencies
4. **Batch operations** when possible

### Internationalization

```php
public function __construct()
{
    parent::__construct(
        'my_provider',
        __('My Provider', 'my-textdomain'),
        __('Description of my provider', 'my-textdomain'),
        $this->get_icon_url()
    );
}
```

## Testing Your Provider

### Basic Tests

1. **Registration**: Check if provider appears in AuthPress settings
2. **Enable/Disable**: Test provider activation
3. **Code Generation**: Verify codes are generated and sent
4. **Code Validation**: Test both valid and invalid codes
5. **Expiration**: Ensure codes expire properly
6. **Security**: Test with malicious inputs

### Debug Mode

Enable WordPress debug mode during development:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Add debug logging to your provider
error_log('SMS Provider: Code sent to ' . $phone_number);
```

## Deployment

### Plugin Structure

```
my-authpress-provider/
├── authpress-my-provider.php      # Main plugin file
├── class-my-provider.php          # Provider class
├── templates/
│   └── provider-my_provider.php   # UI template
├── assets/
│   ├── icon.png                   # Provider icon
│   ├── script.js                  # Frontend JS
│   └── style.css                  # Styles
├── languages/                     # Translation files
├── readme.txt                     # WordPress plugin readme
└── LICENSE                        # License file
```

### WordPress Plugin Headers

```php
<?php
/**
 * Plugin Name: AuthPress Provider - My Service
 * Plugin URI: https://example.com
 * Description: Custom 2FA provider for AuthPress using My Service
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: authpress-my-provider
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 */
```

## Troubleshooting

### Common Issues

1. **Provider not appearing**: Check class autoloading and filter registration
2. **Codes not sending**: Verify API credentials in AuthPress → Providers configuration
3. **Configuration not showing**: Ensure config template is at `templates/admin/provider-configs/{key}.php`
4. **Template not loading**: Ensure correct file naming and placement
5. **Validation failing**: Check code normalization and hashing
6. **Settings not saving**: Verify configuration template uses correct field names

### Debug Information

```php
// Add to your provider for debugging
public function get_debug_info()
{
    return [
        'enabled' => $this->is_enabled(),
        'configured' => $this->is_configured(),
        'api_status' => $this->check_api_status(),
        'user_count' => $this->get_active_users_count()
    ];
}
```

## Resources

- **AuthPress Documentation**: [Link to main docs]
- **WordPress Plugin Development**: https://developer.wordpress.org/plugins/
- **WordPress Hooks Reference**: https://developer.wordpress.org/reference/hooks/
- **Example Provider Code**: See `/examples/sms-provider-aimon/` directory

## Support

For questions about developing custom providers:

1. Check the example implementation in `/examples/sms-provider-aimon/`
2. Review AuthPress source code for built-in providers
3. Test with WordPress debug mode enabled
4. Check WordPress and PHP error logs

---

*This guide covers the complete process of creating custom AuthPress 2FA providers. The system is designed to be flexible and secure while maintaining simplicity for developers.*

![AuthPress](https://github.com/debba/wp-two-factor-authentication-with-telegram/raw/master/.wordpress-org/assets/banner-772x250.png?raw=true)

# AuthPress - Advanced WordPress 2FA Plugin

**AuthPress** is a comprehensive two-factor authentication plugin for WordPress that started with Telegram support and has evolved into a flexible multi-provider 2FA solution. Secure your WordPress site with multiple authentication methods and extensible provider system.

## 🚀 Key Features

### 🔐 Multiple Authentication Providers

- **Telegram** 📱: Our original provider - receive codes via Telegram messages
- **Email** 📧: Send verification codes via email
- **Authenticator Apps** 🔐: TOTP support for Google Authenticator, Authy, Microsoft Authenticator, and more
- **Recovery Codes** 🔑: Emergency backup codes for account recovery
- **Custom Providers** 🔧: Extensible system supporting SMS, Passkey, and other custom implementations

### 📱 Telegram Provider (Original)

- Instant authentication code delivery via Telegram bot
- Failed login attempt notifications
- Works on any device with Telegram installed
- Simple setup with Bot Token from @BotFather
- Admin alerts for security monitoring

### 📧 Email Provider

- Send verification codes via email
- Configurable token duration
- Compatible with all email clients
- Fallback option when other methods unavailable

### 🔐 Authenticator Apps (TOTP)

- Standard TOTP (Time-based One-Time Password) support
- Works completely offline
- Compatible with all major authenticator apps
- Easy setup with QR codes or manual entry
- 30-second rotating codes

### 🔧 Extensibility & Custom Providers

- **Developer-friendly**: Simple API for creating custom providers
- **SMS Support**: Ready-to-use SMS providers (via extensions)
- **Passkey Support**: Modern WebAuthn implementation available
- **Plugin Architecture**: Each provider can be a separate plugin
- **Seamless Integration**: All providers work together in the same interface

### 🛡️ Security & Management

- **Recovery Codes**: Single-use emergency access codes
- **Advanced Logging**: Detailed activity monitoring with pagination
- **Centralized Management**: All providers configured in one place
- **Rate Limiting**: Protection against brute force attacks
- **Secure Storage**: Hashed codes and encrypted user data

## Installation

1. Download the plugin
2. Upload the folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to **Settings > AuthPress** to configure providers

## Configuration

### 1. Provider Configuration

Go to **Settings > AuthPress > Providers** to configure available 2FA methods:

- **Telegram Provider**: Enter Bot Token and configure notifications
- **Email Provider**: Set token duration and email templates
- **Authenticator Provider**: Enable TOTP support for authenticator apps
- **Recovery Codes**: Configure emergency access codes
- **Custom Providers**: Configure any installed third-party providers (SMS, Passkey, etc.)

### 2. Telegram Configuration

1. Create a Telegram bot via [@BotFather](https://telegram.me/botfather)
2. Get the Bot Token
3. Enter the token in the Telegram Provider section
4. Configure failed login notifications (optional)

### 3. User Configuration

Users can enable and configure 2FA in their WordPress profile:

1. Navigate to **Users > Your Profile**
2. Scroll to the **AuthPress** section
3. Choose and configure your preferred 2FA methods:
   - **Telegram**: Enter Chat ID and verify with test code
   - **Email**: Automatically uses account email
   - **Authenticator**: Scan QR code or manually enter secret
   - **Recovery Codes**: Generate and securely store backup codes
4. Users can enable multiple providers for redundancy
5. Test each method before relying on it for login

## Usage

### 2FA Login

When a user with 2FA enabled logs in:

1. Enter username and password
2. Redirected to 2FA verification page
3. Choose authentication method (if both configured)
4. Enter received code
5. Access the site

### Authentication Methods

#### Telegram
- Receive a 6-digit code via Telegram message
- Instant delivery with confirmation buttons
- Codes expire after 5 minutes for security
- Admin notifications for failed attempts

#### Email
- Verification codes sent to registered email address
- Configurable expiration time (default: 20 minutes)
- HTML formatted emails with security information
- Works with all email providers

#### Authenticator App (TOTP)
- Use Google Authenticator, Authy, Microsoft Authenticator, or any TOTP app
- 6-digit codes that refresh every 30 seconds
- Works completely offline
- Scan QR code for quick setup

#### Recovery Codes
- Emergency single-use backup codes (typically 8 codes)
- Use when primary methods are unavailable
- Regenerate new codes anytime
- Store securely offline

#### Custom Providers
- SMS codes via various SMS providers
- Passkey/WebAuthn for biometric authentication
- Any custom implementation following AuthPress API

## Administration

### Provider Management

Administrators can:

- Enable/disable providers globally
- Configure settings for each provider
- View activity logs
- Manage security notifications

### Logs and Monitoring

- **Bot Logs**: View all Telegram bot activities
- **Authentication Logs**: Monitor login attempts and authentication
- **Notifications**: Receive alerts about suspicious access attempts

### User Management

- View user 2FA status
- Disable 2FA for specific users
- Manage individual configurations

## Compatibility

### Supported Authenticator Apps

- Google Authenticator
- Microsoft Authenticator
- Authy
- 1Password
- Bitwarden
- KeePass
- And any other TOTP-compatible app

### WordPress Versions

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

## Security

### Best Practices

- Always use HTTPS
- Generate recovery codes and store them securely
- Enable both providers for maximum flexibility
- Regularly monitor access logs
- Configure notifications for failed login attempts

### Security Features

- Automatically expiring codes
- Rate limiting on access attempts
- Complete activity logging
- Server-side validation
- Protection against brute force attacks

## 🔧 For Developers

### Creating Custom Providers

AuthPress features a powerful extensible architecture that allows developers to create custom 2FA providers. The system supports:

- **SMS Providers**: Integrate with services like Twilio, MessageBird, etc.
- **Push Notifications**: Mobile app-based authentication
- **Hardware Tokens**: YubiKey, RSA tokens, etc.
- **Biometric Authentication**: Passkey/WebAuthn support
- **Custom APIs**: Any external authentication service

### Getting Started with Custom Providers

1. **Study the Documentation**: Check `https://github.com/dueclic/authpress/custom_providers/DEVELOPER-GUIDE-CUSTOM-PROVIDERS.md`
2. **Simple API**: Extend the `Abstract_Provider` class
3**WordPress Integration**: Use standard WordPress hooks and filters

```php
// Register your provider
function my_sms_provider_register($providers) {
    $providers['my_sms'] = 'MyPlugin\\SMS_Provider';
    return $providers;
}
add_filter('authpress_register_providers', 'my_sms_provider_register');
```

## 📞 Support

For support and assistance:

- **Email**: info@dueclic.com
- **WordPress.org**: [Support Section](https://wordpress.org/support/plugin/two-factor-login-telegram/)
- **GitHub**: [Issues](https://github.com/dueclic/authpress/issues)
- **Developer Documentation**: `https://github.com/dueclic/authpress/custom_providers/DEVELOPER-GUIDE-CUSTOM-PROVIDERS.md`

## Changelog

### Version 4.0.x - "AuthPress" (Current)

- 🎉 **Rebranded to AuthPress** - reflecting the evolution beyond Telegram
- 🔧 **Extensible Provider System** - developers can create custom 2FA providers
- 📧 **Email Provider** - built-in email-based 2FA support
- 🔐 **Enhanced TOTP** - improved authenticator app support
- 🛠️ **Developer API** - comprehensive system for custom providers
- 📊 **Professional Logging** - WP_List_Table implementation with pagination
- 🗄️ **Database Migration** - moved from WordPress options to MySQL tables
- 🎨 **UI/UX Overhaul** - completely redesigned interface
- 🌐 **Better i18n** - improved internationalization support
- 🔒 **Enhanced Security** - improved validation and rate limiting

### Version 3.5.x

- Enhanced logs system with professional interface
- Better user management in admin area
- Improved Chat ID validation
- JavaScript translations
- Template system for error handling

### Version 3.0-3.4

- Webhook system for Telegram user_id retrieval
- Activity logs implementation
- WordPress 6.x compatibility updates
- Security improvements and bug fixes

### Earlier Versions (1.0-2.9)

- Original Telegram-only implementation
- Basic 2FA functionality
- WordPress compatibility updates
- Foundation features and security fixes

## License

This plugin is released under the GPL v2 or later license.

## Authors

- **DueClic** - [info@dueclic.com](mailto:info@dueclic.com)
- **GitHub**: [debba](https://github.com/debba)

---

**AuthPress** - Advanced security for WordPress with flexible and user-friendly two-factor authentication.

# AuthPress - Two-Factor Authentication with Telegram and Authenticator Apps

A WordPress plugin for two-factor authentication that supports both Telegram and authenticator apps (Google Authenticator, Authy, Microsoft Authenticator, etc.).

## Features

### 🔐 Authentication Providers

- **Telegram**: Receive authentication codes via Telegram messages
- **Authenticator Apps**: Use standard TOTP apps like Google Authenticator, Authy, Microsoft Authenticator
- **Multi-Provider Support**: Users can configure both methods for enhanced security

### 📱 Telegram Provider

- Automatic authentication code delivery
- Failed login attempt notifications
- Works on any device with Telegram
- Simple configuration via Bot Token

### 🔐 Authenticator Provider

- TOTP (Time-based One-Time Password) standard
- Works offline (no internet connection required)
- Compatible with all major authenticator apps
- QR codes for quick setup

### 🛡️ Security Features

- Recovery codes for emergency access
- Detailed activity logging
- Centralized provider management
- Compatibility with existing systems

## Installation

1. Download the plugin
2. Upload the folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to **Settings > AuthPress** to configure providers

## Configuration

### 1. Provider Configuration

Go to **Settings > AuthPress > Providers** to:

- **Enable/Disable Providers**: Choose which 2FA methods to make available
- **Configure Telegram**: Enter Bot Token and configure notifications
- **Configure Authenticator**: Enable support for authenticator apps

### 2. Telegram Configuration

1. Create a Telegram bot via [@BotFather](https://telegram.me/botfather)
2. Get the Bot Token
3. Enter the token in the Telegram Provider section
4. Configure failed login notifications (optional)

### 3. User Configuration

Users can configure 2FA in their profile:

1. Go to **User Profile**
2. **AuthPress** section
3. Enable 2FA
4. Choose preferred method:
   - **Telegram**: Enter Chat ID and verify
   - **Authenticator**: Scan QR code or enter manual code
5. Configure recovery codes

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

- Receive a code via Telegram message
- Enter the code on the login page
- Code automatically expires after 5 minutes

#### Authenticator App

- Open your authenticator app
- Enter the displayed 6-digit code
- Code updates every 30 seconds

#### Recovery Codes

- Use one of the generated recovery codes
- Codes are single-use
- You can regenerate new codes when needed

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

## Support

For support and assistance:

- **Email**: info@dueclic.com
- **WordPress.org**: [Support Section](https://wordpress.org/support/plugin/two-factor-login-telegram/)
- **GitHub**: [Issues](https://github.com/dueclic/authpress/issues)

## Changelog

### Version 2.0.0

- ✨ New provider system (Telegram + Authenticator)
- 🔐 Support for TOTP authenticator apps
- 📱 Improved user interface for method selection
- 🛡️ Centralized settings management
- 📊 Enhanced logging for all methods
- 🔄 Compatibility with existing configurations

### Previous Versions

- Telegram support with notifications
- Recovery codes
- Activity logging
- User management

## License

This plugin is released under the GPL v2 or later license.

## Authors

- **DueClic** - [info@dueclic.com](mailto:info@dueclic.com)
- **GitHub**: [debba](https://github.com/debba)

---

**AuthPress** - Advanced security for WordPress with flexible and user-friendly two-factor authentication.

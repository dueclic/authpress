<?php

namespace Authpress;

use AuthPress\Providers\Abstract_Provider;

class AuthPress_Auth_Factory
{
    /**
     * @deprecated Use AuthPress_Provider_Registry::get() instead
     */
    const METHOD_TELEGRAM_OTP = 'telegram';
    const METHOD_EMAIL_OTP = 'email';
    const METHOD_RECOVERY_CODES = 'recovery_codes';
    const METHOD_TOTP = 'authenticator';

    /**
     * Create or get an authentication method instance
     * @param string $method_type The type of authentication method
     * @return Abstract_Provider|null The authentication method instance
     * @deprecated Use AuthPress_Provider_Registry::get() instead
     */
    public static function create($method_type)
    {
        // Map old constants to new keys
        $key_mapping = [
            self::METHOD_TELEGRAM_OTP => 'telegram',
            self::METHOD_EMAIL_OTP => 'email',
            self::METHOD_RECOVERY_CODES => 'recovery_codes',
            self::METHOD_TOTP => 'authenticator',
            'telegram_otp' => 'telegram',
            'email_otp' => 'email',
            'totp' => 'authenticator',
        ];

        $key = $key_mapping[$method_type] ?? $method_type;
        return AuthPress_Provider_Registry::get($key);
    }

    /**
     * Get the appropriate auth method based on login method
     * @param string $login_method The login method from form data
     * @return Abstract_Provider|null
     * @deprecated Use AuthPress_Provider_Registry::get_by_login_method() instead
     */
    public static function getByLoginMethod($login_method)
    {
        return AuthPress_Provider_Registry::get_by_login_method($login_method);
    }

    /**
     * Validate a code using the appropriate method
     * @param string $code The code to validate
     * @param int $user_id The user ID
     * @param string $login_method The login method
     * @return bool True if valid, false otherwise
     */
    public static function validateByMethod($code, $user_id, $login_method = 'telegram'): bool
    {
        $auth_method = AuthPress_Provider_Registry::get_by_login_method($login_method);
        if (!$auth_method) {
            return false;
        }

        return $auth_method->validate_code($code, $user_id);
    }

}

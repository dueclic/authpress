<?php

use AuthPress\Providers\Telegram_Provider;
use AuthPress\Providers\Email_Provider;
use AuthPress\Providers\TOTP_Provider;
use AuthPress\Providers\Recovery_Codes_Provider;
use AuthPress\Providers\Abstract_Provider;

class AuthPress_Auth_Factory
{
    const METHOD_TELEGRAM_OTP = 'telegram_otp';
    const METHOD_EMAIL_OTP = 'email_otp';
    const METHOD_RECOVERY_CODES = 'recovery_codes';
    const METHOD_TOTP = 'totp';

    /**
     * @var array Auth method instances cache
     */
    private static $instances = [];

    /**
     * Create or get an authentication method instance
     * @param string $method_type The type of authentication method
     * @return Abstract_Provider|null The authentication method instance
     */
    public static function create($method_type)
    {
        if (isset(self::$instances[$method_type])) {
            return self::$instances[$method_type];
        }

        switch ($method_type) {
            case self::METHOD_TELEGRAM_OTP:
                self::$instances[$method_type] = new Telegram_Provider();
                break;

            case self::METHOD_EMAIL_OTP:
                self::$instances[$method_type] = new Email_Provider();
                break;

            case self::METHOD_RECOVERY_CODES:
                self::$instances[$method_type] = new Recovery_Codes_Provider();
                break;

            case self::METHOD_TOTP:
                self::$instances[$method_type] = new TOTP_Provider();
                break;

            default:
                return null;
        }

        return self::$instances[$method_type];
    }

    /**
     * Get the appropriate auth method based on login method
     * @param string $login_method The login method from form data
     * @return AuthPress_Auth_Method|null
     */
    public static function getByLoginMethod($login_method)
    {
        switch ($login_method) {
            case 'recovery':
                return self::create(self::METHOD_RECOVERY_CODES);

            case 'totp':
                return self::create(self::METHOD_TOTP);

            case 'email':
                return self::create(self::METHOD_EMAIL_OTP);

            case 'telegram':
            default:
                return self::create(self::METHOD_TELEGRAM_OTP);
        }
    }

    /**
     * Validate a code using the appropriate method
     * @param string $code The code to validate
     * @param int $user_id The user ID
     * @param string $login_method The login method
     * @return bool True if valid, false otherwise
     */
    public static function validateByMethod($code, $user_id, $login_method = 'telegram')
    {
        $auth_method = self::getByLoginMethod($login_method);
        if (!$auth_method) {
            return false;
        }

        return $auth_method->validate_code($code, $user_id);
    }

}

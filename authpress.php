<?php

/**
 * Plugin Name: AuthPress
 * Plugin URI: https://blog.dueclic.com/wordpress-autenticazione-due-fattori-telegram/
 * Description: This plugin enables two factor authentication with Telegram by increasing your website security and sends an alert every time a wrong login occurs.
 * Version: 3.5.4
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 7.0
 * Author: dueclic
 * Author URI: https://www.dueclic.com
 * Text Domain: two-factor-login-telegram
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

error_reporting(E_ERROR);

if (!defined('ABSPATH')) {
    die;
}

define('WP_FACTOR_PLUGIN_VERSION', '3.6.0');

define('WP_FACTOR_AUTHCODE_EXPIRE_SECONDS', 60 * 20);

/**
 *
 * Full path to the WP Two Factor Telegram File
 *
 */

define('WP_FACTOR_TG_FILE', __FILE__);

define('WP_FACTOR_TG_GETME_TRANSIENT', 'tg_wp_factor_valid_bot');

/**
 *
 * The main plugin class
 *
 */

// Load Composer autoloader first
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once(dirname(__FILE__) . '/vendor/autoload.php');
}

require_once("includes/class-authpress-plugin.php");

function authpress_providers()
{

    $authpress_providers = get_option('wp_factor_providers', array(
        'authenticator' => array('enabled' => false),
        'telegram' => array('enabled' => false, 'bot_token' => '', 'failed_login_reports' => false),
        'email' => array('enabled' => false),
        'default_provider' => 'telegram'
    ));

    return apply_filters('authpress_providers', $authpress_providers);
}

function authpress_logo() {

    $plugin_logo = plugins_url('assets/img/plugin_logo.png', WP_FACTOR_TG_FILE);

    $plugin_logo = apply_filters_deprecated(
        'two_factor_login_telegram_logo',
        array($plugin_logo),
        '3.6.0',
        'authpress_logo',
        __('two_factor_login_telegram_logo filter is deprecated. Use authpress_logo instead.', 'two-factor-login-telegram')
    );

    $plugin_logo = apply_filters('authpress_logo', $plugin_logo);

    return $plugin_logo;
}

function authpress_tg_provider_bot_token_valid($bot_token)
{
    if (empty($bot_token)) {
        return false;
    }

    // Check if token has the expected Telegram bot token format
    // Telegram bot tokens are typically in format: 123456789:ABCdefGHIjklMNOpqrsTUVwxyz
    return preg_match('/^\d+:[A-Za-z0-9_-]+$/', $bot_token);
}


function WFT()
{
    return \Authpress\AuthPress_Plugin::get_instance();
}

WFT();


<?php

/**
 * Plugin Name: AuthPress
 * Plugin URI: https://blog.dueclic.com/wordpress-autenticazione-due-fattori-telegram/
 * Description: This plugin enables two factor authentication with Telegram by increasing your website security and sends an alert every time a wrong login occurs.
 * Version: 4.0.0
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

define('AUTHPRESS_PLUGIN_VERSION', '4.0.0');

if (!function_exists('get_auth_token_duration')) {
    function get_auth_token_duration() {
        $providers = authpress_providers();
        $duration = $providers['email']['token_duration'] ?? 20;
        return absint($duration) * 60;
    }
}



/**
 *
 * Full path to the AuthPress File
 *
 */

define('AUTHPRESS_PLUGIN_FILE', __FILE__);

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

require_once(dirname(__FILE__) . "/includes/class-authpress-plugin.php");

function authpress_provider_categories(
    $available_providers = array()
)
{
    $provider_categories = [
        'messaging' => [
            'title' => __("Messaging Providers", "two-factor-login-telegram"),
            'description' => __("Receive authentication codes directly via messaging platforms.", "two-factor-login-telegram"),
            'providers' => ['telegram', 'email']
        ],
        'authenticator' => [
            'title' => __("Authenticator Apps", "two-factor-login-telegram"),
            'description' => __("Time-based One-Time Password apps that generate codes offline.", "two-factor-login-telegram"),
            'providers' => ['authenticator']
        ]
    ];

    $provider_categories = apply_filters('authpress_provider_categories', $provider_categories);

    $categorized_providers = [];
    foreach ($provider_categories as $category) {
        $categorized_providers = array_merge($categorized_providers, $category['providers']);
    }

    $uncategorized_providers = array_diff(array_keys($available_providers), $categorized_providers);
// Exclude recovery_codes from being shown in admin interface - it's a backend-only provider
    $uncategorized_providers = array_diff($uncategorized_providers, ['recovery_codes']);
    if (!empty($uncategorized_providers)) {
        $provider_categories['other'] = [
            'title' => __("Other Providers", "two-factor-login-telegram"),
            'description' => __("Additional 2FA methods provided by plugins.", "two-factor-login-telegram"),
            'providers' => $uncategorized_providers
        ];
    }

    return $provider_categories;

}

function authpress_providers()
{

    $authpress_providers = get_option('authpress_providers', array(
        'authenticator' => array('enabled' => false),
        'telegram' => array('enabled' => false, 'bot_token' => '', 'failed_login_reports' => false),
        'email' => array('enabled' => false)
    ));

    return apply_filters('authpress_providers', $authpress_providers);
}

function authpress_provider_config(
    $provider_name
)
{
    $providers = authpress_providers();
    if (isset($providers[$provider_name])) {
        return $providers[$provider_name];
    }
    return null;
}

/**
 * @param $category
 * @return bool
 */

function authpress_category_has_providers(
    $category,
    $available_providers
){

    $has_available_providers = false;

    foreach ($category['providers'] as $provider_key) {
        $provider = $available_providers[$provider_key] ?? null;
        if ($provider) {
            $has_available_providers = true;
            break;
        }
    }

    return apply_filters('authpress_category_has_providers', $has_available_providers, $category, $available_providers);
}

function authpress_logo()
{

    $plugin_logo = plugins_url('assets/images/plugin_logo.png', AUTHPRESS_PLUGIN_FILE);

    $plugin_logo = apply_filters_deprecated(
        'two_factor_login_telegram_logo',
        array($plugin_logo),
        '4.0.0',
        'authpress_logo',
        __('two_factor_login_telegram_logo filter is deprecated. Use authpress_logo instead.', 'two-factor-login-telegram')
    );

    return apply_filters('authpress_logo', $plugin_logo);
}

function authpress_get_template($template_path, $context = array(), $full_path = false)
{

    $full_path = $full_path ? $template_path : plugin_dir_path(AUTHPRESS_PLUGIN_FILE) . $template_path;

    if (!file_exists($full_path)) {
        return '';
    }

    // Extract context variables
    if (!empty($context)) {
        extract($context, EXTR_SKIP);
    }

    ob_start();
    include $full_path;
    return ob_get_clean();
}

function WFT()
{
    return \Authpress\AuthPress_Plugin::get_instance();
}

WFT();


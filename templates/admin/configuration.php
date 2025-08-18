<?php

/**
 * @var array $providers Legacy providers array for backward compatibility
 */

if (isset($_GET['tab'])) {
    $active_tab = sanitize_text_field($_GET['tab']);
} else {
    $active_tab = 'providers';
}

?>

<div class="authpress-ui ap-wpbody-content">
    <div class="ap-container">

    <div class="ap-topbar">
        <div class="ap-logo-section">
            <img src="<?php echo plugins_url("assets/img/plugin_logo.png", WP_FACTOR_TG_FILE); ?>"
                 alt="AuthPress"
                 class="ap-logo"
                 width="120"
                 height="auto">
            <span class="ap-logo-text">AuthPress</span>
        </div>
        <nav class="ap-tabs" aria-label="Secondary">
            <a href="<?php echo admin_url('options-general.php?page=authpress-conf&tab=providers'); ?>"
               class="ap-tab <?php echo $active_tab == 'providers' ? 'ap-tab--active' : ''; ?>">
                <?php _e("Providers", "two-factor-login-telegram"); ?>
            </a>
            <a href="<?php echo admin_url('options-general.php?page=authpress-conf&tab=howto'); ?>"
               class="ap-tab <?php echo $active_tab == 'howto' ? 'ap-tab--active' : ''; ?>">
                <?php _e("FAQ", "two-factor-login-telegram"); ?>
            </a>
            <a href="<?php echo admin_url('options-general.php?page=authpress-conf&tab=logs'); ?>"
               class="ap-tab <?php echo $active_tab == 'logs' ? 'ap-tab--active' : ''; ?>">
                <?php _e("Logs", "two-factor-login-telegram"); ?>
            </a>
            <a href="<?php echo admin_url('options-general.php?page=authpress-conf&tab=settings'); ?>"
               class="ap-tab <?php echo $active_tab == 'settings' ? 'ap-tab--active' : ''; ?>">
                <?php _e("Settings", "two-factor-login-telegram"); ?>
            </a>
            <a href="<?php echo admin_url('options-general.php?page=authpress-conf&tab=suggestions'); ?>"
               class="ap-tab <?php echo $active_tab == 'suggestions' ? 'ap-tab--active' : ''; ?>">
                <?php _e("Suggestions", "two-factor-login-telegram"); ?>
            </a>
        </nav>
    </div>

    <?php
    switch($active_tab) {
        case 'logs':
            include dirname(__FILE__) . '/logs.php';
            break;
        case 'howto':
            include dirname(__FILE__) . '/howto.php';
            break;
        case 'settings':
            include dirname(__FILE__) . '/settings.php';
            break;
        case 'suggestions':
            include dirname(__FILE__) . '/suggestions.php';
            break;
        default:
            include dirname(__FILE__) . '/providers.php';
            break;
    }
    ?>
    </div>
    <?php do_action("authpress_copyright"); ?>

</div>



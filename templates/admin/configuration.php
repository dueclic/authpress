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

<div class="wrap">

    <div class="heading-top">
        <div class="cover-tg-plugin">
        </div>
        <h1><?php _e("AuthPress", "two-factor-login-telegram"); ?> - <?php _e("Configuration", "two-factor-login-telegram"); ?></h1>
    </div>

    <h2 class="wpft-tab-wrapper nav-tab-wrapper">
        <a href="<?php echo admin_url('options-general.php?page=authpress-conf&tab=providers'); ?>"
           class="nav-tab <?php echo $active_tab == 'providers' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-plugins"></span> <?php _e("Providers", "two-factor-login-telegram"); ?>
        </a>
        <a href="<?php echo admin_url('options-general.php?page=authpress-conf&tab=howto'); ?>"
           class="nav-tab <?php echo $active_tab == 'howto' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-editor-help"></span> <?php _e("FAQ", "two-factor-login-telegram"); ?>
        </a>

        <a href="<?php echo admin_url('options-general.php?page=authpress-conf&tab=logs'); ?>"
           class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-list-view"></span> <?php _e("Logs", "two-factor-login-telegram"); ?>
        </a>
        <a href="<?php echo admin_url('options-general.php?page=authpress-conf&tab=settings'); ?>"
           class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-settings"></span> <?php _e("Settings", "two-factor-login-telegram"); ?>
        </a>
        <a href="<?php echo admin_url('options-general.php?page=authpress-conf&tab=suggestions'); ?>"
           class="nav-tab <?php echo $active_tab == 'suggestions' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-heart"></span> <?php _e("Suggestions", "two-factor-login-telegram"); ?>
        </a>
    </h2>

    <div class="wpft-container">

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

</div>


<?php do_action("authpress_copyright"); ?>

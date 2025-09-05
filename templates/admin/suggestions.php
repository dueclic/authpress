<?php
/**
 * Suggestions tab view
 */
?>

<h2><?php _e("Suggestions", "two-factor-login-telegram"); ?></h2>

<div id="suggestions">
    <p>
        <?php _e("We would love to hear your feedback and suggestions! You can share them with us in different ways:", "two-factor-login-telegram"); ?>
    </p>
    <ol>
        <li>
            <?php printf(__('Consult our website <a target="_blank" href="%s">authpress.dev</a>.',
                    "two-factor-login-telegram"),
                    'https://authpress.dev'); ?>
        </li>
        <li>
            <?php _e('Send us an email at <a href="mailto:info@dueclic.com">info@dueclic.com</a>.',
                    "two-factor-login-telegram"); ?>
        </li>
        <li>
            <?php
            printf(__('Visit the <a href="%s" target="_blank">support section on WordPress.org</a>.',
                    "two-factor-login-telegram"),
                    'https://wordpress.org/support/plugin/two-factor-login-telegram/');
            ?>
        </li>
        <li>
            <?php
            printf(__('Submit your issues or ideas on our <a href="%s" target="_blank">GitHub project page</a>.',
                    "two-factor-login-telegram"),
                    'https://github.com/dueclic/authpress/issues');
            ?>
        </li>
    </ol>
</div>

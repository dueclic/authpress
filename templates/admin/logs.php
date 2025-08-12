<?php
/**
 * Logs tab view
 * @var array $providers
 */

use Authpress\Authpress_Logs_List_Table;

global $wpdb;

$activities_table = $wpdb->prefix . 'wp2fat_activities';

// Create an instance of our package class
$logs_list_table = new Authpress_Logs_List_Table();

// Handle clear logs action
if (isset($_POST['clear_logs']) && wp_verify_nonce($_POST['_wpnonce'], 'clear_telegram_logs')) {
    $wpdb->query("DELETE FROM $activities_table");
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Logs cleared successfully.', 'two-factor-login-telegram') . '</p></div>';
}

// Process bulk actions
$logs_list_table->process_bulk_action();

// Prepare the table
$logs_list_table->prepare_items();
?>

<h2><?php _e("Logs", "two-factor-login-telegram"); ?></h2>

<form method="post">
    <?php wp_nonce_field('clear_telegram_logs'); ?>
    <input type="submit" name="clear_logs" class="tg-action-button" value="<?php _e('Clear Logs', 'two-factor-login-telegram'); ?>" onclick="return confirm('<?php _e('Are you sure you want to clear all logs?', 'two-factor-login-telegram'); ?>')">
</form>

<br>

<form method="get">
    <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
    <input type="hidden" name="tab" value="logs" />
    <?php $logs_list_table->display(); ?>
</form>

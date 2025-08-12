<?php

namespace Authpress;

class AuthPress_Logger
{
    public function log_action($action, $data = array())
    {
        global $wpdb;

        $activities_table = $wpdb->prefix . 'wp2fat_activities';

        $wpdb->insert(
            $activities_table,
            array(
                'timestamp' => current_time('mysql'),
                'action' => $action,
                'data' => maybe_serialize($data)
            ),
            array('%s', '%s', '%s')
        );

        // Clean up old entries - keep only last 1000 entries
        $wpdb->query("DELETE FROM $activities_table WHERE id NOT IN (SELECT id FROM (SELECT id FROM $activities_table ORDER BY timestamp DESC LIMIT 1000) temp_table)");
    }
}

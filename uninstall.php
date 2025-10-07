<?php
// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if we should delete all data
$delete_data = get_option('tpgs_delete_data_on_uninstall', false);

if ($delete_data) {
    // Delete all user meta
    $users = get_users(array(
        'fields' => 'ID'
    ));
    
    foreach ($users as $user_id) {
        // Delete pod data
        for ($i = 1; $i <= 12; $i++) {
            delete_user_meta($user_id, 'tpgs_pod_' . $i);
        }
        
        // Delete gamification stats
        delete_user_meta($user_id, 'tpgs_gamification_stats');
    }
    
    // Delete options
    delete_option('tpgs_vegetables');
    delete_option('tpgs_delete_data_on_uninstall');
    
    // Clear any scheduled events
    wp_clear_scheduled_hook('tpgs_daily_growth_tracker');
}
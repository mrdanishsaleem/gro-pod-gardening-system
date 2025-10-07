<?php
class TPGS_Cron_Handler
{
    public function __construct()
    {
        add_action('tpgs_daily_growth_tracker', array($this, 'process_daily_growth'));
        add_action('tpgs_weekly_streak_reset', array($this, 'reset_weekly_streaks'));
    }

    public function process_daily_growth()
    {
        $users = get_users(array(
            'meta_key' => 'tpgs_pod_1',
            'meta_compare' => 'EXISTS'
        ));

        foreach ($users as $user) {
            $this->process_user_pods($user->ID);
        }
    }

    private function process_user_pods($user_id)
    {
        // Use the new method to calculate actual days remaining for all pods
        TPGS_Pod_Manager::update_all_pods_days_remaining($user_id);
    }

    public function reset_weekly_streaks()
    {
        $users = get_users(array('meta_key' => 'tpgs_pod_1', 'meta_compare' => 'EXISTS'));
        // Use Monday of current week in WordPress timezone
        $current_week_start = wp_date('Y-m-d', strtotime('monday this week', current_time('timestamp')));

        foreach ($users as $user) {
            $weekly_streak = get_user_meta($user->ID, 'tpgs_weekly_streak', true) ?: [];
            $last_week_start = wp_date('Y-m-d', strtotime('-1 week', strtotime($current_week_start)));

            // Remove only the last week's data
            if (isset($weekly_streak[$last_week_start])) {
                unset($weekly_streak[$last_week_start]);
                update_user_meta($user->ID, 'tpgs_weekly_streak', $weekly_streak);
            }

            // Ensure current week is initialized if not set
            if (!isset($weekly_streak[$current_week_start])) {
                $weekly_streak[$current_week_start] = [];
                update_user_meta($user->ID, 'tpgs_weekly_streak', $weekly_streak);
            }
        }
    }
}

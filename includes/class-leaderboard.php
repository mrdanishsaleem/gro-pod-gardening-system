<?php
class TPGS_Leaderboard {
    public function __construct() {
        add_shortcode('tpgs_leaderboard', array($this, 'display_leaderboard'));
        add_action('tpgs_daily_growth_tracker', array($this, 'clear_leaderboard_cache'));
    }

    public static function get_top_gardeners($limit = 10) {
        $cache_key = 'tpgs_leaderboard_top_' . $limit;
        $cached = get_transient($cache_key);
        
        if (false !== $cached) {
            return $cached;
        }

        $users = get_users(array(
            'meta_key' => 'tpgs_gamification_stats',
            'number' => $limit
        ));

        // Filter users with valid stats and sort
        $users = array_filter($users, function($user) {
            $stats = get_user_meta($user->ID, 'tpgs_gamification_stats', true);
            return !empty($stats) && is_array($stats);
        });

        usort($users, function($a, $b) {
            $a_stats = get_user_meta($a->ID, 'tpgs_gamification_stats', true) ?: array('level' => 0, 'experience' => 0);
            $b_stats = get_user_meta($b->ID, 'tpgs_gamification_stats', true) ?: array('level' => 0, 'experience' => 0);
            
            return ($b_stats['level'] <=> $a_stats['level']) ?: ($b_stats['experience'] <=> $a_stats['experience']);
        });

        $users = array_slice($users, 0, $limit);
        set_transient($cache_key, $users, 12 * HOUR_IN_SECONDS);
        
        return $users;
    }

    public function display_leaderboard() {
        $gardeners = self::get_top_gardeners();
        
        ob_start(); ?>
        <div class="tpgs-leaderboard">
            <h3>Top Gardeners</h3>
            <?php if (empty($gardeners)): ?>
                <p>No gardeners found. Be the first to plant something!</p>
            <?php else: ?>
                <ol>
                    <?php foreach ($gardeners as $gardener): 
                        $stats = get_user_meta($gardener->ID, 'tpgs_gamification_stats', true) ?: array();
                        $stats = wp_parse_args($stats, array(
                            'level' => 1,
                            'experience' => 0,
                            'badges' => array()
                        ));
                    ?>
                        <li>
                            <span class="user"><?php echo esc_html($gardener->display_name); ?></span>
                            <span class="level">Level <?php echo esc_html($stats['level']); ?></span>
                            <span class="xp">(<?php echo esc_html($stats['experience']); ?> XP)</span>
                            <span class="badges">
                                <?php foreach (array_slice((array)$stats['badges'], 0, 3) as $badge): 
                                    if (!empty($badge['emoji']) && !empty($badge['title'])): ?>
                                        <span class="badge" title="<?php echo esc_attr($badge['title']); ?>">
                                            <?php echo esc_html($badge['emoji']); ?>
                                        </span>
                                    <?php endif;
                                endforeach; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function init_user_stats($user_id) {
        $default_stats = array(
            'level' => 1,
            'planted' => 0,
            'harvested' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'experience' => 0,
            'badges' => array(),
            'first_planting' => '',
            'last_harvest' => '',
            'last_activity' => ''
        );
        
        update_user_meta($user_id, 'tpgs_gamification_stats', $default_stats);
        return $default_stats;
    }

    public static function clear_leaderboard_cache() {
        delete_transient('tpgs_leaderboard_top_10');
    }
}
<?php
defined('ABSPATH') || exit;

class TPGS_Gamipress_Integration
{

    public function __construct()
    {
        // Register custom trigger
        add_filter('gamipress_activity_triggers', [$this, 'register_triggers']);

        // Validate badge awards
        add_filter('gamipress_user_has_access_to_achievement', [$this, 'validate_badge'], 10, 6);

        // Auto-track recipe views on learn pages
        add_action('wp', [$this, 'auto_track_recipe_view']);
    }

    /**
     * Register custom triggers
     */
    public function register_triggers($triggers)
    {
        $triggers['GRO Pod Gardening'] = [
            'tpgs_plant_pod' => __('Plant a pod', 'gro-pod-gardening'),
            'tpgs_harvest_pod' => __('Harvest a pod', 'gro-pod-gardening'),
            'tpgs_log_care' => __('Log plant care', 'gro-pod-gardening'),
            'tpgs_view_recipe' => __('View recipe page', 'gro-pod-gardening'),
            'create_community_post' => __('Create community post', 'gro-pod-gardening'),
            'comment_on_community_post' => __('Comment on community post', 'gro-pod-gardening'),
            'share_community_tip' => __('Share community tip', 'gro-pod-gardening')
        ];
        return $triggers;
    }

    /**
     * Validate badge requirements using new condition-based system
     */
    public function validate_badge($can_earn, $user_id, $achievement, $trigger, $site_id, $args)
    {
        // Only for our badge type
        if ($achievement->post_type !== 'gardening_badges') {
            return $can_earn;
        }

        // Use the new condition-based evaluation
        $pod_manager = new TPGS_Pod_Manager();
        $achievement_slug = $achievement->post_name;

        // Check specific condition for this badge
        switch ($achievement_slug) {
            case 'seed-starter':
                return $pod_manager->check_seed_starter($user_id);

            case 'tending-with-care':
                return $pod_manager->check_tending_with_care($user_id);

            case 'harvest-hero':
                return $pod_manager->check_harvest_hero($user_id);

            case 'taste-the-triumph':
                return $pod_manager->check_taste_the_triumph($user_id);

            case 'grow-expert':
                return $pod_manager->check_grow_expert($user_id);

            case 'community-cultivator':
                return $pod_manager->check_community_cultivator($user_id);

            default:
                return $can_earn;
        }
    }

    public function check_new_badges()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        $recent_badges = [];

        if ($user_id) {
            $recent_badges = gamipress_get_user_achievements([
                'user_id' => $user_id,
                'achievement_type' => 'gardening_badges',
                'since' => date('Y-m-d H:i:s', time() - 3600) // Last hour
            ]);
        }

        wp_send_json_success([
            'badges' => array_map(function ($badge) {
                return [
                    'title' => $badge->post_title,
                    'image' => get_the_post_thumbnail_url($badge->ID, 'thumbnail')
                ];
            }, $recent_badges)
        ]);
    }

    public function ajax_refresh_badges()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();

        if (!$user_id || !is_user_logged_in()) {
            wp_send_json_error('Invalid user ID or not logged in.', $user_id);
        }

        // Force WordPress to clear transients
        wp_cache_delete('user_achievements_' . $user_id, 'gamipress');
        delete_transient('tpgs_badges_' . $user_id);

        // Render fresh badge HTML
        ob_start();
        $this->render_badges_section($user_id);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    private function render_badges_section($user_id)
    {
        $badges = [
            'all' => gamipress_get_achievements([
                'post_type' => 'gardening_badges',
                'posts_per_page' => -1,
                'orderby' => 'ID',
                'order' => 'ASC'
            ]),
            'earned' => gamipress_get_user_achievements([
                'user_id' => $user_id,
                'achievement_type' => 'gardening_badges'
            ])
        ];
?>
        <div class="badges-card badges-section">
            <div class="badges-header">
                <span class="badges-icon">⚡</span>
                <h3>Badges</h3>
                <small class="text-muted">
                    <?php echo count($badges['earned']); ?>/<?php echo count($badges['all']); ?> earned
                </small>
            </div>

            <div class="badges-grid">
                <?php foreach ($badges['all'] as $badge) :
                    $earned = in_array($badge->ID, wp_list_pluck($badges['earned'], 'ID'));
                    $badge_slug = $badge->post_name;
                    $unlock_instructions = TPGS_Pod_Manager::get_badge_unlock_instructions($badge_slug);
                ?>
                    <div class="badge <?php echo $earned ? 'earned' : 'locked'; ?>"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="<?php echo $earned ? esc_attr($badge->post_title . ' - Earned!') : esc_attr($unlock_instructions); ?>">
                        <div class="badge-icon">
                            <img src="<?php echo esc_url(get_the_post_thumbnail_url($badge->ID, 'thumbnail')); ?>"
                                alt="<?php echo esc_attr($badge->post_title); ?>">
                        </div>
                        <span class="badge-name"><?php echo esc_html($badge->post_title); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
<?php
    }

    /**
     * Track recipe view for badge progress
     */
    public function track_recipe_view()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        $pod_manager = new TPGS_Pod_Manager();

        // Update gamification stats
        $pod_manager->update_gamification_stats($user_id, 'recipe_viewed');

        // Check for badge progress
        $badge_result = $pod_manager->evaluate_badges($user_id);

        wp_send_json_success([
            'message' => 'Recipe view tracked',
            'badges_updated' => $badge_result['updated'],
            'new_badges' => $this->get_newly_earned_badges($user_id)
        ]);
    }

    /**
     * Get newly earned badges for notifications
     */
    private function get_newly_earned_badges($user_id)
    {
        $recent_badges = gamipress_get_user_achievements([
            'user_id' => $user_id,
            'achievement_type' => 'gardening_badges',
            'since' => date('Y-m-d H:i:s', time() - 60) // Last minute
        ]);

        return array_map(function ($badge) {
            return [
                'title' => $badge->post_title,
                'image' => get_the_post_thumbnail_url($badge->ID, 'thumbnail'),
                'description' => $badge->post_content
            ];
        }, $recent_badges);
    }

    /**
     * Auto-track recipe views on learn pages
     */
    public function auto_track_recipe_view()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $current_url = $_SERVER['REQUEST_URI'] ?? '';

        // Check if we're on a learn/recipe page
        if (strpos($current_url, '/learn/') !== false && strpos($current_url, '/learn/') === 0) {
            $user_id = get_current_user_id();
            $pod_manager = new TPGS_Pod_Manager();

            // Update gamification stats
            $pod_manager->update_gamification_stats($user_id, 'recipe_viewed');

            // Check for badge progress
            $pod_manager->evaluate_badges($user_id);
        }
    }
}

// Initialize integration
if (class_exists('GamiPress')) {
    new TPGS_Gamipress_Integration();
}

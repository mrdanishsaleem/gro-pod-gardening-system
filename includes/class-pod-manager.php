<?php
class TPGS_Pod_Manager
{

    public function __construct()
    {
        add_action('wp_ajax_tpgs_plant_plant', array($this, 'plant_plant'));
        add_action('wp_ajax_tpgs_update_pod_date', array($this, 'update_pod_date'));
        add_action('wp_ajax_tpgs_reset_pod', array($this, 'reset_pod'));
        add_action('wp_ajax_tpgs_get_pod_details', array($this, 'get_pod_details'));
        add_action('wp_ajax_tpgs_get_pod_html', array($this, 'ajax_get_pod_html'));
        add_action('wp_ajax_tpgs_log_streak', array($this, 'log_streak'));
        add_action('wp_ajax_tpgs_get_streak_modal', array($this, 'get_streak_modal'));
        add_action('wp_ajax_tpgs_get_streak_section', array($this, 'get_streak_section'));
        add_action('wp_ajax_tpgs_mark_intro_completed', array($this, 'mark_intro_completed'));
        add_action('wp_ajax_tpgs_harvest_pod', array($this, 'harvest_pod'));
        add_action('wp_ajax_tpgs_refresh_pod_days', array($this, 'refresh_pod_days_remaining'));
        add_action('tpgs_weekly_streak_reset', array($this, 'reset_weekly_streak'));
        add_action('wp_ajax_tpgs_track_community_post', array($this, 'track_community_post'));
        add_action('wp_ajax_tpgs_track_community_comment', array($this, 'track_community_comment'));
        add_action('wp_ajax_tpgs_track_community_tip', array($this, 'track_community_tip'));
        add_action('wp_ajax_tpgs_test_community_events', array($this, 'test_community_events'));
        add_action('wp_ajax_tpgs_track_recipe_view', array($this, 'track_recipe_view'));
        add_action('wp_ajax_tpgs_refresh_badges', array($this, 'refresh_badges'));
        add_action('wp_ajax_tpgs_check_badges', array($this, 'check_badges'));
        
        // Hook into real community actions (BuddyBoss)
        add_action('bp_activity_after_save', array($this, 'track_real_community_actions'));
    }

    public static function get_user_pods($user_id)
    {
        $pods = array();

        for ($i = 1; $i <= 12; $i++) {
            $pod_data = get_user_meta($user_id, 'tpgs_pod_' . $i, true);

            if ($pod_data && $pod_data['status'] !== 'empty') {
                // Calculate current days remaining for active pods
                $pod_data['days_remaining'] = self::calculate_days_remaining($pod_data);

                // Update status if needed
                if ($pod_data['days_remaining'] <= 0 && $pod_data['status'] === 'growing') {
                    $pod_data['status'] = 'ready';
                    // Update the stored data
                    update_user_meta($user_id, 'tpgs_pod_' . $i, $pod_data);
                }
            } else {
                $pod_data = array(
                    'plant_id' => 0,
                    'date_planted' => '',
                    'days_remaining' => 0,
                    'status' => 'empty'
                );
            }

            $pods[$i] = $pod_data;
        }

        return $pods;
    }

    public function ajax_get_pod_html()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $pod_id = isset($_POST['pod_id']) ? intval($_POST['pod_id']) : 0;
        $pod_data = isset($_POST['pod_data']) ? $_POST['pod_data'] : array();

        if ($pod_id < 1 || $pod_id > 12) {
            wp_send_json_error('Invalid pod ID');
        }

        wp_send_json_success(array(
            'html' => self::get_pod_html($pod_id, $pod_data)
        ));
    }

    public function get_pod_details()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        $pod_id = isset($_POST['pod_id']) ? intval($_POST['pod_id']) : 0;

        if ($pod_id < 1 || $pod_id > 12) {
            wp_send_json_error('Invalid pod ID');
        }

        $pod_data = get_user_meta($user_id, 'tpgs_pod_' . $pod_id, true);
        if (empty($pod_data)) {
            $pod_data = array(
                'plant_id' => 0,
                'date_planted' => '',
                'days_remaining' => 0,
                'status' => 'empty'
            );
        }

        ob_start();
        include TPGS_PLUGIN_DIR . 'templates/frontend/pod-detail.php';
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'pod_data' => $pod_data
        ));
    }

    public static function get_active_pod_count($user_id)
    {
        $pods = self::get_user_pods($user_id);
        $count = 0;

        foreach ($pods as $pod) {
            if ($pod['status'] !== 'empty') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Award badges when pod count reaches thresholds
     */
    // private function award_badges($user_id, $new_pod_count) {
    //     if (!function_exists('gamipress_trigger_event')) return;

    //     // Trigger check for all thresholds
    //     $thresholds = [1, 3, 6, 9, 12];
    //     foreach ($thresholds as $threshold) {
    //         if ($new_pod_count >= $threshold) {
    //             gamipress_trigger_event([
    //                 'event' => 'tpgs_pod_threshold',
    //                 'user_id' => $user_id,
    //                 'pod_count' => $new_pod_count
    //             ]);
    //         }
    //     }
    // }

    public function plant_plant()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        $pod_id = isset($_POST['pod_id']) ? intval($_POST['pod_id']) : 0;
        $plant_id = isset($_POST['plant_id']) ? intval($_POST['plant_id']) : 0;

        if ($pod_id < 1 || $pod_id > 12) {
            wp_send_json_error('Invalid pod ID');
        }

        $vegetable = TPGS_Plant_Manager::get_plant($plant_id);
        if (!$vegetable) {
            wp_send_json_error('Invalid vegetable');
        }

        // Check if pod is empty
        $current_pod = get_user_meta($user_id, 'tpgs_pod_' . $pod_id, true);
        if (!empty($current_pod) && $current_pod['status'] !== 'empty') {
            wp_send_json_error('Pod is not empty');
        }

        // Update pod data
        $pod_data = array(
            'plant_id' => $plant_id,
            'date_planted' => current_time('mysql'),
            'days_remaining' => $vegetable['growth_duration'],
            'status' => 'growing',  // Make sure this is set to 'growing'
            'icon' => $vegetable['icon'] // Include the icon if needed
        );

        if (update_user_meta($user_id, 'tpgs_pod_' . $pod_id, $pod_data)) {
            $this->update_gamification_stats($user_id, 'planted');
            $active_count = self::get_active_pod_count($user_id);
            $badge_result = $this->evaluate_badges($user_id, $active_count);
            $next_harvest = self::get_next_harvest($user_id);

            // Generate modal HTML
            ob_start();
?>
            <div class="modal fade" id="plantSuccessModal" tabindex="-1" aria-labelledby="plantSuccessLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content plant-success-modal">
                        <div class="modal-header border-0 text-center">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center px-4 pb-4">
                            <!-- Vegetable Image -->
                            <div class="plant-image mb-3">
                                <img src="<?php echo esc_url($vegetable['icon']); ?>"
                                    alt="<?php echo esc_attr($vegetable['name']); ?>"
                                    class="plant-success-image">
                            </div>

                            <!-- Success Title -->
                            <h3 class="plant-success-title mb-3">
                                Pod <?php echo $pod_id; ?> planted successfully!
                            </h3>

                            <!-- Success Message -->
                            <p class="plant-success-message mb-4">
                                You've just planted your first <?php echo strtolower(esc_html($vegetable['name'])); ?> pod—great job!
                                <img src="<?php echo esc_url($vegetable['icon']); ?>" alt="<?php echo esc_attr($vegetable['name']); ?>" class="success-icon">
                            </p>

                            <!-- Days to Harvest Card -->
                            <div class="harvest-countdown-card mb-4">
                                <div class="harvest-days"><?php echo esc_html($vegetable['growth_duration']); ?></div>
                                <div class="harvest-label">Days to harvest</div>
                            </div>

                            <!-- Journey Message -->
                            <p class="journey-message mb-4">
                                Your hydroponic journey is officially underway. We'll guide you with
                                reminders and tips as your <?php echo strtolower(esc_html($vegetable['name'])); ?> grows.
                            </p>

                            <!-- Action Buttons -->
                            <button type="button" class="btn btn-primary ready-to-grow-btn mb-3" data-bs-dismiss="modal">
                                🎉 You’re ready to grow!
                            </button>

                            <button type="button" class="btn btn-outline-secondary share-btn" data-bs-dismiss="modal">
                                <img src="<?php echo TPGS_PLUGIN_URL . 'assets/images/icn-share.svg'; ?>" alt="Share icon" width="16" height="16">
                                Share with community
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php
            $modal_html = ob_get_clean();

            $response_data = array(
                'modal_html' => $modal_html,
                'pod_html' => self::get_pod_html($pod_id, $pod_data),
                'active_count' => $active_count,
                'badges_updated' => $badge_result['updated'],
                'badges_lost' => $badge_result['lost'],
                'next_harvest' => $next_harvest
            );

            // Debug logging

            wp_send_json_success($response_data);
        }
    }

    public function update_pod_date()
    {
        // Verify nonce first
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tpgs_nonce')) {
            wp_send_json_error('Invalid nonce', 403);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in', 401);
        }

        $user_id = get_current_user_id();
        $pod_id = isset($_POST['pod_id']) ? intval($_POST['pod_id']) : 0;
        $new_date = isset($_POST['new_date']) ? sanitize_text_field($_POST['new_date']) : '';

        // Debugging - log received data

        // Validate pod ID
        if ($pod_id < 1 || $pod_id > 12) {
            wp_send_json_error('Invalid pod ID. Pod ID must be between 1-12.', 400);
        }

        // Validate date
        if (empty($new_date)) {
            wp_send_json_error('Please select a valid date', 400);
        }

        $date_time = strtotime($new_date);
        if (!$date_time || $date_time > current_time('timestamp')) {
            wp_send_json_error('Invalid date - must be in the past and valid', 400);
        }

        $pod_data = get_user_meta($user_id, 'tpgs_pod_' . $pod_id, true);
        if (empty($pod_data) || $pod_data['status'] === 'empty') {
            wp_send_json_error('Pod is empty', 400);
        }

        $vegetable = TPGS_Plant_Manager::get_plant($pod_data['plant_id']);
        if (!$vegetable) {
            wp_send_json_error('Invalid vegetable in pod', 400);
        }

        // Calculate new days remaining
        $days_passed = floor((current_time('timestamp') - $date_time) / DAY_IN_SECONDS);
        $days_remaining = $vegetable['growth_duration'] - $days_passed;

        if ($days_remaining <= 0) {
            $days_remaining = 0;
            $status = 'ready';

            // Trigger notification if status changed to ready
            if ($pod_data['status'] !== 'ready') {
                TPGS_Notifications::send_harvest_notification($user_id, $pod_id, $pod_data['plant_id']);
            }
        } else {
            $status = 'growing';
        }

        // Update pod data
        $pod_data['date_planted'] = date('Y-m-d H:i:s', $date_time);
        $pod_data['days_remaining'] = $days_remaining;
        $pod_data['status'] = $status;

        if (update_user_meta($user_id, 'tpgs_pod_' . $pod_id, $pod_data)) {
            // Return updated pod HTML and active count
            $updated_pod_html = self::get_pod_html($pod_id, $pod_data);
            $active_count = self::get_active_pod_count($user_id);
            $next_harvest = self::get_next_harvest($user_id);

            wp_send_json_success(array(
                'days_remaining' => $days_remaining,
                'status' => $status,
                'status_text' => self::get_status_text($status),
                'pod_html' => $updated_pod_html,
                'active_count' => $active_count,
                'next_harvest' => $next_harvest
            ));
        } else {
            wp_send_json_error('Failed to update pod date', 500);
        }
    }

    public function harvest_pod()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        $pod_id = isset($_POST['pod_id']) ? intval($_POST['pod_id']) : 0;

        if ($pod_id < 1 || $pod_id > 12) {
            wp_send_json_error('Invalid pod ID');
        }

        $pod_data = get_user_meta($user_id, 'tpgs_pod_' . $pod_id, true);
        if (empty($pod_data) || $pod_data['status'] === 'empty') {
            wp_send_json_error('Pod is empty');
        }

        // Update gamification stats for harvesting
        $this->update_gamification_stats($user_id, 'harvested');

        // Clear the pod after harvesting
        $empty_pod = array(
            'plant_id' => 0,
            'date_planted' => '',
            'days_remaining' => 0,
            'status' => 'empty'
        );

        if (update_user_meta($user_id, 'tpgs_pod_' . $pod_id, $empty_pod)) {
            $active_count = self::get_active_pod_count($user_id);
            $badge_result = $this->evaluate_badges($user_id, $active_count);
            $next_harvest = self::get_next_harvest($user_id);

            $response_data = array(
                'message' => 'Plant harvested successfully! Pod is now ready for new plants.',
                'pod_html' => self::get_pod_html($pod_id, $empty_pod),
                'active_count' => $active_count,
                'badges_updated' => $badge_result['updated'],
                'badges_lost' => $badge_result['lost'],
                'next_harvest' => $next_harvest
            );

            wp_send_json_success($response_data);
        } else {
            wp_send_json_error('Failed to harvest pod');
        }
    }

    public function reset_pod()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        $pod_id = isset($_POST['pod_id']) ? intval($_POST['pod_id']) : 0;

        if ($pod_id < 1 || $pod_id > 12) {
            wp_send_json_error('Invalid pod ID');
        }

        $pod_data = get_user_meta($user_id, 'tpgs_pod_' . $pod_id, true);
        if (empty($pod_data) || $pod_data['status'] === 'empty') {
            wp_send_json_error('Pod is already empty');
        }

        // Update gamification stats if pod was ready to harvest
        if ($pod_data['status'] === 'ready') {
            $this->update_gamification_stats($user_id, 'harvested');
        }

        // Reset pod
        $empty_pod = array(
            'plant_id' => 0,
            'date_planted' => '',
            'days_remaining' => 0,
            'status' => 'empty'
        );

        if (update_user_meta($user_id, 'tpgs_pod_' . $pod_id, $empty_pod)) {
            $active_count = self::get_active_pod_count($user_id);
            $badge_result = $this->evaluate_badges($user_id, $active_count);
            $next_harvest = self::get_next_harvest($user_id);

            if ($pod_data['status'] === 'ready') {
                $this->update_gamification_stats($user_id, 'harvested');
            }

            $response_data = array(
                'message' => 'Pod reset successfully',
                'pod_html' => self::get_pod_html($pod_id, $empty_pod),
                'active_count' => $active_count,
                'badges_updated' => $badge_result['updated'],
                'badges_lost' => $badge_result['lost'],
                'next_harvest' => $next_harvest
            );

            // Debug logging
            wp_send_json_success($response_data);
        }
    }

    /**
     * New condition-based badge evaluation
     */
    public function evaluate_badges($user_id, $current_pods = null)
    {
        if (!class_exists('GamiPress')) {
            return array('updated' => false, 'lost' => 0);
        }

        // Get all gardening badges
        $all_badges = gamipress_get_achievements([
            'post_type' => 'gardening_badges',
            'posts_per_page' => -1,
            'orderby' => 'ID',
            'order' => 'ASC'
        ]);


        $badges_updated = false;
        $badges_lost = 0;

        foreach ($all_badges as $badge) {
            $slug = $badge->post_name;
            $has_earned = gamipress_has_user_earned_achievement($badge->ID, $user_id);
            $meets_condition = $this->check_badge_condition($user_id, $slug);


            if ($meets_condition && !$has_earned) {
                $award_result = gamipress_award_achievement_to_user($badge->ID, $user_id);
                $badges_updated = true;
            } elseif (!$meets_condition && $has_earned) {
                // Only revoke badges for reversible conditions
                if ($this->is_reversible_badge($slug)) {
                    gamipress_revoke_achievement_to_user($badge->ID, $user_id);
                    $badges_updated = true;
                    $badges_lost++;
                }
            }
        }

        // Clear Gamipress cache
        if ($badges_updated) {
            delete_transient('tpgs_badges_' . $user_id);
        }

        $result = [
            'updated' => $badges_updated,
            'lost' => $badges_lost
        ];

        // Debug logging

        return $result;
    }

    /**
     * Check if user meets specific badge condition
     */
    public function check_badge_condition($user_id, $badge_slug)
    {
        switch ($badge_slug) {
            case 'seed-starter':
                return $this->check_seed_starter($user_id);

            case 'tending-with-care':
                return $this->check_tending_with_care($user_id);

            case 'harvest-hero':
                return $this->check_harvest_hero($user_id);

            case 'taste-the-triumph':
                return $this->check_taste_the_triumph($user_id);

            case 'grow-expert':
                return $this->check_grow_expert($user_id);

            case 'community-cultivator':
                return $this->check_community_cultivator($user_id);

            default:
                return false;
        }
    }

    /**
     * Seed Starter: Plant 1 pod
     */
    public function check_seed_starter($user_id)
    {
        $stats = get_user_meta($user_id, 'tpgs_gamification_stats', true);
        return !empty($stats['planted']) && $stats['planted'] >= 1;
    }

    /**
     * Tending with Care: Log 3 check-ins on 3 different days
     */
    public function check_tending_with_care($user_id)
    {
        $streak_log = get_user_meta($user_id, 'tpgs_streak_log', true) ?: [];
        $unique_days = array_unique(array_column($streak_log, 'date'));
        return count($unique_days) >= 3;
    }

    /**
     * Harvest Hero: Harvest first vegetable
     */
    public function check_harvest_hero($user_id)
    {
        $stats = get_user_meta($user_id, 'tpgs_gamification_stats', true);
        return !empty($stats['harvested']) && $stats['harvested'] >= 1;
    }

    /**
     * Taste the Triumph: View 1 recipe page
     */
    public function check_taste_the_triumph($user_id)
    {
        $stats = get_user_meta($user_id, 'tpgs_gamification_stats', true);
        return !empty($stats['first_recipe_viewed']);
    }

    /**
     * Grow Expert: Harvest 4 plants (any pods)
     */
    public function check_grow_expert($user_id)
    {
        $stats = get_user_meta($user_id, 'tpgs_gamification_stats', true);
        return !empty($stats['harvested']) && $stats['harvested'] >= 4;
    }

    /**
     * Community Cultivator: Complete any 2 of the 3 community actions
     */
    public function check_community_cultivator($user_id)
    {
        if (!class_exists('GamiPress')) {
            return false;
        }

        // Check if user has earned the Community Cultivator badge through GamiPress
        $badge = get_posts([
            'post_type' => 'gardening_badges',
            'name' => 'community-cultivator',
            'posts_per_page' => 1
        ]);

        if (empty($badge)) {
            return false;
        }

        // Check if already earned
        if (gamipress_has_user_earned_achievement($badge[0]->ID, $user_id)) {
            return true;
        }

        // Check if user meets the requirements (2 of 3 community actions)
        $stats = get_user_meta($user_id, 'tpgs_gamification_stats', true) ?: [];
        $community_actions = 0;
        
        if (!empty($stats['community_posts'])) {
            $community_actions++;
        }
        if (!empty($stats['community_comments'])) {
            $community_actions++;
        }
        if (!empty($stats['community_tips'])) {
            $community_actions++;
        }

        $meets_requirements = $community_actions >= 2;
        
        return $meets_requirements;
    }

    /**
     * Determine if a badge is reversible (can be lost)
     */
    private function is_reversible_badge($badge_slug)
    {
        // Only community engagement badges are reversible
        // Once you plant, harvest, or view recipes, those achievements are permanent
        $reversible_badges = [
            'community-cultivator'
        ];

        return in_array($badge_slug, $reversible_badges);
    }

    /**
     * Get unlock instructions for a badge
     */
    public static function get_badge_unlock_instructions($badge_slug)
    {
        $instructions = [
            'seed-starter' => 'Plant your first vegetable in any pod to unlock this badge!',
            'tending-with-care' => 'Log plant care activities on 3 different days to unlock this badge!',
            'harvest-hero' => 'Harvest your first vegetable to unlock this badge!',
            'taste-the-triumph' => 'Visit any recipe page in the Learn section to unlock this badge!',
            'grow-expert' => 'Harvest 4 plants (from any pods) to unlock this badge!',
            'community-cultivator' => 'Complete any 2 community actions: create a post, comment, or share a tip!'
        ];

        return $instructions[$badge_slug] ?? 'Complete the required actions to unlock this badge!';
    }

    public function update_gamification_stats($user_id, $action)
    {
        $stats = get_user_meta($user_id, 'tpgs_gamification_stats', true);

        // Force initialization if stats don't exist or are empty
        if (!$stats || empty($stats) || !is_array($stats) || !isset($stats['community_posts'])) {
            $stats = array(
                'planted' => 0,
                'harvested' => 0,
                'first_planting' => '',
                'last_harvest' => '',
                'first_recipe_viewed' => '',
                'community_posts' => 0,
                'community_comments' => 0,
                'community_tips' => 0
            );
        }

        $old_stats = $stats; // Store old stats for comparison

        if ($action === 'planted') {
            $stats['planted']++;

            if (empty($stats['first_planting'])) {
                $stats['first_planting'] = current_time('mysql');
            }
        } elseif ($action === 'harvested') {
            $stats['harvested']++;
            $stats['last_harvest'] = current_time('mysql');
        } elseif ($action === 'recipe_viewed') {
            if (empty($stats['first_recipe_viewed'])) {
                $stats['first_recipe_viewed'] = current_time('mysql');
            }
        } elseif ($action === 'community_post') {
            $stats['community_posts']++;
        } elseif ($action === 'community_comment') {
            $stats['community_comments']++;
        } elseif ($action === 'community_tip') {
            $stats['community_tips']++;
        }

        $update_result = update_user_meta($user_id, 'tpgs_gamification_stats', $stats);
    }

    public static function get_pod_html($pod_id, $pod_data)
    {
        ob_start();

        $user_id = get_current_user_id();
        $stored_pod_data = get_user_meta($user_id, 'tpgs_pod_' . $pod_id, true); // Fetch latest from DB
        $pod_data = !empty($stored_pod_data) ? $stored_pod_data : ($pod_data ?: ['status' => 'empty', 'plant_id' => 0, 'days_remaining' => 0]);

        // Calculate current days remaining for active pods
        if ($pod_data['status'] !== 'empty') {
            $pod_data['days_remaining'] = self::calculate_days_remaining($pod_data);
            // Update status if needed
            if ($pod_data['days_remaining'] <= 0 && $pod_data['status'] === 'growing') {
                $pod_data['status'] = 'ready';
                update_user_meta($user_id, 'tpgs_pod_' . $pod_id, $pod_data);
            }
        }

        $vegetable = $pod_data['plant_id'] ? TPGS_Plant_Manager::get_plant($pod_data['plant_id']) : false;
        $status = $pod_data['status'] ?? 'empty';
        $status_class = $status === 'empty' ? 'empty-pod' : 'active-pod';

        // Note: Removed action image logic to always show original plant icon

        ?>
        <div class="pod <?php echo esc_attr($status_class); ?> pod-<?php echo $pod_id; ?><?php echo (($status === 'growing' || $status === 'ready') && (int)$pod_data['days_remaining'] === 0) ? ' ready-to-harvest' : ''; ?>" data-pod-id="<?php echo $pod_id; ?>" data-status="<?php echo esc_attr($status); ?>" data-days="<?php echo esc_attr($pod_data['days_remaining']); ?>" <?php if ($vegetable && isset($vegetable['growth_duration'])): ?> data-growth-duration="<?php echo esc_attr($vegetable['growth_duration']); ?>" <?php endif; ?><?php if (isset($pod_data['date_planted'])): ?> data-planting-date="<?php echo esc_attr($pod_data['date_planted']); ?>" <?php endif; ?><?php if ($vegetable): ?> data-plant-name="<?php echo esc_attr($vegetable['name']); ?>" <?php endif; ?>>

            <?php if ($status === 'empty'): ?>
                <div class="add-plant"><img src="<?php echo TPGS_PLUGIN_URL . 'assets/images/Plus.svg'; ?>" alt="Add pod" class="plus-icon"></div>
                <span class="pod-label">Pod <?php echo $pod_id; ?></span>
                <span class="pod-number d-none"><?php echo $pod_id; ?></span>
                <span class="pod-status d-none">Empty</span>
            <?php else: ?>
                <div class="plant-container">
                    <div class="<?php echo sanitize_title($vegetable['name']); ?>-plant">
                        <?php if ($vegetable && !empty($vegetable['icon'])): ?>
                            <img src="<?php echo esc_url($vegetable['icon'] . '?v=' . time()); ?>"
                                alt="<?php echo esc_attr($vegetable['name']); ?>" class="vegetable-icon">
                        <?php else: ?>
                            <div class="placeholder-icon">?</div> <!-- Fallback if no image -->
                        <?php endif; ?>
                    </div>
                </div>
                <div class="plant-info">
                    <h4 class="plant-name"><?php echo esc_html($vegetable['name']); ?></h4>
                    <p class="ready-to-harvest"><?php if ($pod_data['days_remaining'] == 0): ?>Ready to harvest<?php else: ?><?php echo esc_html($pod_data['days_remaining']); ?> days to harvest<?php endif; ?></p>
                </div>
                <span class="pod-number d-none"><?php echo $pod_id; ?></span>
                <span class="pod-status d-none"><?php echo esc_html(self::get_status_text($status)); ?></span>
                <span class="vegetable-name d-none"><?php echo esc_html($vegetable['name']); ?></span>
                <span class="days-remaining d-none"><?php echo esc_html($pod_data['days_remaining']); ?></span>
            <?php endif; ?>

        </div>
        <?php

        $html = ob_get_clean();
        if (empty(trim($html))) {
        }
        return $html;
    }

    public static function get_status_text($status)
    {
        switch ($status) {
            case 'empty':
                return 'Empty';
            case 'growing':
                return 'Growing';
            case 'ready':
                return 'Ready to Harvest';
            default:
                return '';
        }
    }

    public static function get_next_harvest($user_id)
    {
        $pods = self::get_user_pods($user_id);
        $next_harvest = null;

        foreach ($pods as $pod) {
            if ($pod['status'] === 'growing') {
                $vegetable = TPGS_Plant_Manager::get_plant($pod['plant_id']);
                if ($vegetable && (!$next_harvest || $pod['days_remaining'] < $next_harvest['days'])) {
                    $next_harvest = [
                        'name' => $vegetable['name'],
                        'icon' => $vegetable['icon'],
                        'days' => $pod['days_remaining']
                    ];
                }
            }
        }

        return $next_harvest;
    }

    public function log_streak1()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        $pods = isset($_POST['pods']) ? array_map('intval', $_POST['pods']) : [];
        $actions = isset($_POST['actions']) ? array_map('sanitize_text_field', $_POST['actions']) : [];

        if (empty($pods) || empty($actions)) {
            wp_send_json_error('Please select a pod and at least one action');
        }

        // Use WordPress timezone and a stable week start (Monday of current week)
        $today = wp_date('Y-m-d', current_time('timestamp'));
        $week_start = wp_date('Y-m-d', strtotime('monday this week', current_time('timestamp')));
        $streak_log = get_user_meta($user_id, 'tpgs_streak_log', true) ?: [];
        $weekly_streak = get_user_meta($user_id, 'tpgs_weekly_streak', true) ?: [];
        $total_streak_days = (int) get_user_meta($user_id, 'tpgs_total_streak_days', true) ?: 0;

        $updated = false;
        $pod_id = $pods[0]; // Use the first pod for response
        $pod_data = get_user_meta($user_id, 'tpgs_pod_' . $pod_id, true);
        $vegetable = $pod_data['plant_id'] ? TPGS_Plant_Manager::get_plant($pod_data['plant_id']) : false;

        if (empty($pod_data) || $pod_data['status'] !== 'growing' || !$vegetable) {
            wp_send_json_error('Pod is not in growing state or invalid vegetable');
            return;
        }

        $action_image = '';
        $action_key = sanitize_title($actions[0]); // Use the first action for image
        if (!empty($vegetable['action_images'][$action_key])) {
            $action_image = $vegetable['action_images'][$action_key];
            // Update pod_data with the action image
            $pod_data['icon'] = $action_image; // Add or update icon field
            update_user_meta($user_id, 'tpgs_pod_' . $pod_id, $pod_data);
        }

        foreach ($pods as $pod_id) {
            if ($pod_id < 1 || $pod_id > 12)
                continue;

            $pod_key = $pod_id . '_' . $today;
            if (!isset($streak_log[$pod_key])) {
                $streak_log[$pod_key] = [
                    'pod_id' => $pod_id,
                    'date' => $today,
                    'actions' => $actions
                ];
                $weekly_streak[$week_start][$pod_id] = $today;
                // Also record the specific day in this week's log so multiple days remain checked
                if (!in_array($today, ($weekly_streak[$week_start] ?? []), true)) {
                    $weekly_streak[$week_start][] = $today;
                }
                $total_streak_days++;
                $updated = true;
            } else {
                $streak_log[$pod_key]['actions'] = array_unique(array_merge($streak_log[$pod_key]['actions'], $actions));
                // Ensure today's date remains recorded for this week
                if (!in_array($today, ($weekly_streak[$week_start] ?? []), true)) {
                    $weekly_streak[$week_start][] = $today;
                }
                $total_streak_days++;
                $updated = true;
            }
        }

        if ($updated) {
            update_user_meta($user_id, 'tpgs_streak_log', $streak_log);
            update_user_meta($user_id, 'tpgs_weekly_streak', $weekly_streak);
            update_user_meta($user_id, 'tpgs_total_streak_days', $total_streak_days);
        }

        $unique_days = array_unique(array_values($weekly_streak[$week_start] ?? []));
        $current_week_streak = count($unique_days);

        // Generate updated HTML with the latest pod_data
        ob_start();
        include TPGS_PLUGIN_DIR . 'templates/frontend/pod-display.php';
        $updated_html = ob_get_clean();

        wp_send_json_success([
            'message' => 'Streak logged successfully',
            'pod_id' => $pod_id,
            'actions' => implode(', ', $actions),
            'weekly_streak' => $current_week_streak,
            'total_streak_days' => $total_streak_days,
            'pod_data' => $pod_data,
            'action_image' => $action_image,
            'html' => $updated_html
        ]);
    }

    public function log_streak()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        $pods = isset($_POST['pods']) ? array_map('intval', $_POST['pods']) : [];
        $actions = isset($_POST['actions']) ? array_map('sanitize_text_field', $_POST['actions']) : [];

        if (empty($pods) || empty($actions)) {
            wp_send_json_error('Please select a pod and at least one action');
        }

        // Use WordPress timezone and a stable week start (Monday of current week)
        $today = wp_date('Y-m-d', current_time('timestamp'));
        $week_start = wp_date('Y-m-d', strtotime('monday this week', current_time('timestamp')));
        $streak_log = get_user_meta($user_id, 'tpgs_streak_log', true) ?: [];
        $weekly_streak = get_user_meta($user_id, 'tpgs_weekly_streak', true) ?: [];
        $total_streak_days = (int) get_user_meta($user_id, 'tpgs_total_streak_days', true) ?: 0;

        $updated = false;
        $pod_id = $pods[0]; // Use the first pod for response
        $pod_data = get_user_meta($user_id, 'tpgs_pod_' . $pod_id, true);
        $vegetable = $pod_data['plant_id'] ? TPGS_Plant_Manager::get_plant($pod_data['plant_id']) : false;

        if (empty($pod_data) || $pod_data['status'] !== 'growing' || !$vegetable) {
            wp_send_json_error('Pod is not in growing state or invalid vegetable');
            return;
        }

        // Note: Removed action image update to keep original plant icon

        // Initialize new week if not set
        if (!isset($weekly_streak[$week_start])) {
            $weekly_streak[$week_start] = [];
        }

        foreach ($pods as $pod_id) {
            if ($pod_id < 1 || $pod_id > 12) continue;

            $pod_key = $pod_id . '_' . $today;
            if (!isset($streak_log[$pod_key])) {
                $streak_log[$pod_key] = [
                    'pod_id' => $pod_id,
                    'date' => $today,
                    'actions' => $actions
                ];
                $weekly_streak[$week_start][$pod_id] = $today; // Mark the current day
                $total_streak_days++;
                $updated = true;
            } else {
                $streak_log[$pod_key]['actions'] = array_unique(array_merge($streak_log[$pod_key]['actions'], $actions));
                // Only update if not already marked for today
                if (!isset($weekly_streak[$week_start][$pod_id]) || $weekly_streak[$week_start][$pod_id] !== $today) {
                    $weekly_streak[$week_start][$pod_id] = $today; // Ensure current day is marked
                    $total_streak_days++;
                    $updated = true;
                }
            }
        }

        if ($updated) {
            update_user_meta($user_id, 'tpgs_streak_log', $streak_log);
            update_user_meta($user_id, 'tpgs_weekly_streak', $weekly_streak);
            update_user_meta($user_id, 'tpgs_total_streak_days', $total_streak_days);

            // Check for badge progress after streak logging
            $badge_result = $this->evaluate_badges($user_id);
        }

        $unique_days = array_unique(array_values($weekly_streak[$week_start] ?? []));
        $current_week_streak = count($unique_days);

        // Generate updated HTML with the latest pod_data
        ob_start();
        include TPGS_PLUGIN_DIR . 'templates/frontend/pod-display.php';
        $updated_html = ob_get_clean();

        wp_send_json_success([
            'message' => 'Streak logged successfully',
            'pod_id' => $pod_id,
            'actions' => implode(', ', $actions),
            'weekly_streak' => $current_week_streak,
            'total_streak_days' => $total_streak_days,
            'pod_data' => $pod_data,
            'html' => $updated_html,
            'badges_updated' => isset($badge_result) ? $badge_result['updated'] : false,
            'badges_lost' => isset($badge_result) ? $badge_result['lost'] : 0
        ]);
    }

    public function get_streak_section()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }

        $user_id = get_current_user_id();
        $weekly_streak = get_user_meta($user_id, 'tpgs_weekly_streak', true) ?: [];
        // Use WordPress timezone with Monday of current week
        $week_start = wp_date('Y-m-d', strtotime('monday this week', current_time('timestamp')));
        $unique_days = array_unique(array_values($weekly_streak[$week_start] ?? []));
        $current_streak = count($unique_days);

        ob_start();
        ?>
        <div class="streak-card">
            <div class="streak-header">
                <span class="streak-icon">
                    <img src="<?php echo TPGS_PLUGIN_URL . 'assets/images/streak.svg'; ?>" alt="Streak icon">
                </span>
                <h3>Weekly streak</h3>
            </div>

            <div class="plant-illustration">
                <img src="<?php echo TPGS_PLUGIN_URL . 'assets/images/nurturing.png'; ?>" alt="Streak icon">
            </div>

            <div class="streak-info">
                <div class="streak-count">
                    <span class="streak-number"><?php echo esc_html($current_streak); ?></span>
                </div>
                <div class="streak-text">Day Streak!</div>
                <div class="streak-subtitle">🌞 Keep your garden thriving</div>
            </div>

            <div class="week-days">
                <?php
                $today = date('Y-m-d');
                for ($i = 0; $i < 7; $i++) {
                    $day_date = date('Y-m-d', strtotime($week_start . " +$i days"));
                    $day_name = date('D', strtotime($day_date));
                    $completed = isset($weekly_streak[$week_start]) && in_array($day_date, $weekly_streak[$week_start]);
                ?>
                    <div class="day <?php echo $completed ? 'completed' : ''; ?>">
                        <?php echo $day_name; ?>
                    </div>
                <?php } ?>
            </div>

            <button class="log-care-btn streak-btn" data-bs-toggle="modal" data-bs-target="#streakModal">Log today plant care</button>
        </div>
    <?php
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'current_streak' => $current_streak
        ]);
    }

    public function mark_intro_completed()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }

        $user_id = get_current_user_id();
        $reset = isset($_POST['reset']) && $_POST['reset'] === 'true';

        if ($reset) {
            // Reset intro completion (for testing)
            delete_user_meta($user_id, 'tpgs_intro_completed');
            wp_send_json_success([
                'message' => 'Intro reset successfully'
            ]);
        } else {
            // Mark intro as completed for this user
            update_user_meta($user_id, 'tpgs_intro_completed', 'true');
            wp_send_json_success([
                'message' => 'Intro marked as completed'
            ]);
        }
    }

    public function reset_weekly_streak()
    {
        $users = get_users(array('meta_key' => 'tpgs_pod_1', 'meta_compare' => 'EXISTS'));
        foreach ($users as $user) {
            $weekly_streak = get_user_meta($user->ID, 'tpgs_weekly_streak', true) ?: [];
            $week_start = date('Y-m-d', strtotime('last Monday', current_time('timestamp')));
            if (isset($weekly_streak[$week_start])) {
                unset($weekly_streak[$week_start]);
                update_user_meta($user->ID, 'tpgs_weekly_streak', $weekly_streak);
            }
        }
    }

    public function get_streak_modal()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }

        $user_id = get_current_user_id();
        $pods = $this->get_user_pods($user_id);
        $plants = TPGS_Plant_Manager::get_plants();

        if (empty($pods) || empty($plants)) {
            wp_send_json_error('No pods or plants available');
            return;
        }

        $selected_pod_id = isset($_POST['pod_id']) ? intval($_POST['pod_id']) : null;
        $has_growing = array_filter($pods, fn($p) => $p['status'] === 'growing');
        $first_growing_pod = $has_growing ? key($has_growing) : null;

        ob_start();
    ?>
        <div class="modal-header">
            <h4 class="streak-modal-title">Log Today's Plant Care</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <?php if (empty($has_growing)): ?>
                <div class="no-plants-message">
                    <div class="no-plants-icon">🌱</div>
                    <h5>No Plants Growing</h5>
                    <p>Please plant and grow a vegetable first to log care activities.</p>
                </div>
            <?php else: ?>
                <div class="streak-form-container">
                    <!-- Pod Selection Section -->
                    <div class="form-section">
                        <h5 class="section-title">Select Pod to care for</h5>
                        <div id="streakPodSelect" class="pod-selection-grid">
                            <?php foreach ($pods as $pod_id => $pod_data):
                                if ($pod_data['status'] === 'growing') {
                                    $vegetable = TPGS_Plant_Manager::get_plant($pod_data['plant_id']);
                                    if ($vegetable && !empty($vegetable['icon'])): ?>
                                        <div class="pod-option">
                                            <input class="pod-radio" type="radio" name="streak_pods" value="<?php echo $pod_id; ?>"
                                                id="pod_<?php echo $pod_id; ?>" <?php echo (!$selected_pod_id && $pod_id === $first_growing_pod) || $pod_id === $selected_pod_id ? 'checked' : ''; ?>>
                                            <label class="pod-label" for="pod_<?php echo $pod_id; ?>">
                                                <div class="pod-icon">
                                                    <img src="<?php echo esc_url($vegetable['icon']); ?>" alt="<?php echo esc_attr($vegetable['name']); ?>">
                                                </div>
                                                <div class="pod-info">
                                                    <span class="pod-number">Pod <?php echo $pod_id; ?></span>
                                                    <span class="vegetable-name"><?php echo esc_html($vegetable['name']); ?></span>
                                                </div>
                                            </label>
                                        </div>
                            <?php endif;
                                }
                            endforeach; ?>
                        </div>
                    </div>

                    <!-- Action Selection Section -->
                    <div class="form-section">
                        <h5 class="section-title">Today's care actions (multiple)</h5>
                        <p class="section-subtitle">Select that apply</p>
                        <div id="streakActions" class="action-selection-grid">
                            <?php
                            $pod_id = $selected_pod_id ?: $first_growing_pod;
                            $pod_data = $pods[$pod_id];
                            $vegetable = $pod_data['plant_id'] ? TPGS_Plant_Manager::get_plant($pod_data['plant_id']) : false;
                            $default_actions = $vegetable['actions'] ?? ['Watered', 'Harvested', 'Fed nutrients', 'Checked plant health', 'Took progress photo'];
                            foreach ($default_actions as $action):
                                $action_key = sanitize_title($action);
                                $action_image = $vegetable['action_images'][$action_key] ?? '';
                            ?>
                                <div class="action-option">
                                    <input class="action-radio" type="checkbox" name="streak_actions[]" value="<?php echo esc_attr($action); ?>"
                                        id="action_<?php echo $action_key; ?>">
                                    <label class="action-label" for="action_<?php echo $action_key; ?>">
                                        <span class="action-text"><?php echo esc_html($action); ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary streak-save-btn" id="saveStreak" <?php echo empty($has_growing) ? 'disabled' : ''; ?>>
                <span class="btn-text">Save</span>
            </button>
        </div>
<?php
        $html = ob_get_clean();

        if (empty($html)) {
            wp_send_json_error('Failed to generate modal content');
            return;
        }

        wp_send_json_success(['html' => $html]);
    }

    public function refresh_pod_days_remaining()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        $pods = self::get_user_pods($user_id);

        foreach ($pods as $pod_id => $pod_data) {
            if ($pod_data['status'] === 'growing') {
                $pod_data['days_remaining'] = self::calculate_days_remaining($pod_data);
                update_user_meta($user_id, 'tpgs_pod_' . $pod_id, $pod_data);
            }
        }

        wp_send_json_success([
            'message' => 'Pod days remaining refreshed successfully.'
        ]);
    }

    /**
     * Calculate the actual days remaining for a pod based on planting date
     */
    public static function calculate_days_remaining($pod_data)
    {
        if (empty($pod_data) || $pod_data['status'] === 'empty' || empty($pod_data['date_planted'])) {
            return 0;
        }

        $vegetable = TPGS_Plant_Manager::get_plant($pod_data['plant_id']);
        if (!$vegetable) {
            return 0;
        }

        $planting_time = strtotime($pod_data['date_planted']);
        $current_time = current_time('timestamp');
        $days_passed = floor(($current_time - $planting_time) / DAY_IN_SECONDS);
        $days_remaining = $vegetable['growth_duration'] - $days_passed;

        return max(0, $days_remaining);
    }

    /**
     * Update days remaining for all user pods
     */
    public static function update_all_pods_days_remaining($user_id)
    {
        for ($i = 1; $i <= 12; $i++) {
            $pod_data = get_user_meta($user_id, 'tpgs_pod_' . $i, true);

            if (empty($pod_data) || $pod_data['status'] === 'empty') {
                continue;
            }

            $old_days_remaining = $pod_data['days_remaining'];
            $new_days_remaining = self::calculate_days_remaining($pod_data);

            // Update days remaining
            $pod_data['days_remaining'] = $new_days_remaining;

            // Check if status should change to ready
            if ($new_days_remaining <= 0 && $pod_data['status'] === 'growing') {
                $pod_data['status'] = 'ready';

                // Send notification if status changed to ready
                TPGS_Notifications::send_harvest_notification($user_id, $i, $pod_data['plant_id']);
            }

            // Update the pod
            update_user_meta($user_id, 'tpgs_pod_' . $i, $pod_data);
        }
    }

    /**
     * Track community post for badge progress
     */
    public function track_community_post()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();

        // Update gamification stats
        $this->update_gamification_stats($user_id, 'community_post');

        // Trigger GamiPress event for community post
        if (function_exists('gamipress_trigger_event')) {
            $event_result = gamipress_trigger_event([
                'event' => 'create_community_post',
                'user_id' => $user_id
            ]);
        } else {
        }

        // Check for badge progress
        $badge_result = $this->evaluate_badges($user_id);

        wp_send_json_success([
            'message' => 'Community post tracked',
            'badges_updated' => $badge_result['updated']
        ]);
    }

    /**
     * Track community comment for badge progress
     */
    public function track_community_comment()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();

        // Update gamification stats
        $this->update_gamification_stats($user_id, 'community_comment');

        // Trigger GamiPress event for community comment
        if (function_exists('gamipress_trigger_event')) {
            $event_result = gamipress_trigger_event([
                'event' => 'comment_on_community_post',
                'user_id' => $user_id
            ]);
        } else {
        }

        // Check for badge progress
        $badge_result = $this->evaluate_badges($user_id);

        wp_send_json_success([
            'message' => 'Community comment tracked',
            'badges_updated' => $badge_result['updated']
        ]);
    }

    /**
     * Track community tip for badge progress
     */
    public function track_community_tip()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();

        // Update gamification stats
        $this->update_gamification_stats($user_id, 'community_tip');

        // Trigger GamiPress event for community tip
        if (function_exists('gamipress_trigger_event')) {
            $event_result = gamipress_trigger_event([
                'event' => 'share_community_tip',
                'user_id' => $user_id
            ]);
        } else {
        }

        // Check for badge progress
        $badge_result = $this->evaluate_badges($user_id);

        wp_send_json_success([
            'message' => 'Community tip tracked',
            'badges_updated' => $badge_result['updated']
        ]);
    }

    /**
     * Test method to manually trigger community events for debugging
     */
    public function test_community_events()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'post';

        // Update gamification stats first
        $this->update_gamification_stats($user_id, 'community_' . $action);

        // Trigger the appropriate event
        if (function_exists('gamipress_trigger_event')) {
            $event_result = false;
            switch ($action) {
                case 'post':
                    $event_result = gamipress_trigger_event([
                        'event' => 'create_community_post',
                        'user_id' => $user_id
                    ]);
                    break;
                case 'comment':
                    $event_result = gamipress_trigger_event([
                        'event' => 'comment_on_community_post',
                        'user_id' => $user_id
                    ]);
                    break;
                case 'tip':
                    $event_result = gamipress_trigger_event([
                        'event' => 'share_community_tip',
                        'user_id' => $user_id
                    ]);
                    break;
            }
        } else {
        }

        // Check badge status
        $badge_result = $this->evaluate_badges($user_id);

        // Get user stats for debugging (after update)
        $stats = get_user_meta($user_id, 'tpgs_gamification_stats', true);
        
        // Check if Community Cultivator badge exists
        $community_badge = get_posts([
            'post_type' => 'gardening_badges',
            'name' => 'community-cultivator',
            'posts_per_page' => 1
        ]);
        $badge_exists = !empty($community_badge);
        
        // Test the badge condition directly
        if ($badge_exists) {
            $meets_condition = $this->check_community_cultivator($user_id);
            $has_earned = gamipress_has_user_earned_achievement($community_badge[0]->ID, $user_id);
            
            // Additional debug for community cultivator check
            $stats = get_user_meta($user_id, 'tpgs_gamification_stats', true) ?: [];
            $community_actions = 0;
            if (!empty($stats['community_posts'])) $community_actions++;
            if (!empty($stats['community_comments'])) $community_actions++;
            if (!empty($stats['community_tips'])) $community_actions++;
        }
        
        wp_send_json_success([
            'message' => "Community $action event triggered",
            'badges_updated' => $badge_result['updated'],
            'action' => $action,
            'debug' => [
                'user_stats' => $stats ?: [],
                'community_actions' => [
                    'posts' => isset($stats['community_posts']) ? $stats['community_posts'] : 0,
                    'comments' => isset($stats['community_comments']) ? $stats['community_comments'] : 0,
                    'tips' => isset($stats['community_tips']) ? $stats['community_tips'] : 0
                ],
                'badge_exists' => $badge_exists,
                'total_actions' => (isset($stats['community_posts']) ? $stats['community_posts'] : 0) + 
                                 (isset($stats['community_comments']) ? $stats['community_comments'] : 0) + 
                                 (isset($stats['community_tips']) ? $stats['community_tips'] : 0)
            ]
        ]);
    }
    
    /**
     * Track real community actions (BuddyBoss hook)
     */
    public function track_real_community_actions($activity)
    {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = $activity->user_id;
        $activity_type = $activity->type;
        
        
        // Determine action type based on activity type
        $action_type = null;
        $gamipress_event = null;
        
        switch ($activity_type) {
            case 'activity_update':
                $action_type = 'community_post';
                $gamipress_event = 'create_community_post';
                break;
            case 'activity_comment':
                $action_type = 'community_comment';
                $gamipress_event = 'comment_on_community_post';
                break;
            case 'activity_share':
            case 'bbp_topic_create':
            case 'bbp_reply_create':
            case 'new_blog_post':
            case 'new_blog_comment':
                $action_type = 'community_tip';
                $gamipress_event = 'share_community_tip';
                break;
        }
        
        // Only process if we identified the action type
        if ($action_type) {
            $this->update_gamification_stats($user_id, $action_type);
            
            // Trigger GamiPress event
            if (function_exists('gamipress_trigger_event') && $gamipress_event) {
                $event_result = gamipress_trigger_event([
                    'event' => $gamipress_event,
                    'user_id' => $user_id
                ]);
            }
            
            // Check for badge progress
            $this->evaluate_badges($user_id);
        }
    }

    /**
     * Track recipe view for badge progress
     */
    public function track_recipe_view()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        
        // Update gamification stats
        $this->update_gamification_stats($user_id, 'recipe_viewed');
        
        // Check for badge progress
        $badge_result = $this->evaluate_badges($user_id);
        
        wp_send_json_success([
            'message' => 'Recipe view tracked',
            'badges_updated' => $badge_result['updated']
        ]);
    }

    /**
     * Refresh badges section
     */
    public function refresh_badges()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        
        // Check for badge progress
        $badge_result = $this->evaluate_badges($user_id);
        
        // Get updated badges HTML
        $badges_html = $this->get_badges_section_html($user_id);
        
        wp_send_json_success([
            'html' => $badges_html,
            'badges_updated' => $badge_result['updated'],
            'badges_lost' => $badge_result['lost']
        ]);
    }

    /**
     * Check badges status
     */
    public function check_badges()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        
        // Check for badge progress
        $badge_result = $this->evaluate_badges($user_id);
        
        wp_send_json_success([
            'badges_updated' => $badge_result['updated'],
            'badges_lost' => $badge_result['lost']
        ]);
    }

    /**
     * Get badges section HTML
     */
    private function get_badges_section_html($user_id)
    {
        ob_start();
        
        // Get user's badges
        $user_badges = gamipress_get_user_achievements([
            'user_id' => $user_id,
            'achievement_type' => 'gardening_badges'
        ]);
        
        // Get all available badges
        $all_badges = gamipress_get_achievements([
            'post_type' => 'gardening_badges',
            'posts_per_page' => -1
        ]);
        
        $earned_badge_ids = wp_list_pluck($user_badges, 'ID');
        
        echo '<div class="badges-section">';
        echo '<div class="badges-grid">';
        
        foreach ($all_badges as $badge) {
            $is_earned = in_array($badge->ID, $earned_badge_ids);
            $badge_class = $is_earned ? 'badge earned' : 'badge locked';
            
            echo '<div class="' . $badge_class . '" data-bs-toggle="tooltip" title="' . esc_attr($badge->post_title) . '">';
            echo '<div class="badge-icon">';
            if ($is_earned && has_post_thumbnail($badge->ID)) {
                echo get_the_post_thumbnail($badge->ID, 'thumbnail');
            } else {
                echo '<i class="fas fa-medal"></i>';
            }
            echo '</div>';
            echo '<div class="badge-name">' . esc_html($badge->post_title) . '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
        return ob_get_clean();
    }
}

<?php
// Initialize variables with defaults
$user_id = get_current_user_id();
$pods = isset($pods) ? $pods : TPGS_Pod_Manager::get_user_pods($user_id);
$next_harvest = TPGS_Pod_Manager::get_next_harvest($user_id);
$vegetables = isset($vegetables) ? $vegetables : TPGS_Vegetable_Manager::get_vegetables();
$badges = isset($badges) ? $badges : (class_exists('GamiPress') ? [
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
] : false);
?>


<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        

        <!-- Weekly Streak -->
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
                    <span class="streak-number">
                        <?php
                        $user_id = get_current_user_id();
                        $weekly_streak = get_user_meta($user_id, 'tpgs_weekly_streak', true) ?: [];
                        // Use WordPress timezone and Monday of the current week
                        $week_start = wp_date('Y-m-d', strtotime('monday this week', current_time('timestamp')));
                        $unique_days = array_unique(array_values($weekly_streak[$week_start] ?? []));
                        echo esc_html(count($unique_days));
                        ?></span>
                </div>
                <div class="streak-text">Day Streak!</div>
                <div class="streak-subtitle">🌞 Keep your garden thriving</div>
            </div>

            <div class="week-days">
                <?php
                $user_id = get_current_user_id();
                $weekly_streak = get_user_meta($user_id, 'tpgs_weekly_streak', true) ?: [];
                // Use WordPress timezone and Monday of the current week
                $today = wp_date('Y-m-d', current_time('timestamp'));
                $week_start = wp_date('Y-m-d', strtotime('monday this week', current_time('timestamp')));
                for ($i = 0; $i < 7; $i++) {
                    $day_date = wp_date('Y-m-d', strtotime($week_start . " +$i days"));
                    $day_name = wp_date('D', strtotime($day_date));
                    $completed = isset($weekly_streak[$week_start]) && in_array($day_date, $weekly_streak[$week_start]);
                ?>
                    <div class="day <?php echo $completed ? 'completed' : ''; ?>">
                        <?php echo $day_name; ?>
                    </div>
                <?php } ?>
            </div>

            <button class="log-care-btn streak-btn" data-bs-toggle="modal" data-bs-target="#streakModal">Log today plant care</button>
        </div>


        <?php if ($badges && !empty($badges['all'])): ?>
            <!-- Badges -->
            <div class="badges-card badges-section">
                <div class="badges-header">
                    <span class="badges-icon"><img src="<?php echo TPGS_PLUGIN_URL . 'assets/images/streak.svg'; ?>" alt="Streak icon"></span>
                    <h3>Badges</h3>
                    <small class="text-muted">
                        <?php echo count($badges['earned'] ?? []); ?>/<?php echo count($badges['all']); ?> earned
                    </small>
                </div>

                <div class="badges-grid ">
                    <?php foreach ($badges['all'] as $badge):
                        $earned = is_array($badges['earned']) && in_array($badge->ID, wp_list_pluck($badges['earned'], 'ID'));
                        $badge_title = esc_html($badge->post_title);
                        $badge_class = sanitize_title($badge_title); // e.g. sprouter, nurturer
                        $badge_slug = $badge->post_name;
                        $unlock_instructions = TPGS_Pod_Manager::get_badge_unlock_instructions($badge_slug);
                    ?>
                        <div class="badge <?php echo $earned ? 'earned' : 'locked'; ?>"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="<?php echo $earned ? esc_attr($badge_title . ' - Earned!') : esc_attr($unlock_instructions); ?>">
                            <div class="badge-icon <?php echo $badge_class; ?>">
                                <img src="<?php echo esc_url(get_the_post_thumbnail_url($badge->ID, 'thumbnail')); ?>"
                                    alt="<?php echo esc_attr($badge_title); ?>">
                            </div>
                            <span class="badge-name"><?php echo $badge_title; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </aside>

    <!-- Main Garden Area -->
    <main class="garden-area">
        <div class="garden-header">
            <h1>Office desk garden</h1>
        </div>

        <div class="garden-content">
            <div class="next-harvest">
                <h2>Next harvest</h2>
                <div class="harvest-item">
                    <?php if ($next_harvest && !empty($next_harvest['icon'])): ?>
                        <span class="harvest-icon">
                            <img src="<?php echo esc_url($next_harvest['icon']); ?>" alt="<?php echo esc_attr($next_harvest['name']); ?>" class="plant-icon">
                        </span>
                    <?php endif; ?>
                    <div class="harvest-info">
                        <?php if ($next_harvest): ?>
                            <h3><?php echo esc_html($next_harvest['name']); ?> in
                                <?php echo esc_html($next_harvest['days']); ?> days</h3>
                        <?php else: ?>
                            <div class="pods-count pod-counter"><span class="active-count">0</span> days</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <div class="active-pods">
                <h2>Active pods</h2>
                <div class="pods-count pod-counter"><span
                        class="active-count"><?php echo TPGS_Pod_Manager::get_active_pod_count($user_id); ?></span>/12
                </div>
            </div>
        </div>

        <!-- Plant Grid -->
        <div class="pods-grid">
            <?php if (is_array($pods) && !empty($pods)): ?>
                <?php foreach ($pods as $pod_id => $pod_data): ?>
                    <?php echo TPGS_Pod_Manager::get_pod_html($pod_id, $pod_data); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <div class="pod-slot">
                        <div class="add-icon">
                            <img src="<?php echo TPGS_PLUGIN_URL . 'assets/images/Plus.svg'; ?>" alt="Add pod" class="plus-icon">
                        </div>
                        <div class="pod-label">Pod <?php echo $i; ?></div>
                    </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Planting Modal -->
<div class="modal fade" id="plantingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ready to plant Pod <span id="plantingPodNumber">1</span></h5>                
            </div>
            <div class="modal-body">
                <h6 class="modal-subtitle">Choose what to plant</h6>
                <div class="vegetables-grid">
                    <?php foreach ($vegetables as $vegetable): ?>
                        <div class="vegetable-item" data-vegetable-id="<?php echo $vegetable['id']; ?>">
                            <?php if (!empty($vegetable['icon'])): ?>
                                <img src="<?php echo esc_url($vegetable['icon']); ?>"
                                    alt="<?php echo esc_attr($vegetable['name']); ?>" class="vegetable-icon">
                            <?php endif; ?>
                            <div class="vegetable-name"><?php echo esc_html($vegetable['name']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmPlanting">Plant</button>
            </div>
        </div>
    </div>
</div>

<!-- Streak Modal -->
<div class="modal fade" id="streakModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Harvest Ready Modal -->
<div class="modal fade" id="harvestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content harvest-ready-modal">
            <div class="modal-header border-0 text-center">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center px-4 pb-4">
                <!-- Plant Image -->
                <div class="harvest-plant-image mb-3">
                    <img src="" alt="Plant ready to harvest" id="harvestPlantImage" class="harvest-plant-img">
                </div>

                <!-- Harvest Title -->
                <h3 class="harvest-ready-title mb-3" id="harvestModalTitle">
                    Pod 1 Ready to harvest!
                </h3>

                <!-- Harvest Description -->
                <p class="harvest-description mb-4" id="harvestDescription">
                    Look at that growth—your <?php echo strtolower(esc_html($vegetable['name'])); ?> is thriving!
                    <img src="<?php echo esc_url($vegetable['icon']); ?>" alt="<?php echo esc_attr($vegetable['name']); ?>" class="success-icon">
                </p>
                </p>

                <!-- Growing Time Card -->
                <div class="growing-time-card mb-4">
                    <div class="growing-days" id="growingDays">42 days</div>
                    <div class="growing-label" id="growingLabel">Total growing time</div>
                    <div class="growing-dates" id="growingDates">May 30, 2025 - July 11, 2025</div>
                </div>

                <!-- Harvest Message -->
                <p class="harvest-message mb-4" id="harvestMessage">
                    You've reached the finish line of your first hydroponic cycle. Harvest, enjoy, and get ready to plant your next crop. 🎉 You did it! Time to taste the results.
                </p>

                <!-- Action Buttons -->
                <div class="harvest-action-buttons">
                    <button type="button" class="btn btn-outline-secondary cancel-harvest-btn me-2" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary harvest-now-btn" id="harvestNowBtn">
                        Harvest now
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Harvest Confirmation Modal -->
<div class="modal fade" id="harvestConfirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content harvest-ready-modal">
            <div class="modal-header border-0 text-center">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center px-4 pb-4">
                <!-- Plant Image -->
                <div class="harvest-plant-image mb-3">
                    <img src="" alt="Plant" id="confirmationPlantImage" class="harvest-plant-img">
                </div>

                <!-- Confirmation Title -->
                <h3 class="harvest-ready-title mb-3" id="confirmationModalTitle">
                    Confirm Harvest
                </h3>

                <!-- Confirmation Message -->
                <p class="harvest-message mb-4" id="confirmationMessage">
                    Are you sure you want to harvest now? There are still <span id="remainingDays">X</span> days remaining.
                </p>

                <!-- Action Buttons -->
                <div class="harvest-action-buttons">
                    <button type="button" class="btn btn-outline-secondary cancel-harvest-btn me-2" data-bs-dismiss="modal">
                        No
                    </button>
                    <button type="button" class="btn btn-primary harvest-now-btn" id="confirmHarvestBtn">
                        Yes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Intro Modal -->
<div class="modal fade" id="introModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content intro-modal">
            <div class="modal-body p-0">
                <!-- Step 1: Add Plant to pod -->
                <div class="intro-step" data-step="1">
                    <div class="intro-image">
                        <img src="<?php echo TPGS_PLUGIN_URL . 'assets/images/intro-step-1.png'; ?>" alt="Plant your first pod" class="step-image">
                    </div>
                    <div class="intro-content">
                        <h2 class="intro-title">Plant your first pod</h2>
                        <p class="intro-text">
                            Choose a pod and fill it with something fresh — 🥬 Basil, Lettuce, 🍅 Tomatoes, 🌱 Mixed Herbs, 🥗 Spinach, or 🌶️ Peppers. Start growing your way — smart, simple, and sustainable.
                        </p>
                    </div>
                </div>

                <!-- Step 2: Log daily plant care -->
                <div class="intro-step" data-step="2" style="display: none;">
                    <div class="intro-image">
                        <img src="<?php echo TPGS_PLUGIN_URL . 'assets/images/intro-step-2.png'; ?>" alt="Log daily plant care" class="step-image">
                    </div>
                    <div class="intro-content">
                        <h2 class="intro-title">Log daily plant care</h2>
                        <p class="intro-text">
                            Build your gardening streak by logging daily care activities. Water, feed nutrients, check plant health, and take progress photos. Every care action helps maintain your growing momentum and earns rewards.
                        </p>
                    </div>
                </div>

                <!-- Step 3: Track Growth Progress -->
                <div class="intro-step" data-step="3" style="display: none;">
                    <div class="intro-image">
                        <img src="<?php echo TPGS_PLUGIN_URL . 'assets/images/intro-step-3.png'; ?>" alt="Track Growth Progress" class="step-image">
                    </div>
                    <div class="intro-content">
                        <h2 class="intro-title">Track Growth Progress</h2>
                        <p class="intro-text">
                            Watch your plants grow day by day with real-time updates. Monitor growth progress, countdown to harvest dates, and receive care reminders. Your garden journey is tracked with precision and care.
                        </p>
                    </div>
                </div>

                <!-- Step 4: Harvest Now -->
                <div class="intro-step" data-step="4" style="display: none;">
                    <div class="intro-image">
                        <img src="<?php echo TPGS_PLUGIN_URL . 'assets/images/intro-step-4.png'; ?>" alt="Harvest Now" class="step-image">
                    </div>
                    <div class="intro-content">
                        <h2 class="intro-title">Harvest Now</h2>
                        <p class="intro-text">
                            When your plants are ready, celebrate your harvest! Enjoy the fruits of your labor and get ready to plant your next crop. Every harvest is a victory and brings you closer to becoming a master gardener.
                        </p>
                    </div>
                </div>

                <!-- Step 5: Ready to Grow again -->
                <div class="intro-step" data-step="5" style="display: none;">
                    <div class="intro-image">
                        <img src="<?php echo TPGS_PLUGIN_URL . 'assets/images/intro-step-5.png'; ?>" alt="Ready to Grow again" class="step-image">
                    </div>
                    <div class="intro-content">
                        <h2 class="intro-title">Ready to Grow again</h2>
                        <p class="intro-text">
                            Your hydroponic garden is ready for the next cycle! Plant new seeds, track your progress, and continue your sustainable growing journey. The cycle never ends — keep growing, keep learning, keep harvesting.
                        </p>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="intro-navigation">
                    <div class="intro-dots-progress">
                        <div class="intro-dots">
                            <span class="intro-dot active" data-step="1"></span>
                            <span class="intro-dot" data-step="2"></span>
                            <span class="intro-dot" data-step="3"></span>
                            <span class="intro-dot" data-step="4"></span>
                            <span class="intro-dot" data-step="5"></span>
                        </div>
                        <div class="intro-progress">1/5</div>
                    </div>
                    <div class="intro-buttons">
                        <button type="button" class="btn btn-outline-secondary intro-skip-btn">Skip</button>
                        <button type="button" class="btn btn-primary intro-next-btn">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!wp_style_is('font-awesome')): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<?php endif; ?>


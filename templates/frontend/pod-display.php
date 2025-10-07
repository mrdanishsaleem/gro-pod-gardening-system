<?php
$status = $pod_data['status'] ?? 'empty';
$status_class = $status === 'empty' ? 'empty-pod' : 'active-pod';
$vegetable = $pod_data['vegetable_id'] ? TPGS_Vegetable_Manager::get_vegetable($pod_data['vegetable_id']) : false;
$pod_icon = $pod_data['icon'] ?? ($vegetable && !empty($vegetable['icon']) ? $vegetable['icon'] : '');
?>

<div class="pod <?php echo esc_attr($status_class); ?> pod-<?php echo $pod_id; ?>" data-pod-id="<?php echo $pod_id; ?>">

    <?php if ($status === 'empty'): ?>
        <div class="add-plant"><img src="<?php echo TPGS_PLUGIN_URL . 'assets/images/Plus.svg'; ?>" alt="Add pod" class="plus-icon"></div>
        <span class="pod-label">Pod <?php echo $pod_id; ?></span>
        <span class="pod-number d-none"><?php echo $pod_id; ?></span>
        <span class="pod-status d-none">Empty</span>
    <?php else: ?>
        <div class="plant-container">
            <div class="<?php echo sanitize_title($vegetable['name']); ?>-plant">
                <?php if (!empty($pod_icon)): ?>
                    <img src="<?php echo esc_url($pod_icon . '?v=' . time()); ?>" alt="<?php echo esc_attr($vegetable['name']); ?> action" class="vegetable-icon">
                <?php else: ?>
                    <div class="placeholder-icon">?</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="plant-info">
            <h4><?php echo esc_html($vegetable['name']); ?></h4>
            <p><?php echo esc_html($pod_data['days_remaining']); ?> days to harvest</p>
        </div>
        <span class="pod-number d-none"><?php echo $pod_id; ?></span>
        <span class="pod-status d-none"><?php echo esc_html(TPGS_Pod_Manager::get_status_text($status)); ?></span>
        <span class="vegetable-name d-none"><?php echo esc_html($vegetable['name']); ?></span>
        <span class="days-remaining d-none"><?php echo esc_html($pod_data['days_remaining']); ?></span>
    <?php endif; ?>

</div>

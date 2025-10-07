<?php
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$pods = TPGS_Pod_Manager::get_user_pods($user_id);
$vegetables = TPGS_Vegetable_Manager::get_vegetables();
$has_growing = array_filter($pods, fn($p) => $p['status'] === 'growing');
$first_growing_pod = $has_growing ? key($has_growing) : null;
?>

<div class="modal-body">
    <?php if (empty($has_growing)) : ?>
        <div class="alert alert-warning text-center">Please grow a plant first.</div>
    <?php else : ?>
        <h4>Select Pod and Action</h4>
        <div id="streakPodSelect">
            <?php foreach ($pods as $pod_id => $pod_data) : 
                if ($pod_data['status'] === 'growing') {
                    $vegetable = TPGS_Vegetable_Manager::get_vegetable($pod_data['vegetable_id']);
                    if ($vegetable && !empty($vegetable['icon'])) : ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="streak_pods" value="<?php echo $pod_id; ?>" id="pod_<?php echo $pod_id; ?>" <?php echo $pod_id === $first_growing_pod ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="pod_<?php echo $pod_id; ?>">
                                <img src="<?php echo esc_url($vegetable['icon']); ?>" alt="<?php echo esc_attr($vegetable['name']); ?>" class="me-2" style="width: 20px; height: 20px;">
                                Pod <?php echo $pod_id; ?>: <?php echo esc_html($vegetable['name']); ?>
                            </label>
                        </div>
                    <?php endif; }
            endforeach; ?>
        </div>
        <div id="streakActions">
            <?php
            $pod_id = $first_growing_pod;
            $pod_data = $pods[$pod_id];
            $vegetable = $pod_data['vegetable_id'] ? TPGS_Vegetable_Manager::get_vegetable($pod_data['vegetable_id']) : false;
            $default_actions = $vegetable['actions'] ?? ['Watered', 'Fed nutrients', 'Checked plant health', 'Took progress photo'];
            foreach ($default_actions as $action) : 
                $action_key = sanitize_title($action);
                $action_image = $vegetable['action_images'][$action_key] ?? '';
                ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="streak_actions" value="<?php echo esc_attr($action); ?>" id="action_<?php echo $action_key; ?>" <?php echo $action === $default_actions[0] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="action_<?php echo $action_key; ?>">
                        <?php if ($action_image) : ?>
                            <img src="<?php echo esc_url($action_image); ?>" alt="<?php echo esc_attr($action); ?>" class="me-2" style="width: 20px; height: 20px;">
                        <?php endif; ?>
                        <?php echo esc_html($action); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" id="saveStreak" <?php echo empty($has_growing) ? 'disabled' : ''; ?>>Save</button>
</div>
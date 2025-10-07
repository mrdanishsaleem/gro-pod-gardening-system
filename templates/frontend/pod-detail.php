<?php
if (!defined('ABSPATH')) {
    exit;
}
$vegetable = $pod_data['vegetable_id'] ? TPGS_Vegetable_Manager::get_vegetable($pod_data['vegetable_id']) : false;
$pod_icon = $pod_data['icon'] ?? ($vegetable && !empty($vegetable['icon']) ? $vegetable['icon'] : '');
?>

<div class="modal-header">
    <h5 class="modal-title">Pod #<?php echo $pod_id; ?> Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <?php if ($pod_data['status'] === 'empty'): ?>
        <div class="alert alert-info">This pod is currently empty.</div>
    <?php else: ?>
        <div class="text-center mb-3">
            <?php if ($pod_icon): ?>
                <img src="<?php echo esc_url($pod_icon . '?v=' . time()); ?>" alt="<?php echo esc_attr($vegetable['name']); ?>" class="pod-detail-image">
            <?php endif; ?>
            <h4><?php echo esc_html($vegetable['name']); ?></h4>
        </div>

        <div class="pod-details">
            <div class="detail-row">
                <span class="detail-label">Planted On:</span>
                <span class="detail-value"><?php echo date('F j, Y', strtotime($pod_data['date_planted'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Days Remaining:</span>
                <span class="detail-value days-remaining-value"><?php echo esc_html($pod_data['days_remaining']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value pod-status-value <?php echo $pod_data['status'] === 'ready' ? 'text-success' : 'text-warning'; ?>">
                    <?php echo esc_html(TPGS_Pod_Manager::get_status_text($pod_data['status'])); ?>
                </span>
            </div>
        </div>

        <?php if ($pod_data['status'] === 'growing'): ?>
            <div class="date-picker-container mt-4">
                <h5>Adjust Planting Date</h5>
                <p class="text-muted">You can backdate your planting if needed.</p>
                <input type="date" id="podDatePicker" class="form-control"
                    value="<?php echo date('Y-m-d', strtotime($pod_data['date_planted'])); ?>"
                    max="<?php echo date('Y-m-d'); ?>"
                    data-pod-id="<?php echo $pod_id; ?>">
                <button type="button" id="updatePodDate" class="btn btn-primary mt-2" data-pod-id="<?php echo $pod_id; ?>">
                    Update Date
                </button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<div class="modal-footer">
    <?php if ($pod_data['status'] !== 'empty'): ?>
        <button type="button" id="resetPod" class="btn btn-danger" data-pod-id="<?php echo $pod_id; ?>">
            Reset Pod
        </button>
    <?php endif; ?>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>

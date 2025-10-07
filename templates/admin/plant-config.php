<div class="wrap">
    <h1>GRO Pod Gardening System</h1>

    <?php if (isset($_GET['settings-updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>Vegetables updated successfully!</p>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php">
        <?php settings_fields('tpgs_vegetables_group'); ?>
        <?php do_settings_sections('tpgs_vegetables_group'); ?>

        <h2>Current Vegetables</h2>
        <table class="form-table widefat tpgs-vegetable-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Icon URL</th>
                    <th>Main Image URL</th>
                    <th>Action Images</th>
                    <th>Growth Duration (days)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="vegetables-list">
                <?php
                $vegetables = TPGS_Vegetable_Manager::get_vegetables();
                if (is_array($vegetables)):
                    foreach ($vegetables as $vegetable):
                        if (!is_array($vegetable)) continue; // Skip if not an array
                        ?>
                        <tr>
                            <td>
                                <input type="hidden" name="tpgs_vegetables[<?php echo esc_attr($vegetable['id']); ?>][id]" value="<?php echo esc_attr($vegetable['id']); ?>">
                                <input type="text" name="tpgs_vegetables[<?php echo esc_attr($vegetable['id']); ?>][name]" value="<?php echo esc_attr($vegetable['name']); ?>" class="regular-text">
                            </td>
                            <td>
                                <input type="text" name="tpgs_vegetables[<?php echo esc_attr($vegetable['id']); ?>][icon]" value="<?php echo esc_url($vegetable['icon']); ?>" class="regular-text">
                            </td>
                            <td>
                                <input type="text" name="tpgs_vegetables[<?php echo esc_attr($vegetable['id']); ?>][main_image]" value="<?php echo esc_url($vegetable['main_image']); ?>" class="regular-text">
                            </td>
                            <td>
                                <?php $actions = ['watered', 'fed-nutrients', 'checked-plant-health', 'took-progress-photo'];
                                foreach ($actions as $action):
                                    $action_name = ucfirst(str_replace('-', ' ', $action));
                                    $value = isset($vegetable['action_images'][$action]) ? $vegetable['action_images'][$action] : '';
                                    ?>
                                    <div class="tpgs-action-input">
                                        <label><?php echo esc_html($action_name); ?></label>
                                        <input type="text" name="tpgs_vegetables[<?php echo esc_attr($vegetable['id']); ?>][action_images][<?php echo $action; ?>]" value="<?php echo esc_url($value); ?>" class="regular-text">
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <input type="number" name="tpgs_vegetables[<?php echo esc_attr($vegetable['id']); ?>][growth_duration]" value="<?php echo esc_attr($vegetable['growth_duration']); ?>" min="1" class="small-text">
                            </td>
                            <td>
                                <button type="button" class="button button-secondary remove-vegetable">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>

        <h2>Add New Vegetable</h2>
        <table class="form-table tpgs-vegetable-form">
            <tr>
                <th scope="row"><label for="new_vegetable_name" class="required">Name</label></th>
                <td><input type="text" id="new_vegetable_name" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="new_vegetable_icon" class="required">Icon URL</label></th>
                <td><input type="text" id="new_vegetable_icon" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="new_vegetable_main_image" class="required">Main Image URL</label></th>
                <td><input type="text" id="new_vegetable_main_image" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label class="required">Action Images</label></th>
                <td>
                    <?php $default_actions = ['Watered', 'Fed nutrients', 'Checked plant health', 'Took progress photo'];
                    foreach ($default_actions as $action) :
                        $action_key = sanitize_title($action); ?>
                        <div class="tpgs-action-row">
                            <label for="new_vegetable_action_<?php echo $action_key; ?>"><?php echo esc_html($action); ?></label>
                            <input type="text" id="new_vegetable_action_<?php echo $action_key; ?>" class="regular-text" data-action="<?php echo $action_key; ?>">
                        </div>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="new_vegetable_duration" class="required">Growth Duration (days)</label></th>
                <td><input type="number" id="new_vegetable_duration" min="1" class="small-text"></td>
            </tr>
            <tr>
                <th scope="row"></th>
                <td><button type="button" id="add_vegetable" class="button button-primary">Add Vegetable</button></td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>


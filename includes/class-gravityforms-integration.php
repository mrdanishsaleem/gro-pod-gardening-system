<?php
if (!defined('ABSPATH')) {
    exit;
}

class TPG_GravityForms_Integration
{
    public function __construct()
    {
        // Populate field 28 (checkbox cards)
        add_filter('gform_pre_render_3',            [$this, 'populate_plant_choices']);
        add_filter('gform_pre_validation_3',        [$this, 'populate_plant_choices']);
        add_filter('gform_pre_submission_filter_3', [$this, 'populate_plant_choices']); // <-- important for validation/submission
        add_filter('gform_admin_pre_render_3',      [$this, 'populate_plant_choices']); // edit-entry/admin views

        // Handle submission
        add_action('gform_after_submission_3', [$this, 'handle_form_submission'], 10, 2);
    }

    /**
     * Populate checkbox field (ID 28) with image-card choices.
     * IMPORTANT: we also rebuild $field->inputs so GF validation works.
     */
    public function populate_plant_choices($form)
    {
        $vegetables = get_option('tpgs_vegetables', []);
        if (empty($vegetables)) {
            return $form;
        }

        foreach ($form['fields'] as &$field) {
            if ((int) $field->id !== 28) {
                continue;
            }

            // Make sure it's a checkbox field
            if ($field->type !== 'checkbox') {
                $field->type = 'checkbox';
            }

            $field->enableChoiceImages = false;
            $field->enableChoiceHTMLFormatting = true;

            $choices = [];
            $inputs  = [];
            $i = 1;

            foreach ($vegetables as $veg) {
                if (empty($veg['icon'])) {
                    continue;
                }

                $name = isset($veg['name']) ? (string) $veg['name'] : '';
                $id   = isset($veg['id'])   ? (int) $veg['id']       : 0;
                if (!$id || $name === '') {
                    continue;
                }

                // Card UI goes INSIDE the label
                $card_html = sprintf(
                    '<div class="plant-card">
                        <div class="plant-card__img"><img src="%s" alt="%s" loading="lazy" /></div>
                        <div class="plant-card__name">%s</div>
                    </div>',
                    esc_url($veg['icon']),
                    esc_attr($name),
                    esc_html($name)
                );

                $choices[] = [
                    'text'  => $card_html,       // rendered in <label>
                    'value' => (string) $id,     // saved value (veg ID)
                ];

                // Rebuild sub-inputs 28.1, 28.2, ... (plain label text here)
                $inputs[] = [
                    'id'    => "{$field->id}.{$i}",
                    'label' => $name,            // must be plain text for GF internals
                ];

                $i++;
            }

            $field->choices = $choices;
            $field->inputs  = $inputs;  // <-- this fixes "required" validation
        }

        return $form;
    }

    /**
     * After submit: Store selected plants for user reference (but don't auto-plant them).
     * Users will start with an empty garden and can manually plant their selected vegetables later.
     */
    public function handle_form_submission($entry, $form)
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();

        // Collect selected plant IDs from field 28 sub-inputs (28.1, 28.2, ...)
        $selected_ids = [];

        // Prefer reading via the field's inputs (reliable)
        $checkbox_field = null;
        foreach ($form['fields'] as $f) {
            if ((int) $f->id === 28) {
                $checkbox_field = $f;
                break;
            }
        }

        if ($checkbox_field && is_array($checkbox_field->inputs)) {
            foreach ($checkbox_field->inputs as $input) {
                $key = (string) $input['id'];     // e.g. '28.3'
                $val = rgar($entry, $key);
                if ($val !== '' && $val !== null) {
                    $selected_ids[] = (int) $val;
                }
            }
        } else {
            // Fallback scan
            foreach ($entry as $k => $v) {
                if (is_string($k) && strpos($k, '28.') === 0 && $v !== '') {
                    $selected_ids[] = (int) $v;
                }
            }
        }

        if (empty($selected_ids)) {
            return;
        }

        // Store selected plants for user reference (optional - for future use)
        // This allows you to show "Your selected plants" or similar features later
        update_user_meta($user_id, 'tpgs_onboarding_selected_plants', $selected_ids);

        // Log the onboarding completion
        update_user_meta($user_id, 'tpgs_onboarding_completed', current_time('mysql'));

        // Note: Plants are NOT automatically planted - user starts with empty garden
        // User can manually plant their selected vegetables through the dashboard
    }
}
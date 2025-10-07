<?php
class TPGS_Vegetable_Manager
{

    private static $vegetables_option = 'tpgs_vegetables';

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public static function setup_default_vegetables()
    {
        $default_vegetables = array(
            array(
                'id' => 1,
                'name' => 'Tomato',
                'icon' => TPGS_PLUGIN_URL . 'assets/images/vegetables/tomato.png',
                'main_image' => TPGS_PLUGIN_URL . 'assets/images/vegetables/tomato_main.png',
                'action_images' => [
                    'watered' => TPGS_PLUGIN_URL . 'assets/images/actions/watering.png',
                    'fed-nutrients' => TPGS_PLUGIN_URL . 'assets/images/actions/nutrients.png',
                    'checked-plant-health' => TPGS_PLUGIN_URL . 'assets/images/actions/health.png',
                    'took-progress-photo' => TPGS_PLUGIN_URL . 'assets/images/actions/photo.png'
                ],
                'growth_duration' => 90
            ),
            // Add similar entries for Carrot and Lettuce
            array(
                'id' => 2,
                'name' => 'Carrot',
                'icon' => TPGS_PLUGIN_URL . 'assets/images/vegetables/carrot.png',
                'main_image' => TPGS_PLUGIN_URL . 'assets/images/vegetables/carrot_main.png',
                'action_images' => [
                    'watered' => TPGS_PLUGIN_URL . 'assets/images/actions/watering.png',
                    'fed-nutrients' => TPGS_PLUGIN_URL . 'assets/images/actions/nutrients.png',
                    'checked-plant-health' => TPGS_PLUGIN_URL . 'assets/images/actions/health.png',
                    'took-progress-photo' => TPGS_PLUGIN_URL . 'assets/images/actions/photo.png'
                ],
                'growth_duration' => 70
            ),
            array(
                'id' => 3,
                'name' => 'Lettuce',
                'icon' => TPGS_PLUGIN_URL . 'assets/images/vegetables/lettuce.png',
                'main_image' => TPGS_PLUGIN_URL . 'assets/images/vegetables/lettuce_main.png',
                'action_images' => [
                    'watered' => TPGS_PLUGIN_URL . 'assets/images/actions/watering.png',
                    'fed-nutrients' => TPGS_PLUGIN_URL . 'assets/images/actions/nutrients.png',
                    'checked-plant-health' => TPGS_PLUGIN_URL . 'assets/images/actions/health.png',
                    'took-progress-photo' => TPGS_PLUGIN_URL . 'assets/images/actions/photo.png'
                ],
                'growth_duration' => 50
            )
        );

        if (false === get_option(self::$vegetables_option)) {
            update_option(self::$vegetables_option, $default_vegetables);
        }
    }

    public static function get_vegetables()
    {
        return get_option(self::$vegetables_option, array());
    }

    public static function get_vegetable($id)
    {
        $vegetables = self::get_vegetables();

        foreach ($vegetables as $vegetable) {
            if ($vegetable['id'] == $id) {
                return $vegetable;
            }
        }

        return false;
    }

    public function add_admin_menu()
    {
        // Add main menu item
        add_menu_page(
            '12-Pod Gardening System', // Page title
            'Garden System', // Menu title
            'manage_options', // Capability
            'tpgs_vegetables_config', // Menu slug
            array($this, 'render_admin_page'), // Callback function
            'dashicons-palmtree', // Icon (you can change this)
            30 // Position in menu
        );

        // Add submenu for vegetables (optional - keeps the same page but under main menu)
        add_submenu_page(
            'tpgs_vegetables_config', // Parent slug
            'Garden Vegetables', // Page title
            'Vegetables', // Menu title
            'manage_options', // Capability
            'tpgs_vegetables_config', // Menu slug (same as parent)
            array($this, 'render_admin_page') // Callback function
        );
    }

    public function register_settings()
    {
        register_setting(
            'tpgs_vegetables_group',
            self::$vegetables_option,
            array($this, 'sanitize_vegetables')
        );
    }

    public function sanitize_vegetables($input) {
    $sanitized = array();

    if (!is_array($input)) {
        return $sanitized;
    }

    // Use array_values to ensure we get all vegetables regardless of array keys
    foreach (array_values($input) as $vegetable) {
        if (!is_array($vegetable)) {
            continue; // Skip if not an array
        }

        $sanitized[] = array(
            'id' => isset($vegetable['id']) ? absint($vegetable['id']) : 0,
            'name' => isset($vegetable['name']) ? sanitize_text_field($vegetable['name']) : '',
            'icon' => isset($vegetable['icon']) ? esc_url_raw($vegetable['icon']) : '',
            'main_image' => isset($vegetable['main_image']) ? esc_url_raw($vegetable['main_image']) : '',
            'action_images' => isset($vegetable['action_images']) ? array_map('esc_url_raw', $vegetable['action_images']) : [],
            'growth_duration' => isset($vegetable['action_images']) ? absint($vegetable['growth_duration']) : 0
        );
    }

    return $sanitized;
}

    public function render_admin_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $vegetables = self::get_vegetables();
        $next_id = !empty($vegetables) ? max(array_column($vegetables, 'id')) + 1 : 1;

        include TPGS_PLUGIN_DIR . 'templates/admin/plant-config.php';
    }

    public static function add_vegetable($name, $icon, $growth_duration)
    {
        $vegetables = self::get_vegetables();
        $next_id = !empty($vegetables) ? max(array_column($vegetables, 'id')) + 1 : 1;

        $new_vegetable = array(
            'id' => $next_id,
            'name' => $name,
            'icon' => $icon,
            'growth_duration' => $growth_duration
        );

        $vegetables[] = $new_vegetable;

        return update_option(self::$vegetables_option, $vegetables);
    }

    public static function delete_vegetable($id)
    {
        $vegetables = self::get_vegetables();
        $updated_vegetables = array();

        foreach ($vegetables as $vegetable) {
            if ($vegetable['id'] != $id) {
                $updated_vegetables[] = $vegetable;
            }
        }

        return update_option(self::$vegetables_option, $updated_vegetables);
    }
}

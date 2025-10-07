<?php
class TPGS_Plant_Manager
{

    private static $plants_option = 'tpgs_plants';

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public static function setup_default_plants()
    {
        $default_plants = array(
            array(
                'id' => 1,
                'name' => 'Tomato',
                'icon' => TPGS_PLUGIN_URL . 'assets/images/plants/tomato.png',
                'main_image' => TPGS_PLUGIN_URL . 'assets/images/plants/tomato_main.png',
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
                'icon' => TPGS_PLUGIN_URL . 'assets/images/plants/carrot.png',
                'main_image' => TPGS_PLUGIN_URL . 'assets/images/plants/carrot_main.png',
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
                'icon' => TPGS_PLUGIN_URL . 'assets/images/plants/lettuce.png',
                'main_image' => TPGS_PLUGIN_URL . 'assets/images/plants/lettuce_main.png',
                'action_images' => [
                    'watered' => TPGS_PLUGIN_URL . 'assets/images/actions/watering.png',
                    'fed-nutrients' => TPGS_PLUGIN_URL . 'assets/images/actions/nutrients.png',
                    'checked-plant-health' => TPGS_PLUGIN_URL . 'assets/images/actions/health.png',
                    'took-progress-photo' => TPGS_PLUGIN_URL . 'assets/images/actions/photo.png'
                ],
                'growth_duration' => 50
            )
        );

        if (false === get_option(self::$plants_option)) {
            update_option(self::$plants_option, $default_plants);
        }
    }

    public static function get_plants()
    {
        return get_option(self::$plants_option, array());
    }

    public static function get_plant($id)
    {
        $plants = self::get_plants();

        foreach ($plants as $plant) {
            if ($plant['id'] == $id) {
                return $plant;
            }
        }

        return false;
    }

    public function add_admin_menu()
    {
        // Add main menu item
        add_menu_page(
            'GRO Pod Garden Management', // Page title
            'GRO Pod Garden', // Menu title
            'manage_options', // Capability
            'tpgs_plants_config', // Menu slug
            array($this, 'render_admin_page'), // Callback function
            'dashicons-palmtree', // Icon (you can change this)
            30 // Position in menu
        );

        // Add submenu for plants (optional - keeps the same page but under main menu)
        add_submenu_page(
            'tpgs_plants_config', // Parent slug
            'Plant Configuration', // Page title
            'Plant Configuration', // Menu title
            'manage_options', // Capability
            'tpgs_plants_config', // Menu slug (same as parent)
            array($this, 'render_admin_page') // Callback function
        );
    }

    public function register_settings()
    {
        register_setting(
            'tpgs_plants_group',
            self::$plants_option,
            array($this, 'sanitize_plants')
        );
    }

    public function sanitize_plants($input) {
    $sanitized = array();

    if (!is_array($input)) {
        return $sanitized;
    }

    // Use array_values to ensure we get all plants regardless of array keys
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

        $plants = self::get_plants();
        $next_id = !empty($plants) ? max(array_column($plants, 'id')) + 1 : 1;

        include TPGS_PLUGIN_DIR . 'templates/admin/plant-config.php';
    }

    public static function add_vegetable($name, $icon, $growth_duration)
    {
        $plants = self::get_plants();
        $next_id = !empty($plants) ? max(array_column($plants, 'id')) + 1 : 1;

        $new_vegetable = array(
            'id' => $next_id,
            'name' => $name,
            'icon' => $icon,
            'growth_duration' => $growth_duration
        );

        $plants[] = $new_vegetable;

        return update_option(self::$plants_option, $plants);
    }

    public static function delete_vegetable($id)
    {
        $plants = self::get_plants();
        $updated_plants = array();

        foreach ($plants as $vegetable) {
            if ($vegetable['id'] != $id) {
                $updated_plants[] = $vegetable;
            }
        }

        return update_option(self::$plants_option, $updated_plants);
    }
}

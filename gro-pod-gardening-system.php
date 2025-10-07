<?php
/*
Plugin Name: GRO Pod Gardening System
Description: A comprehensive WordPress plugin that transforms your website into an interactive hydroponic gardening experience. Users can manage virtual plant pods, track growth progress, earn achievements, and engage with the community.
Version: 2.1.3
Author: Danish Saleem
Text Domain: gro-pod-gardening
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Network: false

Documentation: See README.md for detailed usage instructions
Installation: See INSTALL.md for setup guide
Changelog: See CHANGELOG.md for version history
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('TPGS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TPGS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load required files
require_once TPGS_PLUGIN_DIR . 'includes/class-pod-manager.php';
require_once TPGS_PLUGIN_DIR . 'includes/class-plant-manager.php';
require_once TPGS_PLUGIN_DIR . 'includes/class-notifications.php';
require_once TPGS_PLUGIN_DIR . 'includes/class-cron-handler.php';
require_once TPGS_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once TPGS_PLUGIN_DIR . 'includes/class-gravityforms-integration.php';
// Load Gamipress integration
if (class_exists('GamiPress')) {
    try {
        require_once TPGS_PLUGIN_DIR . 'includes/class-gamipress-integration.php';
    } catch (Exception $e) {
    }
}

class GRO_Pod_Gardening_System
{

    public function __construct()
    {
        // Initialize all components
        $this->init_components();
        $this->register_hooks();
    }

    private function init_components()
    {
        new TPGS_Pod_Manager();
        new TPGS_Plant_Manager();
        new TPGS_Notifications();
        new TPGS_Cron_Handler();
        new TPGS_Shortcodes();
        new TPG_GravityForms_Integration();
        add_action('init', array($this, 'tpgs_setup_cron'));
    }

    function tpgs_setup_cron()
    {
        if (!wp_next_scheduled('tpgs_weekly_streak_reset')) {
            wp_schedule_event(strtotime('next Sunday 00:00:00'), 'weekly', 'tpgs_weekly_streak_reset');
        }
        if (!wp_next_scheduled('tpgs_daily_growth_tracker')) {
            wp_schedule_event(time(), 'daily', 'tpgs_daily_growth_tracker');
        }
    }

    private function register_hooks()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function activate()
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        TPGS_Plant_Manager::setup_default_plants();
        if (!wp_next_scheduled('tpgs_daily_growth_tracker')) {
            wp_schedule_event(time(), 'daily', 'tpgs_daily_growth_tracker');
        }
        if (!wp_next_scheduled('tpgs_weekly_streak_reset')) {
            wp_schedule_event(strtotime('next monday 00:00:00'), 'weekly', 'tpgs_weekly_streak_reset');
        }
        update_option('tpgs_version', '1.0');
        $users = get_users();
        foreach ($users as $user) {
            delete_user_meta($user->ID, '_gamipress_achievements');
            delete_transient('tpgs_badges_' . $user->ID);
        }
    }

    public function deactivate()
    {
        // Clear our cron event
        wp_clear_scheduled_hook('tpgs_daily_growth_tracker');
    }

    public function enqueue_assets()
    {
        // Bootstrap CSS
        wp_enqueue_style(
            'tpgs-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            array(),
            '5.3.0'
        );

        // Bootstrap JS Bundle (with Popper)
        wp_enqueue_script(
            'tpgs-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            array('jquery'),
            '5.3.0',
            true
        );

        // Plugin styles
        // wp_enqueue_style(
        //     'tpgs-styles',
        //     TPGS_PLUGIN_URL . 'assets/css/style.css',
        //     array(),
        //     filemtime(TPGS_PLUGIN_DIR . 'assets/css/style.css')
        // );
        wp_enqueue_style(
            'tpgs-styles-v3',
            TPGS_PLUGIN_URL . 'assets/css/gro-dashboard.css',
            array(),
            filemtime(TPGS_PLUGIN_DIR . 'assets/css/gro-dashboard.css')
        );
        wp_enqueue_style(
            'modals-v3',
            TPGS_PLUGIN_URL . 'assets/css/modals.css',
            array(),
            filemtime(TPGS_PLUGIN_DIR . 'assets/css/modals.css')
        );

        // Plugin scripts
        wp_enqueue_script(
            'tpgs-scripts',
            TPGS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'tpgs-bootstrap'),
            filemtime(TPGS_PLUGIN_DIR . 'assets/js/frontend.js'),
            true
        );

        // Localize script for AJAX
        wp_localize_script('tpgs-scripts', 'tpgs_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tpgs_nonce'),
            'user_id' => get_current_user_id(),
            'plants' => TPGS_Plant_Manager::get_plants(),
            'is_new_user' => self::is_new_user()
        ));
    }

    public function enqueue_admin_assets($hook)
    {
        // Check if we're on our plugin page by checking the hook contains our slug
        if (strpos($hook, 'tpgs_plants_config') !== false) {
            wp_enqueue_style('tpgs-admin-styles', TPGS_PLUGIN_URL . 'assets/css/admin.css');
            wp_enqueue_script('tpgs-admin-scripts', TPGS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), null, true);
        }
    }

    /**
     * Check if the current user is a new user (registered within the last 24 hours)
     */
    public static function is_new_user()
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        $user_registered = get_userdata($user_id)->user_registered;
        $registration_time = strtotime($user_registered);
        $current_time = current_time('timestamp');

        // Check if user registered within the last 24 hours
        $is_new = ($current_time - $registration_time) < (24 * 60 * 60);

        // Also check if they haven't completed the intro before
        $intro_completed = get_user_meta($user_id, 'tpgs_intro_completed', true);

        return $is_new && !$intro_completed;
    }
}

// Initialize the plugin
new GRO_Pod_Gardening_System();
<?php
class TPGS_Shortcodes
{

    public function __construct()
    {
        add_shortcode('gro_pod_dashboard', [$this, 'render_gro_dashboard']);
    }


    /**
     * Get User Statistics
     */
    private function get_user_stats($user_id)
    {
        $stats = get_user_meta($user_id, 'tpgs_gamification_stats', true) ?: [];

        return wp_parse_args($stats, [
            'total_planted' => 0,
            'total_harvested' => 0,
            'first_planting' => '',
            'last_harvest' => ''
        ]);
    }

    /**
     * Login Required Message
     */
    private function login_required_message()
    {
        return '<div class="alert alert-warning text-center">
            <i class="fas fa-sign-in-alt me-2"></i>
            Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access your garden.
        </div>';
    }

    public function render_gro_dashboard()
    {
        if (!is_user_logged_in()) {
            return $this->login_required_message();
        }

        ob_start();
        include TPGS_PLUGIN_DIR . 'templates/frontend/gro-dashboard.php';
        return ob_get_clean();
    }
}

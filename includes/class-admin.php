<?php
namespace BookingApp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Interface Handler
 */
class Admin
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_notices', [$this, 'display_notices']);
    }

    /**
     * Display admin notices.
     */
    public function display_notices()
    {
    }

    /**
     * Register Admin Menus.
     */
    public function register_menu()
    {
        // Main menu (Overview) - this will be the parent for the CPT as well
        add_menu_page(
            __('Bookings', 'booking-app'),
            __('Bookings', 'booking-app'),
            'manage_options',
            'booking-app',
        [$this, 'render_overview_page'],
            'dashicons-calendar-alt',
            58
        );

        // Subpages
        add_submenu_page(
            'booking-app',
            __('Overview', 'booking-app'),
            __('Overview', 'booking-app'),
            'manage_options',
            'booking-app',
        [$this, 'render_overview_page']
        );

        add_submenu_page(
            'booking-app',
            __('Create Booking', 'booking-app'),
            __('Create Booking', 'booking-app'),
            'manage_options',
            'booking-app-create',
        [$this, 'render_create_booking_page']
        );

        add_submenu_page(
            'booking-app',
            __('Settings', 'booking-app'),
            __('Settings', 'booking-app'),
            'manage_options',
            'booking-app-settings',
        [$this, 'render_settings_page']
        );
    }

    public function render_overview_page()
    {
        echo '<div class="wrap"><h1>' . esc_html__('Bookings Overview', 'booking-app') . '</h1></div>';
    }

    public function render_create_booking_page()
    {
        echo '<div class="wrap"><h1>' . esc_html__('Create Booking', 'booking-app') . '</h1></div>';
    }

    public function render_settings_page()
    {
        $settings = Settings::instance()->get_options();
        $template = BOOKING_APP_PATH . 'templates/settings.php';

        if (file_exists($template)) {
            require $template;
        }
        else {
            echo '<div class="wrap"><h1>' . esc_html__('Booking Settings', 'booking-app') . '</h1><p>' . esc_html__('Template missing.', 'booking-app') . '</p></div>';
        }
    }

    /**
     * Enqueue Admin Assets.
     */
    public function enqueue_assets($hook)
    {
        // Only load on our plugin pages
        if (strpos($hook, 'booking-app') === false && strpos($hook, 'consultation') === false) {
            return;
        }

        // Tailwind CDN + Flowbite (placeholder for build process)
        wp_register_style('booking-app-tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css', [], null);
        wp_register_script('flowbite', 'https://unpkg.com/flowbite@1.6.5/dist/flowbite.js', [], null, true);

        wp_enqueue_style('booking-app-tailwind');
        wp_enqueue_style('booking-app-settings', BOOKING_APP_URL . 'assets/css/admin-settings.css', [], BOOKING_APP_VERSION);

        wp_enqueue_script('flowbite');
        wp_enqueue_script('booking-app-settings', BOOKING_APP_URL . 'assets/js/admin-settings.js', ['jquery'], BOOKING_APP_VERSION, true);

        wp_localize_script('booking-app-settings', 'BookingAppSettings', [
            'nonce' => wp_create_nonce('booking_app_settings_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }
}

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
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function display_notices()
    {
    }

    public function register_menu()
    {
        add_menu_page(
            __('Bookings', 'booking-app'),
            __('Bookings', 'booking-app'),
            'manage_options',
            'booking-app',
        [$this, 'render_overview_page'],
            'dashicons-calendar-alt',
            58
        );

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
            __('Services Management', 'booking-app'),
            __('Services', 'booking-app'),
            'manage_options',
            'booking-app-services',
        [$this, 'render_services_page']
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
        $stats = Stats_Service::get_dashboard_stats();
        // Pagination for bookings list in admin
        $per_page = 20;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($paged - 1) * $per_page;

        $recent_bookings = Booking_Service::get_bookings(['limit' => $per_page, 'offset' => $offset]);
        $total_bookings = Booking_Service::count_bookings();
        $total_pages = $per_page ? (int) ceil($total_bookings / $per_page) : 1;

        require BOOKING_APP_PATH . 'templates/overview.php';
    }

    public function render_services_page()
    {
        $services = Service_Manager::instance()->get_services();
        require BOOKING_APP_PATH . 'templates/services.php';
    }

    public function render_settings_page()
    {
        $settings = Settings::instance()->get_options();
        require BOOKING_APP_PATH . 'templates/settings.php';
    }

    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'booking-app') === false && strpos($hook, 'consultation') === false) {
            return;
        }

        wp_enqueue_style('booking-app-tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css', [], null);
        wp_enqueue_style('booking-app-flowbite', 'https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css', [], null);
        wp_enqueue_style('booking-app-admin', BOOKING_APP_URL . 'assets/css/admin.css', [], BOOKING_APP_VERSION);

        wp_enqueue_script('flowbite', 'https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js', [], null, true);

        wp_enqueue_script('booking-app-settings', BOOKING_APP_URL . 'assets/js/admin-settings.js', ['jquery'], BOOKING_APP_VERSION, true);
        wp_localize_script('booking-app-settings', 'BookingAppSettings', [
            'nonce' => wp_create_nonce('booking_app_settings_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);

        wp_enqueue_script('booking-app-services', BOOKING_APP_URL . 'assets/js/admin-services.js', ['jquery', 'flowbite'], BOOKING_APP_VERSION, true);
        wp_localize_script('booking-app-services', 'bookingAppAdmin', [
            'restUrl' => esc_url_raw(rest_url('booking-app/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    public function register_rest_routes()
    {
        register_rest_route('booking-app/v1', '/services', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_services_rest'],
                'permission_callback' => [$this, 'check_admin_permissions'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'save_service_rest'],
                'permission_callback' => [$this, 'check_admin_permissions'],
            ],
        ]);

        register_rest_route('booking-app/v1', '/services/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_service_rest'],
            'permission_callback' => [$this, 'check_admin_permissions'],
        ]);
    }

    public function check_admin_permissions()
    {
        return current_user_can('manage_options');
    }

    public function get_services_rest()
    {
        $services = Service_Manager::instance()->get_services();
        return new \WP_REST_Response($services, 200);
    }

    public function save_service_rest($request)
    {
        $data = $request->get_params();
        $result = Service_Manager::instance()->save_service($data);

        if (isset($result['error'])) {
            return new \WP_REST_Response($result, 500);
        }

        return new \WP_REST_Response($result, 200);
    }

    public function delete_service_rest($request)
    {
        $id = $request['id'];
        Service_Manager::instance()->delete_service($id);
        return new \WP_REST_Response(['success' => true], 200);
    }
}

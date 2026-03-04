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
        add_action('wp_ajax_mbs_test_stripe_connection', [$this, 'test_stripe_connection']);
    }

    public function display_notices()
    {
    }

    public function register_menu()
    {
        add_menu_page(
            __('Bookings', 'mbs-booking'),
            __('Bookings', 'mbs-booking'),
            'manage_options',
            'booking-app',
        [$this, 'render_overview_page'],
            'dashicons-calendar-alt',
            58
        );

        add_submenu_page(
            'booking-app',
            __('Overview', 'mbs-booking'),
            __('Overview', 'mbs-booking'),
            'manage_options',
            'booking-app',
        [$this, 'render_overview_page']
        );

        add_submenu_page(
            'booking-app',
            __('Services Management', 'mbs-booking'),
            __('Services', 'mbs-booking'),
            'manage_options',
            'booking-app-services',
        [$this, 'render_services_page']
        );

        add_submenu_page(
            'booking-app',
            __('Settings', 'mbs-booking'),
            __('Settings', 'mbs-booking'),
            'manage_options',
            'booking-app-settings',
        [$this, 'render_settings_page']
        );
    }

    public function render_overview_page()
    {
        // Handle bulk/single delete actions submitted via POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mbs_bookings_nonce']) && wp_verify_nonce($_POST['mbs_bookings_nonce'], 'mbs_bookings_bulk_action')) {
            if (!current_user_can('manage_options')) {
                wp_die(__('Unauthorized', 'mbs-booking'));
            }

            $action = isset($_POST['mbs_bulk_action_type']) ? sanitize_text_field($_POST['mbs_bulk_action_type']) : '';
            $ids = isset($_POST['booking_ids']) && is_array($_POST['booking_ids']) ? array_map('intval', $_POST['booking_ids']) : [];
            $deleted = 0;

            if ($action === 'delete' && !empty($ids)) {
                foreach ($ids as $id) {
                    if ($id && Booking_Service::delete_booking($id)) {
                        $deleted++;
                    }
                }
            }

            // Redirect back to the overview preserving filters
            $redirect = admin_url('admin.php?page=booking-app');
            $args = wp_unslash($_GET);
            if (!empty($args)) {
                $redirect = add_query_arg($args, $redirect);
            }
            if ($deleted) {
                $redirect = add_query_arg('deleted', $deleted, $redirect);
            }

            wp_safe_redirect($redirect);
            exit;
        }
        $stats = Stats_Service::get_dashboard_stats();
        // Pagination for bookings list in admin
        $per_page = 20;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($paged - 1) * $per_page;

        // Read filter inputs from query string (safe defaults)
        $sort = isset($_GET['sort']) && in_array($_GET['sort'], ['newest', 'oldest'], true) ? $_GET['sort'] : 'newest';
        $selected_statuses = isset($_GET['statuses']) && is_array($_GET['statuses']) ? array_map('sanitize_text_field', $_GET['statuses']) : [];

        $order = ($sort === 'oldest') ? 'ASC' : 'DESC';

        $booking_args = [
            'limit' => $per_page,
            'offset' => $offset,
            'orderby' => 'booking_datetime_utc',
            'order' => $order,
        ];

        if (!empty($selected_statuses)) {
            $booking_args['statuses'] = $selected_statuses;
        }

        $recent_bookings = Booking_Service::get_bookings($booking_args);
        $total_bookings = Booking_Service::count_bookings(isset($booking_args['statuses']) ? ['statuses' => $booking_args['statuses']] : []);
        $total_pages = $per_page ? (int)ceil($total_bookings / $per_page) : 1;

        // expose selected filters to template
        $selected_sort = $sort;
        $selected_statuses = $selected_statuses;

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

        if (is_rtl()) {
            wp_enqueue_style('booking-app-rtl', BOOKING_APP_URL . 'assets/css/booking-rtl.css', ['booking-app-admin'], BOOKING_APP_VERSION);
        }

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

    /**
     * AJAX handler to test Stripe connection.
     */
    public function test_stripe_connection()
    {
        check_ajax_referer('booking_app_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized.', 'mbs-booking')]);
        }

        try {
            $options = \BookingApp\Settings::instance()->get_options();
            $encrypted_secret = $options['payments']['stripe']['secret'] ?? '';

            if (empty($encrypted_secret)) {
                wp_send_json_error(['message' => __('No Secret Key found. Please save your settings first.', 'mbs-booking')]);
            }

            $secret_key = \MyBooking\Payments\PaymentsSecurity::decrypt($encrypted_secret);

            if (empty($secret_key)) {
                wp_send_json_error(['message' => __('Failed to decrypt Secret Key. Please re-enter it.', 'mbs-booking')]);
            }

            $stripe_client = new \Stripe\StripeClient($secret_key);

            // Use balance retrieve as a simple "ping" test
            $stripe_client->balance->retrieve([]);

            wp_send_json_success(['message' => __('Connection successful!', 'mbs-booking')]);
        }
        catch (\Exception $e) {
            wp_send_json_error(['message' => __('Connection failed: ', 'mbs-booking') . $e->getMessage()]);
        }
    }
}

<?php
namespace BookingApp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend Interface Handler
 * 
 * Manages [booking_app] shortcode and public REST API.
 */
class Frontend
{
    public function __construct()
    {
        add_shortcode('booking_app', [$this, 'render_booking_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_public_rest_routes']);
    }

    /**
     * Render the booking app container.
     */
    public function render_booking_shortcode()
    {
        ob_start();
        require BOOKING_APP_PATH . 'templates/shortcode-booking.php';
        return ob_get_clean();
    }

    /**
     * Enqueue public assets.
     */
    public function enqueue_assets()
    {
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'booking_app')) {
            return;
        }

        wp_enqueue_style('booking-app-tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css', [], null);
        wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', [], '4.6.13');
        wp_enqueue_style('booking-app-frontend', BOOKING_APP_URL . 'assets/css/frontend.css', [], BOOKING_APP_VERSION);

        wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', [], '4.6.13', true);
        wp_enqueue_script('booking-app-frontend', BOOKING_APP_URL . 'assets/js/frontend-booking.js', ['jquery', 'flatpickr'], BOOKING_APP_VERSION, true);
        wp_localize_script('booking-app-frontend', 'bookingAppPublic', [
            'restUrl' => esc_url_raw(rest_url('booking-app/v1/public')),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    /**
     * Register public REST routes.
     */
    public function register_public_rest_routes()
    {
        // Get active services
        register_rest_route('booking-app/v1/public', '/services', [
            'methods' => 'GET',
            'callback' => [$this, 'get_active_services'],
            'permission_callback' => '__return_true',
        ]);

        // Get slots for service and date
        register_rest_route('booking-app/v1/public', '/slots', [
            'methods' => 'GET',
            'callback' => [$this, 'get_available_slots'],
            'permission_callback' => '__return_true',
        ]);

        // Get availability configuration (disabled days, etc)
        register_rest_route('booking-app/v1/public', '/availability-config', [
            'methods' => 'GET',
            'callback' => [$this, 'get_availability_config'],
            'permission_callback' => '__return_true',
        ]);

        // Post new booking
        register_rest_route('booking-app/v1/public', '/bookings', [
            'methods' => 'POST',
            'callback' => [$this, 'create_booking'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function get_active_services()
    {
        $services = Service_Manager::instance()->get_services();
        $active_services = array_filter($services, function ($s) {
            return $s->status === 'active';
        });
        return new \WP_REST_Response(array_values($active_services), 200);
    }

    public function get_available_slots($request)
    {
        $service_id = $request->get_param('service_id');
        $date = $request->get_param('date');

        if (!$service_id || !$date) {
            return new \WP_REST_Error('missing_params', 'Service ID and Date are required.', ['status' => 400]);
        }

        // We use Availability_Engine but we might need to map Service ID back to CPT or bypass post_meta
        // For now, let's just use Availability_Engine directly.
        $slots = Availability_Engine::get_available_slots($date, $service_id);

        return new \WP_REST_Response($slots, 200);
    }

    public function get_availability_config()
    {
        $settings = Settings::instance()->get_options();
        $disabled_days = [];

        // JavaScript Days of week: 0 (Sun) to 6 (Sat)
        for ($js_day = 0; $js_day <= 6; $js_day++) {
            // Map JS day to Settings index where Monday = 0, Sunday = 6
            $settings_day_index = ($js_day + 6) % 7;

            $day_config = $settings['availability'][$settings_day_index] ?? null;
            if (!$day_config || empty($day_config['enabled'])) {
                $disabled_days[] = $js_day;
            }
        }

        return new \WP_REST_Response([
            'disabledDays' => $disabled_days,
            'minDate' => current_time('Y-m-d'), // WordPress Local Today
            'timeZone' => $settings['timezone'] ?? 'UTC',
        ], 200);
    }

    public function create_booking($request)
    {
        $data = $request->get_params();
        $result = Booking_Service::create_booking($data);

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message()
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'booking_id' => $result
        ], 200);
    }
}

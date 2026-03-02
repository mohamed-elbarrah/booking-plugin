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

        // Tricking gateways like Stripe that check is_checkout() before enqueuing scripts
        add_filter('woocommerce_is_checkout', [$this, 'force_checkout_context'], 20);
    }

    /**
     * Spoof checkout context for the booking page to enable payment gateways.
     */
    public function force_checkout_context($is_checkout)
    {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'booking_app')) {
            return true;
        }
        return $is_checkout;
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

        if (class_exists('WooCommerce')) {
            wp_enqueue_script('wc-checkout');
            $gateways = \WC()->payment_gateways->get_available_payment_gateways();
            foreach ($gateways as $gateway) {
                if ($gateway->is_available()) {
                    // Only call payment_scripts() if the gateway implements it (some built-in gateways like COD do not)
                    if (method_exists($gateway, 'payment_scripts')) {
                        try {
                            $gateway->payment_scripts();
                        } catch (\Throwable $e) {
                            error_log('Booking-app: payment_scripts() for gateway ' . ($gateway->id ?? '(unknown)') . ' threw: ' . $e->getMessage());
                        }
                    } else {
                        // Optional debug hint when WP_DEBUG is enabled
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('Booking-app: gateway ' . ($gateway->id ?? '(unknown)') . ' does not implement payment_scripts(). Skipping.');
                        }
                    }
                }
            }
        }

        if (class_exists('WooCommerce')) {
            // Standard WC Scripts for payment gateways
            wp_enqueue_script('wc-checkout');
        // Some gateways like Stripe only enqueue their scripts on is_checkout() or is_add_payment_method()
        // We might need to manually trigger their script enqueues if wc-checkout isn't enough
        }

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

        // Prepare payment (create pending booking + WC order)
        register_rest_route('booking-app/v1/public', '/bookings/prepare-payment', [
            'methods' => 'POST',
            'callback' => [$this, 'prepare_payment'],
            'permission_callback' => '__return_true',
        ]);

        // Process final payment via AJAX
        register_rest_route('booking-app/v1/public', '/bookings/process-payment', [
            'methods' => 'POST',
            'callback' => [$this, 'process_payment'],
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
            'minDate' => current_time('Y-m-d'),
            'timeZone' => $settings['timezone'] ?? 'UTC',
            'businessName' => $settings['business_name'] ?? '',
            'businessLogo' => $settings['business_logo_url'] ?? '',
        ], 200);
    }

    public function create_booking($request)
    {
        $data = $request->get_params();

        // Map service_id (frontend) to consultation_id (backend)
        if (isset($data['service_id'])) {
            $data['consultation_id'] = $data['service_id'];
        }
        if (isset($data['slot'])) {
            $data['booking_datetime_utc'] = $data['slot'];
        }
        if (isset($data['customer_email'])) {
            $data['email'] = $data['customer_email'];
        }
        if (isset($data['customer_phone'])) {
            $data['phone'] = $data['customer_phone'];
        }

        $result = Booking_Service::create_booking($data);

        if (is_wp_error($result) || $result === false) {
            $message = is_wp_error($result) ? $result->get_error_message() : 'Failed to create booking. Please check logs.';
            return new \WP_REST_Response([
                'success' => false,
                'message' => $message
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'booking_id' => $result
        ], 200);
    }

    public function prepare_payment($request)
    {
        $data = $request->get_params();
        $service_id = $data['service_id'] ?? 0;
        $service = Service_Manager::instance()->get_service($service_id);

        if (!$service) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Invalid service.'], 400);
        }

        // 1. Create a pending booking first so we have an ID to track
        $data['consultation_id'] = $service_id;
        $data['status'] = 'pending_payment';
        // Map frontend fields to backend
        if (isset($data['slot'])) {
            $data['booking_datetime_utc'] = $data['slot'];
        }
        if (isset($data['customer_email'])) {
            $data['email'] = $data['customer_email'];
        }
        if (isset($data['customer_phone'])) {
            $data['phone'] = $data['customer_phone'];
        }

        $booking_id = Booking_Service::create_booking($data);

        if (!$booking_id) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Failed to initiate booking.'], 500);
        }

        // Store pending booking info in WC session so the checkout flow can access billing country/phone later
        if (class_exists('WooCommerce') && function_exists('WC')) {
            if (WC()->session) {
                WC()->session->set('mbs_pending_booking_id', $booking_id);
                WC()->session->set('mbs_pending_booking_country', $data['customer_country'] ?? '');
                WC()->session->set('mbs_pending_booking_phone', $data['customer_phone'] ?? '');
            }
        }

        // 2. Prepare the WooCommerce Cart
        WooCommerce_Handler::prepare_cart($service, $data);

        // 3. Return cart total and available gateways from the cart context
        $gateways = WooCommerce_Handler::get_available_gateways();

        // Safe WC access
        $total = 0;
        $total_formatted = '';
        if (function_exists('WC') && WC()->cart) {
            $total = WC()->cart->get_total('edit');
            $total_formatted = wc_price($total);
        }

        return new \WP_REST_Response([
            'success' => true,
            'booking_id' => $booking_id,
            'total' => $total,
            'total_formatted' => $total_formatted,
            'currency' => get_woocommerce_currency_symbol(),
            'gateways' => $gateways
        ], 200);
    }

    public function process_payment($request)
    {
        $params = $request->get_params();
        $booking_id = $params['booking_id'] ?? 0; // Use booking_id now
        $payment_method = $params['payment_method'] ?? '';
        $gateway_data = $params['gateway_data'] ?? [];

        if (!$booking_id || !$payment_method) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Missing required parameters.'], 400);
        }

        // We'll use a wrapper to catch the checkout result
        // Since process_checkout() usually triggers redirects, we need to handle its exit/return patterns.

        // Before processing, we must ensure the customer data is set for the checkout
        $result = WooCommerce_Handler::process_payment($booking_id, $payment_method, $gateway_data);

        if (is_wp_error($result)) {
            return new \WP_REST_Response(['success' => false, 'message' => $result->get_error_message()], 400);
        }

        return new \WP_REST_Response($result, 200);
    }
}

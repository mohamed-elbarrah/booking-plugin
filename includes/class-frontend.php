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

        if (is_rtl()) {
            wp_enqueue_style('booking-app-rtl', BOOKING_APP_URL . 'assets/css/booking-rtl.css', ['booking-app-frontend'], BOOKING_APP_VERSION);
        }

        wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', [], '4.6.13', true);

        // Enqueue Stripe.js
        wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', [], null, false);

        wp_enqueue_script('booking-app-frontend', BOOKING_APP_URL . 'assets/js/frontend-booking.js', ['jquery', 'flatpickr', 'stripe-js'], BOOKING_APP_VERSION, true);

        $settings = Settings::instance()->get_options();
        $stripe_publishable_key = $settings['payments']['stripe']['publishable'] ?? '';

        wp_localize_script('booking-app-frontend', 'bookingAppPublic', [
            'restUrl' => esc_url_raw(rest_url('booking-app/v1/public')),
            'webhookUrl' => esc_url_raw(rest_url('my-booking/v1/webhook')), // Publicly known for setting up Stripe
            'nonce' => wp_create_nonce('wp_rest'),
            'stripePublishableKey' => $stripe_publishable_key,
            'isRTL' => is_rtl(),
            'locale' => get_locale(),
            'i18n' => [
                'consultation' => __('Consultation', 'mbs-booking'),
                'noServices' => __('No services available.', 'mbs-booking'),
                'free' => __('Free', 'mbs-booking'),
                'min' => __('min', 'mbs-booking'),
                'selectDate' => __('Select a date', 'mbs-booking'),
                'selectDateMsg' => __('Select a date from the calendar to see available slots.', 'mbs-booking'),
                'noSlots' => __('No slots available for this day.', 'mbs-booking'),
                'processing' => __('Processing...', 'mbs-booking'),
                'payAndConfirm' => __('Pay & Confirm', 'mbs-booking'),
                'errorPrefix' => __('Error: ', 'mbs-booking'),
                'paymentErrorPrefix' => __('Payment Error: ', 'mbs-booking'),
                'stripeInitError' => __('Stripe is not initialized.', 'mbs-booking'),
                'selectServiceStep' => __('Select Service', 'mbs-booking'),
                'pickTimeStep' => __('Pick a Time', 'mbs-booking'),
                'userDetailsStep' => __('Your Details', 'mbs-booking'),
            ],
        ]);
    }

    /**
     * Register public REST routes.
     */
    public function register_public_rest_routes()
    {
        // ... (existing routes up to /bookings)

        register_rest_route('booking-app/v1/public', '/services', [
            'methods' => 'GET',
            'callback' => [$this, 'get_active_services'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('booking-app/v1/public', '/slots', [
            'methods' => 'GET',
            'callback' => [$this, 'get_available_slots'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('booking-app/v1/public', '/availability-config', [
            'methods' => 'GET',
            'callback' => [$this, 'get_availability_config'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('booking-app/v1/public', '/bookings', [
            'methods' => 'POST',
            'callback' => [$this, 'create_booking'],
            'permission_callback' => '__return_true',
        ]);

        // NEW: Create Stripe Payment Intent
        register_rest_route('booking-app/v1/public', '/bookings/create-payment-intent', [
            'methods' => 'POST',
            'callback' => [$this, 'create_payment_intent'],
            'permission_callback' => '__return_true',
        ]);

        // NEW: Stripe Webhook
        register_rest_route('my-booking/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_stripe_webhook'],
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

        // Feature Flag: If MBS_USE_NATIVE_CHECKOUT is true, tell JS to render checkout in-place
        if (defined('MBS_USE_NATIVE_CHECKOUT') && MBS_USE_NATIVE_CHECKOUT) {
            return new \WP_REST_Response([
                'success' => true,
                'booking_id' => $booking_id,
                'render_native_checkout' => true
            ], 200);
        }

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
        return new \WP_REST_Error('deprecated', 'This endpoint is deprecated. Use Stripe Elements instead.', ['status' => 410]);
    }

    /**
     * Create a Stripe PaymentIntent.
     */
    public function create_payment_intent($request)
    {
        $params = $request->get_params();
        $booking_id = $params['booking_id'] ?? 0;

        if (!$booking_id) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Missing booking ID.'], 400);
        }

        $booking = Booking_Service::get_booking($booking_id);
        if (!$booking) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $service = Service_Manager::instance()->get_service(intval($booking->consultation_id));
        if (!$service) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Service not found.'], 404);
        }

        try {
            $stripe = \MyBooking\Payments\PaymentManager::get_driver('stripe');
            $intent = $stripe->create_intent($booking_id, [
                'amount' => $service->price,
                'currency' => Settings::instance()->get_options()['currency'] ?? 'usd',
            ]);

            return new \WP_REST_Response([
                'success' => true,
                'clientSecret' => $intent['client_secret'],
                'intentId' => $intent['id']
            ], 200);
        }
        catch (\Exception $e) {
            return new \WP_REST_Response(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle Stripe Webhook.
     */
    public function handle_stripe_webhook($request)
    {
        $payload = $request->get_body();
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $stripe = \MyBooking\Payments\PaymentManager::get_driver('stripe');
            $event = $stripe->verify_webhook($payload, $sig_header);

            if (!$event) {
                return new \WP_REST_Response(['status' => 'invalid signature'], 400);
            }

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $intent = $event->data->object;
                    $booking_id = $intent->metadata->booking_id ?? 0;

                    if ($booking_id) {
                        Booking_Service::update_status($booking_id, 'confirmed');

                        global $wpdb;
                        $wpdb->update($wpdb->prefix . 'bookings',
                        ['payment_status' => 'paid', 'updated_at' => current_time('mysql', 1)],
                        ['id' => $booking_id]
                        );

                        $stripe->log("Webhook: Booking #{$booking_id} confirmed via payment intent {$intent->id}");

                        // Trigger custom hook
                        do_action('my_booking_payment_completed', $booking_id, $intent);
                    }
                    break;

                case 'payment_intent.payment_failed':
                    $intent = $event->data->object;
                    $booking_id = $intent->metadata->booking_id ?? 0;
                    if ($booking_id) {
                        $stripe->log("Webhook: Payment failed for booking #{$booking_id}: " . ($intent->last_payment_error->message ?? 'Unknown error'), 'error');
                    }
                    break;
            }

            return new \WP_REST_Response(['status' => 'success'], 200);
        }
        catch (\Exception $e) {
            return new \WP_REST_Response(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}

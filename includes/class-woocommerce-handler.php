<?php
namespace BookingApp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Handler
 * 
 * Manages WooCommerce orders for bookings.
 */
class WooCommerce_Handler
{
    /**
     * Create a WooCommerce order for a booking.
     * 
     * @param int $booking_id
     * @param array $data Booking data.
     * @param object $service Service object.
     * @return int|\WP_Error Order ID on success.
     */
    public static function create_order_for_booking($booking_id, $data, $service)
    {
        if (!class_exists('WooCommerce')) {
            return new \WP_Error('woocommerce_missing', 'WooCommerce is not active.');
        }

        try {
            $order = wc_create_order();

            // Add service as a product or fee
            // For now, we'll add it as a custom fee/item based on the service price
            $order->add_product(self::get_or_create_service_product($service), 1);

            $order->set_address([
                'first_name' => $data['customer_name'] ?? '',
                'email' => $data['customer_email'] ?? '',
                'phone' => $data['customer_phone'] ?? '',
                'country' => $data['customer_country'] ?? '',
            ], 'billing');

            $order->set_customer_id(get_current_user_id());
            $order->add_order_note(sprintf('Booking ID: %d', $booking_id));

            // Link Booking ID to Order for status sync
            update_post_meta($order->get_id(), '_mbs_booking_id', $booking_id);

            $order->calculate_totals();
            $order->save();

            return $order->get_id();
        }
        catch (\Exception $e) {
            return new \WP_Error('order_creation_failed', $e->getMessage());
        }
    }

    /**
     * Find or create a hidden WooCommerce product for a service.
     */
    private static function get_or_create_service_product($service)
    {
        $product_id = get_post_meta($service->id, '_wc_product_id', true);

        if ($product_id && get_post($product_id)) {
            $product = wc_get_product($product_id);
            $product->set_regular_price($service->price);
            $product->save();
            return $product;
        }

        // Create new product
        $product = new \WC_Product_Simple();
        $product->set_name($service->name);
        $product->set_regular_price($service->price);
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_virtual(true); // Services don't need shipping
        $product->save();

        update_post_meta($service->id, '_wc_product_id', $product->get_id());

        return $product;
    }

    /**
     * Listen for payment completion to confirm the booking.
     */
    public static function init_hooks()
    {
        add_action('woocommerce_order_status_completed', [self::class , 'handle_payment_complete']);
        add_action('woocommerce_order_status_processing', [self::class , 'handle_payment_complete']);

        // Link booking to order created via checkout
        add_action('woocommerce_checkout_update_order_meta', [self::class , 'link_booking_to_new_order'], 10, 2);
        // Inject billing values into checkout (server-side) so gateways always see required fields
        add_filter('woocommerce_checkout_get_value', [self::class, 'inject_checkout_value'], 10, 2);
    }

    /**
     * Inject non-empty billing values for checkout fields using pending booking/session data.
     * This ensures gateways receive required billing_country/phone/address values.
     *
     * @param mixed $value The current field value
     * @param string $input The input name being requested
     * @return mixed
     */
    public static function inject_checkout_value($value, $input)
    {
        // If value already present, don't override
        if (!empty($value)) {
            return $value;
        }

        // Ensure WC session/customer available
        if (!function_exists('WC')) {
            return $value;
        }

        self::ensure_wc_session();

        $session = WC()->session;
        $booking = null;
        $booking_id = $session ? $session->get('mbs_pending_booking_id') : null;
        if ($booking_id) {
            $booking = Booking_Service::get_booking($booking_id);
        }

        // Session overrides set during prepare_payment
        $sess_phone = $session ? ($session->get('mbs_pending_booking_phone') ?? '') : '';
        $sess_country = $session ? ($session->get('mbs_pending_booking_country') ?? '') : '';

        $booking_name = $booking->customer_name ?? '';
        $booking_email = $booking->customer_email ?? '';
        $booking_phone = $booking->customer_phone ?? '';
        $country_code = $sess_country ?: ($booking->customer_country ?? '');

        // Human readable country label
        $countries = method_exists('\WC_Countries', 'get_countries') ? WC()->countries->get_countries() : [];
        $country_label = '';
        if ($country_code && is_array($countries) && isset($countries[$country_code])) {
            $country_label = $countries[$country_code];
        }

        // Default fallbacks
        $defaults = [
            'first_name' => 'Customer',
            'last_name' => 'N/A',
            'email' => 'no-reply@localhost',
            'phone' => '0000000000',
            'country' => WC()->countries->get_base_country() ?: 'US',
            'address_1' => $country_label ?: ($country_code ?: 'Unknown'),
            'city' => $country_label ?: ($country_code ?: 'Unknown'),
            'postcode' => '00000',
        ];

        switch ($input) {
            case 'billing_first_name':
                $name = trim($booking_name ?: $booking->customer_name ?? '');
                if ($name) {
                    $parts = preg_split('/\s+/', $name);
                    if (count($parts) > 1) {
                        $first = array_shift($parts);
                        return $first ?: $defaults['first_name'];
                    }
                    return $name ?: $defaults['first_name'];
                }
                return $defaults['first_name'];

            case 'billing_last_name':
                $name = trim($booking_name ?: $booking->customer_name ?? '');
                if ($name) {
                    $parts = preg_split('/\s+/', $name);
                    if (count($parts) > 1) {
                        $last = array_pop($parts);
                        return $last ?: $defaults['last_name'];
                    }
                    return $defaults['last_name'];
                }
                return $defaults['last_name'];

            case 'billing_email':
                return $booking_email ?: $defaults['email'];

            case 'billing_phone':
                return $sess_phone ?: ($booking_phone ?: $defaults['phone']);

            case 'billing_country':
                return $country_code ?: $defaults['country'];

            case 'billing_address_1':
                return $country_label ?: $defaults['address_1'];

            case 'billing_city':
                return $country_label ?: $defaults['city'];

            case 'billing_postcode':
                return $defaults['postcode'];

            default:
                return $value;
        }
    }

    public static function link_booking_to_new_order($order_id, $data)
    {
        $booking_id = WC()->session->get('mbs_pending_booking_id');
        if ($booking_id) {
            update_post_meta($order_id, '_mbs_booking_id', $booking_id);
            // Clear from session
            WC()->session->set('mbs_pending_booking_id', null);
        }
    }

    public static function handle_payment_complete($order_id)
    {
        $booking_id = get_post_meta($order_id, '_mbs_booking_id', true);
        if ($booking_id) {
            Booking_Service::update_status($booking_id, 'confirmed');
            // Update payment status too
            global $wpdb;
            $wpdb->update($wpdb->prefix . 'bookings', ['payment_status' => 'paid'], ['id' => $booking_id]);
        }
    }

    /**
     * Ensure WooCommerce session and cart are initialized.
     * Required for REST API or AJAX contexts where WC might not load them by default.
     */
    private static function ensure_wc_session()
    {
        if (!class_exists('WooCommerce')) {
            return;
        }

        if (is_null(WC()->session)) {
            $session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
            WC()->session = new $session_class();
            WC()->session->init();
        }

        if (is_null(WC()->customer)) {
            WC()->customer = new \WC_Customer(get_current_user_id(), true);
        }

        if (is_null(WC()->cart)) {
            WC()->cart = new \WC_Cart();
        }

        if (is_null(WC()->checkout)) {
            WC()->checkout = new \WC_Checkout();
        }

        if (WC()->session && !WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }
    }

    public static function get_available_gateways($order_id = 0)
    {
        if (!class_exists('WooCommerce')) {
            return [];
        }

        self::ensure_wc_session();

        $available_gateways = \WC()->payment_gateways->get_available_payment_gateways();
        $output = [];

        foreach ($available_gateways as $gateway) {
            // Capture payment fields HTML
            ob_start();
            $gateway->payment_fields();
            $fields_html = ob_get_clean();

            $output[] = [
                'id' => $gateway->id,
                'title' => $gateway->get_title(),
                'description' => $gateway->get_description(),
                'icon' => $gateway->get_icon(),
                'has_fields' => $gateway->has_fields(),
                'fields_html' => $fields_html,
            ];
        }

        return $output;
    }

    /**
     * Prepare the WooCommerce cart for a booking.
     * This provides the necessary context for gateways like Stripe.
     */
    public static function prepare_cart($service, $customer_data)
    {
        if (!class_exists('WooCommerce')) {
            return;
        }

        self::ensure_wc_session();

        // 1. Clear previous items to avoid double booking/payment
        WC()->cart->empty_cart();

        // 2. Add the service product
        $product = self::get_or_create_service_product($service);
        WC()->cart->add_to_cart($product->get_id(), 1);

        // 3. Set billing details in customer session
        WC()->customer->set_billing_first_name($customer_data['customer_name'] ?? '');
        WC()->customer->set_billing_email($customer_data['customer_email'] ?? '');
        if (isset($customer_data['customer_phone'])) {
            WC()->customer->set_billing_phone($customer_data['customer_phone']);
        }
        if (isset($customer_data['customer_country'])) {
            WC()->customer->set_billing_country($customer_data['customer_country']);
        }

        // Save customer data to session
        WC()->customer->save();

        // 4. Force calculation
        WC()->cart->calculate_totals();
    }

    /**
     * Process payment using the standard WooCommerce checkout engine.
     */
    public static function process_payment($booking_id, $payment_method, $gateway_data = [])
    {
        self::ensure_wc_session();

        $booking = Booking_Service::get_booking($booking_id);
        if (!$booking) {
            return new \WP_Error('invalid_booking', 'Booking not found.');
        }

        // Populate $_POST with gateway data so some gateways can read expected fields
        if (!empty($gateway_data) && is_array($gateway_data)) {
            foreach ($gateway_data as $key => $value) {
                $_POST[$key] = $value;
            }
        }

        // Create a WC order for this booking (so gateways receive an order ID)
        $service = \BookingApp\Service_Manager::instance()->get_service(intval($booking->consultation_id ?? 0));
        // Allow session overrides (set during prepare_payment) for country/phone
        $session_country = '';
        $session_phone = '';
        if (function_exists('WC') && WC()->session) {
            $session_country = WC()->session->get('mbs_pending_booking_country') ?? '';
            $session_phone = WC()->session->get('mbs_pending_booking_phone') ?? '';
        }

        $order_or_error = self::create_order_for_booking($booking_id, [
            'customer_name' => $booking->customer_name,
            'customer_email' => $booking->customer_email,
            'customer_phone' => $session_phone ?: $booking->customer_phone,
            'customer_country' => $session_country ?: ($booking->customer_country ?? ''),
        ], $service);

        if (is_wp_error($order_or_error)) {
            return $order_or_error;
        }

        $order_id = intval($order_or_error);

        // Ensure payment method exists
        $gateways = \WC()->payment_gateways->get_available_payment_gateways();
        if (empty($gateways[$payment_method])) {
            return new \WP_Error('invalid_gateway', 'Requested payment gateway not available.');
        }

        $gateway = $gateways[$payment_method];

        // Some gateways expect specific POST fields and will return an array with 'result' and optional 'redirect'
        try {
            // Let gateway validate fields first if available so we can return helpful errors
            if (method_exists($gateway, 'validate_fields')) {
                // Clear previous notices
                if (function_exists('wc_clear_notices')) wc_clear_notices();

                $valid = $gateway->validate_fields();
                if ($valid === false) {
                    $errors = [];
                    if (function_exists('wc_get_notices')) {
                        $notices = wc_get_notices('error');
                        foreach ($notices as $n) {
                            $errors[] = is_array($n) && isset($n['notice']) ? $n['notice'] : (string)$n;
                        }
                        wc_clear_notices();
                    }

                    $msg = !empty($errors) ? implode('; ', $errors) : 'Gateway validation failed. Missing required fields.';
                    return ['result' => 'failure', 'messages' => $msg];
                }
            }

            // Clear notices before processing
            if (function_exists('wc_clear_notices')) wc_clear_notices();
            $result = $gateway->process_payment($order_id);

            // If gateway returned a redirect (off-site flows), pass it back
            if (is_array($result) && (isset($result['result']) || isset($result['redirect']))) {
                // If failure with no message, try gather notices
                if (!empty($result['result']) && $result['result'] === 'failure' && empty($result['messages'])) {
                    $errors = [];
                    if (function_exists('wc_get_notices')) {
                        $notices = wc_get_notices('error');
                        foreach ($notices as $n) {
                            $errors[] = is_array($n) && isset($n['notice']) ? $n['notice'] : (string)$n;
                        }
                        wc_clear_notices();
                    }
                    if (!empty($errors)) {
                        $result['messages'] = implode('; ', $errors);
                    }
                }

                return $result;
            }

            // If gateway returned WP_Error
            if (is_wp_error($result)) {
                return ['result' => 'failure', 'messages' => $result->get_error_message()];
            }

            // Fallback: return generic success
            return ['result' => 'success', 'order_id' => $order_id];
        } catch (\Exception $e) {
            return new \WP_Error('gateway_exception', $e->getMessage());
        }
    }
}

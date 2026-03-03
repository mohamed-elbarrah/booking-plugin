<?php
namespace BookingApp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Management Class
 * 
 * Handles storage, retrieval, and sanitization of plugin options.
 */
final class Settings
{
    /** @var Settings|null */
    private static $instance = null;

    const OPTION_KEY = 'booking_app_settings';

    private function __construct()
    {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register settings in WordPress.
     */
    public function register_settings()
    {
        register_setting('booking_app', self::OPTION_KEY, [$this, 'sanitize']);
    }

    /**
     * Get all plugin options with defaults.
     */
    public function get_options()
    {
        $defaults = [
            'business_name' => '',
            'business_logo_url' => '',
            'admin_email' => get_option('admin_email'),
            'currency' => 'USD',
            'timezone' => 'UTC',
            'payments' => [
                'stripe' => [
                    'publishable' => '',
                    'secret' => '',
                    'webhook_secret' => '',
                    'sandbox' => '1',
                ],
                'paypal' => [
                    'client_id' => '',
                    'secret' => '',
                    'sandbox' => '1',
                ],
            ],
            'availability' => [],
            'google' => [
                'client_id' => '',
                'client_secret' => '',
                'two_way' => '0',
                'auto_meeting' => '0',
            ],
        ];

        $options = get_option(self::OPTION_KEY, []);
        return array_replace_recursive($defaults, (array)$options);
    }

    /**
     * Sanitize all settings fields.
     */
    public function sanitize($input)
    {
        $output = [];

        // General Settings
        $output['business_name'] = isset($input['business_name']) ? sanitize_text_field($input['business_name']) : '';
        $output['business_logo_url'] = isset($input['business_logo_url']) ? esc_url_raw($input['business_logo_url']) : '';
        $output['admin_email'] = isset($input['admin_email']) ? sanitize_email($input['admin_email']) : '';
        $output['currency'] = isset($input['currency']) ? sanitize_text_field($input['currency']) : 'USD';
        $output['timezone'] = isset($input['timezone']) ? sanitize_text_field($input['timezone']) : 'UTC';

        // Availability Handling
        if (isset($input['availability']) && is_array($input['availability'])) {
            $output['availability'] = [];
            foreach ($input['availability'] as $day_index => $day_data) {
                $day_clean = [
                    'enabled' => isset($day_data['enabled']) ? '1' : '0',
                    'start' => sanitize_text_field($day_data['start'] ?? '09:00'),
                    'end' => sanitize_text_field($day_data['end'] ?? '17:00'),
                    'breaks' => [],
                ];

                if (isset($day_data['breaks']) && is_array($day_data['breaks'])) {
                    foreach ($day_data['breaks'] as $break) {
                        if (!empty($break['start']) && !empty($break['end'])) {
                            $day_clean['breaks'][] = [
                                'start' => sanitize_text_field($break['start']),
                                'end' => sanitize_text_field($break['end']),
                            ];
                        }
                    }
                }
                $output['availability'][$day_index] = $day_clean;
            }
        }

        // Payments Integration
        if (isset($input['payments']) && is_array($input['payments'])) {
            $existing = $this->get_options();

            $output['payments'] = [
                'stripe' => [
                    'publishable' => sanitize_text_field($input['payments']['stripe']['publishable'] ?? ''),
                    'secret' => !empty($input['payments']['stripe']['secret'])
                    ?\MyBooking\Payments\PaymentsSecurity::encrypt(sanitize_text_field($input['payments']['stripe']['secret']))
                    : ($existing['payments']['stripe']['secret'] ?? ''),
                    'webhook_secret' => !empty($input['payments']['stripe']['webhook_secret'])
                    ?\MyBooking\Payments\PaymentsSecurity::encrypt(sanitize_text_field($input['payments']['stripe']['webhook_secret']))
                    : ($existing['payments']['stripe']['webhook_secret'] ?? ''),
                    'sandbox' => isset($input['payments']['stripe']['sandbox']) ? '1' : '0',
                ],
                'paypal' => [
                    'client_id' => sanitize_text_field($input['payments']['paypal']['client_id'] ?? ''),
                    'secret' => !empty($input['payments']['paypal']['secret'])
                    ?\MyBooking\Payments\PaymentsSecurity::encrypt(sanitize_text_field($input['payments']['paypal']['secret']))
                    : ($existing['payments']['paypal']['secret'] ?? ''),
                    'sandbox' => isset($input['payments']['paypal']['sandbox']) ? '1' : '0',
                ],
            ];
        }
        else {
            // If payments key is missing from input (e.g. partial save), preserve existing
            $existing = $this->get_options();
            $output['payments'] = $existing['payments'] ?? [];
        }

        return $output;
    }
}

// Initialize settings singleton
Settings::instance();

<?php
/**
 * Plugin Name: Booking App
 * Description: A bookings plugin
 * Version:     0.1.0
 * Author:      Mohamed ElBarrah
 * Text Domain: mbs-booking
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Feature flag for WooCommerce-native checkout (refactor/checkout-integration)
if (!defined('MBS_USE_NATIVE_CHECKOUT')) {
    define('MBS_USE_NATIVE_CHECKOUT', true);
}

// Composer autoload if present
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Basic requires (fallback if not using composer)
if (!class_exists('BookingApp\\Plugin')) {
    require_once __DIR__ . '/includes/class-plugin.php';
}

// Bootstrap
add_action('plugins_loaded', function () {
    load_plugin_textdomain(
        'mbs-booking',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
    
    // Log active locale for verification
    error_log('MBS-Booking Debug: Active locale is: ' . get_locale());
    
    \BookingApp\Plugin::instance();
});

// Activation / Deactivation hooks
register_activation_hook(__FILE__, [\BookingApp\Plugin::class , 'activate']);
register_deactivation_hook(__FILE__, [\BookingApp\Plugin::class , 'deactivate']);

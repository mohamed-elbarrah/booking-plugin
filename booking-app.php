<?php
/**
 * Plugin Name: Booking App
 * Plugin URI:  https://example.com/booking-app
 * Description: A bookings plugin
 * Version:     0.1.0
 * Author:      Mohamed ElBarrah
 * Text Domain: booking-app
 * Domain Path: /booking-app
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
    \BookingApp\Plugin::instance();
});

// Activation / Deactivation hooks
register_activation_hook(__FILE__, [\BookingApp\Plugin::class , 'activate']);
register_deactivation_hook(__FILE__, [\BookingApp\Plugin::class , 'deactivate']);

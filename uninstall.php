<?php
/**
 * Uninstall handler for Booking App
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$table_name = $wpdb->prefix . 'bookings';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

delete_option( 'booking_app_db_version' );

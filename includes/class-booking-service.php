<?php
namespace BookingApp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Booking Service
 * 
 * Handles all CRUD operations for bookings.
 */
class Booking_Service
{

    /**
     * Create a new booking.
     * 
     * @param array $data Booking data.
     * @return int|false Booking ID on success, false on failure.
     */
    public static function create_booking($data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookings';

        // Validation
        if (empty($data['email']) || empty($data['booking_datetime_utc'])) {
            return false;
        }

        $inserted = $wpdb->insert($table_name, [
            'consultation_id' => intval($data['consultation_id'] ?? 0),
            'customer_name' => sanitize_text_field($data['customer_name'] ?? ''),
            'customer_email' => sanitize_email($data['email'] ?? $data['customer_email'] ?? ''),
            'customer_phone' => sanitize_text_field($data['phone'] ?? $data['customer_phone'] ?? ''),
            'booking_datetime_utc' => $data['booking_datetime_utc'],
            'duration' => intval($data['duration'] ?? 30),
            'status' => sanitize_text_field($data['status'] ?? 'pending'),
            'payment_status' => sanitize_text_field($data['payment_status'] ?? 'unpaid'),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1),
        ]);

        if (!$inserted) {
            Logger::error('Failed to create booking', ['data' => $data, 'error' => $wpdb->last_error]);
            return false;
        }

        $booking_id = $wpdb->insert_id;
        do_action('booking_app_after_booking_created', $booking_id, $data);

        return $booking_id;
    }

    /**
     * Get bookings with filters.
     */
    public static function get_bookings($args = [])
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookings';

        $defaults = [
            'status' => '',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'booking_datetime_utc',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM $table_name WHERE 1=1";
        $params = [];

        if (!empty($args['status'])) {
            $query .= " AND status = %s";
            $params[] = $args['status'];
        }

        $query .= " ORDER BY " . esc_sql($args['orderby']) . " " . esc_sql($args['order']);
        $query .= " LIMIT %d OFFSET %d";
        $params[] = intval($args['limit']);
        $params[] = intval($args['offset']);

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    /**
     * Update booking status.
     */
    public static function update_status($id, $status)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookings';

        $updated = $wpdb->update(
            $table_name,
        ['status' => $status, 'updated_at' => current_time('mysql', 1)],
        ['id' => intval($id)]
        );

        if ($updated) {
            do_action('booking_app_booking_status_updated', $id, $status);
            return true;
        }

        return false;
    }
}

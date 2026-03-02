<?php
namespace BookingApp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stats Service
 * 
 * Provides intelligence and reporting data.
 */
class Stats_Service
{

    /**
     * Get simple KPI counts.
     */
    public static function get_dashboard_stats()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookings';

        $stats = [
            'total' => 0,
            'confirmed' => 0,
            'pending' => 0,
            'revenue' => 0.00,
        ];

        $results = $wpdb->get_results("SELECT status, COUNT(*) as count FROM $table_name GROUP BY status");

        foreach ($results as $row) {
            $stats['total'] += $row->count;
            if ($row->status === 'confirmed')
                $stats['confirmed'] = $row->count;
            if ($row->status === 'pending')
                $stats['pending'] = $row->count;
        }

        // Revenue calculation from paid bookings
        $service_table = $wpdb->prefix . 'booking_services';
        $stats['revenue'] = $wpdb->get_var("
            SELECT SUM(s.price) 
            FROM $table_name b 
            JOIN $service_table s ON b.consultation_id = s.id 
            WHERE b.payment_status = 'paid'
        ") ?: 0;

        return $stats;
    }

    /**
     * Get upcoming bookings for Today.
     */
    public static function get_today_bookings_count()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookings';
        $today_start = current_time('Y-m-d 00:00:00', 1);
        $today_end = current_time('Y-m-d 23:59:59', 1);

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE booking_datetime_utc >= %s AND booking_datetime_utc <= %s",
            $today_start, $today_end
        ));
    }
}

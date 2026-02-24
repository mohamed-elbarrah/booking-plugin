<?php
namespace BookingApp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Availability Engine
 * 
 * Calculates available booking slots based on internal business hours,
 * breaks, and existing bookings in the database.
 */
class Availability_Engine
{

    /**
     * Get available slots for a specific date and consultation type.
     * 
     * @param string $date Date in Y-m-d format.
     * @param int $consultation_id ID of the consultation CPT.
     * @return array Array of available slot start times (UTC, RFC3339).
     */
    public static function get_available_slots($date, $consultation_id)
    {
        $settings = Settings::instance()->get_options();
        $day_index = date('w', strtotime($date)); // 0 (Sunday) to 6 (Saturday)

        $availability = $settings['availability'][$day_index] ?? null;
        if (!$availability || empty($availability['enabled'])) {
            return [];
        }

        $business_start = $availability['start'];
        $business_end = $availability['end'];
        $breaks = $availability['breaks'] ?? [];

        // Fetch consultation duration
        $duration = 30; // Default to 30 mins
        $consultation_duration = get_post_meta($consultation_id, '_duration', true);
        if ($consultation_duration) {
            $duration = intval($consultation_duration);
        }

        // Fetch existing bookings for this date
        $existing_bookings = self::get_bookings_for_date($date);

        $slots = [];
        $current_time = strtotime("$date $business_start");
        $end_time = strtotime("$date $business_end");

        while ($current_time + ($duration * 60) <= $end_time) {
            $slot_start = $current_time;
            $slot_end = $current_time + ($duration * 60);

            if (self::is_slot_available($slot_start, $slot_end, $breaks, $existing_bookings, $date)) {
                // Convert to UTC RFC3339
                $slots[] = Timezone_Handler::to_rfc3339(date('Y-m-d H:i:s', $slot_start), 'UTC');
            }

            $current_time += ($duration * 60); // Simple increment by duration
        }

        return $slots;
    }

    /**
     * Check if a slot is available (not in break and no conflict with other bookings).
     */
    private static function is_slot_available($start, $end, $breaks, $bookings, $date)
    {
        // Check breaks
        foreach ($breaks as $break) {
            $break_start = strtotime("$date " . $break['start']);
            $break_end = strtotime("$date " . $break['end']);

            // Overlap check
            if ($start < $break_end && $end > $break_start) {
                return false;
            }
        }

        // Check internal bookings
        foreach ($bookings as $booking) {
            $booking_start = strtotime($booking->booking_datetime_utc);
            $booking_end = $booking_start + ($booking->duration * 60);

            if ($start < $booking_end && $end > $booking_start) {
                return false;
            }
        }

        return true;
    }

    /**
     * Fetch all confirmed bookings for a specific date from the database.
     */
    private static function get_bookings_for_date($date)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookings';

        $start_date = "$date 00:00:00";
        $end_date = "$date 23:59:59";

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT booking_datetime_utc, duration FROM $table_name 
             WHERE booking_datetime_utc >= %s AND booking_datetime_utc <= %s 
             AND status NOT IN ('cancelled', 'rejected')",
            $start_date, $end_date
        ));

        return $results ? $results : [];
    }
}

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
     * @param int $service_id ID of the service.
     * @return array Array of available slot start times (UTC, RFC3339).
     */
    public static function get_available_slots($date, $service_id)
    {
        $settings = Settings::instance()->get_options();
        $php_day_index = date('w', strtotime($date)); // 0 (Sunday) to 6 (Saturday)

        // Map PHP day to Settings index where Monday = 0, Sunday = 6
        $settings_day_index = ($php_day_index + 6) % 7;

        $availability = $settings['availability'][$settings_day_index] ?? null;
        if (!$availability || empty($availability['enabled'])) {
            return [];
        }

        $business_start = $availability['start'];
        $business_end = $availability['end'];
        $breaks = $availability['breaks'] ?? [];

        // Fetch service details
        $service = Service_Manager::instance()->get_service($service_id);
        if (!$service) {
            return [];
        }
        $duration = intval($service->duration);

        // Fetch existing bookings for this date
        $existing_bookings = self::get_bookings_for_date($date);

        $tz = wp_timezone();

        try {
            $start_dt = new \DateTime("$date $business_start", $tz);
            $end_dt = new \DateTime("$date $business_end", $tz);
        }
        catch (\Exception $e) {
            return [];
        }

        $slots = [];
        // Standardize slot generation interval to 30 minutes for better granularity (or duration if < 30)
        $step_minutes = min(30, $duration);
        $interval = new \DateInterval("PT{$step_minutes}M");
        $current_dt = clone $start_dt;

        while ($current_dt < $end_dt) {
            $slot_start_ts = $current_dt->getTimestamp();
            $slot_end_ts = $slot_start_ts + ($duration * 60);

            // Don't cross the end of business day
            if ($slot_end_ts > $end_dt->getTimestamp()) {
                break;
            }

            $is_available = self::is_slot_available($slot_start_ts, $slot_end_ts, $breaks, $existing_bookings, $date);

            $slots[] = [
                'time' => Timezone_Handler::to_rfc3339_from_local($current_dt->format('Y-m-d H:i:s')),
                'display_time' => $current_dt->format('g:i A'),
                'duration' => $duration,
                'available' => $is_available
            ];

            $current_dt->add($interval);
        }

        return $slots;
    }

    /**
     * Check if a slot is available (not in break and no conflict with other bookings).
     */
    private static function is_slot_available($start, $end, $breaks, $bookings, $date)
    {
        $tz = wp_timezone();

        // Check breaks
        foreach ($breaks as $break) {
            try {
                $break_start_dt = new \DateTime("$date " . $break['start'], $tz);
                $break_end_dt = new \DateTime("$date " . $break['end'], $tz);

                $break_start = $break_start_dt->getTimestamp();
                $break_end = $break_end_dt->getTimestamp();

                // Check if the slot START time falls within the break
                // The user specifically requested that appointments spanning into breaks
                // should be allowed, as long as they don't *start* during the break.
                if ($start >= $break_start && $start < $break_end) {
                    return false;
                }
            }
            catch (\Exception $e) {
                continue;
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
        $tz = wp_timezone();

        try {
            // Convert start and end of local day to UTC for DB query
            $start_local = new \DateTime("$date 00:00:00", $tz);
            $end_local = new \DateTime("$date 23:59:59", $tz);

            $start_local->setTimezone(new \DateTimeZone('UTC'));
            $end_local->setTimezone(new \DateTimeZone('UTC'));

            $start_utc = $start_local->format('Y-m-d H:i:s');
            $end_utc = $end_local->format('Y-m-d H:i:s');
        }
        catch (\Exception $e) {
            $start_utc = "$date 00:00:00";
            $end_utc = "$date 23:59:59";
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT booking_datetime_utc, duration FROM $table_name 
             WHERE booking_datetime_utc >= %s AND booking_datetime_utc <= %s 
             AND status NOT IN ('cancelled', 'rejected')",
            $start_utc, $end_utc
        ));

        return $results ? $results : [];
    }
}

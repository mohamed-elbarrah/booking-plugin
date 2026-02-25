<?php
namespace BookingApp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Timezone Handler Service
 * 
 * Ensures all dates are stored in UTC and converted correctly for display.
 * Following AI_RULES.md mandatory timezone rules.
 */
class Timezone_Handler
{

    /**
     * Convert a local datetime string to UTC RFC3339 format.
     * 
     * @param string $datetime_str
     * @param string $timezone_str
     * @return string
     */
    public static function to_utc($datetime_str, $timezone_str = null)
    {
        if (!$timezone_str) {
            $timezone_str = wp_timezone_string();
        }

        try {
            $date = new \DateTime($datetime_str, new \DateTimeZone($timezone_str));
            $date->setTimezone(new \DateTimeZone('UTC'));
            return $date->format('Y-m-d H:i:s');
        }
        catch (\Exception $e) {
            return $datetime_str;
        }
    }

    /**
     * Convert a UTC datetime string to a specific timezone.
     * 
     * @param string $utc_datetime
     * @param string $timezone_str
     * @return string
     */
    public static function from_utc($utc_datetime, $timezone_str = null)
    {
        if (!$timezone_str) {
            $timezone_str = wp_timezone_string();
        }

        try {
            $date = new \DateTime($utc_datetime, new \DateTimeZone('UTC'));
            $date->setTimezone(new \DateTimeZone($timezone_str));
            return $date->format('Y-m-d H:i:s');
        }
        catch (\Exception $e) {
            return $utc_datetime;
        }
    }

    /**
     * Format a date for Google Calendar API (RFC3339).
     * 
     * @param string $utc_datetime
     * @return string
     */
    public static function to_rfc3339($utc_datetime)
    {
        try {
            $date = new \DateTime($utc_datetime, new \DateTimeZone('UTC'));
            return $date->format(\DateTime::RFC3339);
        }
        catch (\Exception $e) {
            return $utc_datetime;
        }
    }

    /**
     * Convert a local datetime string to UTC RFC3339 format.
     */
    public static function to_rfc3339_from_local($local_datetime, $timezone_str = null)
    {
        if (!$timezone_str) {
            $timezone_str = wp_timezone_string();
        }

        try {
            $date = new \DateTime($local_datetime, new \DateTimeZone($timezone_str));
            $date->setTimezone(new \DateTimeZone('UTC'));
            return $date->format(\DateTime::RFC3339);
        }
        catch (\Exception $e) {
            return $local_datetime;
        }
    }
}

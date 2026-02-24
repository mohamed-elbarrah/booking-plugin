<?php
namespace BookingApp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bookings Table Handler
 * 
 * Manages the custom database table for bookings.
 * Follows AI_RULES.md database standards.
 */
class Bookings_Table
{
    const OPTION_DB_VERSION = 'booking_app_db_version';
    const DB_VERSION = '1.1.0';

    /**
     * Create or update the custom table.
     */
    public static function create_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bookings';
        $charset_collate = $wpdb->get_charset_collate();

        // Schema as per AI_RULES.md roadmap
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            consultation_id bigint(20) unsigned NOT NULL,
            user_name varchar(255) NOT NULL,
            user_email varchar(191) NOT NULL,
            booking_datetime_utc datetime NOT NULL,
            duration int(11) unsigned NOT NULL,
            price_total decimal(10,2) NOT NULL DEFAULT '0.00',
            payment_status varchar(50) NOT NULL DEFAULT 'unpaid',
            meeting_link text DEFAULT NULL,
            google_event_id varchar(255) DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY consultation_id (consultation_id),
            KEY user_email (user_email),
            KEY booking_datetime_utc (booking_datetime_utc),
            KEY payment_status (payment_status),
            KEY google_event_id (google_event_id),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option(self::OPTION_DB_VERSION, self::DB_VERSION);

        // Log the schema update
        if (class_exists('BookingApp\\Logger')) {
            Logger::info('Database table created or updated.', ['version' => self::DB_VERSION]);
        }
    }

    /**
     * Drop the table (clean uninstall).
     */
    public static function drop_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookings';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        delete_option(self::OPTION_DB_VERSION);
    }
}

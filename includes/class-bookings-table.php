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
    const DB_VERSION = '1.3.0';

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
            customer_name varchar(255) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_phone varchar(50) DEFAULT '',
            booking_datetime_utc datetime NOT NULL,
            duration int(11) NOT NULL DEFAULT 30,
            status varchar(50) NOT NULL DEFAULT 'pending',
            payment_status varchar(50) DEFAULT 'unpaid',
            google_event_id varchar(255) DEFAULT '',
            notes text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY consultation_id (consultation_id),
            KEY customer_email (customer_email),
            KEY customer_phone (customer_phone),
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

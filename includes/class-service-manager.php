<?php
namespace BookingApp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Service Manager
 * 
 * Handles all logic for managing booking services.
 */
class Service_Manager
{
    /** @var Service_Manager|null */
    private static $instance = null;

    private function __construct()
    {
    }

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Add or Update a service.
     */
    public function save_service($data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mbs_services';

        $id = intval($data['id'] ?? 0);
        $name = sanitize_text_field($data['name'] ?? '');
        $desc = sanitize_textarea_field($data['description'] ?? '');
        $dur = max(1, intval($data['duration'] ?? 30));
        $price = floatval($data['price'] ?? 0.00);
        $status = sanitize_text_field($data['status'] ?? 'active');

        $format_data = [
            'name' => $name,
            'description' => $desc,
            'duration' => $dur,
            'price' => number_format($price, 2, '.', ''),
            'status' => $status,
        ];

        if ($id > 0) {
            $updated = $wpdb->update($table_name, $format_data, ['id' => $id]);
            if ($updated === false) {
                return ['error' => true, 'message' => $wpdb->last_error ?: 'Database update failed'];
            }
            return ['id' => $id];
        }
        else {
            $inserted = $wpdb->insert($table_name, $format_data);
            if (!$inserted) {
                return ['error' => true, 'message' => $wpdb->last_error ?: 'Database insert failed. Check if table wp_mbs_services exists.'];
            }
            return ['id' => $wpdb->insert_id];
        }
    }

    /**
     * Delete a service.
     */
    public function delete_service($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mbs_services';
        return $wpdb->delete($table_name, ['id' => intval($id)]);
    }

    /**
     * Get all services.
     */
    public function get_services($status = '')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mbs_services';

        // Ensure table exists check or just try
        $query = "SELECT * FROM $table_name";
        if ($status) {
            $query .= $wpdb->prepare(" WHERE status = %s", $status);
        }
        $query .= " ORDER BY id DESC";

        $results = $wpdb->get_results($query);
        return $results ? $results : [];
    }

    /**
     * Get a single service by ID.
     */
    public function get_service($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mbs_services';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }
}

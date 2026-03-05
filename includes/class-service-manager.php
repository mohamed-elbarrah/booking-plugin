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
            // ensure position column exists (for older installs)
            $this->ensure_position_column();

            // determine next position if column exists
            $has_position = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'position'");
            if ($has_position) {
                $max_pos = (int) $wpdb->get_var("SELECT COALESCE(MAX(position), 0) FROM $table_name");
                $format_data['position'] = $max_pos + 1;
            }

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
        $id = intval($id);
        if (!$id) {
            return false;
        }

        // Ensure position column exists before querying (older installs may not have it)
        $this->ensure_position_column();

        // Get current position (if column available)
        $has_position = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'position'");
        if ($has_position) {
            $pos = (int) $wpdb->get_var($wpdb->prepare("SELECT position FROM $table_name WHERE id = %d", $id));
        } else {
            $pos = 0;
        }

        $deleted = $wpdb->delete($table_name, ['id' => $id]);

        if ($deleted && $pos > 0) {
            // shift positions down for items after deleted one
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET position = position - 1 WHERE position > %d", $pos));
        }

        return $deleted;
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
        // Prefer explicit position ordering if column exists; fall back to id
        $has_position = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'position'");
        if ($has_position) {
            $query .= " ORDER BY position ASC, id DESC";
        } else {
            $query .= " ORDER BY id DESC";
        }

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

    /**
     * Update ordering of services. Accepts array of IDs in desired order.
     */
    public function update_order($ordered_ids)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mbs_services';

        if (!is_array($ordered_ids)) {
            return false;
        }

        if (!$this->ensure_position_column()) {
            return false;
        }

        $i = 1;
        foreach ($ordered_ids as $id) {
            $id = intval($id);
            if (!$id) continue;
            $wpdb->update($table_name, ['position' => $i], ['id' => $id]);
            $i++;
        }

        return true;
    }

    /**
     * Ensure the `position` column exists in services table.
     * Attempts to add the column if missing.
     */
    private function ensure_position_column()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mbs_services';

        $has = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'position'");
        if ($has) {
            return true;
        }

        $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN position int(11) NOT NULL DEFAULT 0");
        return ($result !== false);
    }
}

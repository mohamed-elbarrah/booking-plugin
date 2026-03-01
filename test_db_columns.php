<?php
require_once('../../../wp-load.php');

global $wpdb;
$table_name = $wpdb->prefix . 'bookings';
$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");

echo "Columns in $table_name:\n";
foreach ($columns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}

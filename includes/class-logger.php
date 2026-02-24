<?php
namespace BookingApp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logger Service
 * 
 * Handles secure logging to the plugin logs directory.
 * Following AI_RULES.md rule 4.
 */
class Logger
{

    /**
     * Log a message to the custom log file.
     * 
     * @param string $message
     * @param string $level (info, error, debug, warning)
     * @param array $context
     */
    public static function log($message, $level = 'info', $context = [])
    {
        $log_dir = BOOKING_APP_PATH . 'logs';

        // Ensure logs directory exists and is secured
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            file_put_contents($log_dir . '/index.html', '');
            file_put_contents($log_dir . '/.htaccess', 'deny from all');
        }

        $log_file = $log_dir . '/plugin.log';
        $timestamp = current_time('mysql');
        $context_str = !empty($context) ? ' ' . json_encode($context) : '';

        $formatted_message = sprintf(
            "[%s] [%s]: %s%s\n",
            $timestamp,
            strtoupper($level),
            $message,
            $context_str
        );

        error_log($formatted_message, 3, $log_file);
    }

    public static function info($message, $context = [])
    {
        self::log($message, 'info', $context);
    }

    public static function error($message, $context = [])
    {
        self::log($message, 'error', $context);
    }

    public static function debug($message, $context = [])
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::log($message, 'debug', $context);
        }
    }
}

<?php
namespace BookingApp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class
 * 
 * Follows Singleton Pattern as per AI_RULES.md
 */
final class Plugin
{
    /** @var Plugin|null */
    private static $instance = null;

    /** @var string */
    public $version = '0.1.0';

    /**
     * Private constructor to prevent multiple instances.
     */
    private function __construct()
    {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Get the singleton instance.
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Define plugin constants.
     */
    private function define_constants()
    {
        if (!defined('BOOKING_APP_VERSION')) {
            define('BOOKING_APP_VERSION', $this->version);
        }
        if (!defined('BOOKING_APP_PATH')) {
            define('BOOKING_APP_PATH', plugin_dir_path(__DIR__));
        }
        if (!defined('BOOKING_APP_URL')) {
            define('BOOKING_APP_URL', plugin_dir_url(dirname(__FILE__)));
        }
    }

    /**
     * Include required files.
     */
    private function includes()
    {
        require_once BOOKING_APP_PATH . 'includes/class-logger.php';
        require_once BOOKING_APP_PATH . 'includes/class-timezone-handler.php';
        require_once BOOKING_APP_PATH . 'includes/class-bookings-table.php';
        require_once BOOKING_APP_PATH . 'includes/class-services-table.php';
        require_once BOOKING_APP_PATH . 'includes/class-consultation-cpt.php';
        require_once BOOKING_APP_PATH . 'includes/class-availability-engine.php';
        require_once BOOKING_APP_PATH . 'includes/class-booking-service.php';
        require_once BOOKING_APP_PATH . 'includes/class-service-manager.php';
        require_once BOOKING_APP_PATH . 'includes/class-stats-service.php';
        require_once BOOKING_APP_PATH . 'includes/class-settings.php';
        require_once BOOKING_APP_PATH . 'includes/class-frontend.php';
        require_once BOOKING_APP_PATH . 'includes/class-admin.php';
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks()
    {
        add_action('init', [$this, 'init_i18n']);

        // Initialize Admin & REST API
        new Admin();
        new Frontend();

        // Run migrations on every request (dbDelta is fast)
        $this->run_migrations();
    }

    /**
     * Load text domain.
     */
    public function init_i18n()
    {
        load_plugin_textdomain('booking-app', false, dirname(plugin_basename(BOOKING_APP_PATH)) . '/languages');
    }

    /**
     * Run database migrations.
     */
    public function run_migrations()
    {
        Bookings_Table::create_table();
        Services_Table::create_table();
    }

    /**
     * Activation hook.
     */
    public static function activate()
    {
        // Ensure table handler is loaded
        if (!class_exists('BookingApp\\Bookings_Table')) {
            require_once __DIR__ . '/class-bookings-table.php';
        }
        Bookings_Table::create_table();

        // Ensure services table handler is loaded
        if (!class_exists('BookingApp\\Services_Table')) {
            require_once __DIR__ . '/class-services-table.php';
        }
        Services_Table::create_table();

        // Flush rewrite rules for CPT
        if (!class_exists('BookingApp\\Consultation_CPT')) {
            require_once __DIR__ . '/class-consultation-cpt.php';
        }
        Consultation_CPT::register();
        flush_rewrite_rules();
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate()
    {
        flush_rewrite_rules();
    }
}

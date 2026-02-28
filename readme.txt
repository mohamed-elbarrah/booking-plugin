=== Booking App ===
Contributors: you
Tags: bookings, appointments
Requires PHP: 7.4
Stable tag: 0.1.0

Booking App is an admin-first bookings plugin scaffold.

== Installation ==

1. Upload the `Booking-app` folder to the `/wp-content/plugins/` directory.
2. Run `composer install` in the plugin folder (optional) and `composer dump-autoload`.
3. Activate the plugin through the 'Plugins' screen in WordPress.



## 📁 Directory Structure

```
Booking-app/
├── booking-app.php              # Entry point (plugin header + bootstrap)
├── AI_RULES.md                  # Architecture spec & development roadmap
├── composer.json
├── uninstall.php
│
├── includes/                    # All PHP classes (13 files)
│   ├── class-plugin.php         # Singleton orchestrator
│   ├── class-admin.php          # Admin panel + admin REST routes
│   ├── class-frontend.php       # Public shortcode + public REST routes
│   ├── class-availability-engine.php  ← recently worked on
│   ├── class-booking-service.php
│   ├── class-service-manager.php
│   ├── class-settings.php
│   ├── class-stats-service.php
│   ├── class-timezone-handler.php     ← recently added
│   ├── class-logger.php
│   ├── class-bookings-table.php
│   ├── class-services-table.php
│   └── class-consultation-cpt.php
│
├── templates/
│   ├── shortcode-booking.php    # Public booking UI (frontend)
│   ├── overview.php             # Admin dashboard
│   ├── services.php             # Admin services manager page
│   └── settings.php             # Admin settings page (tabbed)
│
├── assets/
│   ├── css/
│   │   ├── frontend.css         # Public booking app styles
│   │   └── admin.css            # Admin panel styles
│   └── js/
│       ├── frontend-booking.js  # 4-step public booking wizard (345 lines)
│       ├── admin-services.js    # Service CRUD via REST (228 lines)
│       └── admin-settings.js    # Settings tabs + break time logic (97 lines)
│
└── logs/                        # Secured log directory (.htaccess: deny all)

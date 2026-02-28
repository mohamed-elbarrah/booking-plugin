# Booking App Plugin — Full Architecture Summary

> **Version:** 0.1.0 · **Author:** Mohamed ElBarrah · **Namespace:** `BookingApp`

---

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
```

---

## 🧠 PHP Class Breakdown

### Core Bootstrap

| Class | Role |
|-------|------|
| `Plugin` | **Singleton**. Defines constants (`BOOKING_APP_PATH`, `BOOKING_APP_URL`, `BOOKING_APP_VERSION`), loads all includes, runs DB migrations on every request via `dbDelta`, hooks `Admin` and `Frontend`. |

---

### Admin Layer (`class-admin.php`)

- Registers **3 admin menu pages**: Overview, Services, Settings
- Enqueues **Tailwind CSS + Flowbite** for admin UI
- Registers **admin-only REST routes** at `booking-app/v1/`:
  - `GET /services` — list all services
  - `POST /services` — create or update a service
  - `DELETE /services/{id}` — delete a service
- All routes protected by `manage_options` capability check

---

### Frontend Layer (`class-frontend.php`)

- Registers shortcode `[booking_app]` → renders `templates/shortcode-booking.php`
- Enqueues **Tailwind + Flatpickr + frontend.css + frontend-booking.js** (only on pages using the shortcode)
- Passes `restUrl` and `nonce` to JS via `wp_localize_script`
- Registers **public REST routes** at `booking-app/v1/public/`:
  - `GET /services` — active services only
  - `GET /slots?service_id=&date=` — available time slots for a date
  - `GET /availability-config` — disabled days + minDate for datepicker
  - `POST /bookings` — submit a new booking

---

### Availability Engine (`class-availability-engine.php`) ⭐ Recently Refactored

**Core logic** for calculating which time slots are available. Key details:

- `get_available_slots($date, $service_id)`
  1. Reads `availability` settings for the day (using a **Mon=0, Sun=6** index)
  2. Fetches the service duration from `wp_mbs_services`
  3. Fetches existing bookings for the date (UTC-aware DB query)
  4. Generates slots every **30 min** (or `min(30, duration)` for short services)
  5. For each slot: checks break conflicts + booking conflicts
  6. Returns array of `{ time (RFC3339/UTC), display_time (local), duration, available }`

- `is_slot_available($start, $end, $breaks, $bookings, $date)`
  - **Break check (fixed):** A slot is blocked only if its **START time falls within the break** window (`$start >= $break_start && $start < $break_end`). Appointments spanning *into* a break are allowed.
  - **Booking check:** Full overlap check — `$start < $booking_end && $end > $booking_start`

- `get_bookings_for_date($date)` — Timezone-aware: converts local date boundaries → UTC for DB query. Excludes `cancelled` and `rejected` statuses.

---

### Timezone Handler (`class-timezone-handler.php`) ⭐ Recently Added

Centralises all date/time conversion. Uses `wp_timezone()` / `wp_timezone_string()`.

| Method | Purpose |
|--------|---------|
| `to_utc($local, $tz)` | Local → UTC (Y-m-d H:i:s) |
| `from_utc($utc, $tz)` | UTC → Local (Y-m-d H:i:s) |
| `to_rfc3339($utc)` | UTC → RFC3339 string |
| `to_rfc3339_from_local($local, $tz)` | Local → UTC RFC3339 (used by Availability Engine) |

---

### Settings (`class-settings.php`)

- **Singleton** storing options in `booking_app_settings` (via `get_option` / `update_option`)
- Sanitizes:
  - General: `business_name`, `admin_email`, `currency`, `timezone`
  - Availability per day (0=Mon … 6=Sun): `enabled`, `start`, `end`, `breaks[]`
  - Payments: Stripe (`publishable`, `secret`, `sandbox`) + PayPal (`client_id`, `secret`, `sandbox`)

---

### Booking Service (`class-booking-service.php`)

Static methods for booking CRUD on `wp_bookings` table:

| Method | Description |
|--------|-------------|
| `create_booking($data)` | Inserts row; fires `booking_app_after_booking_created` action |
| `get_bookings($args)` | Filterable list (status, limit, offset, orderby) |
| `update_status($id, $status)` | Updates status; fires `booking_app_booking_status_updated` |

---

### Service Manager (`class-service-manager.php`)

Singleton. CRUD on `wp_mbs_services`:

| Method | Description |
|--------|-------------|
| `save_service($data)` | Insert (id=0) or Update (id>0) |
| `delete_service($id)` | Hard delete |
| `get_services($status)` | List all (optionally filtered by status) |
| `get_service($id)` | Single row by ID |

---

### Stats Service (`class-stats-service.php`)

| Method | Returns |
|--------|---------|
| `get_dashboard_stats()` | `{ total, confirmed, pending, revenue }` |
| `get_today_bookings_count()` | Count of today's bookings (UTC-based) |

---

### Logger (`class-logger.php`)

- Writes to `logs/plugin.log` (secured with `.htaccess deny from all`)
- Levels: `info`, `error`, `debug` (debug only fires when `WP_DEBUG` is on)
- Context array serialized as JSON in log line

---

### Database Tables

| Table | Class | Schema highlights |
|-------|-------|-------------------|
| `wp_bookings` | `Bookings_Table` | `id, consultation_id, customer_name, customer_email, booking_datetime_utc (UTC!), duration, price_total, payment_status, meeting_link, google_event_id, status, created_at` |
| `wp_mbs_services` | `Services_Table` | `id, name, description, duration, price, status, created_at, updated_at` |

Both use `dbDelta` for safe schema migrations on every request.

---

## 🖥️ Frontend JavaScript Modules

### `frontend-booking.js` — 4-Step Booking Wizard

**State object:** `{ currentStep, services[], selectedService, selectedDate, selectedSlot, slots[], loading, availabilityConfig }`

| Step | Action |
|------|--------|
| **1 – Service** | Fetches active services → renders styled cards (middle card marked "Popular") |
| **2 – Date & Time** | Initialises **Flatpickr** (inline, week starts Monday) using `/availability-config` to disable non-working days. On date select → fetches `/slots` → renders available (clickable) and unavailable (disabled, greyed out) pill buttons |
| **3 – Details** | Simple form (name, email, phone, notes) |
| **4 – Success** | POSTs to `/bookings` with RFC3339 UTC slot time; shows confirmation screen |

Progress bar updates each step (25% / 50% / 75% / 100%).

---

### `admin-services.js` — Service CRUD

- Fetches services list on load and re-renders after mutations
- Uses **Flowbite modal** for Add / Edit form
- Status toggled via inline checkbox → `POST /services` with `{ id, status }`
- Animated toast notifications (slide-in, auto-dismiss after 3s)

---

### `admin-settings.js` — Settings Page

- Tab switching (General / Availability / Payments)
- Dynamic **break time rows** per day: add row (clones `<template>`), remove row, re-indexes `name` attributes to match PHP serialisation format (`booking_app_settings[availability][dayIndex][breaks][breakIndex][start]`)
- Submit button shows "Saving..." state

---

## 🌐 REST API Map

| Namespace | Endpoint | Method | Auth | Handler |
|-----------|----------|--------|------|---------|
| `booking-app/v1/public` | `/services` | GET | Public | `Frontend::get_active_services` |
| `booking-app/v1/public` | `/slots` | GET | Public | `Frontend::get_available_slots` |
| `booking-app/v1/public` | `/availability-config` | GET | Public | `Frontend::get_availability_config` |
| `booking-app/v1/public` | `/bookings` | POST | Public (nonce) | `Frontend::create_booking` |
| `booking-app/v1` | `/services` | GET | Admin | `Admin::get_services_rest` |
| `booking-app/v1` | `/services` | POST | Admin | `Admin::save_service_rest` |
| `booking-app/v1` | `/services/{id}` | DELETE | Admin | `Admin::delete_service_rest` |

---

## ✅ Recently Implemented Features

| Feature | File(s) |
|---------|---------|
| **Timezone-aware slot generation** — Availability Engine now uses `wp_timezone()` (`DateTime` objects) instead of raw string parsing | `class-availability-engine.php` |
| **Timezone-aware DB queries** — `get_bookings_for_date()` converts local date midnight/end to UTC before querying | `class-availability-engine.php` |
| **Fixed break overlap logic** — Only blocks slots whose *start* falls inside a break; allows bookings that span into a break | `class-availability-engine.php` |
| **Centralized Timezone Handler** — Dedicated class for all UTC↔Local conversions and RFC3339 formatting | `class-timezone-handler.php` |
| **Flatpickr datepicker fixes** — Inline calendar initialised correctly in Step 2, week starts Monday, disabled days from API | `frontend-booking.js` |
| **Slot interval standardised** — Uses 30-min step (or service duration if shorter) for consistent slot boundary grid | `class-availability-engine.php` |
| **Availability config endpoint** — `/availability-config` returns disabled JS day-of-week numbers + minDate for the datepicker | `class-frontend.php` |

---

## 🔭 Planned / Not Yet Implemented (from AI_RULES.md)

- Revenue charts and analytics on Overview dashboard
- Payment gateway integration (Stripe / PayPal fields exist in Settings but not wired)
- Google Calendar integration (`google_event_id` column exists in DB)
- Meeting link generation (`meeting_link` column exists in DB)
- Holiday management (specific date exclusions)
- Manual admin booking creation page
- Email confirmation on booking
- License / SaaS multi-tenant layer

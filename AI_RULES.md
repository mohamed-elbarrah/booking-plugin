================================================================================
MY BOOKING SAAS – ADMIN & FRONTEND STRUCTURE PLAN (v1.0)
================================================================================

ADMIN PANEL STRUCTURE
Total Admin Pages: 3 Main Pages
(All pages follow WordPress capability check: current_user_can('manage_options'))

------------------------------------------------------------
1) OVERVIEW PAGE (Dashboard & Analytics)
------------------------------------------------------------
Purpose:
Central control panel showing real-time business data and analytics.

Route:
wp-admin/admin.php?page=mbs-overview

Sections:

A. Quick Stats Cards
- Total Bookings
- Confirmed Bookings
- Pending Bookings
- Cancelled Bookings
- Revenue (formatted using PricingService::formatPrice())
- Today’s Bookings
- Upcoming Bookings

B. Revenue Analytics
- Revenue chart (daily / weekly / monthly)
- Booking trends graph
- Service performance comparison

C. Recent Bookings Table
- Customer Name
- Service
- Date & Time (converted from UTC at presentation layer)
- Status
- Actions (View / Cancel / Reschedule)

D. Availability Snapshot
- Today’s occupancy %
- Next available slot
- Internal conflict warnings (Overlapping bookings)

E. System Health
- Database connection status
- License status (future SaaS readiness)
- Version info

Data Source:
All data must be retrieved through Services layer only.
No SQL in controller.

------------------------------------------------------------
2) CREATE BOOKING PAGE (Manual Booking Management)
------------------------------------------------------------
Purpose:
Allow admin to manually create bookings for customers.

Route:
wp-admin/admin.php?page=mbs-create-booking

Sections:

A. Service Selector
- Dropdown list of services
- Duration auto-loaded from service
- Price displayed via PricingService::formatPrice()

B. Date & Time Picker
- Dynamic availability calendar
- Fetch availability via REST
- Disabled slots:
  - Local DB conflicts (Conflicting internal bookings)
  - Business hour exclusions (Breaks/Holidays)
- Double booking prevention rule applied in Service layer

C. Customer Details Form
- Full Name
- Email
- Phone
- Notes

D. Booking Summary
- Service
- Duration
- Date
- Final price
- Status selector (Confirmed / Pending)

E. Submit Button
- Validate input
- Call BookingService::createBooking()
- Return standardized JSON response

------------------------------------------------------------
3) SETTINGS PAGE
------------------------------------------------------------
Purpose:
Business configuration and SaaS integrations.

Route:
wp-admin/admin.php?page=mbs-settings

Tabs inside Settings:

------------------------------------------------------------
TAB 1: General Settings
------------------------------------------------------------
- Business Name
- Brand Logo Upload
- Primary Color
- Email From Address
- Booking Confirmation Template
- Timezone (stored, but DB dates remain UTC)

------------------------------------------------------------
TAB 2: Business Hours & Availability
------------------------------------------------------------
- Working Days (Mon–Sun toggles)
- Opening Time
- Closing Time
- Break Times (multiple intervals)
- Slot Interval (15 / 30 / 60 min)
- Buffer Time between bookings
- Holiday Management (date exclusions)

All stored in wp_mbs_availability table.

------------------------------------------------------------
TAB 3: Services Management
------------------------------------------------------------
- Add / Edit / Delete Service
- Service Name
- Description
- Duration
- Price
- Status (Active/Inactive)

Stored in wp_mbs_services table.

------------------------------------------------------------
TAB 4: Advanced Availability Settings
------------------------------------------------------------
- Holiday Management (Specific date exclusions)
- Buffer Time controls
- Slot Generation intervals

Important:
Availability is calculated in real-time by Availability_Engine.
It aggregates Settings (Hours/Breaks) + Database (Confirmed Bookings).
No external API dependency.

------------------------------------------------------------
TAB 5: Advanced / SaaS
------------------------------------------------------------
- License Key
- Webhook URL
- Enable Telemetry (toggle)
- Debug Mode (log viewer)

------------------------------------------------------------
FRONTEND STRUCTURE (PUBLIC BOOKING UI)
------------------------------------------------------------

Design Philosophy:
- 100% Minimal
- Clean
- White space heavy
- Inspired by shadcn style system
- No visual noise
- Clear typography hierarchy
- Rounded-md components
- Soft borders
- Neutral color palette

Tech:
- React (UI only)
- TailwindCSS
- No business logic in frontend
- All data via REST API
- Nonce passed via wp_localize_script

------------------------------------------------------------
FRONTEND FLOW (Single Page App Style)
------------------------------------------------------------

Step 1: Service Selection
- Clean card layout
- Service name
- Short description
- Duration
- Price badge
- “Select” button

Step 2: Calendar & Time Selection
- Minimal calendar
- Available slots (clickable)
- Unavailable slots:
  - Greyed out
  - Disabled
- Loading skeleton states
- No price logic here

Step 3: Customer Details
- Name
- Email
- Phone
- Notes
- Clean input fields
- Subtle focus states

Step 4: Confirmation Screen
- Booking summary
- Service
- Date & Time (converted to user local time)
- Final price
- Confirm button

Step 5: Success Screen
- Large check icon
- Booking confirmed message
- Optional add-to-calendar button
- Email confirmation note

------------------------------------------------------------
UI COMPONENT RULES
------------------------------------------------------------

- Buttons: Rounded-md, solid primary color
- Cards: Shadow-sm, border, rounded-lg
- Inputs: Border, focus:ring-1
- Typography:
  - Heading: text-xl font-semibold
  - Body: text-sm text-muted
- Spacing: generous padding (p-6)
- Animations: subtle fade transitions only

------------------------------------------------------------
DATA & LOGIC FLOW
------------------------------------------------------------

Frontend → REST Controller → Service Layer → Repository → DB

No shortcuts.
No logic duplication.
No SQL outside Repository.
No pricing formatting in React.

------------------------------------------------------------
SCALABILITY PREPARATION
------------------------------------------------------------

- Multi-tenant architecture ready
- Hooks for:
  do_action('mbs_after_booking_confirmed')
  do_action('mbs_after_booking_cancelled')
- License validation ready
- Modular structure for future add-ons

------------------------------------------------------------
TOTAL STRUCTURE SUMMARY
------------------------------------------------------------

ADMIN:
1. Overview
2. Create Booking
3. Settings (with 5 internal tabs)

FRONTEND:
Single clean booking flow (SPA style)

Architecture strictly follows:
Database → Repository → Services → REST → React

================================================================================
END OF STRUCTURE PLAN
================================================================================
# Booking App Project Summary

## 🌟 Overview
**Booking App** is a professional-grade WordPress plugin designed for service-based businesses to manage appointments. It features a modern, single-page application (SPA) frontend and a clean, service-oriented backend architecture.

---

## 🏗️ Architecture & Core Logic
The plugin follows a strict separation of concerns, as outlined in the `AI_RULES.md`.

### Backend (PHP)
- **Singleton Pattern**: Core classes like `Plugin`, `Settings`, and `Service_Manager` use the singleton pattern for consistent state management.
- **Service Layer**: Business logic is encapsulated in service classes (e.g., `Booking_Service`, `Stats_Service`), ensuring no direct SQL logic in controllers.
- **Data Layer**: Uses custom database tables (`wp_bookings`, `wp_mbs_services`) with a repository-like approach for data access.
- **REST API**: Built-in WordPress REST API endpoints for both public (booking) and admin (management) operations.

### Frontend (User Interface)
- **SPA Experience**: The frontend booking flow is a step-by-step application driven by jQuery state management.
- **Modern UI**: Styled with **Tailwind CSS** and **Flowbite**, following a "shadcn-inspired" minimal design philosophy.
- **Dynamic Availability**: The **Availability Engine** calculates real-time slots by checking business hours, break times, and conflicting bookings in the database.

---

## 🧩 Key Components

| Component | Responsibility |
| :--- | :--- |
| **Plugin** | Main bootstrapper, handles hooks, migrations, and initialization. |
| **Availability_Engine** | The "brain" that calculates available time slots for any given date. |
| **Booking_Service** | Manages the lifecycle of a booking (Create, Update, Status tracking). |
| **Service_Manager** | Handles CRUD for the various services/consultations offered. |
| **Admin** | Manages the dashboard analytics, service list, and business settings. |
| **Frontend** | Renders the `[booking_app]` shortcode and handles the booking UI. |
| **Timezone_Handler** | Ensures all dates are stored in UTC and converted correctly for users. |

---

## 📂 Project Structure
- `booking-app.php`: Main entry point.
- `includes/`: Core PHP logic and classes.
- `templates/`: HTML/PHP templates for admin and frontend.
- `assets/`: 
    - `js/`: Frontend state logic (`frontend-booking.js`) and admin scripts.
    - `css/`: Tailwind-based styling.
- `AI_RULES.md`: The "Bible" of the project, defining architectural constraints and design rules.

---

## 💡 Design Philosophy
- **Minimalism**: Focus on white space, clean typography, and subtle transitions.
- **No Logic Duplication**: Business rules are strictly in the backend; the frontend is purely for presentation and state handling.
- **SaaS Ready**: Prepared for multi-tenancy and external integrations (Stripe/PayPal hooks present).

---
*Generated on 2026-02-24*

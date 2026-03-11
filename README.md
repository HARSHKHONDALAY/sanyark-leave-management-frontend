# Sanyark Leave Management System – Frontend

This repository contains the custom WordPress plugin frontend for the Sanyark Leave Management System.

The frontend is built as a plugin-based headless WordPress interface that connects to the Spring Boot backend API using JWT authentication. It provides separate employee and manager flows, dashboard pages, leave application and management screens, and a custom team calendar UI.

------------------------------------------------------------

Technology Stack

Frontend Platform
- WordPress
- PHP

UI / Styling
- Custom plugin-based rendering
- Custom CSS
- Custom JavaScript
- Particles.js background effects

Architecture
- Headless-style WordPress frontend
- Spring Boot REST API integration
- JWT session-based authentication

------------------------------------------------------------

Project Structure

The plugin is organized as follows:

sanyark-leave-management
- sanyark-leave-management.php
- assets
  - css
    - slm-style.css
  - js
    - slm-app.js
- includes
  - class-slm-api.php
  - class-slm-auth.php
  - class-slm-pages.php
  - class-slm-shortcodes.php

Main Files

sanyark-leave-management.php  
Plugin bootstrap file. Loads dependencies, enqueues assets, defines constants, starts session handling, and initializes the plugin.

class-slm-api.php  
Handles backend API communication such as login, dashboard data, leave APIs, and manager actions.

class-slm-auth.php  
Handles session-based login state and JWT token storage.

class-slm-pages.php  
Handles page protection, role-based redirects, and logout behavior.

class-slm-shortcodes.php  
Contains shortcode rendering and form handling for login, dashboards, leave forms, leave lists, manager approvals, and team calendar.

slm-style.css  
Contains the premium space-themed UI styling, dashboard layouts, cards, forms, filters, badges, and calendar styles.

slm-app.js  
Contains frontend behavior such as particles initialization and page-level enhancements.

------------------------------------------------------------

Main Features

Authentication
- Shared login page for employees and managers
- JWT token stored in PHP session
- Role-based redirects after login

Employee Features
- Employee dashboard
- Apply leave form
- My leaves page
- Cancel pending leave
- Logout

Manager Features
- Manager dashboard
- Manage leave requests
- Approve leave with comment
- Reject leave with comment
- Employee search on leave management page
- Team calendar
- Logout

Dashboard Features

Employee dashboard includes:
- Total leaves
- Used leaves
- Remaining leaves
- Pending leaves
- Upcoming holidays
- Upcoming approved leaves

Manager dashboard includes:
- Total employees
- Pending approvals
- Employees currently on leave
- Leaves this week
- Leaves this month
- Calendar access

------------------------------------------------------------

Frontend Pages and Shortcodes

Known page structure:

- /login/ → [slm_login_form]
- /employee-dashboard/ → [slm_employee_dashboard]
- /apply-leave/ → [slm_apply_leave_form]
- /my-leaves/ → [slm_my_leaves]
- /manager-dashboard/ → [slm_manager_dashboard]
- /manage-leaves/ → [slm_manage_leaves]
- /team-calendar/ → [slm_team_calendar]

------------------------------------------------------------

Backend Integration

This plugin communicates with the Spring Boot backend API.

Current local API base URL is configured in the plugin and should be updated for production deployment.

Examples of backend integration handled by the plugin:
- Login
- Employee dashboard
- Manager dashboard
- Leave creation
- Leave listing
- Leave cancellation
- Leave approval and rejection

------------------------------------------------------------

Setup Instructions

1. Place the Plugin in WordPress

Copy the plugin folder into:

wp-content/plugins/sanyark-leave-management

2. Activate the Plugin

From WordPress Admin:
- Go to Plugins
- Activate "Sanyark Leave Management"

3. Create Required Pages

Create these WordPress pages and place the corresponding shortcode inside each page:

- Login
- Employee Dashboard
- Apply Leave
- My Leaves
- Manager Dashboard
- Manage Leaves
- Team Calendar

4. Ensure Backend API is Running

The frontend expects the backend API to be accessible and reachable from WordPress.

Example local backend:

http://localhost:8080

5. Test Authentication and Flows

Test:
- Employee login
- Manager login
- Employee leave application
- My leaves listing
- Leave cancellation
- Manager approval and rejection
- Team calendar access

------------------------------------------------------------

Design Decisions and Assumptions

Plugin-based WordPress Frontend  
The frontend was implemented as a custom WordPress plugin to keep the leave management system self-contained and portable across WordPress environments.

Headless-style Integration  
WordPress is used only for frontend page rendering and session flow, while business logic and data operations are delegated to the Spring Boot backend via REST APIs.

Session-based JWT Handling  
The JWT token returned by the backend is stored in PHP session for authenticated requests from the WordPress side.

Role-based User Experience  
Separate employee and manager dashboards are provided to keep the UX focused and relevant to each role.

Custom Calendar UI  
The team calendar is currently implemented as a custom PHP/CSS month view rather than using a third-party JS calendar library.

Assumptions
- WordPress is available and running.
- Backend API is running and accessible.
- Backend is the source of truth for authentication, leave data, balances, holidays, and approvals.
- Production deployment will require replacing localhost API configuration with the production API URL.

------------------------------------------------------------

Future Improvements

Possible enhancements:
- FullCalendar integration for enterprise-grade calendar UI
- Stronger production configuration management for API base URL
- Improved responsive optimization across all pages
- Admin settings page for plugin configuration
- Email notification support

------------------------------------------------------------

Author

Harsh Khondalay
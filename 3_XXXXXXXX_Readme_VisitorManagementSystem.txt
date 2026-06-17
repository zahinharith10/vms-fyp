================================================================================
   SECURE VISITOR MANAGEMENT SYSTEM WITH FACE RECOGNITION
   FOR SRI AYU APARTMENT
   Final Year Project — Examiner README / Guideline
================================================================================

   File      : 3_XXXXXXXX_Readme_VisitorManagementSystem.txt
   Author    : [Your Full Name]
   Matric    : XXXXXXXX
   Program   : [Your Programme / Course Code]
   Session   : 2025/2026
   Supervisor: [Supervisor Name]

================================================================================
TABLE OF CONTENTS
================================================================================

  1. Project Overview
  2. Live Server Access (RECOMMENDED FOR EXAMINERS)
  3. Demonstration Video
  4. User Credentials (Test Accounts)
  5. System Modules / Features
  6. System Flow (End-to-End)
     6.1  Visitor Flow (Self-Register Path)
     6.2  Visitor Flow (Pre-Registered by Resident Path)
     6.3  Delivery Personnel Flow
     6.4  Inquiry / Contact Us Flow
     6.5  Visit Status Lifecycle
     6.6  Delivery Status Lifecycle
  7. How to Run Locally (Alternative Setup)
     7.1  System Requirements
     7.2  Prerequisites
     7.3  Step-by-Step Installation
     7.4  Seeding the Database
     7.5  Starting the Application
  8. Important Notes
  9. Project File Structure

================================================================================
1. PROJECT OVERVIEW
================================================================================

The Secure Visitor Management System with Face Recognition for Sri Ayu
Apartment is a full-stack web application developed as a Final Year Project.
It is designed to digitise and streamline visitor, delivery, and resident
management operations for Sri Ayu Apartment, a gated residential community.

Key capabilities include:
  - Face recognition-based identity verification at the gate
  - Pre-registration of visitor and delivery visits by residents
  - QR code-based check-in / check-out via Guard portal
  - Real-time in-app notifications across all user portals
  - Role-based access control (Admin, Guard, Resident, Visitor, Delivery)
  - Inquiry / Contact Us system with live chat threads
  - Audit logs and exportable reports

Technology Stack:
  - Backend  : Laravel 11 (PHP)
  - Frontend : Vue 3 + Inertia.js
  - Database : MySQL
  - Build    : Vite (Node.js)
  - Hosting  : Live Production Server

================================================================================
2. LIVE SERVER ACCESS (RECOMMENDED FOR EXAMINERS)
================================================================================

  The system is fully deployed and accessible online. No installation required.
  Simply open a browser and navigate to the links below.

  IMPORTANT: Use Google Chrome or Microsoft Edge for best compatibility,
             especially for the face recognition and QR camera features.

  +------------------------------------------------------------------+
  |  PORTAL          |  URL                                          |
  +------------------------------------------------------------------+
  |  Main Page       |  www.sriayuvisitor.com                        |
  |  (Visitor &      |                                               |
  |   Resident)      |                                               |
  +------------------------------------------------------------------+
  |  Admin Portal    |  www.sriayuvisitor.com/admin/login            |
  +------------------------------------------------------------------+
  |  Guard Portal    |  www.sriayuvisitor.com/guard/login            |
  +------------------------------------------------------------------+
  |  Delivery Portal |  www.sriayuvisitor.com/delivery/register      |
  |  (Register/Login)|                                               |
  +------------------------------------------------------------------+

  HOW TO ACCESS EACH ROLE:

  [ADMIN]
  > Go to: www.sriayuvisitor.com/admin/login
  > Log in with Admin credentials (see Section 4).
  > Features: Manage users, view logs, reply to inquiries, export reports.

  [GUARD]
  > Go to: www.sriayuvisitor.com/guard/login
  > Log in with Guard credentials (see Section 4).
  > Features: QR code scanner, face recognition, check-in & check-out.
  > NOTE: Camera access must be allowed in the browser for QR scanning
          and face recognition to function.

  [RESIDENT]
  > Go to: www.sriayuvisitor.com/resident/login
  > Log in with Resident credentials (see Section 4).
  > Features: Pre-register visitors, approve/reject requests, contact us.

  [VISITOR]
  > Go to: www.sriayuvisitor.com
  > Click "Register" to create a new visitor account with face scan.
  > Or log in using OTP sent to registered phone/email.
  > Features: Request visits, view QR pass, track visit status.

  [DELIVERY PERSONNEL]
  > Go to: www.sriayuvisitor.com/delivery/register
  > Register a new account with face scan, or log in if already registered.
  > Features: Create delivery trips (single or multi-stop), show QR code.

================================================================================
3. DEMONSTRATION VIDEO
================================================================================

  A pre-recorded demonstration video is provided alongside this submission.

  Video File  : [e.g., 3_XXXXXXXX_Demo_VisitorManagementSystem.mp4]
  Duration    : Approximately [XX] minutes
  Format      : MP4 (H.264)

  HOW TO VIEW:
  ------------
  1. Locate the video file in the submitted folder / link below.
  2. Open with any media player (Windows Media Player, VLC, etc.).
  3. Ensure audio is enabled — the video includes voice narration.

  Google Drive / OneDrive Link (if applicable):
  >>> [Insert shared video link here] <<<

  The demonstration video covers:
    - System overview and login for each user role (live server)
    - Resident creating a visitor pre-registration
    - Visitor registering with face scan and requesting a visit
    - Guard scanning QR code with face recognition check-in/check-out
    - Delivery personnel creating and managing a delivery trip
    - Admin dashboard, reports, and inquiry management
    - Notification system in action
    - Inquiry / Contact Us chat thread feature

================================================================================
4. USER CREDENTIALS (TEST ACCOUNTS)
================================================================================

  Use the following test accounts to log in to the LIVE server:

  +------------------+-----------------------------+------------------+
  | Role             | Email / Phone               | Password         |
  +------------------+-----------------------------+------------------+
  | Admin            | admin@sriayuvisitor.com     | [admin pass]     |
  | Guard            | guard@sriayuvisitor.com     | [guard pass]     |
  | Resident         | resident@sriayuvisitor.com  | [resident pass]  |
  | Visitor          | (OTP via phone / email)     | (OTP-based)      |
  | Delivery         | delivery@sriayuvisitor.com  | [delivery pass]  |
  +------------------+-----------------------------+------------------+

  NOTE: Update the credentials above with the actual test account details
        before submitting this file to your examiner.

  Portal Login URLs (LIVE):
    Admin    : www.sriayuvisitor.com/admin/login
    Guard    : www.sriayuvisitor.com/guard/login
    Resident : www.sriayuvisitor.com/resident/login
    Visitor  : www.sriayuvisitor.com  (main landing page)
    Delivery : www.sriayuvisitor.com/delivery/register

================================================================================
5. SYSTEM MODULES / FEATURES
================================================================================

  MODULE 1  — Admin Portal  (www.sriayuvisitor.com/admin/login)
    > Manage residents, guards, delivery personnel, and house units
    > View and filter all visit logs and delivery logs
    > Manage inquiries — view threads and reply to user messages
    > Generate and export reports
    > Real-time in-app notification bell

  MODULE 2  — Guard Portal  (www.sriayuvisitor.com/guard/login)
    > QR code scanner for visitor / delivery check-in & check-out
    > Built-in face recognition to verify visitor identity at gate
    > View real-time active sessions dashboard with parking lot status
    > View all visit records and delivery records
    > Manual walk-in visitor and delivery registration

  MODULE 3  — Resident Portal  (www.sriayuvisitor.com/resident/login)
    > Pre-register visitor appointments (QR pass generated instantly)
    > Share guest pass link with visitor via WhatsApp / Email
    > Approve / reject visitor and delivery requests
    > View full visit and delivery history for their unit
    > Contact Us / Inquiry system with chat thread

  MODULE 4  — Visitor Portal  (www.sriayuvisitor.com)
    > Register with face scan and profile photo (one-time setup)
    > Log in via OTP (no password required)
    > Request a visit to a specific unit with host name and purpose
    > View real-time visit status (Pending → Approved → Checked In)
    > Display QR code pass for gate entry
    > Cancel pending or approved visits
    > Contact Us / Inquiry system

  MODULE 5  — Delivery Personnel Portal  (www.sriayuvisitor.com/delivery/register)
    > Register with face scan and vehicle details (one-time setup)
    > Create single or multi-stop delivery trips
    > System auto-approves if resident has approved, otherwise pending
    > Display delivery QR code at gate
    > Cancel pending or approved trips
    > View delivery trip history
    > Contact Us / Inquiry system

  MODULE 6  — Inquiry / Contact Us System
    > Users can open inquiries and exchange messages with admin
    > Admin can view, filter, and reply to all inquiries
    > Full conversation thread preserved (chat-style)
    > Only users can end/close the inquiry thread
    > Notifications triggered on: submission, admin reply, user reply, closure

  MODULE 7  — Notification System
    > In-app notification bell (polls every 30 seconds automatically)
    > Notifications for: new visit requests, visitor check-in/arrival,
      visit status changes, new inquiries, inquiry replies, inquiry closure

  MODULE 8  — Face Recognition
    > Visitor and Delivery Personnel register their face during sign-up
    > Guard portal verifies face at check-in using live camera feed
    > Face descriptors stored securely (128-point facial embedding)

================================================================================
6. SYSTEM FLOW (END-TO-END)
================================================================================

  This section describes the complete step-by-step flow of the system from
  the perspective of each user type.

--------------------------------------------------------------------------------
6.1  VISITOR FLOW — SELF-REGISTER PATH
        (Visitor walks in and registers themselves)
--------------------------------------------------------------------------------

  ACTORS : Visitor, Resident, Guard

  STEP 1  [Visitor — Register]
          > Go to: www.sriayuvisitor.com
          > Click "Register" and fill in:
              Name, Email, Phone, IC Number, Vehicle Number
          > Complete face scan (webcam) to register face descriptor.
          > Upload a profile photo.
          > Account created and logged in automatically.

  STEP 2  [Visitor — Request a Visit]
          > From the Visitor Dashboard, click "Request Visit".
          > Enter the destination Unit Number (Block-Floor-Unit), e.g. 1-2-3
          > Enter Host Name and Purpose of Visit.
          > Submit the form.
          > Visit status set to: PENDING
          > Resident of that unit receives an in-app notification.

  STEP 3  [Resident — Approve or Reject]
          > Resident logs in at: www.sriayuvisitor.com/resident/login
          > Opens notification or goes to "Visitor Management".
          > Views the pending visit request.
          > Clicks [Approve] or [Reject].
          > If Approved: a unique QR code token is generated.
          > Visit status changes to: APPROVED
          > Visitor receives a real-time status update on their dashboard.

  STEP 4  [Visitor — Show QR Code]
          > Visitor opens their dashboard — visit shows as Approved.
          > Clicks "Show QR" to display the QR code on screen.

  STEP 5  [Guard — Scan QR at Gate]
          > Guard logs in at: www.sriayuvisitor.com/guard/login
          > Opens the QR Scanner page.
          > Uses device camera to scan the visitor's QR code.
          > System displays visitor details:
              Name, Photo, Vehicle Number, Unit, Purpose, Status.

  STEP 6  [Guard — Face Verification & Check In]
          > Guard verifies visitor's live face against stored descriptor.
          > Guard clicks [Check In].
          > System auto-assigns a parking lot (if visitor has a vehicle).
          > Visit status changes to: CHECKED IN
          > A visit session record is created with check-in timestamp.
          > Resident receives a notification: "Visitor has arrived".

  STEP 7  [Guard — Temporary Out (Optional)]
          > If visitor leaves temporarily, Guard scans QR again.
          > Clicks [Check Out — Temporary].
          > Visit status changes to: TEMPORARILY OUT
          > Visitor may re-enter — Guard scans and checks in again.
          > A new session record is created for each re-entry.
          > Unlimited re-entries are supported.

  STEP 8  [Guard — Final Check Out]
          > When visitor leaves permanently, Guard scans QR.
          > Clicks [Check Out — Final].
          > The open session record is closed with check-out timestamp.
          > Visit status changes to: CHECKED OUT
          > Parking lot is freed.

  STEP 9  [Auto-Finalization]
          > The system automatically finalizes old "Checked In" visits
            (older than 24 hours) to prevent stale records.

--------------------------------------------------------------------------------
6.2  VISITOR FLOW — PRE-REGISTERED BY RESIDENT PATH
        (Resident books a visitor in advance)
--------------------------------------------------------------------------------

  ACTORS : Resident, Visitor, Guard

  STEP 1  [Resident — Pre-Register a Visitor]
          > Resident logs in and goes to "Visitor Management" > "Pre-Register".
          > Enters Visitor's Name, Email, and Purpose.
          > System finds or creates a Visitor profile based on email.
          > Visit created with status: APPROVED (pre-approved by host).
          > A unique QR token is generated immediately.

  STEP 2  [Resident — Share Guest Pass]
          > Resident clicks "Share Pass" to copy a guest entry link.
          > Link is shared with visitor (via WhatsApp, Email, etc.).
          > Link format: www.sriayuvisitor.com/pass/{token}

  STEP 3  [Visitor — Complete Profile via Pass Link]
          > Visitor opens the shared link in a browser.
          > If first time, completes profile:
              IC Number, Phone, Vehicle Number, Face Scan, Photo.
          > Account created and linked to the pre-registered visit.

  STEP 4  [Visitor — View QR Code]
          > After completing profile, the QR code is displayed.
          > Visitor screenshots or keeps the page open for gate entry.

  STEP 5  [Guard — Scan & Check In / Check Out]
          > Same as Steps 5–8 in Section 6.1 above.

--------------------------------------------------------------------------------
6.3  DELIVERY PERSONNEL FLOW
--------------------------------------------------------------------------------

  ACTORS : Delivery Personnel, Resident, Guard

  STEP 1  [Delivery — Register]
          > Go to: www.sriayuvisitor.com/delivery/register
          > Fill in: Name, Email, Company, Phone, IC Number,
            Vehicle Type, Vehicle Number.
          > Complete face scan and upload a photo.
          > Account created and logged in automatically.

  STEP 2  [Delivery — Create a Delivery Trip]
          > From the Delivery Dashboard, click "New Delivery".
          > Select delivery type:
              Single — one destination unit.
              Multi  — multiple destination units (multi-stop trip).
          > Enter Unit Number(s) and Host Name(s).
          > Submit the trip.
          > System checks if the resident has pre-approved deliveries.
              If yes : status = APPROVED automatically.
              If no  : status = PENDING (awaiting resident approval).
          > A QR code is generated for the trip.

  STEP 3  [Resident — Approve or Reject Delivery]
          > Resident logs in and opens "Visitor Management".
          > Views the pending delivery request in the Deliveries tab.
          > Clicks [Approve] or [Reject].
          > Status changes to: APPROVED or REJECTED.

  STEP 4  [Delivery Personnel — Show QR Code]
          > Dashboard shows the active trip's QR code.
          > Personnel presents QR at the gate.

  STEP 5  [Guard — Scan QR & Face Verify & Check In]
          > Guard scans the delivery QR code.
          > System displays: Name, Company, Vehicle, Destinations, Status.
          > Guard verifies face using face recognition.
          > Guard clicks [Check In].
          > Entry time recorded for all stops in the trip.
          > Status changes to: CHECKED IN.

  STEP 6  [Guard — Check Out Delivery]
          > After delivery complete, Guard scans QR again.
          > Clicks [Check Out — Final].
          > Exit time recorded.
          > Status changes to: CHECKED OUT.

  STEP 7  [Delivery Personnel — View History]
          > Portal shows full history of all past trips and statuses.

--------------------------------------------------------------------------------
6.4  INQUIRY / CONTACT US FLOW
--------------------------------------------------------------------------------

  ACTORS : Resident / Visitor / Delivery Personnel, Admin

  STEP 1  [User — Submit Inquiry]
          > User (any portal) logs in.
          > Navigates to "Contact Us" in the sidebar.
          > Clicks "New Inquiry".
          > Fills in: Subject and Message (Name/Email auto-filled).
          > Submits the form.
          > Inquiry status: PENDING
          > ALL Admins receive an in-app notification: "New inquiry submitted".

  STEP 2  [Admin — View and Reply]
          > Admin logs in at: www.sriayuvisitor.com/admin/login
          > Goes to the "Inquiries" section.
          > Can filter by type (Resident/Visitor/Delivery) or status.
          > Clicks on an inquiry to expand the conversation thread.
          > Types a reply and clicks [Send Reply].
          > The user receives an in-app notification: "Admin replied".

  STEP 3  [User — Reply Back]
          > User sees the new message in their inquiry thread.
          > Types a reply and clicks [Send Reply].
          > ALL Admins receive a notification: "[Name] replied to inquiry".
          > Conversation continues until resolved.

  STEP 4  [User — End Inquiry]
          > User (not Admin) clicks [End Inquiry] when satisfied.
          > Inquiry status changes to: RESOLVED
          > ALL Admins receive a notification: "Inquiry resolved by [Name]".
          > No further replies can be sent after resolution.

--------------------------------------------------------------------------------
6.5  VISIT STATUS LIFECYCLE
--------------------------------------------------------------------------------

  [Pending] ──Resident Approves──> [Approved]
                                        |
                                   Guard Check In
                                        |
                                        v
                                  [Checked In]
                                        |
                          +─────────────+─────────────+
                   Temp Check Out               Final Check Out
                          |                           |
                          v                           v
                  [Temporarily Out]           [Checked Out]
                          |
                    Re-enters Gate
                          |
                          v
                    [Checked In]  (new session created)

  Other terminal statuses:
    [Pending]     ──Resident Rejects──> [Rejected]
    [Pending]     ──Visitor Cancels───> [Cancelled]
    [Approved]    ──Visitor Cancels───> [Cancelled]
    [Checked In]  ──24h auto-timeout──> [Checked Out]

--------------------------------------------------------------------------------
6.6  DELIVERY STATUS LIFECYCLE
--------------------------------------------------------------------------------

  [Pending] ──Resident Approves──> [Approved]
                                        |
                                  Guard Check In
                                        |
                                        v
                                  [Checked In]
                                        |
                          +─────────────+─────────────+
                   Temp Check Out               Final Check Out
                          |                           |
                          v                           v
                  [Temporarily Out]           [Checked Out]
                          |
                    Re-enters Gate
                          |
                          v
                    [Checked In]  (entry_time updated)

  Other terminal statuses:
    [Pending]  ──Resident Rejects──> [Rejected]
    [Pending]  ──Personnel Cancels─> [Cancelled]
    [Approved] ──Personnel Cancels─> [Cancelled]

================================================================================
7. HOW TO RUN LOCALLY (ALTERNATIVE SETUP)
================================================================================

  NOTE: This section is only needed if you wish to run the system on your own
        machine. The live server at www.sriayuvisitor.com is the recommended
        and easiest way to evaluate the system.

7.1  SYSTEM REQUIREMENTS
--------------------------

  - Operating System : Windows 10 / 11
  - XAMPP            : Version 8.2 or higher (PHP 8.2+)
  - Node.js          : Version 18 or higher  (https://nodejs.org)
  - Composer         : Version 2.x           (https://getcomposer.org)
  - Browser          : Google Chrome / Microsoft Edge (latest)
  - RAM              : Minimum 4 GB (8 GB recommended)
  - Disk Space       : Minimum 500 MB free

7.2  PREREQUISITES
------------------

  [1] XAMPP    — https://www.apachefriends.org/download.html
  [2] Node.js  — https://nodejs.org/en/download (LTS version)
  [3] Composer — https://getcomposer.org/download/

7.3  STEP-BY-STEP INSTALLATION
-------------------------------

  STEP 1 — Copy the project folder
    > Copy the project folder "vms" into:
        C:\xampp\htdocs\VMS-FYP\vms

  STEP 2 — Start XAMPP Services
    > Open XAMPP Control Panel.
    > Click [Start] for both Apache and MySQL.
    > Confirm both show "Running" status (green).

  STEP 3 — Create the Database
    > Open browser and go to: http://localhost/phpmyadmin
    > Click "New" on the left sidebar.
    > Create a database named: vms
    > Collation: utf8mb4_unicode_ci
    > Click "Create".

  STEP 4 — Configure Environment File
    > In the project root, locate: .env.example
    > Duplicate it and rename the copy to: .env
    > Open .env and verify / update these values:

        APP_NAME="Secure Visitor Management System"
        APP_URL=http://127.0.0.1:8000

        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1
        DB_PORT=3306
        DB_DATABASE=vms
        DB_USERNAME=root
        DB_PASSWORD=

  STEP 5 — Open Terminal / Command Prompt
    > Open PowerShell or Command Prompt.
    > Navigate to the project:
        cd C:\xampp\htdocs\VMS-FYP\vms

  STEP 6 — Install PHP Dependencies
    > Run: composer install

  STEP 7 — Install Node.js Dependencies
    > Run: npm install

  STEP 8 — Generate Application Key
    > Run: php artisan key:generate

  STEP 9 — Run Database Migrations
    > Run: php artisan migrate

7.4  SEEDING THE DATABASE
--------------------------

  > To populate with sample data, run:

      php artisan db:seed

  > This creates: Admin, Guard, Residents, Visitors, House Units,
    sample visit records and inquiries.

7.5  STARTING THE APPLICATION
------------------------------

  You need TWO terminals open simultaneously:

  TERMINAL 1 — Laravel Development Server
  ----------------------------------------
  > Run: php artisan serve
  > Application available at: http://127.0.0.1:8000

  TERMINAL 2 — Vite Frontend Dev Server
  ---------------------------------------
  > Run: npm run dev
  > This compiles Vue/JS assets in real time.

  OPEN IN BROWSER:
  > Go to: http://127.0.0.1:8000
  > The main landing page will be displayed.

  NOTE: Both terminals must remain open while using the system.

================================================================================
8. IMPORTANT NOTES
================================================================================

  [!] For the LIVE SERVER, use Google Chrome or Microsoft Edge.
      Camera access must be ALLOWED for face recognition and QR scanning.

  [!] For LOCAL setup, both "php artisan serve" and "npm run dev" must run
      simultaneously.

  [!] If you encounter a 500 error on local run:
        - Ensure .env is configured correctly.
        - Run: php artisan config:clear
        - Run: php artisan cache:clear

  [!] If assets are not loading (CSS/JS broken) on local:
        - Ensure "npm run dev" is still running.
        - Try: npm run build (production build).

  [!] Email/OTP features require a valid mail configuration in .env.
      For local testing, set MAIL_MAILER=log to capture emails in
      storage/logs/laravel.log.

  [!] Face recognition requires a device with a working webcam and a browser
      that supports the MediaDevices API (Chrome/Edge recommended).

================================================================================
9. PROJECT FILE STRUCTURE
================================================================================

  vms/
  +-- app/
  |   +-- Http/Controllers/     — All controller logic
  |   +-- Models/               — Eloquent database models
  |   +-- Notifications/        — In-app notification classes
  |   +-- Events/               — Real-time broadcast events
  |   +-- Services/             — Business logic services
  +-- database/
  |   +-- migrations/           — Database table definitions
  |   +-- seeders/              — Sample data seeders
  +-- resources/
  |   +-- js/
  |       +-- Pages/            — Vue page components (Admin/Resident/etc.)
  |       +-- Layouts/          — Shared layout components
  |       +-- Components/       — Reusable UI components (NotificationDropdown, etc.)
  +-- routes/
  |   +-- web.php               — All application routes
  +-- public/                   — Public assets (entry point)
  +-- .env                      — Environment configuration
  +-- composer.json             — PHP dependencies
  +-- package.json              — Node.js dependencies

================================================================================
   END OF README

   System Title : Secure Visitor Management System with Face Recognition
                  for Sri Ayu Apartment

   Live URL     : www.sriayuvisitor.com
   Admin URL    : www.sriayuvisitor.com/admin/login
   Guard URL    : www.sriayuvisitor.com/guard/login

   For any enquiries regarding this submission, please contact:
   [Your Full Name] — [Your Email Address]
================================================================================

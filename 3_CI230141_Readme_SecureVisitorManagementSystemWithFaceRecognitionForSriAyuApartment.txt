================================================================================
         README / EXAMINER GUIDELINE
================================================================================

  Project Title  : Secure Visitor Management System with Face Recognition
                   for Sri Ayu Apartment
  Student Name   : Muhammad Zahin Harith Bin Zulkifli
  Matric Number  : CI230141
  Supervisor     : Dr Shamsul Kamal Bin Ahmad Khalid
  Programme      : BIS
  Faculty        : FSKTM
  Year           : 2026

================================================================================
  1. ABOUT THE SYSTEM
================================================================================

  This is a web-based Visitor Management System (VMS) developed for
  Sri Ayu Apartment. It digitalises and secures the visitor entry process
  using face recognition, QR code scanning and role-based access control.

  The system serves five (5) types of users:

    - Admin          : Full system management (residents, guards, reports)
    - Resident       : Approves visitor and delivery requests
    - Guard          : Scans QR codes and verifies visitor identity at gate
    - Visitor        : Self-registers, receives a QR pass, and checks in/out
    - Delivery       : Self-registers and receives gate access for deliveries

  Technology Stack:
    - Backend   : Laravel 12 (PHP 8.2)
    - Frontend  : Vue.js 3 + Inertia.js
    - Database  : MySQL
    - Realtime  : Laravel Reverb (WebSocket)
    - Face Rec  : face-api.js (browser-based machine learning)
    - QR Code   : Simple QR Code Library
    - Email     : Resend API / Gmail SMTP
    - reCAPTCHA : Google reCAPTCHA v3

================================================================================
  2. HOW TO ACCESS THE LIVE SYSTEM
================================================================================

  The system is hosted on a live server and is ready to use immediately.
  No installation or local setup is required.

  Simply open Google Chrome and visit the appropriate URL below:

  +------------------------------------------------------------------+
  |  MAIN PAGE  (Visitor Registration & Resident Login)              |
  |  URL : https://www.sriayuvisitor.com                             |
  +------------------------------------------------------------------+

  +------------------------------------------------------------------+
  |  ADMIN PAGE                                                      |
  |  URL : https://www.sriayuvisitor.com/admin/login                 |
  +------------------------------------------------------------------+

  +------------------------------------------------------------------+
  |  GUARD PAGE                                                      |
  |  URL : https://www.sriayuvisitor.com/guard/login                 |
  +------------------------------------------------------------------+

  NOTE: Visitors and Delivery Personnel can self-register directly
  from the main landing page at https://www.sriayuvisitor.com

================================================================================
  4. USER ROLES & LOGIN CREDENTIALS
================================================================================

  Use the credentials below to log in and explore each role.

  -----------------------------------------------------------------------
  ROLE       : Admin
  Login URL  : https://www.sriayuvisitor.com/admin/login
  Email      : demoadmin@sriayu.com
  Password   : @Admin12345
  -----------------------------------------------------------------------

  -----------------------------------------------------------------------
  ROLE       : Resident
  Login URL  : https://www.sriayuvisitor.com (click "Resident Login")
  Email      : demosriayu@Gmail.com
  Password   : @Resident12345
  -----------------------------------------------------------------------

  -----------------------------------------------------------------------
  ROLE       : Guard
  Login URL  : https://www.sriayuvisitor.com/guard/login
  Employee ID     : G-2026-001
  Password   : @Guard12345
  -----------------------------------------------------------------------

  -----------------------------------------------------------------------
  ROLE       : Visitor
  Login URL  : https://www.sriayuvisitor.com (click "Visitor Login")
  Email      : your email because need to enter the OTP
  -----------------------------------------------------------------------

  -----------------------------------------------------------------------
  ROLE       : Delivery Personnel
  Login URL  : https://www.sriayuvisitor.com (click "Delivery Login")
  Email      : your other email because need to enter the OTP
  -----------------------------------------------------------------------

================================================================================
  5. SYSTEM FLOW & WORKFLOW GUIDE
================================================================================

  This section explains the step-by-step workflow for each main process
  in the system. Examiners can follow these flows to test the system.

  ------------------------------------------------------------------------
  FLOW A : VISITOR VISIT REQUEST FLOW
  ------------------------------------------------------------------------

  STEP 1 — Visitor Registers a Visit
    - Open: https://www.sriayuvisitor.com
    - Click "Visitor Login" > then "Register" (if new visitor)
    - Fill in: Full Name, IC Number, Phone, Vehicle Number, Photo
    - After registration, log in as Visitor
    - Click "Request a Visit"
    - Select the resident's unit number
    - Enter the purpose of visit and submit the request
    - Status will show as "Pending"

  STEP 2 — Resident Approves the Visit
    - Log in as Resident: https://www.sriayuvisitor.com
    - Click "Resident Login" and enter resident credentials
    - Go to "Visitor Requests" section
    - Review the visitor's details and click "Approve" or "Reject"

  STEP 3 — Visitor Receives QR Code Pass
    - The visitor can view the QR pass by logging in to the
      system and going to "My Visits" > "View QR Pass"
    - The visitor presents the QR code at the guardhouse upon arrival

  STEP 4 — Guard Scans QR Code at Gate
    - Log in as Guard: https://www.sriayuvisitor.com/guard/login
    - Use Employee ID and Password to log in
    - Go to "Scan QR Code" on the guard dashboard
    - Allow camera access when prompted by the browser
    - Point the camera at the visitor's QR Code pass
    - The system will display the visitor's details
    - automatically "Check In" to record entry

  STEP 5 — Guard Verifies Face (Optional)
    - On the verify visitor page, click "Start Face Recognition"
    - Allow webcam access
    - The system will compare the visitor's live face against their
      registered face photo using face-api.js
    - A MATCH result confirms the visitor's identity

  STEP 6 — Visitor Exits the Premises
    - When the visitor leaves, the guard scans the QR Code again
    - Click "Check Out" to record the exit time
    - The visit session is now closed and logged

  ------------------------------------------------------------------------
  FLOW B : DELIVERY PERSONNEL FLOW
  ------------------------------------------------------------------------

  STEP 1 — Delivery Personnel Registers & Submits Delivery
    - Open: https://www.sriayuvisitor.com
    - Click "Delivery Login" > then "Register" (if new)
    - Fill in: Name, IC, Company, Vehicle Type, Vehicle Number, Email
    - After registration, log in as Delivery Personnel
    - Click "New Delivery Request"
    - Select the resident's unit and enter parcel/delivery details
    - Submit the request — Status will show as "Pending"

  STEP 2 — Resident Approves the Delivery
    - Log in as Resident (same as Flow A, Step 2)
    - Go to "Delivery Requests" on the dashboard
    - Click "Approve" or "Reject"

  STEP 3 — Guard Scans Delivery QR Code
    - Log in as Guard and go to "Scan QR Code"
    - Scan the delivery personnel's QR Code
    - Click "Check In" to allow entry
    - Click "Check Out" when delivery is complete

  ------------------------------------------------------------------------
  FLOW C : ADMIN MANAGEMENT FLOW
  ------------------------------------------------------------------------

  STEP 1 — Admin Logs In
    - Open: https://www.sriayuvisitor.com/admin/login
    - Enter Admin Email and Password

  STEP 2 — Admin Dashboard Overview
    - View total residents, guards, active visits, and system statistics
    - Monitor real-time activity on the dashboard

  STEP 3 — Manage Residents
    - Go to "Residents" in the sidebar
    - Add a new resident by entering name, IC, unit, phone, and email
    - A verification email is sent to the resident to set their password
    - Activate or deactivate resident accounts as needed

  STEP 4 — Manage Guards
    - Go to "Guards" in the sidebar
    - Add a new guard by entering name, Employee ID, IC, shift, and
      setting a password
    - Activate or deactivate guard accounts as needed

  STEP 5 — Manage House Units
    - Go to "House Units" in the sidebar
    - Add or edit unit numbers and floor information

  STEP 6 — View Reports & Logs
    - Go to "Visit Logs" to see all visitor entries and exits
    - Go to "Delivery Logs" to see all delivery activity
    - Go to "Reports" to generate and download summary reports

================================================================================
  6. KEY FEATURES BY ROLE
================================================================================

  ADMIN
  -----
  - View the admin dashboard with system statistics
  - Manage residents, house units, and guard accounts
  - View all visit logs and delivery logs
  - Generate and view system reports

  RESIDENT
  ---------
  - View the resident dashboard
  - Approve or reject incoming visitor requests
  - Approve or reject incoming delivery requests

  GUARD
  -----
  - View the guard dashboard with active visits
  - Scan visitor QR codes to check visitors in/out
  - Perform face recognition verification via webcam
  - View visit records and active logs

  VISITOR
  -------
  - Register a visit request (requires resident approval)
  - Receive a QR Code pass upon approval
  - View visit history and status
  - View the public QR pass for gate scanning

  DELIVERY PERSONNEL
  ------------------
  - Register a delivery request to a resident unit
  - Receive a QR Code pass upon resident approval
  - View delivery history

================================================================================
  7. RECOMMENDED BROWSER
================================================================================

  *** IMPORTANT: Use GOOGLE CHROME for the best experience. ***

  Google Chrome is required for:
    - Face recognition via webcam (face-api.js)
    - QR code scanning features
    - Real-time notifications (WebSocket)

  Do NOT use Internet Explorer. Microsoft Edge and Firefox are
  partially supported but Chrome is strongly recommended.

================================================================================
  8. NOTES FOR EXAMINERS
================================================================================

  1. The system is fully hosted and live — no installation is needed.
     Just open Chrome and go to https://www.sriayuvisitor.com

  2. For face recognition features, please allow the browser to access
     your webcam when prompted.

  3. The QR code scanning feature works best under good lighting.

  4. If any page is slow to load on first visit, please wait a few seconds
     as the server may be waking up from idle state.

================================================================================
  END OF README
================================================================================

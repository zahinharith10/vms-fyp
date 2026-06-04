# Visitor Authentication & Middleware Analysis Report

## 1. VisitorAuthController.php - Full Content Review

**File Location:** [app/Http/Controllers/VisitorAuthController.php](app/Http/Controllers/VisitorAuthController.php)

### Key Methods:

#### sendOtp(Request $request)
- **Purpose:** Sends OTP to visitor's email for authentication
- **Rate Limiting:** 3 requests per 5 minutes per email/IP
- **Process:**
  1. Validates email and login_type ('visitor' or 'delivery')
  2. Prevents cross-registration (email can't be both visitor AND delivery)
  3. Generates random 6-digit OTP
  4. Stores OTP in `visitor_otps` table with 10-minute expiration
  5. Sends email via Mail::to() using OtpMail class
  6. Returns JSON response (success/failure)
- **Status:** ✅ Handles mail exceptions gracefully

#### verifyOtp(Request $request)
- **Purpose:** Validates OTP and authenticates user
- **Process:**
  1. Validates email, otp, login_type
  2. Queries `visitor_otps` table for matching OTP
  3. Checks if OTP hasn't expired
  4. If delivery user: logs in via 'delivery' guard → redirects to delivery.dashboard
  5. If visitor:
     - Queries Visitor model by email
     - If exists: logs in via 'visitor' guard → redirects to visitor.dashboard
     - If not exists: redirects to visitor.register page with email
  6. Regenerates session
- **Status:** ✅ Proper guard selection and session handling

#### create(Request $request)
- **Purpose:** Shows visitor registration form
- **Response:** Inertia render of 'Visitor/Register' view with email/phone from query params
- **Status:** ✅ Simple form rendering

#### store(Request $request)
- **Purpose:** Creates new visitor account and logs them in
- **Validation:**
  - name (required, string, max:255)
  - phone (required, string, unique:visitors)
  - email (required, email, unique in both visitors and delivery_personnels)
  - ic_number (required, string, max:255)
  - vehicle_number (required, string, max:20)
  - face_descriptor (required, JSON)
  - photo (nullable, image, max:2048)
- **Process:**
  1. Uploads photo to 'visitors' storage disk if provided
  2. Creates Visitor record with all fields
  3. JSON encodes face_descriptor
  4. Logs in visitor via 'visitor' guard
  5. Redirects to visitor.dashboard
- **Status:** ✅ All fields stored correctly

#### dashboard()
- **Purpose:** Visitor homepage showing recent visits and unit selector
- **Critical Enforcement:**
  - **Redirects to profile if missing:** name, photo, or ic_number
  - Message: "Please complete your profile details (including IC Number) and upload a photo before proceeding."
- **Data Returned:**
  - Visitor object with recent 3 visits
  - Unit map structure: block → floor → [unit_numbers]
- **Status:** ✅ Proper identity verification enforcement

#### history()
- **Purpose:** Shows all visitor's past visits (paginated)
- **Enforcement:** Same identity verification as dashboard()
- **Status:** ✅ Consistent enforcement

#### profile()
- **Purpose:** Profile update form
- **Returns:** Current visitor data via Inertia
- **Status:** ✅ Simple form view

#### updateProfile(Request $request)
- **Purpose:** Updates visitor profile information
- **Validation:**
  - name, phone, ic_number, vehicle_number (required)
  - phone: unique per visitor
  - face_descriptor (nullable)
  - photo (nullable, image)
- **Process:**
  1. Gets current authenticated visitor
  2. Updates all fields
  3. JSON encodes face_descriptor if provided
  4. Uploads new photo if provided
  5. Saves model
  6. Redirects with success message
- **Status:** ✅ Proper validation and file handling

#### destroy(Request $request)
- **Purpose:** Logout visitor
- **Process:**
  1. Logs out via 'visitor' guard
  2. Invalidates session
  3. Regenerates CSRF token
  4. Redirects to home
- **Status:** ✅ Proper session cleanup

#### showQr(Visit $visit)
- **Purpose:** Shows QR code for checking in to visited unit
- **Enforcement:**
  - Identity verification (name, photo, ic_number required)
  - Visit status must be: 'Approved', 'Checked In', or 'Temporarily Out'
- **Security:** Implicit visitor ownership check via model binding
- **Status:** ⚠️ POTENTIAL ISSUE: No explicit visitor ID check in controller code

#### showPublicPass($token)
- **Purpose:** Public guest entry pass display (not requiring auth)
- **Uses:** QR token lookup
- **Status:** ✅ Accessible to public

---

## 2. Visitor Guard Configuration (auth.php)

### Guard Definition:
```php
'visitor' => [
    'driver' => 'session',
    'provider' => 'visitors',
],
```

### Provider Definition:
```php
'visitors' => [
    'driver' => 'eloquent',
    'model' => App\Models\Visitor::class,
],
```

**Status:** ✅ Correctly configured for session-based authentication

---

## 3. Visitor Routes & Middleware Protection

**File:** [routes/web.php](routes/web.php) (Lines 195-225)

### Route Group: `visitor.*`

#### Public Routes (No Auth Required):
- `POST /otp/send` → VisitorAuthController@sendOtp
  - Middleware: throttle:5,1 (5 requests per minute)
- `POST /otp/verify` → VisitorAuthController@verifyOtp
  - Middleware: throttle:5,1
- `GET /pass/{token}` → VisitorAuthController@showPublicPass (public entry pass)

#### Guest-Only Routes (`middleware('guest:visitor')`):
- `GET /register` → VisitorAuthController@create
- `POST /register` → VisitorAuthController@store
  - Middleware: throttle:10,1 (10 requests per minute)

#### Protected Routes (`middleware('auth:visitor')`):
```
GET  /visitor/dashboard           → VisitorAuthController@dashboard
POST /visitor/visits              → VisitController@store          ← 500 ERROR HERE
GET  /visitor/visits/history      → VisitorAuthController@history
DELETE /visitor/visits/{visit}    → VisitController@destroy
GET  /visitor/visits/{visit}/qr   → VisitorAuthController@showQr
GET  /visitor/profile             → VisitorAuthController@profile
PATCH /visitor/profile            → VisitorAuthController@updateProfile
GET  /visitor/manual              → Manual page (Inertia render)
POST /visitor/logout              → VisitorAuthController@destroy
```

---

## 4. Visit Submission Handler - VisitController@store()

**File:** [app/Http/Controllers/VisitController.php](app/Http/Controllers/VisitController.php)

### Request Flow:
1. **Unit Number Normalization:**
   - Removes spaces around hyphens: "1 - 2 - 5" → "1-2-5"
   - Converts numeric parts: "01-02-05" → "1-2-5"

2. **Identity Verification (Pre-check):**
   ```php
   if (empty($visitor->ic_number) || empty($visitor->photo)) {
       return redirect()->route('visitor.profile')->with('info', '...');
   }
   ```
   - Redirects to profile if missing IC or photo

3. **Active Visit Check:**
   ```php
   $activeVisit = Visit::where('visitor_id', $visitor->id)
       ->whereIn('status', ['Pending', 'Approved', 'Checked In'])
       ->first();
   ```
   - Prevents multiple concurrent active visits

4. **Form Validation:**
   - unit_number: Must be format "Block-Floor-Unit" with positive integers
   - Validates unit exists in HouseUnit table
   - purpose: Required string
   - host_name: Required string, max 255

5. **Visit Record Creation:**
   ```php
   $visit = Visit::create([
       'visitor_id' => Auth::guard('visitor')->id(),
       'unit_number' => $request->unit_number,
       'purpose' => $request->purpose,
       'host_name' => $request->host_name,
       'status' => 'Pending',
   ]);
   ```

6. **Resident Notification:**
   - Parses unit_number to find HouseUnit
   - Gets first resident of that unit
   - Sends VisitRequestNotification
   - Broadcasts NewVisitRequested event

7. **Response:**
   ```php
   return redirect()->back()->with('success', 'Visit request submitted successfully!');
   ```

### ⚠️ POTENTIAL ISSUES:

#### Issue 1: Redirect Response for POST Request
**Problem:** `redirect()->back()` returns redirect response, not JSON
- If frontend expects JSON, 500 error could occur if response type mismatch
- Inertia may not handle form POSTs correctly if expecting JSON

**Impact:** Could cause 500 error if frontend is AJAX-based

**Solution:** Should return Inertia response for Inertia forms

#### Issue 2: Response Handling
**Current:** `redirect()->back()` - relies on HTTP referrer
- If no referrer header, redirects to home `/`
- Might not work for API/mobile clients

#### Issue 3: Broadcasting Exception Handling
```php
try {
    broadcast(new NewVisitRequested(...));
} catch (\Exception $e) {
    \Illuminate\Support\Facades\Log::warning('Broadcasting failed: ' . $e->getMessage());
}
```
- Handles broadcast failures gracefully (won't cause 500)
- But note: Reverb/Pusher connection failures are logged

---

## 5. Custom Middleware Files

### HandleInertiaRequests.php

**Location:** [app/Http/Middleware/HandleInertiaRequests.php](app/Http/Middleware/HandleInertiaRequests.php)

**Purpose:** Shares authentication data and flash messages with all Inertia responses

**Guard Detection Logic:**
```php
if ($request->is('visitor*')) {
    $guards = ['visitor'];
}

foreach ($guards as $guard) {
    if (auth($guard)->check()) {
        $user = auth($guard)->user();
        break;
    }
}
```

**Shared Props:**
```php
'auth' => [
    'user' => $user,
    'role' => $role,
],
'flash' => [
    'success' => $request->session()->get('success'),
    'error' => $request->session()->get('error'),
    'info' => $request->session()->get('info'),
    'message' => $request->session()->get('message'),
]
```

**Status:** ✅ Correctly prioritizes visitor guard for /visitor* routes

### InactivityTimeout.php

**Location:** [app/Http/Middleware/InactivityTimeout.php](app/Http/Middleware/InactivityTimeout.php)

**Visitor Timeout:** 15 minutes

**Process:**
- Checks `session('last_activity_visitor')`
- If idle > 15 min, logs out and redirects to login
- No custom redirect for visitor (would use default)

**Status:** ✅ Properly configured

---

## 6. Configuration Issues & Analysis

### Auth Configuration (config/auth.php)

**✅ Correct:**
- Visitor guard uses 'session' driver
- Provider correctly maps to Visitor model
- Guard and provider names match route middleware

**⚠️ Observation:**
- No password reset configuration for visitors
- Uses basic session authentication

---

## 7. Root Cause Analysis for 500 Error

### Most Likely Causes:

#### 1. **Response Type Mismatch** (HIGH PROBABILITY)
- VisitController@store() returns HTML redirect
- Inertia forms expect JSON response
- **Fix:** Change to Inertia response

**Current Code:**
```php
return redirect()->back()->with('success', 'Visit request submitted successfully!');
```

**Should Be:**
```php
return back()->with('success', 'Visit request submitted successfully!');
// OR for Inertia:
return inertia()->location(route('visitor.dashboard'));
```

#### 2. **Missing Guard Assignment** (MEDIUM PROBABILITY)
- POST route may not have auth:visitor guard properly applied
- Could cause Auth::guard('visitor')->user() to return null
- **Fix:** Verify route is in `auth:visitor` group

**Code causing potential issue (Line 32):**
```php
$visitor = Auth::guard('visitor')->user();
if (empty($visitor->ic_number) || empty($visitor->photo)) {
    return redirect()->route('visitor.profile')->with('info', '...');
}
```

#### 3. **Exception in Visit Creation** (MEDIUM PROBABILITY)
- Validation might fail on specific edge case
- Notification sending might fail
- Broadcasting might throw uncaught exception

**Fix:** Add error handling

#### 4. **Session/Database Connection** (LOW PROBABILITY)
- Previous logs showed MySQL connection refused error
- If MySQL reconnects intermittently, could cause 500 during session read

---

## 8. Recommended Fixes

### Fix 1: Change Response Type in VisitController
```php
// app/Http/Controllers/VisitController.php line ~120
// BEFORE:
return redirect()->back()->with('success', 'Visit request submitted successfully!');

// AFTER (if using Inertia):
// Simply keep the redirect, but ensure it's working with Inertia
return back()->with('success', 'Visit request submitted successfully!');

// OR if this is an API endpoint:
return response()->json([
    'success' => true,
    'message' => 'Visit request submitted successfully!',
    'visit_id' => $visit->id
], 201);
```

### Fix 2: Add Response Type Detection
```php
// Check if this is an AJAX request
if ($request->expectsJson()) {
    return response()->json([
        'success' => true,
        'message' => 'Visit request submitted successfully!',
        'visit_id' => $visit->id
    ], 201);
}

// Normal form submission
return back()->with('success', 'Visit request submitted successfully!');
```

### Fix 3: Add Error Handling Around Notification
```php
try {
    if ($resident) {
        $resident->notify(new VisitRequestNotification($visit->load('visitor')));
        try {
            broadcast(new NewVisitRequested(
                $visit->id,
                $visitor->name,
                $request->unit_number,
                $request->purpose
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcasting failed: ' . $e->getMessage());
        }
    }
} catch (\Exception $e) {
    \Illuminate\Support\Facades\Log::error('Notification failed: ' . $e->getMessage());
    // Notification failure shouldn't stop visit creation
}
```

### Fix 4: Verify Route Guard
In [routes/web.php](routes/web.php), ensure the route is within `auth:visitor` middleware:
```php
Route::middleware('auth:visitor')->group(function () {
    Route::post('/visitor/visits', [App\Http\Controllers\VisitController::class, 'store'])->name('visits.store');
    // ... other routes
});
```

---

## 9. Summary Table

| Component | Status | Issue | Severity |
|-----------|--------|-------|----------|
| VisitorAuthController | ✅ OK | None identified | - |
| Visitor Guard Config | ✅ OK | None identified | - |
| Routes Protection | ✅ OK | None identified | - |
| VisitController@store | ⚠️ ISSUE | Response type mismatch (redirect vs JSON) | HIGH |
| HandleInertiaRequests | ✅ OK | None identified | - |
| InactivityTimeout | ✅ OK | None identified | - |
| Broadcasting Error Handling | ✅ OK | Handled gracefully | - |
| Notification Error Handling | ⚠️ WARNING | Not wrapped in try-catch | MEDIUM |

---

## 10. Next Steps

1. **Verify Request Type:** Check if frontend sends form POST or AJAX request
2. **Check Browser Console:** Look for 'X-Requested-With: XMLHttpRequest' header
3. **Test with Redirect:** Change VisitController to use proper redirect response
4. **Add Logging:** Log the exact error message from 500 response
5. **Test Error Scenarios:** 
   - Missing IC/photo
   - Active visit exists
   - Invalid unit number
   - Notification failures

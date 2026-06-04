# 500 Error Diagnosis - POST /visitor/visits Route

## Quick Summary

The `/visitor/visits` POST route is protected by `auth:visitor` middleware and handled by `VisitController@store()`. The controller logic appears sound, but **the most likely cause of a 500 error is a response type mismatch between what Inertia expects and what the controller returns**.

---

## Component Status Check

### ✅ Properly Configured
- **Visitor Guard:** Correctly set to 'session' driver with Visitor model provider
- **Route Protection:** Route is inside `Route::middleware('auth:visitor')->group()`
- **Resident Model:** Has `Notifiable` trait for notification support
- **HouseUnit Relationship:** `residents()` hasMany relationship is properly defined

### ⚠️ Potential Issues

#### 1. **Response Type Mismatch** (PRIMARY SUSPECT)

**Location:** [VisitController.php line 117](app/Http/Controllers/VisitController.php#L117)

```php
return redirect()->back()->with('success', 'Visit request submitted successfully!');
```

**Problem:** 
- If the frontend sends the form via Inertia (which may use AJAX), it expects a JSON response
- Returning an HTML redirect could cause a 500 error if Inertia can't parse the response
- The error would occur when the frontend tries to process the redirect response as JSON

**Symptoms:**
- 500 error only on POST, not on form page load
- Works in raw form submission but fails in AJAX
- Browser shows redirect loop or parsing error

**Fix:**
```php
// Option 1: Simple redirect (works if form is traditional)
return back()->with('success', 'Visit request submitted successfully!');

// Option 2: JSON response for API clients
if ($request->expectsJson()) {
    return response()->json([
        'success' => true,
        'message' => 'Visit request submitted successfully!',
        'visit_id' => $visit->id,
        'redirect' => route('visitor.dashboard')
    ], 201);
}
return back()->with('success', 'Visit request submitted successfully!');

// Option 3: Inertia response
return back()->with('success', 'Visit request submitted successfully!');
```

---

#### 2. **Null Reference Error** (SECONDARY SUSPECT)

**Location:** [VisitController.php line 32](app/Http/Controllers/VisitController.php#L32)

```php
$visitor = Auth::guard('visitor')->user();
if (empty($visitor->ic_number) || empty($visitor->photo)) {
    // ...
}
```

**Scenario:** Although unlikely (route has auth:visitor), if `$visitor` is somehow null:
- Accessing `$visitor->ic_number` would throw "Trying to get property of null" error
- This would cause a 500 error

**Status:** Very unlikely because route is protected, but should add defensive check

**Fix:**
```php
$visitor = Auth::guard('visitor')->user();
if (!$visitor) {
    return redirect()->route('visitor.profile')->with('error', 'Authentication failed. Please log in again.');
}

if (empty($visitor->ic_number) || empty($visitor->photo)) {
    return redirect()->route('visitor.profile')->with('info', 'Please complete your profile details and upload a photo before requesting a visit.');
}
```

---

#### 3. **Unhandled Notification Exception** (MEDIUM SUSPECT)

**Location:** [VisitController.php line 101](app/Http/Controllers/VisitController.php#L101)

```php
$resident->notify(new VisitRequestNotification($visit->load('visitor')));
```

**Problem:**
- This line is NOT wrapped in try-catch
- If notification fails, it throws unhandled exception → 500 error
- Broadcasting errors ARE handled, but notification isn't

**Scenarios that could fail:**
- Resident record deleted between query and notification
- Notification channel configuration error
- Database connection issue during notification

**Status:** Possible but less likely (broadcast failures are handled)

**Fix:**
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
    \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
    // Don't fail the request if notification fails
}
```

---

#### 4. **Validation Exception Handling** (LOW SUSPECT)

**Location:** [VisitController.php line 46](app/Http/Controllers/VisitController.php#L46)

```php
$request->validate([
    'unit_number' => [
        'required',
        'string',
        function ($attribute, $value, $fail) {
            // Custom validation
        },
    ],
    'purpose' => 'required|string',
    'host_name' => 'required|string|max:255',
]);
```

**Problem:**
- If validation fails, Laravel automatically returns 422 response (not 500)
- Custom validation rule might throw exception instead of calling $fail()

**Status:** Would return 422, not 500, so probably not the issue

---

#### 5. **Database Connection Intermittent Failure** (LOW SUSPECT)

**Previous Logs Show:**
```
[2026-06-02 10:22:45] local.ERROR: SQLSTATE[HY000] [2002] No connection could be made 
because the target machine actively refused it
```

**Problem:**
- If MySQL becomes unavailable during request processing
- Any database query in VisitController would fail

**Status:** Previous tests confirmed DB is working now; unlikely unless MySQL is intermittently failing

---

## Diagnostic Steps

### Step 1: Check Response Type
```bash
# In browser DevTools, Network tab, check the POST /visitor/visits response
# Look at:
# 1. Response headers (Content-Type: application/json vs text/html)
# 2. Response body (is it HTML redirect or JSON?)
# 3. Response status (500? 422? 302?)
```

### Step 2: Check Error Logs
```bash
# In storage/logs/laravel.log, search for the exact 500 error
# Look for:
tail -f storage/logs/laravel.log | grep -A 10 "500 ERROR"
```

### Step 3: Test with Raw Form Submission
```html
<!-- Create a simple form test (no AJAX) -->
<form method="POST" action="/visitor/visits">
    @csrf
    <input type="hidden" name="unit_number" value="1-2-5">
    <input type="hidden" name="purpose" value="Test Visit">
    <input type="hidden" name="host_name" value="Test Host">
    <button type="submit">Submit</button>
</form>
```

### Step 4: Enable Debug Mode
In `.env`:
```env
APP_DEBUG=true
```

This will show the actual error stack trace instead of generic 500 error.

### Step 5: Add Temporary Logging
In VisitController@store(), add at the beginning:
```php
\Illuminate\Support\Facades\Log::info('Visit submission started');
\Illuminate\Support\Facades\Log::info('Visitor: ' . json_encode($visitor));
\Illuminate\Support\Facades\Log::info('Request data: ' . json_encode($request->all()));
```

---

## Most Likely Resolution

**Based on the architecture using Inertia.js:**

1. The form is likely submitted via Inertia (AJAX)
2. Inertia expects a redirect response to be returned as JSON with redirect URL
3. Returning `redirect()->back()` as HTML when Inertia expects JSON causes 500 error

**Quick Test:**
```php
// In VisitController@store(), line 117, change from:
return redirect()->back()->with('success', 'Visit request submitted successfully!');

// To:
return back()->with('success', 'Visit request submitted successfully!');
```

This ensures Laravel's `back()` helper properly formats the response for Inertia.

---

## Files for Reference
- [VisitController.php](app/Http/Controllers/VisitController.php) - Main controller with store() method
- [VisitorAuthController.php](app/Http/Controllers/VisitorAuthController.php) - Auth flow reference
- [routes/web.php](routes/web.php#L207) - Route definition
- [app/Notifications/VisitRequestNotification.php](app/Notifications/VisitRequestNotification.php) - Notification handler
- [config/auth.php](config/auth.php) - Guard configuration

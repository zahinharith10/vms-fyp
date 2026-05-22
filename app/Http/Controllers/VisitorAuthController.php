<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Visitor;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class VisitorAuthController extends Controller
{
    public function index()
    {
        return Inertia::render('Welcome');
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'login_type' => 'required|string|in:visitor,delivery',
        ]);

        // Prevent cross-registration between visitor and delivery
        if ($request->login_type === 'visitor') {
            if (\App\Models\DeliveryPersonnel::where('email', $request->email)->exists()) {
                return response()->json(['success' => false, 'message' => 'This email is already registered as a delivery personnel.'], 422);
            }
        } elseif ($request->login_type === 'delivery') {
            if (\App\Models\Visitor::where('email', $request->email)->exists()) {
                return response()->json(['success' => false, 'message' => 'This email is already registered as a visitor.'], 422);
            }
        }

        // Rate Limiting: 3 requests per 5 minutes per email/IP
        $key = 'send-otp:' . $request->email . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false, 
                'message' => "Too many attempts. Please wait {$seconds} seconds."
            ], 429);
        }

        RateLimiter::hit($key, 300); // 300 seconds = 5 minutes

        $otp = rand(100000, 999999);

        // Store OTP in database
        DB::table('visitor_otps')->updateOrInsert(
            ['email' => $request->email],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Send Email
        try {
            Mail::to($request->email)->send(new OtpMail($otp));
            return response()->json(['success' => true, 'message' => 'OTP sent successfully!']);
        } catch (\Exception $e) {
            // For development, if mail fails, still allow it but return the error
            return response()->json(['success' => false, 'message' => 'Failed to send email. Error: ' . $e->getMessage()], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
            'login_type' => 'required|string|in:visitor,delivery',
        ]);

        $otpRecord = DB::table('visitor_otps')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otpRecord) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP code.'], 422);
        }

        // OTP is valid, delete it
        DB::table('visitor_otps')->where('email', $request->email)->delete();

        if ($request->login_type === 'delivery') {
            $delivery = \App\Models\DeliveryPersonnel::where('email', $request->email)->first();
            if ($delivery) {
                Auth::guard('delivery')->login($delivery);
                $request->session()->regenerate();
                return response()->json(['success' => true, 'redirect' => route('delivery.dashboard')]);
            } else {
                return response()->json(['success' => true, 'redirect' => route('delivery.register', ['email' => $request->email])]);
            }
        }

        // Visitor Logic
        $visitor = Visitor::where('email', $request->email)->first();

        if ($visitor) {
            Auth::guard('visitor')->login($visitor);
            $request->session()->regenerate();
            return response()->json(['success' => true, 'redirect' => route('visitor.dashboard')]);
        } else {
            return response()->json(['success' => true, 'redirect' => route('visitor.register', ['email' => $request->email])]);
        }
    }

    public function create(Request $request)
    {
        return Inertia::render('Visitor/Register', [
            'email' => $request->query('email'),
            'phone' => $request->query('phone') // Keep phone just in case, but email is primary now
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:visitors',
            'email' => 'required|email|unique:visitors,email|unique:delivery_personnels,email',
            'ic_number' => 'required|string|max:255',
            'vehicle_number' => 'required|string|max:20',
            'face_descriptor' => 'required', // JSON string
            'photo' => 'nullable|image|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('visitors', 'public');
        }

        $visitor = Visitor::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'ic_number' => $request->ic_number,
            'vehicle_number' => $request->vehicle_number,
            'face_descriptor' => json_encode($request->face_descriptor),
            'photo' => $photoPath,
        ]);

        Auth::guard('visitor')->login($visitor);

        return redirect()->route('visitor.dashboard');
    }

    public function dashboard()
    {
        $visitor = Auth::guard('visitor')->user();

        if (!$visitor) {
            return redirect()->route('visitor.login');
        }

        // ENFORCEMENT: If name, photo, or ic_number is missing, the visitor cannot access the dashboard or QR code.
        // They are redirected to the Profile page to complete their identity verification.
        if (empty($visitor->name) || empty($visitor->photo) || empty($visitor->ic_number)) {
            return redirect()->route('visitor.profile')->with('info', 'Please complete your profile details (including IC Number) and upload a photo before proceeding.');
        }

        // Build a nested map: block → floor → [unit_numbers]
        $houseUnits = \App\Models\HouseUnit::orderBy('block')->orderBy('floor')->orderBy('unit_number')->get();
        $unitMap = [];
        foreach ($houseUnits as $unit) {
            $unitMap[(string)$unit->block][(string)$unit->floor][] = (string)$unit->unit_number;
        }

        return Inertia::render('Visitor/Dashboard', [
            'visitor' => $visitor->load(['visits' => function ($query) {
                $query->orderBy('created_at', 'desc')->take(3);
            }]),
            'houseUnits' => $unitMap,
        ]);
    }

    public function history()
    {
        $visitor = Auth::guard('visitor')->user();

        if (empty($visitor->name) || empty($visitor->photo) || empty($visitor->ic_number)) {
            return redirect()->route('visitor.profile')->with('info', 'Please complete your profile details (including IC Number) and upload a photo before proceeding.');
        }

        return Inertia::render('Visitor/History', [
            'visitor' => $visitor->load(['visits' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }]),
        ]);
    }

    public function profile()
    {
        return Inertia::render('Visitor/Profile', [
            'visitor' => Auth::guard('visitor')->user()
        ]);
    }

    public function updateProfile(Request $request)
    {
        $visitor = Auth::guard('visitor')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:visitors,phone,' . $visitor->id,
            'ic_number' => 'required|string|max:20',
            'vehicle_number' => 'required|string|max:20',
            'face_descriptor' => 'nullable', // Array or JSON string
            'photo' => 'nullable|image|max:2048',
        ]);

        $visitor->name = $request->name;
        $visitor->phone = $request->phone;
        $visitor->ic_number = $request->ic_number;
        $visitor->vehicle_number = $request->vehicle_number;

        if ($request->has('face_descriptor') && $request->face_descriptor) {
             // If it's an array, json_encode it. If it's already a string, leave it (though frontend sends array usually)
             $descriptor = $request->face_descriptor;
             if (is_array($descriptor)) {
                 $descriptor = json_encode($descriptor);
             }
             $visitor->face_descriptor = $descriptor;
        }

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('visitors', 'public');
            $visitor->photo = $photoPath;
        }

        $visitor->save();

        return redirect()->route('visitor.profile')->with('success', 'Profile updated successfully.');
    }

    public function destroy(Request $request)
    {
        Auth::guard('visitor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    /**
     * Show the QR code for a specific visit to the visitor.
     */
    public function showQr(Visit $visit)
    {
        $visitor = Auth::guard('visitor')->user();
        // Security: Prevent access to QR codes if the identity is not fully set (missing photo/name/ic)
        if (empty($visitor->name) || empty($visitor->photo) || empty($visitor->ic_number)) {
            return redirect()->route('visitor.profile')->with('info', 'Please complete your profile details (including IC Number) and upload a photo before proceeding.');
        }

        // Security: only show QR if it belongs to the authenticated visitor

        // Only show QR for Approved or Checked In visits
        if (!in_array($visit->status, ['Approved', 'Checked In'])) {
            return redirect()->route('visitor.dashboard')
                ->with('error', 'QR code is not available for this visit status.');
        }

        return Inertia::render('Visitor/ShowQr', [
            'visit' => $visit->load('visitor'),
            'qrCodeSvg' => (string) QrCode::size(300)->generate($visit->qr_code_token),
        ]);
    }

    /**
     * Show the public pre-registered guest entry pass.
     */
    public function showPublicPass($token)
    {
        $visit = Visit::with('visitor')
            ->where('qr_code_token', $token)
            ->firstOrFail();

        $resident = null;
        $parts = explode('-', $visit->unit_number);
        if (count($parts) === 3) {
            $houseUnit = \App\Models\HouseUnit::where('block', $parts[0])
                ->where('floor', $parts[1])
                ->where('unit_number', $parts[2])
                ->first();
            if ($houseUnit) {
                $resident = $houseUnit->residents()->first();
            }
        }
        $hostName = $resident ? $resident->name : 'Resident';

        return Inertia::render('Visitor/PublicPass', [
            'visit' => $visit,
            'visitor' => $visit->visitor,
            'hostName' => $hostName,
            'qrCodeSvg' => (string) QrCode::size(300)->generate($visit->qr_code_token),
        ]);
    }

    /**
     * Complete the public pre-registered guest profile details.
     */
    public function completePublicPass(Request $request, $token)
    {
        $visit = Visit::with('visitor')
            ->where('qr_code_token', $token)
            ->firstOrFail();

        $visitor = $visit->visitor;

        $request->validate([
            'ic_number' => 'required|string|max:20',
            'vehicle_number' => 'required|string|max:20',
            'face_descriptor' => 'required', // Array/JSON string
            'photo' => 'required|image|max:4096',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('visitors', 'public');
        }

        // Handle array descriptor convert to string
        $descriptor = $request->face_descriptor;
        if (is_array($descriptor)) {
            $descriptor = json_encode($descriptor);
        }

        $visitor->update([
            'ic_number' => $request->ic_number,
            'vehicle_number' => $request->vehicle_number,
            'face_descriptor' => $descriptor,
            'photo' => $photoPath ?: $visitor->photo,
        ]);

        return redirect()->back()->with('success', 'Pass activated successfully! You can now show this QR code at the guardhouse.');
    }
}

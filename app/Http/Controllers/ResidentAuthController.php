<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\Rules\Password;

class ResidentAuthController extends Controller
{
    public function create()
    {
        return Inertia::render('Resident/Login');
    }

    public function dashboard()
    {
        $resident = Auth::guard('resident')->user();
        // Eager load the house unit for the view
        $resident->load('houseUnit');

        $unitNumber = $resident->houseUnit->formatted_unit;
        $deliveryUnitNumber = $resident->houseUnit->formatted_unit;

        $stats = [
            'total_visitors' => \App\Models\Visit::where('unit_number', $unitNumber)->count(),
            'pending_visitors' => \App\Models\Visit::where('unit_number', $unitNumber)->where('status', 'Pending')->count(),
            'pending_deliveries' => \App\Models\DeliveryLog::where('destination', $deliveryUnitNumber)->where('status', 'Pending')->count(),
            'active_visitors' => \App\Models\Visit::where('unit_number', $unitNumber)->where('status', 'Checked In')->count() + \App\Models\DeliveryLog::where('destination', $deliveryUnitNumber)->whereNotNull('entry_time')->whereNull('exit_time')->count(),
            'upcoming_visitors' => \App\Models\Visit::where('unit_number', $unitNumber)->where('status', 'Approved')->count(),
            'upcoming_deliveries' => \App\Models\DeliveryLog::where('destination', $deliveryUnitNumber)->where('status', 'Approved')->whereNull('entry_time')->count(),
        ];
        
        $stats['pending_requests'] = $stats['pending_visitors'] + $stats['pending_deliveries'];
        
        return Inertia::render('Resident/Dashboard', [
            'stats' => $stats
        ]);
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $resident = \App\Models\Resident::where('email', $credentials['email'])->first();

        if ($resident && $resident->status !== 'active') {
            return back()->withErrors([
                'email' => 'Your account is currently inactive. Please check your email and verify your address using the welcome link we sent.',
            ]);
        }

        $credentials['status'] = 'active';

        if (Auth::guard('resident')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('resident.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function verify($token)
    {
        $resident = \App\Models\Resident::where('verification_token', $token)->first();

        if (!$resident) {
            return redirect()->route('resident.login')->with('error', 'The verification link is invalid or has expired.');
        }

        $resident->update([
            'status' => 'active',
            'verification_token' => null,
        ]);

        return redirect()->route('resident.login')->with('success', 'Your Sri Ayu account has been verified successfully! You can now log in.');
    }

    public function profile()
    {
        $resident = Auth::guard('resident')->user()->load('houseUnit');

        return Inertia::render('Resident/Profile', [
            'resident' => $resident,
        ]);
    }

    public function family()
    {
        $resident = Auth::guard('resident')->user()->load('houseUnit');

        if ($resident->type !== 'owner') {
            abort(403, 'Unauthorized action. Only unit owners can view household family members.');
        }

        $familyMembers = \App\Models\Resident::where('house_unit_id', $resident->house_unit_id)
            ->where('id', '!=', $resident->id)
            ->where('type', 'family')
            ->get(['id', 'name', 'phone', 'email', 'ic_number', 'type', 'status']);

        return Inertia::render('Resident/Family', [
            'resident' => $resident,
            'familyMembers' => $familyMembers,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $resident = Auth::guard('resident')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:residents,email,' . $resident->id,
            'phone' => 'required|string|max:20',
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'auto_approve_deliveries' => 'nullable|boolean',
        ]);

        $resident->name = $request->name;
        $resident->email = $request->email;
        $resident->phone = $request->phone;

        if ($resident->type === 'owner') {
            $resident->auto_approve_deliveries = $request->boolean('auto_approve_deliveries');
        } else {
            $resident->auto_approve_deliveries = false;
        }

        if ($request->filled('password')) {
            $resident->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $resident->save();

        return redirect()->route('resident.profile')->with('success', 'Profile updated successfully.');
    }

    public function destroy(Request $request)
    {
        Auth::guard('resident')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('welcome');
    }
}

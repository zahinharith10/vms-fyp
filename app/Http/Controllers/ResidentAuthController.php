<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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

        $unitNumber = $resident->houseUnit->block . '-' . $resident->houseUnit->floor . '-' . $resident->houseUnit->unit_number;

        $stats = [
            'total_visitors' => \App\Models\Visit::where('unit_number', $unitNumber)->count(),
            'pending_visitors' => \App\Models\Visit::where('unit_number', $unitNumber)->where('status', 'Pending')->count(),
            'pending_deliveries' => \App\Models\DeliveryLog::where('destination', $unitNumber)->where('status', 'Pending')->count(),
            'active_visitors' => \App\Models\Visit::where('unit_number', $unitNumber)->where('status', 'Checked In')->count() + \App\Models\DeliveryLog::where('destination', $unitNumber)->whereNotNull('entry_time')->whereNull('exit_time')->count(),
            'upcoming_visits' => \App\Models\Visit::where('unit_number', $unitNumber)->where('status', 'Approved')->count(),
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
        return Inertia::render('Resident/Profile', [
            'resident' => Auth::guard('resident')->user()->load('houseUnit')
        ]);
    }

    public function updateProfile(Request $request)
    {
        $resident = Auth::guard('resident')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:residents,email,' . $resident->id,
            'phone' => 'required|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'auto_approve_deliveries' => 'nullable|boolean',
        ]);

        $resident->name = $request->name;
        $resident->email = $request->email;
        $resident->phone = $request->phone;
        $resident->auto_approve_deliveries = $request->boolean('auto_approve_deliveries');

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

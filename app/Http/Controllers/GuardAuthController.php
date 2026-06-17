<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\Rules\Password;
use App\Services\RecaptchaService;

class GuardAuthController extends Controller
{
    public function create()
    {
        return Inertia::render('Guard/Login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'recaptcha_token' => ['required'],
        ]);

        if (!RecaptchaService::verify($request->recaptcha_token)) {
            return back()->withErrors([
                'recaptcha' => 'reCAPTCHA verification failed. Please try again.',
            ])->onlyInput('employee_id');
        }

        $credentials = $request->validate([
            'employee_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('guard')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('guard.dashboard'));
        }

        return back()->withErrors([
            'employee_id' => 'The provided credentials do not match our records.',
        ]);
    }

    public function profile()
    {
        return Inertia::render('Guard/Profile', [
            'guard' => Auth::guard('guard')->user()
        ]);
    }

    public function updateProfile(Request $request)
    {
        $guard = Auth::guard('guard')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:guards,email,' . $guard->id,
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^(?:\+?6)?01[0-9](?:[- ]?\d){7,8}$/'
            ],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'phone.regex' => 'The phone number must be a valid Malaysian mobile number (e.g. 012-3456789 or 011-12345678).',
        ]);

        $guard->name = $request->name;
        $guard->email = $request->email;
        $guard->phone = $request->phone;

        if ($request->filled('password')) {
            $guard->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $guard->save();

        return redirect()->route('guard.profile')->with('success', 'Profile updated successfully.');
    }

    public function destroy(Request $request)
    {
        Auth::guard('guard')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/guard/login');
    }
}

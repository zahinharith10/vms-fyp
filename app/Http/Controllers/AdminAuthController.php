<?php

namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Inertia\Inertia;
    use Illuminate\Http\RedirectResponse;
    use Illuminate\Validation\Rules\Password;
    use App\Services\RecaptchaService;

    class AdminAuthController extends Controller
    {
        public function create()
        {
            return Inertia::render('Admin/Login');
        }

        public function store(Request $request): RedirectResponse
        {
            // Temporarily disable reCAPTCHA to test login
            // \Log::info('Login attempt with data: ', $request->all());

            // $request->validate([
            //     'recaptcha_token' => ['required'],
            // ]);

            // \Log::info('reCAPTCHA token received: ' . substr($request->recaptcha_token, 0, 50) . '...');

            // if (!RecaptchaService::verify($request->recaptcha_token)) {
            //     \Log::warning('reCAPTCHA verification failed');
            //     return back()->withErrors([
            //         'recaptcha' => 'reCAPTCHA verification failed. Please try again.',
            //     ])->onlyInput('email');
            // }

            // \Log::info('reCAPTCHA verification passed');

            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            $credentials['status'] = 'active';

            if (Auth::guard('admin')->attempt($credentials)) {
                $request->session()->regenerate();

                return redirect()->intended(route('admin.dashboard'));
            }

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        public function destroy(Request $request): RedirectResponse
        {
            Auth::guard('admin')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login');
        }

        public function profile()
        {
            return Inertia::render('Admin/Profile', [
                'admin' => Auth::guard('admin')->user()
            ]);
        }

        public function updateProfile(Request $request)
        {
            $admin = Auth::guard('admin')->user();

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:admins,email,' . $admin->id,
                'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            ]);

            $admin->name = $request->name;
            $admin->email = $request->email;

            if ($request->filled('password')) {
                $admin->password = \Illuminate\Support\Facades\Hash::make($request->password);
            }

            $admin->save();

            return redirect()->route('admin.profile')->with('success', 'Profile updated successfully.');
        }
    }
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InactivityTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $timeout = 15 * 60; // 15 minutes

        // Check if the user is authenticated as a guard
        if (Auth::guard('guard')->check()) {
            // Update last activity but never log them out
            session(['last_activity' => time()]);
            return $next($request);
        }

        // List of other guards subject to timeout
        $guardsToCheck = ['admin', 'resident', 'visitor', 'delivery', 'web'];
        $activeGuard = null;

        foreach ($guardsToCheck as $guardName) {
            if (Auth::guard($guardName)->check()) {
                $activeGuard = $guardName;
                break;
            }
        }

        if ($activeGuard) {
            $lastActivity = session('last_activity');

            if ($lastActivity && (time() - $lastActivity > $timeout)) {
                // Determine redirect route based on guard
                $redirectRoute = '/';
                if ($activeGuard === 'admin') {
                    $redirectRoute = route('admin.login');
                } elseif ($activeGuard === 'resident') {
                    $redirectRoute = route('resident.login');
                } elseif ($activeGuard === 'visitor') {
                    $redirectRoute = route('visitor.login');
                }

                Auth::guard($activeGuard)->logout();
                session()->invalidate();
                session()->regenerateToken();

                return redirect($redirectRoute)->with('error', 'You have been logged out due to inactivity.');
            }

            session(['last_activity' => time()]);
        }

        return $next($request);
    }
}

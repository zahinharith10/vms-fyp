<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InactivityTimeout
{
    /**
     * Inactivity timeout per guard (in seconds).
     * Guards use shared guardhouse terminals → 30 min.
     * All other authenticated roles → 15 min.
     */
    protected array $timeouts = [
        'guard'    => 30 * 60,  // 30 minutes — shared guardhouse device
        'admin'    => 15 * 60,  // 15 minutes
        'resident' => 15 * 60,  // 15 minutes
        'visitor'  => 15 * 60,  // 15 minutes
        'delivery' => 15 * 60,  // 15 minutes
        'web'      => 15 * 60,  // 15 minutes (default Laravel users)
    ];

    /**
     * Redirect routes per guard on timeout.
     */
    protected array $loginRoutes = [
        'guard'    => 'guard.login',
        'admin'    => 'admin.login',
        'resident' => 'resident.login',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->timeouts as $guardName => $timeout) {
            if (!Auth::guard($guardName)->check()) {
                continue;
            }

            $lastActivity = session('last_activity_' . $guardName);

            if ($lastActivity && (time() - $lastActivity > $timeout)) {
                $redirectRoute = isset($this->loginRoutes[$guardName])
                    ? route($this->loginRoutes[$guardName])
                    : '/';

                Auth::guard($guardName)->logout();
                session()->invalidate();
                session()->regenerateToken();

                return redirect($redirectRoute)
                    ->with('error', 'You have been logged out due to inactivity.');
            }

            // Update the per-guard last-activity timestamp
            session(['last_activity_' . $guardName => time()]);
            break; // Only one guard can be active per request
        }

        return $next($request);
    }
}


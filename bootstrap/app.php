<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\InactivityTimeout::class,
        ]);

        //
        $middleware->redirectTo(function ($request) {
            if ($request->is('admin/*')) {
                return route('admin.login');
            }
            if ($request->is('guard/*')) {
                return route('guard.login');
            }
            if ($request->is('resident/*')) {
                return route('resident.login');
            }
            if ($request->is('visitor/*')) {
                // Visitors login at the welcome page
                return route('welcome'); 
            }
            return route('login');
        });

        $middleware->redirectUsersTo(function ($request) {
             if ($request->is('admin/*')) {
                return route('admin.dashboard');
            }
            if ($request->is('guard/*')) {
                return route('guard.dashboard');
            }
            if ($request->is('resident/*')) {
                return route('resident.dashboard');
            }
            if ($request->is('visitor/*') || $request->is('register')) {
                return route('visitor.dashboard');
            }
            return '/dashboard'; // Default for normal users
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
            $headers = $e->getHeaders();
            $seconds = $headers['Retry-After'] ?? 60;
            
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            
            if ($minutes > 0) {
                $waitTime = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ($remainingSeconds > 0 ? ' and ' . $remainingSeconds . ' second' . ($remainingSeconds > 1 ? 's' : '') : '');
            } else {
                $waitTime = $remainingSeconds . ' second' . ($remainingSeconds > 1 ? 's' : '');
            }
            
            $message = "Too many attempts. Please wait {$waitTime} before trying again.";
            
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 429);
            }
            
            return back()->withErrors(['throttle' => $message]);
        });
    })->create();

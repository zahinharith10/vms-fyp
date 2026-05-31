<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Check multiple guards to find the authenticated user
        $user = null;
        $guards = ['admin', 'guard', 'resident', 'visitor', 'delivery', 'web'];

        // Reprioritize based on current URL path to ensure correct user identity in sidebars
        if ($request->is('admin*')) {
            $guards = ['admin'];
        } elseif ($request->is('guard*')) {
            $guards = ['guard'];
        } elseif ($request->is('resident*')) {
            $guards = ['resident'];
        } elseif ($request->is('visitor*')) {
            $guards = ['visitor'];
        } elseif ($request->is('delivery*')) {
            $guards = ['delivery'];
        }
        
        foreach ($guards as $guard) {
            if (auth($guard)->check()) {
                $user = auth($guard)->user();
                break;
            }
        }

        // If resident, load houseUnit relationship
        if ($user instanceof \App\Models\Resident) {
            $user->loadMissing('houseUnit');
        }

        $role = 'guest';
        if ($user instanceof \App\Models\Admin) {
            $role = 'admin';
        } elseif ($user instanceof \App\Models\Resident) {
            $role = 'resident';
        } elseif ($user instanceof \App\Models\Guard) {
            $role = 'guard';
        } elseif ($user instanceof \App\Models\Visitor) {
            $role = 'visitor';
        } elseif ($user instanceof \App\Models\DeliveryPersonnel) {
            $role = 'delivery';
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'role' => $role,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'info' => $request->session()->get('info'),
                'message' => $request->session()->get('message'),
            ],
        ];
    }
}

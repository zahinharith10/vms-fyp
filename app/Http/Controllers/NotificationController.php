<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = $this->getUser();
        
        if (!$user) {
            return response()->json(['notifications' => [], 'unread_count' => 0]);
        }

        return response()->json([
            'notifications' => $user->notifications()->latest()->take(10)->get(),
            'unread_count' => $user->unreadNotifications()->count()
        ]);
    }

    public function markAsRead(Request $request)
    {
        $user = $this->getUser();
        
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    private function getUser()
    {
        if (Auth::guard('resident')->check()) {
            return Auth::guard('resident')->user();
        }
        
        if (Auth::guard('admin')->check()) {
            return Auth::guard('admin')->user();
        }

        return null;
    }
}

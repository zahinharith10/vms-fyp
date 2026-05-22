<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeliveryLog;
use Inertia\Inertia;

class DeliveryLogController extends Controller
{
    public function index()
    {
        $logs = DeliveryLog::with('personnel')->orderBy('created_at', 'desc')->get();
        return Inertia::render('Admin/Delivery/Logs/Index', ['logs' => $logs]);
    }
}

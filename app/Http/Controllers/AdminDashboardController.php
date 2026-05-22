<?php

namespace App\Http\Controllers;

use App\Models\Guard;
use App\Models\Resident;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // 1. Core Counts
        $totalResidents = Resident::count();
        $totalVisitors = Visitor::count();
        $totalGuards = Guard::count();
        
        $activeVisits = Visit::where('status', 'Checked In')->count();
        $activeDeliveries = \App\Models\DeliveryLog::whereNotNull('entry_time')
            ->whereNull('exit_time')
            ->count();
        
        $activeTotal = $activeVisits + $activeDeliveries;

        // 2. Visit Trends (Last 14 Days)
        $startDate = Carbon::now()->subDays(13)->startOfDay();
        $visitsData = Visit::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $trendLabels = [];
        $trendData = [];
        for ($i = 0; $i < 14; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $trendLabels[] = $startDate->copy()->addDays($i)->format('M d');
            $record = $visitsData->firstWhere('date', $date);
            $trendData[] = $record ? $record->count : 0;
        }

        // 3. Visitor Type Distribution (Doughnut)
        $deliveryCount = \App\Models\DeliveryPersonnel::count();
        // Regular visitors are those who are not deliveries (already have totalVisitors)

        // 4. Purpose Breakdown (Bar Chart)
        $purposes = Visit::select('purpose', DB::raw('count(*) as count'))
            ->groupBy('purpose')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // 5. Recent Activity Feed (Logs)
        $recentVisits = Visit::with('visitor')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'name' => $v->visitor->name,
                    'type' => 'Visitor',
                    'status' => $v->status,
                    'time' => $v->updated_at->diffForHumans(),
                    'photo' => $v->visitor->photo,
                ];
            });

        $recentDeliveries = \App\Models\DeliveryLog::with('personnel')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'name' => $d->personnel->name,
                    'type' => 'Delivery',
                    'status' => $d->status,
                    'time' => $d->updated_at->diffForHumans(),
                    'photo' => $d->personnel->photo,
                ];
            });

        $activityFeed = $recentVisits->concat($recentDeliveries)->sortByDesc(function ($item) {
            return $item['id']; // Approximation for latest
        })->values()->take(8);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_residents' => $totalResidents,
                'total_visitors' => $totalVisitors,
                'total_guards' => $totalGuards,
                'active_visitors' => $activeTotal,
            ],
            'charts' => [
                'trends' => [
                    'labels' => $trendLabels,
                    'data' => $trendData,
                ],
                'distribution' => [
                    'labels' => ['Visitors', 'Deliveries'],
                    'data' => [$totalVisitors, $deliveryCount],
                ],
                'purposes' => [
                    'labels' => $purposes->pluck('purpose'),
                    'data' => $purposes->pluck('count'),
                ]
            ],
            'recentActivity' => $activityFeed
        ]);
    }
}

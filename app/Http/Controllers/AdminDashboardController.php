<?php

namespace App\Http\Controllers;

use App\Models\DeliveryLog;
use App\Models\DeliveryPersonnel;
use App\Models\Guard;
use App\Models\HouseUnit;
use App\Models\Resident;
use App\Models\Visit;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $today        = Carbon::today();
        $yesterday    = Carbon::yesterday();
        $start14Days  = Carbon::now()->subDays(13)->startOfDay();

        // ── Core Totals ──────────────────────────────────────────────
        $totalResidents          = Resident::count();
        $ownerCount              = Resident::where('type', 'owner')->count();
        $familyCount             = Resident::where('type', 'family')->count();
        $totalVisitors           = Visitor::count();
        $totalGuards             = Guard::count();
        $activeGuards            = Guard::where('status', 'active')->count();
        $totalHouseUnits         = HouseUnit::count();
        $totalDeliveryPersonnel  = DeliveryPersonnel::count();

        // ── Active Right Now ─────────────────────────────────────────
        $activeVisits     = Visit::where('status', 'Checked In')->count();
        $activeDeliveries = DeliveryLog::whereNotNull('entry_time')->whereNull('exit_time')->count();
        $activeTotal      = $activeVisits + $activeDeliveries;

        // ── Today's Activity ─────────────────────────────────────────
        $todayVisits     = Visit::whereDate('created_at', $today)->count();
        $todayDeliveries = DeliveryLog::whereDate('created_at', $today)->count();
        $yesterdayVisits = Visit::whereDate('created_at', $yesterday)->count();

        // ── Pending Approvals ────────────────────────────────────────
        $pendingVisits     = Visit::where('status', 'Pending')->count();
        $pendingDeliveries = DeliveryLog::where('status', 'Pending')->count();
        $totalPending      = $pendingVisits + $pendingDeliveries;

        // ── Visit + Delivery Trends (Last 14 Days) ───────────────────
        $visitsByDay = Visit::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', $start14Days)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $deliveriesByDay = DeliveryLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', $start14Days)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $trendLabels     = [];
        $trendVisits     = [];
        $trendDeliveries = [];

        for ($i = 0; $i < 14; $i++) {
            $date              = $start14Days->copy()->addDays($i)->format('Y-m-d');
            $trendLabels[]     = $start14Days->copy()->addDays($i)->format('M d');
            $trendVisits[]     = $visitsByDay[$date]     ?? 0;
            $trendDeliveries[] = $deliveriesByDay[$date] ?? 0;
        }

        // ── Visit Purpose Breakdown ──────────────────────────────────
        $purposes = Visit::select('purpose', DB::raw('count(*) as count'))
            ->groupBy('purpose')
            ->orderByDesc('count')
            ->limit(6)
            ->get();

        // ── Guard Shift Distribution ─────────────────────────────────
        $allGuards   = Guard::all(['shift']);
        $shiftCounts = ['morning' => 0, 'afternoon' => 0, 'night' => 0];
        foreach ($allGuards as $g) {
            $shifts = is_array($g->shift) ? $g->shift : [$g->shift];
            foreach ($shifts as $s) {
                $key = strtolower(trim($s ?? ''));
                if (isset($shiftCounts[$key])) {
                    $shiftCounts[$key]++;
                }
            }
        }

        // ── Recent Activity Feed ─────────────────────────────────────
        $recentVisits = Visit::with('visitor')
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn($v) => [
                'id'     => $v->id,
                'name'   => $v->visitor?->name ?? 'Unknown',
                'type'   => 'Visitor',
                'status' => $v->status,
                'time'   => $v->updated_at->diffForHumans(),
                'photo'  => $v->visitor?->photo,
                'unit'   => null,
            ]);

        $recentDeliveries = DeliveryLog::with('personnel')
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn($d) => [
                'id'     => $d->id,
                'name'   => $d->personnel?->name ?? 'Unknown',
                'type'   => 'Delivery',
                'status' => $d->status,
                'time'   => $d->updated_at->diffForHumans(),
                'photo'  => $d->personnel?->photo,
                'unit'   => $d->destination,
            ]);

        $activityFeed = $recentVisits
            ->concat($recentDeliveries)
            ->sortByDesc('time')
            ->values()
            ->take(10);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_residents'          => $totalResidents,
                'owner_count'              => $ownerCount,
                'family_count'             => $familyCount,
                'total_visitors'           => $totalVisitors,
                'total_guards'             => $totalGuards,
                'active_guards'            => $activeGuards,
                'total_house_units'        => $totalHouseUnits,
                'total_delivery_personnel' => $totalDeliveryPersonnel,
                'active_now'               => $activeTotal,
                'active_visits'            => $activeVisits,
                'active_deliveries'        => $activeDeliveries,
                'today_visits'             => $todayVisits,
                'today_deliveries'         => $todayDeliveries,
                'yesterday_visits'         => $yesterdayVisits,
                'pending_visits'           => $pendingVisits,
                'pending_deliveries'       => $pendingDeliveries,
                'total_pending'            => $totalPending,
            ],
            'charts' => [
                'trends' => [
                    'labels'     => $trendLabels,
                    'visits'     => $trendVisits,
                    'deliveries' => $trendDeliveries,
                ],
                'distribution' => [
                    'labels' => ['Visitors', 'Delivery Personnel'],
                    'data'   => [$totalVisitors, $totalDeliveryPersonnel],
                ],
                'residents' => [
                    'labels' => ['Owners', 'Family Members'],
                    'data'   => [$ownerCount, $familyCount],
                ],
                'purposes' => [
                    'labels' => $purposes->pluck('purpose'),
                    'data'   => $purposes->pluck('count'),
                ],
                'shifts' => [
                    'labels' => ['Morning', 'Afternoon', 'Night'],
                    'data'   => array_values($shiftCounts),
                ],
            ],
            'recentActivity' => $activityFeed,
        ]);
    }
}

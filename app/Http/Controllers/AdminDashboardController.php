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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $today        = Carbon::today();
        $yesterday    = Carbon::yesterday();

        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');

        if ($startDateInput && $endDateInput) {
            try {
                $start = Carbon::parse($startDateInput)->startOfDay();
                $end = Carbon::parse($endDateInput)->endOfDay();
                if ($start->gt($end)) {
                    $temp = $start;
                    $start = $end;
                    $end = $temp;
                }
            } catch (\Exception $e) {
                $start = Carbon::now()->subDays(13)->startOfDay();
                $end = Carbon::now()->endOfDay();
            }
        } else {
            $start = Carbon::now()->subDays(13)->startOfDay();
            $end = Carbon::now()->endOfDay();
        }

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
        $activeVisitCount     = Visit::where('status', 'Checked In')->count();
        $activeDeliveryCount  = DeliveryLog::whereNotNull('entry_time')->whereNull('exit_time')->count();
        $activeTotal          = $activeVisitCount + $activeDeliveryCount;

        // ── Today's Activity ─────────────────────────────────────────
        $todayVisits     = Visit::whereDate('created_at', $today)->count();
        $todayDeliveries = DeliveryLog::whereDate('created_at', $today)->count();
        $yesterdayVisits = Visit::whereDate('created_at', $yesterday)->count();

        // ── Pending Approvals ────────────────────────────────────────
        $pendingVisits     = Visit::where('status', 'Pending')->count();
        $pendingDeliveries = DeliveryLog::where('status', 'Pending')->count();
        $totalPending      = $pendingVisits + $pendingDeliveries;

        // ── Visit + Delivery Trends (Custom Range) ───────────────────
        $visitsByDay = Visit::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $deliveriesByDay = DeliveryLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $trendLabels     = [];
        $trendVisits     = [];
        $trendDeliveries = [];

        $daysCount = $start->diffInDays($end) + 1;
        if ($daysCount > 180) {
            $daysCount = 180;
        }

        for ($i = 0; $i < $daysCount; $i++) {
            $currentDate = $start->copy()->addDays($i);
            $dateKey = $currentDate->format('Y-m-d');
            $trendLabels[]     = $currentDate->format('M d');
            $trendVisits[]     = $visitsByDay[$dateKey]     ?? 0;
            $trendDeliveries[] = $deliveriesByDay[$dateKey] ?? 0;
        }

        // ── Visitor Peak Times Breakdown ─────────────────────────────
        $visitTimes = Visit::whereBetween('created_at', [$start, $end])
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->get();

        $timeCounts = ['morning' => 0, 'afternoon' => 0, 'night' => 0];

        foreach ($visitTimes as $vt) {
            $hour = (int)$vt->hour;
            $count = (int)$vt->count;
            if ($hour >= 6 && $hour < 12) {
                $timeCounts['morning'] += $count;
            } elseif ($hour >= 12 && $hour < 18) {
                $timeCounts['afternoon'] += $count;
            } else {
                $timeCounts['night'] += $count;
            }
        }

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

        // ── Top 5 Most Frequent Visitors ────────────────────────────
        $topFrequentVisitors = Visitor::withCount('visits')
            ->orderByDesc('visits_count')
            ->limit(5)
            ->get();

        $frequentVisitorData = $topFrequentVisitors->map(fn($visitor) => [
            'id'           => $visitor->id,
            'name'         => $visitor->name,
            'photo'        => $visitor->photo,
            'email'        => $visitor->email,
            'phone'        => $visitor->phone,
            'visits_count' => $visitor->visits_count,
        ])->toArray();

        // ── Active/On-Premise Activity Feed ───────────────────────────
        $onSiteVisits = Visit::with('visitor')
            ->where('status', 'Checked In')
            ->latest('updated_at')
            ->get()
            ->map(fn($v) => [
                'id'     => $v->id,
                'name'   => $v->visitor?->name ?? 'Unknown',
                'type'   => 'Visitor',
                'status' => $v->status,
                'time'   => $v->updated_at->diffForHumans(),
                'photo'  => $v->visitor?->photo,
                'unit'   => $v->unit_number,
            ]);

        $onSiteDeliveries = DeliveryLog::with('personnel')
            ->whereNotNull('entry_time')
            ->whereNull('exit_time')
            ->latest('updated_at')
            ->get()
            ->map(fn($d) => [
                'id'     => $d->id,
                'name'   => $d->personnel?->name ?? 'Unknown',
                'type'   => 'Delivery',
                'status' => 'Checked In',
                'time'   => $d->updated_at->diffForHumans(),
                'photo'  => $d->personnel?->photo,
                'unit'   => $d->destination,
            ]);

        $activeOnSite = $onSiteVisits
            ->concat($onSiteDeliveries)
            ->sortByDesc('time')
            ->values();

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
                'active_visits'            => $activeVisitCount,
                'active_deliveries'        => $activeDeliveryCount,
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
                'visit_times' => [
                    'labels' => ['Morning (6 AM - 12 PM)', 'Afternoon (12 PM - 6 PM)', 'Night (6 PM - 6 AM)'],
                    'data'   => [$timeCounts['morning'], $timeCounts['afternoon'], $timeCounts['night']],
                ],
                'shifts' => [
                    'labels' => ['Morning', 'Afternoon', 'Night'],
                    'data'   => array_values($shiftCounts),
                ],
            ],
            'activeOnSite' => $activeOnSite,
            'mostFrequentVisitor' => $frequentVisitorData,
            'filters' => [
                'start_date' => $startDateInput ? $start->format('Y-m-d') : null,
                'end_date' => $endDateInput ? $end->format('Y-m-d') : null,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class VisitorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Visitor::latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('ic_number', 'like', "%{$search}%")
                  ->orWhere('vehicle_number', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Admin/Visitors/Index', [
            'visitors' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only('search')
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Visitors/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255|unique:visitors',
            'ic_number' => 'required|string|max:255',
            'vehicle_number' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
            // Face descriptor is optional in simpler CRUD, or can be added later
            'face_descriptor' => 'nullable' 
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('visitors', 'public');
        }

        Visitor::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'ic_number' => $request->ic_number,
            'vehicle_number' => $request->vehicle_number,
            'photo' => $photoPath,
            'face_descriptor' => $request->face_descriptor ? json_encode($request->face_descriptor) : null,
        ]);

        return redirect()->route('admin.visitors.index')->with('success', 'Visitor registered successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Visitor $visitor)
    {
        return Inertia::render('Admin/Visitors/Edit', [
            'visitor' => $visitor
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Visitor $visitor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255|unique:visitors,phone,' . $visitor->id,
            'ic_number' => 'required|string|max:255',
            'vehicle_number' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
        ]);

        $data = $request->except(['photo']);

        if ($request->hasFile('photo')) {
            if ($visitor->photo) {
                Storage::disk('public')->delete($visitor->photo);
            }
            $data['photo'] = $request->file('photo')->store('visitors', 'public');
        }

        $visitor->update($data);

        return redirect()->route('admin.visitors.index')->with('success', 'Visitor updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Visitor $visitor)
    {
        if ($visitor->photo) {
            Storage::disk('public')->delete($visitor->photo);
        }
        $visitor->delete();
        return redirect()->route('admin.visitors.index')->with('success', 'Visitor deleted successfully.');
    }
    /**
     * Display all visit logs for visitors.
     */
    public function logs()
    {
        return Inertia::render('Admin/Visitors/Logs', [
            'logs' => \App\Models\Visit::with(['visitor', 'sessions'])->latest('updated_at')->get()
        ]);
    }
    /**
     * Display the details of a specific visit log.
     */
    public function showLog(\App\Models\Visit $visit)
    {
        $visit->load(['visitor', 'sessions']);
        return Inertia::render('Admin/Visitors/ShowLog', [
            'visit' => $visit
        ]);
    }
    public function exportLogs()
    {
        $logs = \App\Models\Visit::with(['visitor', 'sessions'])->latest()->get();
        $filename = "visit_logs_" . now()->format('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        // ── Helpers ───────────────────────────────────────────────────────────────
        $tz = 'Asia/Kuala_Lumpur';

        // Format a Carbon/datetime value as a readable Malaysia-time string.
        // Accepts Carbon instances (already cast by model) or raw strings.
        $fmtDt = function ($dt) use ($tz): string {
            if (!$dt) return '-';
            $carbon = ($dt instanceof \Carbon\Carbon) ? $dt : \Carbon\Carbon::parse($dt);
            return $carbon->timezone($tz)->format('d/m/Y h:i:s A');
        };

        // Format the difference between two Carbon/datetime values as Xh Xm Xs.
        $fmtDiff = function ($start, $end): string {
            if (!$start || !$end) return '-';
            $s = ($start instanceof \Carbon\Carbon) ? $start : \Carbon\Carbon::parse($start);
            $e = ($end   instanceof \Carbon\Carbon) ? $end   : \Carbon\Carbon::parse($end);
            $secs = (int) $s->diffInSeconds($e, false); // signed
            if ($secs <= 0) return '-';
            $h = intdiv($secs, 3600);
            $m = intdiv($secs % 3600, 60);
            $sc = $secs % 60;
            return ($h > 0 ? "{$h}h " : '') . "{$m}m {$sc}s";
        };

        // ── Max sessions across all visits (dynamic column count) ─────────────────
        $maxSessions = $logs->max(fn($log) => $log->sessions->count()) ?: 1;

        $callback = function() use ($logs, $fmtDt, $fmtDiff, $maxSessions) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // ── Header ────────────────────────────────────────────────────────────
            $header = [
                'Visit ID', 'Visitor Name', 'IC Number', 'Phone', 'Email',
                'Vehicle Number', 'Unit Number', 'Host Name', 'Purpose',
                'Parking Lot', 'Approved By', 'Status',
                'Total Sessions', 'Total Stay Duration',
                'First Check-In', 'Last Check-Out',
            ];

            for ($i = 1; $i <= $maxSessions; $i++) {
                $suffix = $maxSessions === 1 ? '' : " $i";
                $header[] = "Session{$suffix} - Check-In";
                $header[] = "Session{$suffix} - " . ($i < $maxSessions ? 'Temp Leave' : 'Check-Out');
                $header[] = "Session{$suffix} - Duration";
            }

            fputcsv($file, $header);

            // ── Rows ──────────────────────────────────────────────────────────────
            foreach ($logs as $log) {
                $sessions = $log->sessions; // already ordered by check_in_time (see model)

                // Total duration (sum all sessions)
                $totalSecs = 0;
                if ($sessions->count() > 0) {
                    foreach ($sessions as $sess) {
                        $start = $sess->check_in_time;
                        $end   = $sess->check_out_time
                            ?? ($log->status === 'Checked In' ? now() : null);
                        if ($start && $end) {
                            $s = ($start instanceof \Carbon\Carbon) ? $start : \Carbon\Carbon::parse($start);
                            $e = ($end   instanceof \Carbon\Carbon) ? $end   : \Carbon\Carbon::parse($end);
                            $diff = (int) $s->diffInSeconds($e, false);
                            if ($diff > 0) $totalSecs += $diff;
                        }
                    }
                } else {
                    // Fallback: legacy first/second columns
                    foreach ([
                        [$log->first_check_in_time,  $log->first_check_out_time],
                        [$log->second_check_in_time, $log->second_check_out_time],
                    ] as [$cin, $cout]) {
                        if ($cin) {
                            $s = \Carbon\Carbon::parse($cin);
                            $e = $cout
                                ? \Carbon\Carbon::parse($cout)
                                : ($log->status === 'Checked In' ? now() : $s);
                            $diff = (int) $s->diffInSeconds($e, false);
                            if ($diff > 0) $totalSecs += $diff;
                        }
                    }
                    // Plain single-session fallback
                    if (!$log->first_check_in_time && $log->check_in_time) {
                        $s = \Carbon\Carbon::parse($log->check_in_time);
                        $e = $log->check_out_time ? \Carbon\Carbon::parse($log->check_out_time) : now();
                        $diff = (int) $s->diffInSeconds($e, false);
                        if ($diff > 0) $totalSecs = $diff;
                    }
                }

                $th = intdiv($totalSecs, 3600);
                $tm = intdiv($totalSecs % 3600, 60);
                $ts = $totalSecs % 60;
                $totalFmt = $totalSecs > 0
                    ? (($th > 0 ? "{$th}h " : '') . "{$tm}m {$ts}s")
                    : '-';

                // Per-session columns (dynamic, padded to $maxSessions)
                $sessionCols = [];
                $sessionList = $sessions->values();
                $count = $sessionList->count();

                for ($i = 0; $i < $maxSessions; $i++) {
                    $sess = $sessionList->get($i);
                    if ($sess) {
                        $isLast    = ($i === $count - 1);
                        $sessEnd   = $sess->check_out_time
                            ?? ($log->status === 'Checked In' ? now() : null);
                        $outLabel  = $sess->check_out_time
                            ? $fmtDt($sess->check_out_time) . (!$isLast ? ' (Temp Leave)' : '')
                            : ($log->status === 'Checked In' ? 'On-Site' : '-');

                        $sessionCols[] = $fmtDt($sess->check_in_time);
                        $sessionCols[] = $outLabel;
                        $sessionCols[] = $fmtDiff($sess->check_in_time, $sessEnd);
                    } else {
                        $sessionCols[] = '-';
                        $sessionCols[] = '-';
                        $sessionCols[] = '-';
                    }
                }

                // ── First check-in / last check-out ─────────────────────────────────
                // Prefer session data (authoritative) over the visits table columns.
                if ($sessions->count() > 0) {
                    $firstCheckIn  = $fmtDt($sessions->first()->check_in_time);
                    $lastCheckOut  = $sessions->last()->check_out_time
                        ? $fmtDt($sessions->last()->check_out_time)
                        : ($log->status === 'Checked In' ? 'On-Site' : '-');
                } else {
                    $firstCheckIn = $fmtDt($log->check_in_time);
                    $lastCheckOut = $log->check_out_time
                        ? $fmtDt($log->check_out_time)
                        : ($log->status === 'Checked In' ? 'On-Site' : '-');
                }

                fputcsv($file, array_merge([
                    $log->id,
                    $log->visitor->name           ?? 'N/A',
                    $log->visitor->ic_number       ?? 'N/A',
                    $log->visitor->phone           ?? 'N/A',
                    $log->visitor->email           ?? 'N/A',
                    $log->visitor->vehicle_number  ?? '-',
                    $log->unit_number,
                    $log->host_name                ?? '-',
                    $log->purpose,
                    $log->parking_lot_number       ?? '-',
                    $log->approved_by              ?? '-',
                    $log->status,
                    $count ?: 1,
                    $totalFmt,
                    $firstCheckIn,
                    $lastCheckOut,
                ], $sessionCols));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

}

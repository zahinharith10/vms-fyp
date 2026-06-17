<?php

namespace App\Http\Controllers;

use App\Events\VisitStatusUpdated;
use App\Models\DeliveryLog;
use App\Models\DeliveryPersonnel;
use App\Models\DeliveryRun;
use App\Models\Resident;
use App\Models\Visit;
use App\Models\Visitor;
use App\Models\VisitSession;
use App\Notifications\VisitRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GuardScanController extends Controller
{
    public function index()
    {
        return Inertia::render('Guard/Scan');
    }

    /**
     * Show the Verify Visitor page.
     */
    public function verify(Visit $visit, Request $request)
    {
        $occupiedCount = Visit::whereIn('status', ['Checked In', 'Temporarily Out'])
            ->whereNotNull('parking_lot_number')
            ->count();

        $visit->load('visitor');
        $visitArray = $visit->toArray();
        $visitArray['checkout_intent'] = $request->query('intent', 'final');

        return Inertia::render('Guard/VerifyVisitor', [
            'visit' => $visitArray,
            'parking' => [
                'occupied' => $occupiedCount,
                'total' => 15,
                'available' => max(0, 15 - $occupiedCount),
            ],
        ]);
    }

    /**
     * Get visit details as JSON (for polling).
     */
    public function show(Visit $visit)
    {
        return response()->json([
            'success' => true,
            'visit' => [
                'id' => $visit->id,
                'status' => $visit->status,
                'unit_number' => $visit->unit_number,
                'purpose' => $visit->purpose,
                'visitor' => [
                    'name' => $visit->visitor->name,
                    'phone' => $visit->visitor->phone,
                    'photo' => $visit->visitor->photo,
                    'vehicle_number' => $visit->visitor->vehicle_number,
                    'face_descriptor' => $visit->visitor->face_descriptor,
                ],
            ],
        ]);
    }

    public function verifyDelivery(DeliveryLog $log, Request $request)
    {
        $log->load(['personnel', 'run.logs']);

        $visit = [
            'id' => $log->id,
            'status' => $log->status,
            'unit_number' => $log->destination,
            'purpose' => 'Delivery Service ('.$log->personnel->company.')',
            'visitor' => $log->personnel,
            'is_delivery' => true,
            'checkout_intent' => $request->query('intent', 'final'),
        ];

        if ($log->run) {
            $visit['run_id'] = $log->run->id;
            $visit['is_delivery_run'] = true;
            $visit['is_multi'] = $log->run->type === 'multi';
            $visit['destinations'] = $log->run->logs->pluck('destination')->values()->all();
            $visit['status'] = $log->run->status;

            if ($log->run->type === 'multi') {
                $visit['unit_number'] = $log->run->logs->count().' units';
                $visit['purpose'] = 'Multi-stop delivery ('.$log->personnel->company.')';
            }
        }

        return Inertia::render('Guard/VerifyVisitor', [
            'visit' => $visit,
        ]);
    }

    /**
     * Get delivery log details as JSON (for polling).
     */
    public function showDelivery(DeliveryLog $log)
    {
        $log->load(['personnel', 'run.logs']);

        $visit = [
            'id' => $log->id,
            'status' => $log->status,
            'unit_number' => $log->destination,
            'purpose' => 'Delivery Service ('.$log->personnel->company.')',
            'visitor' => [
                'name' => $log->personnel->name,
                'phone' => $log->personnel->phone,
                'photo' => $log->personnel->photo,
                'vehicle_number' => $log->personnel->vehicle_number,
                'face_descriptor' => $log->personnel->face_descriptor,
            ],
            'is_delivery' => true,
        ];

        if ($log->run) {
            $visit['run_id'] = $log->run->id;
            $visit['is_delivery_run'] = true;
            $visit['is_multi'] = $log->run->type === 'multi';
            $visit['destinations'] = $log->run->logs->pluck('destination')->values()->all();
            $visit['status'] = $log->run->status;

            if ($log->run->type === 'multi') {
                $visit['unit_number'] = $log->run->logs->count().' units';
                $visit['purpose'] = 'Multi-stop delivery ('.$log->personnel->company.')';
            }
        }

        return response()->json([
            'success' => true,
            'visit' => $visit,
        ]);
    }

    public function dashboard()
    {
        $this->autoFinalizeOldVisits();
        $today = now()->startOfDay();

        // Get active visits with assigned parking lot number
        $occupiedLotsMap = Visit::with('visitor')
            ->whereIn('status', ['Checked In', 'Temporarily Out'])
            ->whereNotNull('parking_lot_number')
            ->get()
            ->keyBy('parking_lot_number');

        $parkingLots = [];
        for ($i = 1; $i <= 15; $i++) {
            $lot = $occupiedLotsMap->get($i);
            $parkingLots[] = [
                'lot_number' => $i,
                'status' => $lot ? 'Occupied' : 'Available',
                'visitor_name' => $lot && $lot->visitor ? $lot->visitor->name : null,
                'vehicle_number' => $lot && $lot->visitor ? $lot->visitor->vehicle_number : null,
                'unit_number' => $lot ? $lot->unit_number : null,
                'visit_id' => $lot ? $lot->id : null,
            ];
        }

        $stats = [
            'visitors_today' => Visit::where('check_in_time', '>=', $today)->count() + DeliveryLog::where('entry_time', '>=', $today)->count(),
            'active_visitors' => Visit::where('status', 'Checked In')->count() + DeliveryLog::where('entry_time', '!=', null)->whereNull('exit_time')->count(),
            'pending_approvals' => Visit::where('status', 'Pending')->count() + DeliveryLog::where('status', 'Pending')->count(),
            'approved_upcoming' => Visit::where('status', 'Approved')->active()->count() + DeliveryLog::where('status', 'Approved')->whereNull('entry_time')->active()->count(),
            'occupied_parking' => $occupiedLotsMap->count(),
            'total_parking' => 15,
        ];

        $approvedDeliveries = DeliveryLog::with('personnel')
            ->where('status', 'Approved')
            ->whereNull('entry_time')
            ->active()
            ->get();

        return Inertia::render('Guard/Dashboard', [
            'stats' => $stats,
            'approvedDeliveries' => $approvedDeliveries,
            'parkingLots' => $parkingLots,
        ]);
    }

    /**
     * Lookup a visit by QR code token (API endpoint).
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $this->autoFinalizeOldVisits();

        $tokenParts = explode(':', $request->token);

        if ($tokenParts[0] === 'DELIVERY_RUN') {
            $runId = $tokenParts[1] ?? null;
            $run = DeliveryRun::with(['personnel', 'logs'])
                ->where('id', $runId)
                ->first();

            // Exclude expired logs
            $activeLogs = $run->logs->filter(fn ($log) => !$log->is_expired);
            if ($activeLogs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This delivery QR code/trip has expired. Deliveries must be checked in within 24 hours of approval.',
                ], 410);
            }

            $primaryLog = $activeLogs->first();

            return response()->json([
                'success' => true,
                'is_delivery' => true,
                'visit' => [
                    'id' => $primaryLog->id,
                    'run_id' => $run->id,
                    'is_delivery_run' => true,
                    'is_multi' => $run->type === 'multi',
                    'destinations' => $activeLogs->pluck('destination')->values()->all(),
                    'visitor_name' => $run->personnel->name ?? 'Unknown',
                    'visitor_phone' => $run->personnel->phone ?? '-',
                    'visitor_photo' => $run->personnel->photo ?? null,
                    'unit_number' => $run->type === 'multi'
                        ? $activeLogs->count().' units'
                        : $primaryLog->destination,
                    'purpose' => $run->type === 'multi'
                        ? 'Multi-stop delivery ('.($run->personnel->company ?? 'Unknown').')'
                        : 'Delivery ('.($run->personnel->company ?? 'Unknown').')',
                    'status' => $run->status,
                    'created_at' => $run->created_at->format('Y-m-d H:i'),
                    'face_descriptor' => $run->personnel->face_descriptor ?? null,
                    'checkout_intent' => 'final',
                ],
            ]);
        }

        if ($tokenParts[0] === 'DELIVERY_LOG') {
            $logId = $tokenParts[1] ?? null;
            $log = DeliveryLog::with('personnel')
                ->where('id', $logId)
                ->first();

            if (! $log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid delivery QR code. No matching log found.',
                ], 404);
            }

            if ($log->is_expired) {
                return response()->json([
                    'success' => false,
                    'message' => 'This delivery QR code has expired. Deliveries must be checked in within 24 hours of approval.',
                ], 410);
            }

            return response()->json([
                'success' => true,
                'is_delivery' => true,
                'visit' => [
                    'id' => $log->id,
                    'visitor_name' => $log->personnel->name ?? 'Unknown',
                    'visitor_phone' => $log->personnel->phone ?? '-',
                    'visitor_photo' => $log->personnel->photo ?? null,
                    'unit_number' => $log->destination,
                    'purpose' => 'Delivery ('.($log->personnel->company ?? 'Unknown').')',
                    'status' => $log->status,
                    'created_at' => $log->created_at->format('Y-m-d H:i'),
                    'face_descriptor' => $log->personnel->face_descriptor ?? null,
                    'checkout_intent' => 'final',
                ],
            ]);
        }

        $cleanToken = $tokenParts[0];
        $intent = $tokenParts[1] ?? 'final';

        $visit = Visit::with('visitor')
            ->where('qr_code_token', $cleanToken)
            ->first();

        if (! $visit) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code. No matching visit found.',
            ], 404);
        }

        if ($visit->status === 'Expired') {
            return response()->json([
                'success' => false,
                'message' => 'This QR code/pass has expired. Passes are only valid for 24 hours after approval.',
            ], 410);
        }

        return response()->json([
            'success' => true,
            'is_delivery' => false,
            'visit' => [
                'id' => $visit->id,
                'visitor_name' => $visit->visitor->name ?? 'Unknown',
                'visitor_phone' => $visit->visitor->phone ?? '-',
                'visitor_photo' => $visit->visitor->photo ?? null,
                'unit_number' => $visit->unit_number,
                'purpose' => $visit->purpose,
                'status' => $visit->status,
                'created_at' => $visit->created_at->format('Y-m-d H:i'),
                'face_descriptor' => $visit->visitor->face_descriptor ?? null,
                'checkout_intent' => $intent,
            ],
        ]);
    }

    /**
     * Check in a visitor (update visit status).
     * Creates a new VisitSession record to support unlimited temporary leaves.
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
        ]);

        $visit = Visit::findOrFail($request->visit_id);

        if ($visit->status === 'Expired') {
            return response()->json([
                'success' => false,
                'message' => 'This guest pass QR code has expired.',
            ], 400);
        }

        if (! in_array($visit->status, ['Approved', 'Temporarily Out'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only Approved or Temporarily Out visits can be checked in. Current status: '.$visit->status,
            ], 400);
        }

        $visitor = $visit->visitor;
        $hasVehicle = $visitor && ! empty($visitor->vehicle_number) && $visitor->vehicle_number !== '-' && strtolower($visitor->vehicle_number) !== 'n/a';

        $parkingLotNumber = $visit->parking_lot_number;
        $parkOutside = $request->boolean('park_outside', false);

        if ($visit->status === 'Approved' && $hasVehicle && is_null($parkingLotNumber) && !$parkOutside) {
            // Find occupied parking lots
            $occupiedLots = Visit::whereIn('status', ['Checked In', 'Temporarily Out'])
                ->whereNotNull('parking_lot_number')
                ->pluck('parking_lot_number')
                ->toArray();

            // Find the first available lot
            for ($i = 1; $i <= 15; $i++) {
                if (! in_array($i, $occupiedLots)) {
                    $parkingLotNumber = $i;
                    break;
                }
            }

            if (is_null($parkingLotNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Visitor parking is full! All 15 visitor parking slots are occupied.',
                ], 422);
            }
        }

        $now = now();

        // Create a new session record for this entry (supports unlimited re-entries)
        VisitSession::create([
            'visit_id' => $visit->id,
            'check_in_time' => $now,
        ]);

        // Also keep legacy columns updated for backward compatibility
        $updateData = [
            'status' => 'Checked In',
            'check_in_time' => $now,
            'parking_lot_number' => $parkingLotNumber,
        ];

        if ($visit->status === 'Approved') {
            $updateData['first_check_in_time'] = $now;
        } elseif ($visit->status === 'Temporarily Out' && is_null($visit->second_check_in_time)) {
            $updateData['second_check_in_time'] = $now;
        }

        $visit->update($updateData);

        // Notify Resident
        $resident = null;
        $parts = array_map('trim', explode('-', $visit->unit_number));
        if (count($parts) === 3) {
            $houseUnit = \App\Models\HouseUnit::where('block', $parts[0])
                ->where('floor', $parts[1])
                ->where('unit_number', $parts[2])
                ->first();
            if ($houseUnit) {
                $resident = $houseUnit->residents()->first();
            }
        }

        if ($resident) {
            $resident->notify(new VisitRequestNotification($visit->load('visitor')));
        }

        // Broadcast check-in status to visitor's phone and guard dashboard
        try {
            broadcast(new VisitStatusUpdated(
                $visit->id,
                'Checked In',
                $parkingLotNumber,
                $visit->unit_number
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcasting failed: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Visitor checked in successfully!',
        ]);
    }

    /**
     * Check in a delivery personnel (update entry_time).
     */
    public function checkInDelivery(Request $request)
    {
        $request->validate([
            'log_id' => 'required_without:run_id|exists:delivery_logs,id',
            'run_id' => 'required_without:log_id|exists:delivery_runs,id',
        ]);

        if ($request->filled('run_id')) {
            $run = DeliveryRun::with('logs')->findOrFail($request->run_id);
            $logsToCheckIn = $run->logs->filter(fn (DeliveryLog $log) => 
                in_array($log->status, ['Approved', 'Temporarily Out'], true) && !$log->is_expired
            );

            if ($logsToCheckIn->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No approved delivery stops are ready/active for check-in.',
                ], 400);
            }

            $now = now();
            foreach ($logsToCheckIn as $log) {
                $log->update([
                    'entry_time' => $now,
                    'status' => 'Checked In',
                ]);
            }

            $run->update([
                'entry_time' => $now,
                'status' => 'Checked In',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery trip checked in successfully!',
            ]);
        }

        $log = DeliveryLog::findOrFail($request->log_id);

        if ($log->is_expired) {
            return response()->json([
                'success' => false,
                'message' => 'This delivery has expired.',
            ], 400);
        }

        if (! in_array($log->status, ['Approved', 'Temporarily Out'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only Approved or Temporarily Out deliveries can be checked in.',
            ], 400);
        }

        $log->update([
            'entry_time' => now(),
            'status' => 'Checked In',
        ]);

        $log->run?->refreshStatus();

        return response()->json([
            'success' => true,
            'message' => 'Delivery checked in successfully!',
        ]);
    }

    /**
     * Check out a visitor (update visit status).
     * Closes the latest open VisitSession to support unlimited temporary leaves.
     */
    public function checkOut(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
        ]);

        $visit = Visit::findOrFail($request->visit_id);

        if ($visit->status !== 'Checked In') {
            return response()->json([
                'success' => false,
                'message' => 'Visitor must be checked in first.',
            ], 400);
        }

        $isTemporary = $request->input('is_temporary', false);
        $newStatus = $isTemporary ? 'Temporarily Out' : 'Checked Out';
        $now = now();

        // Close the latest open session
        $openSession = VisitSession::where('visit_id', $visit->id)
            ->whereNull('check_out_time')
            ->latest('check_in_time')
            ->first();

        if ($openSession) {
            $openSession->update(['check_out_time' => $now]);
        }

        // Also keep legacy columns updated for backward compatibility
        $updateData = [
            'status' => $newStatus,
            'check_out_time' => $now,
        ];

        if ($isTemporary) {
            if (is_null($visit->first_check_out_time)) {
                $updateData['first_check_out_time'] = $now;
            }
        } else {
            if (is_null($visit->first_check_out_time)) {
                $updateData['first_check_out_time'] = $now;
            } elseif (is_null($visit->second_check_out_time)) {
                $updateData['second_check_out_time'] = $now;
            }
            // For 3rd+ leaves, the legacy columns are not updated (sessions table is authoritative)
        }

        $visit->update($updateData);

        // Broadcast check-out status to visitor's phone and guard dashboard
        try {
            broadcast(new VisitStatusUpdated(
                $visit->id,
                $newStatus,
                $visit->parking_lot_number,
                $visit->unit_number
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcasting failed: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => $isTemporary ? 'Visitor marked as Temporarily Out.' : 'Visitor checked out successfully!',
        ]);
    }

    /**
     * Check out a delivery personnel.
     */
    public function checkOutDelivery(Request $request)
    {
        $request->validate([
            'log_id' => 'required_without:run_id|exists:delivery_logs,id',
            'run_id' => 'required_without:log_id|exists:delivery_runs,id',
        ]);

        $isTemporary = $request->boolean('is_temporary');

        if ($request->filled('run_id')) {
            $run = DeliveryRun::with('logs')->findOrFail($request->run_id);
            $logsToCheckOut = $run->logs->filter(fn (DeliveryLog $log) => $log->entry_time && ! $log->exit_time);

            if ($logsToCheckOut->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Personnel must be checked in and not already checked out.',
                ], 400);
            }

            $now = now();
            foreach ($logsToCheckOut as $log) {
                $log->update([
                    'status' => $isTemporary ? 'Temporarily Out' : 'Checked Out',
                    'exit_time' => $isTemporary ? null : $now,
                ]);
            }

            if (! $isTemporary) {
                $run->update([
                    'status' => 'Checked Out',
                    'exit_time' => $now,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $isTemporary ? 'Delivery trip marked as Temporarily Out.' : 'Delivery trip checked out successfully!',
            ]);
        }

        $log = DeliveryLog::findOrFail($request->log_id);

        if (! $log->entry_time || $log->exit_time) {
            return response()->json([
                'success' => false,
                'message' => 'Personnel must be checked in and not already checked out.',
            ], 400);
        }

        $log->update([
            'status' => $isTemporary ? 'Temporarily Out' : 'Checked Out',
            'exit_time' => $isTemporary ? null : now(),
        ]);

        $log->run?->refreshStatus();

        return response()->json([
            'success' => true,
            'message' => $isTemporary ? 'Delivery marked as Temporarily Out.' : 'Delivery checked out successfully!',
        ]);
    }

    /**
     * Display a list of active visitors and deliveries.
     */
    public function activeLogs()
    {
        $this->autoFinalizeOldVisits();
        $activeVisitors = Visit::with(['visitor', 'sessions'])
            ->whereIn('status', ['Checked In', 'Temporarily Out'])
            ->get()
            ->map(function ($visit) {
                $firstSession = $visit->sessions->first();
                return [
                    'id' => $visit->id,
                    'type' => 'Visitor',
                    'name' => $visit->visitor->name,
                    'phone' => $visit->visitor->phone,
                    'photo' => $visit->visitor->photo,
                    'vehicle_number' => $visit->visitor->vehicle_number,
                    'unit_number' => $visit->unit_number,
                    'purpose' => $visit->purpose,
                    'entry_time' => $firstSession ? $firstSession->check_in_time : $visit->check_in_time,
                    'status' => $visit->status,
                    'parking_lot_number' => $visit->parking_lot_number,
                ];
            });

        $activeDeliveries = DeliveryLog::with('personnel')
            ->whereNotNull('entry_time')
            ->whereNull('exit_time')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => 'Delivery',
                    'name' => $log->personnel->name,
                    'phone' => $log->personnel->phone,
                    'photo' => $log->personnel->photo,
                    'vehicle_number' => $log->personnel->vehicle_number,
                    'unit_number' => $log->destination,
                    'purpose' => 'Delivery ('.$log->personnel->company.')',
                    'entry_time' => $log->entry_time,
                    'is_delivery' => true,
                    'status' => $log->status,
                ];
            });

        $activeLogs = $activeVisitors->concat($activeDeliveries)->sortByDesc('entry_time')->values();

        return Inertia::render('Guard/ActiveLogs', [
            'activeLogs' => $activeLogs,
        ]);
    }

    /**
     * Display a list of all visit records (visitors and deliveries).
     */
    public function visitRecords()
    {
        $this->autoFinalizeOldVisits();
        $allVisitors = Visit::with(['visitor', 'sessions'])
            ->get()
            ->map(function ($visit) {
                $firstSession = $visit->sessions->first();
                $lastSession = $visit->sessions->last();
                return [
                    'id' => $visit->id,
                    'type' => 'Visitor',
                    'name' => $visit->visitor->name,
                    'phone' => $visit->visitor->phone,
                    'photo' => $visit->visitor->photo,
                    'vehicle_number' => $visit->visitor->vehicle_number,
                    'unit_number' => $visit->unit_number,
                    'purpose' => $visit->purpose,
                    'entry_time' => $firstSession ? $firstSession->check_in_time : $visit->check_in_time,
                    'exit_time' => $lastSession ? $lastSession->check_out_time : $visit->check_out_time,
                    'status' => $visit->status,
                    'parking_lot_number' => $visit->parking_lot_number,
                    'created_at' => $visit->created_at,
                    'sessions' => $visit->sessions->map(fn($s) => [
                        'check_in_time'  => $s->check_in_time,
                        'check_out_time' => $s->check_out_time,
                    ])->values(),
                ];
            });

        $allDeliveries = DeliveryLog::with('personnel')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => 'Delivery',
                    'name' => $log->personnel->name,
                    'phone' => $log->personnel->phone,
                    'photo' => $log->personnel->photo,
                    'vehicle_number' => $log->personnel->vehicle_number,
                    'unit_number' => $log->destination,
                    'purpose' => 'Delivery ('.$log->personnel->company.')',
                    'entry_time' => $log->entry_time,
                    'exit_time' => $log->exit_time,
                    'is_delivery' => true,
                    'status' => $log->status,
                    'created_at' => $log->created_at,
                ];
            });

        $visitRecords = $allVisitors->concat($allDeliveries)->sortByDesc('created_at')->values();

        return Inertia::render('Guard/VisitRecords', [
            'visitRecords' => $visitRecords,
        ]);
    }

    public function showRegistration()
    {
        $units = \App\Models\HouseUnit::orderBy('block')
            ->orderBy('floor')
            ->orderBy('unit_number')
            ->get();

        return Inertia::render('Guard/Register', [
            'units' => $units
        ]);
    }

    /**
     * Register a new visitor and create an instant check-in.
     */
    public function registerVisitor(Request $request)
    {
        $normalizeUnit = function ($val) {
            if (empty($val)) return $val;
            $parts = preg_split('/\s*-\s*/', trim((string) $val));
            if (count($parts) !== 3) return $val;
            $normaliseSegment = fn($s) => is_numeric($s) ? (string)(int)$s : trim($s);
            return $normaliseSegment($parts[0]) . '-' . $normaliseSegment($parts[1]) . '-' . $normaliseSegment($parts[2]);
        };

        if ($request->has('unit_number')) {
            $request->merge([
                'unit_number' => $normalizeUnit($request->unit_number),
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    $email = strtolower(trim($value));
                    if (\App\Models\Visitor::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                        $fail('This email is already registered as a Visitor.');
                        return;
                    }
                    if (\App\Models\DeliveryPersonnel::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                        $fail('This email is already registered as a Delivery Personnel.');
                        return;
                    }
                    if (\App\Models\Guard::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                        $fail('This email is already registered as a Guard.');
                        return;
                    }
                    if (\App\Models\Resident::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                        $fail('This email is already registered as a Resident.');
                        return;
                    }
                },
            ],
            'phone' => [
                'required',
                'string',
                'unique:visitors',
                'regex:/^(?:\+?6)?01[0-9](?:[- ]?\d){7,8}$/'
            ],
            'ic_number' => [
                'required',
                'string',
                'regex:/^(?:\d{6}-\d{2}-\d{4}|\d{12}|[a-zA-Z0-9]{6,20})$/'
            ],
            'vehicle_number' => 'required|string|max:20',
            'unit_number' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $parts = explode('-', $value);
                    if (count($parts) !== 3) {
                        $fail('The '.$attribute.' must be in the format Block-Floor-House Number.');

                        return;
                    }

                    foreach ($parts as $part) {
                        if (! ctype_digit(trim($part)) || (int) trim($part) <= 0) {
                            $fail('Each part of the '.$attribute.' must be a positive integer.');

                            return;
                        }
                    }

                    [$block, $floor, $unit] = array_map('trim', $parts);

                    $exists = \App\Models\HouseUnit::where('block', $block)
                        ->where('floor', $floor)
                        ->where('unit_number', $unit)
                        ->exists();

                    if (! $exists) {
                        $fail('The selected house unit does not exist.');
                    }
                },
            ],
            'host_name' => 'required|string|max:255',
            'purpose' => 'required|string',
            'face_descriptor' => 'required',
            'photo' => 'nullable|image|max:2048',
        ], [
            'phone.regex' => 'The phone number must be a valid Malaysian mobile number (e.g. 012-3456789 or 011-12345678).',
            'ic_number.regex' => 'The IC Number must be a valid Malaysian IC (e.g. 950101-14-1234) or a valid Passport Number (6-20 alphanumeric characters).',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('visitors', 'public');
        }

        $visitor = Visitor::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'ic_number' => $request->ic_number,
            'vehicle_number' => $request->vehicle_number,
            'face_descriptor' => json_encode($request->face_descriptor),
            'photo' => $photoPath,
        ]);

        $visit = Visit::create([
            'visitor_id' => $visitor->id,
            'unit_number' => $request->unit_number,
            'purpose' => $request->purpose,
            'host_name' => $request->host_name,
            'status' => 'Pending', // Enforce Pending status for guard-registered visitors
            'qr_code_token' => Str::random(40),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Visitor registered successfully. Waiting for resident approval.',
            'redirect' => route('guard.scan.verify', $visit->id),
        ]);
    }

    /**
     * Register a new delivery personnel and create an instant log.
     */
    public function registerDelivery(Request $request)
    {
        $normalizeUnit = function ($val) {
            if (empty($val)) return $val;
            $parts = preg_split('/\s*-\s*/', trim((string) $val));
            if (count($parts) !== 3) return $val;
            $normaliseSegment = fn($s) => is_numeric($s) ? (string)(int)$s : trim($s);
            return $normaliseSegment($parts[0]) . '-' . $normaliseSegment($parts[1]) . '-' . $normaliseSegment($parts[2]);
        };

        if ($request->has('unit_number')) {
            $request->merge([
                'unit_number' => $normalizeUnit($request->unit_number),
            ]);
        }

        if ($request->has('unit_numbers') && is_array($request->unit_numbers)) {
            $normalized = array_map(fn($val) => $normalizeUnit($val), $request->unit_numbers);
            $request->merge(['unit_numbers' => $normalized]);
        }

        $isMulti = $request->input('delivery_type') === 'multi';

        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    $email = strtolower(trim($value));
                    if (\App\Models\Visitor::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                        $fail('This email is already registered as a Visitor.');
                        return;
                    }
                    if (\App\Models\DeliveryPersonnel::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                        $fail('This email is already registered as a Delivery Personnel.');
                        return;
                    }
                    if (\App\Models\Guard::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                        $fail('This email is already registered as a Guard.');
                        return;
                    }
                    if (\App\Models\Resident::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                        $fail('This email is already registered as a Resident.');
                        return;
                    }
                },
            ],
            'phone' => [
                'required',
                'string',
                'unique:delivery_personnels',
                'regex:/^(?:\+?6)?01[0-9](?:[- ]?\d){7,8}$/'
            ],
            'company' => 'required|string',
            'vehicle_number' => 'required|string',
            'ic_number' => [
                'required',
                'string',
                'regex:/^(?:\d{6}-\d{2}-\d{4}|\d{12}|[a-zA-Z0-9]{6,20})$/',
                function ($attribute, $value, $fail) {
                    $exists = DeliveryPersonnel::all()->contains(function ($personnel) use ($value) {
                        return $personnel->ic_number === $value;
                    });
                    if ($exists) {
                        $fail('The ' . str_replace('_', ' ', $attribute) . ' has already been taken.');
                    }
                },
            ],
            'delivery_type' => 'required|in:single,multi',
            'face_descriptor' => 'required',
            'photo' => 'nullable|image|max:2048',
        ];

        if ($isMulti) {
            $rules['unit_numbers'] = 'required|array|min:2';
            $rules['unit_numbers.*'] = [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $parts = explode('-', $value);
                    if (count($parts) !== 3) {
                        $fail('The '.$attribute.' must be in the format Block-Floor-House Number.');
                        return;
                    }
                    foreach ($parts as $part) {
                        if (! ctype_digit(trim($part)) || (int) trim($part) <= 0) {
                            $fail('Each part of the '.$attribute.' must be a positive integer.');
                            return;
                        }
                    }
                    [$block, $floor, $unit] = array_map('trim', $parts);
                    $exists = \App\Models\HouseUnit::where('block', $block)
                        ->where('floor', $floor)
                        ->where('unit_number', $unit)
                        ->exists();
                    if (! $exists) {
                        $fail('The selected house unit does not exist.');
                    }
                }
            ];
            $rules['host_names'] = 'required|array|min:2';
            $rules['host_names.*'] = 'required|string|max:255';
        } else {
            $rules['unit_number'] = [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $parts = explode('-', $value);
                    if (count($parts) !== 3) {
                        $fail('The '.$attribute.' must be in the format Block-Floor-House Number.');
                        return;
                    }
                    foreach ($parts as $part) {
                        if (! ctype_digit(trim($part)) || (int) trim($part) <= 0) {
                            $fail('Each part of the '.$attribute.' must be a positive integer.');
                            return;
                        }
                    }
                    [$block, $floor, $unit] = array_map('trim', $parts);
                    $exists = \App\Models\HouseUnit::where('block', $block)
                        ->where('floor', $floor)
                        ->where('unit_number', $unit)
                        ->exists();
                    if (! $exists) {
                        $fail('The selected house unit does not exist.');
                    }
                }
            ];
            $rules['host_name'] = 'required|string|max:255';
        }

        $request->validate($rules, [
            'phone.regex' => 'The phone number must be a valid Malaysian mobile number (e.g. 012-3456789 or 011-12345678).',
            'ic_number.regex' => 'The IC Number must be a valid Malaysian IC (e.g. 950101-14-1234) or a valid Passport Number (6-20 alphanumeric characters).',
            'unit_numbers.min' => 'You must add at least 2 destinations for multi-stop delivery.',
            'host_names.min' => 'You must add at least 2 host names for multi-stop delivery.',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('delivery_photos', 'public');
        }

        $delivery = DeliveryPersonnel::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'company' => $request->company,
            'vehicle_type' => 'Other',
            'vehicle_number' => $request->vehicle_number,
            'ic_number' => $request->ic_number,
            'face_descriptor' => json_encode($request->face_descriptor),
            'photo' => $photoPath,
            'status' => 'Active',
        ]);

        $destinations = $isMulti
            ? array_values(array_unique($request->input('unit_numbers', [])))
            : [$request->string('unit_number')->toString()];

        $hostNameParam = $isMulti
            ? $request->input('host_names')
            : $request->string('host_name')->toString();

        $deliveryTripService = new \App\Services\DeliveryTripService();
        $run = $deliveryTripService->createRun(
            $delivery,
            $request->input('delivery_type'),
            $destinations,
            $hostNameParam
        );

        $firstLog = $run->logs()->first();

        $approvedCount = $run->logs->where('status', 'Approved')->count();
        $pendingCount = $run->logs->where('status', 'Pending')->count();

        if ($isMulti) {
            $message = "Multi-stop trip registered with {$run->logs->count()} units.";
            if ($approvedCount > 0) {
                $message .= " {$approvedCount} auto-approved.";
            }
            if ($pendingCount > 0) {
                $message .= " {$pendingCount} waiting for resident approval.";
            }
        } else {
            $message = $approvedCount > 0
                ? 'Delivery registered successfully. Auto-approved by resident!'
                : 'Delivery registered successfully. Waiting for resident approval.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'redirect' => route('guard.scan.verify-delivery', $firstLog->id),
        ]);
    }

    /**
     * Automatically check out stale visits and delivery logs that are older than 24 hours (or 6 hours for temporary leave).
     */
    protected function autoFinalizeOldVisits()
    {
        $cutoff = now()->subHours(24);
        $tempCutoff = now()->subHours(6);

        // Auto check-out visits older than 24 hours that are still Checked In
        $oldVisits = Visit::where('status', 'Checked In')
            ->where('check_in_time', '<', $cutoff)
            ->get();

        foreach ($oldVisits as $v) {
            $now = now();
            // Close any open session records
            VisitSession::where('visit_id', $v->id)
                ->whereNull('check_out_time')
                ->update(['check_out_time' => $now]);

            $upData = [
                'status' => 'Checked Out',
                'check_out_time' => $now,
            ];
            if (is_null($v->first_check_out_time)) {
                $upData['first_check_out_time'] = $now;
            } elseif (is_null($v->second_check_out_time)) {
                $upData['second_check_out_time'] = $now;
            }
            $v->update($upData);
        }

        // Auto check-out 'Temporarily Out' visits that have been out for more than 6 hours
        Visit::where('status', 'Temporarily Out')
            ->where('updated_at', '<', $tempCutoff)
            ->update([
                'status' => 'Checked Out',
                'check_out_time' => now(),
            ]);

        // Auto check-out delivery logs older than 24 hours
        DeliveryLog::whereNotNull('entry_time')
            ->whereNull('exit_time')
            ->where('entry_time', '<', $cutoff)
            ->update([
                'status' => 'Checked Out',
                'exit_time' => now(),
            ]);

        // Auto check-out 'Temporarily Out' delivery logs that have been out for more than 6 hours
        DeliveryLog::where('status', 'Temporarily Out')
            ->where('updated_at', '<', $tempCutoff)
            ->update([
                'status' => 'Checked Out',
                'exit_time' => now(),
            ]);
    }
}

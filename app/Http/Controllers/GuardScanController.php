<?php

namespace App\Http\Controllers;

use App\Events\VisitStatusUpdated;
use App\Models\Visitor;
use App\Models\Visit;
use App\Models\DeliveryPersonnel;
use App\Models\DeliveryLog;
use App\Models\Resident;
use App\Notifications\VisitRequestNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

class GuardScanController extends Controller
{
    public function index()
    {
        return Inertia::render('Guard/Scan');
    }

    /**
     * Show the Verify Visitor page.
     */
    public function verify(Visit $visit)
    {
        $occupiedCount = Visit::whereIn('status', ['Checked In', 'Temporarily Out'])
            ->whereNotNull('parking_lot_number')
            ->count();

        return Inertia::render('Guard/VerifyVisitor', [
            'visit' => $visit->load('visitor'),
            'parking' => [
                'occupied' => $occupiedCount,
                'total' => 15,
                'available' => max(0, 15 - $occupiedCount)
            ]
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
                ]
            ]
        ]);
    }

    public function verifyDelivery(DeliveryLog $log)
    {
        return Inertia::render('Guard/VerifyVisitor', [
            'visit' => [
                'id' => $log->id,
                'status' => $log->status,
                'unit_number' => $log->destination,
                'purpose' => 'Delivery Service (' . $log->personnel->company . ')',
                'visitor' => $log->personnel,
                'is_delivery' => true
            ]
        ]);
    }

    /**
     * Get delivery log details as JSON (for polling).
     */
    public function showDelivery(DeliveryLog $log)
    {
        return response()->json([
            'success' => true,
            'visit' => [
                'id' => $log->id,
                'status' => $log->status,
                'unit_number' => $log->destination,
                'purpose' => 'Delivery Service (' . $log->personnel->company . ')',
                'visitor' => [
                    'name' => $log->personnel->name,
                    'phone' => $log->personnel->phone,
                    'photo' => $log->personnel->photo,
                    'vehicle_number' => $log->personnel->vehicle_number,
                    'face_descriptor' => $log->personnel->face_descriptor,
                ],
                'is_delivery' => true
            ]
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
            'approved_upcoming' => Visit::where('status', 'Approved')->count() + DeliveryLog::where('status', 'Approved')->whereNull('entry_time')->count(),
            'occupied_parking' => $occupiedLotsMap->count(),
            'total_parking' => 15,
        ];

        $approvedDeliveries = DeliveryLog::with('personnel')
            ->where('status', 'Approved')
            ->whereNull('entry_time')
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
        
        if ($tokenParts[0] === 'DELIVERY_LOG') {
            $logId = $tokenParts[1] ?? null;
            $log = DeliveryLog::with('personnel')
                ->where('id', $logId)
                ->first();

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid delivery QR code. No matching log found.',
                ], 404);
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
                    'purpose' => 'Delivery (' . ($log->personnel->company ?? 'Unknown') . ')',
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

        if (!$visit) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code. No matching visit found.',
            ], 404);
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
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
        ]);

        $visit = Visit::findOrFail($request->visit_id);

        if (!in_array($visit->status, ['Approved', 'Temporarily Out'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only Approved or Temporarily Out visits can be checked in. Current status: ' . $visit->status,
            ], 400);
        }

        $visitor = $visit->visitor;
        $hasVehicle = $visitor && !empty($visitor->vehicle_number) && $visitor->vehicle_number !== '-' && strtolower($visitor->vehicle_number) !== 'n/a';
        
        $parkingLotNumber = $visit->parking_lot_number;
        if ($hasVehicle && is_null($parkingLotNumber)) {
            // Find occupied parking lots
            $occupiedLots = Visit::whereIn('status', ['Checked In', 'Temporarily Out'])
                ->whereNotNull('parking_lot_number')
                ->pluck('parking_lot_number')
                ->toArray();
            
            // Find the first available lot
            for ($i = 1; $i <= 15; $i++) {
                if (!in_array($i, $occupiedLots)) {
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

        $visit->update([
            'status' => 'Checked In',
            'check_in_time' => now(),
            'parking_lot_number' => $parkingLotNumber,
        ]);

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
        broadcast(new VisitStatusUpdated(
            $visit->id,
            'Checked In',
            $parkingLotNumber,
            $visit->unit_number
        ));

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
            'log_id' => 'required|exists:delivery_logs,id',
        ]);

        $log = DeliveryLog::findOrFail($request->log_id);

        if (!in_array($log->status, ['Approved', 'Temporarily Out'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only Approved or Temporarily Out deliveries can be checked in.',
            ], 400);
        }

        $log->update([
            'entry_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery checked in successfully!',
        ]);
    }

    /**
     * Check out a visitor (update visit status).
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

        $visit->update([
            'status' => $newStatus,
            'check_out_time' => $isTemporary ? null : now(),
        ]);

        // Broadcast check-out status to visitor's phone and guard dashboard
        broadcast(new VisitStatusUpdated(
            $visit->id,
            $newStatus,
            $visit->parking_lot_number,
            $visit->unit_number
        ));

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
            'log_id' => 'required|exists:delivery_logs,id',
        ]);

        $log = DeliveryLog::findOrFail($request->log_id);

        if (!$log->entry_time || $log->exit_time) {
            return response()->json([
                'success' => false,
                'message' => 'Personnel must be checked in and not already checked out.',
            ], 400);
        }

        $isTemporary = $request->input('is_temporary', false);

        $log->update([
            'status' => $isTemporary ? 'Temporarily Out' : 'Checked Out',
            'exit_time' => $isTemporary ? null : now(),
        ]);

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
        $activeVisitors = Visit::with('visitor')
            ->whereIn('status', ['Checked In', 'Temporarily Out'])
            ->get()
            ->map(function ($visit) {
                return [
                    'id' => $visit->id,
                    'type' => 'Visitor',
                    'name' => $visit->visitor->name,
                    'phone' => $visit->visitor->phone,
                    'photo' => $visit->visitor->photo,
                    'vehicle_number' => $visit->visitor->vehicle_number,
                    'unit_number' => $visit->unit_number,
                    'purpose' => $visit->purpose,
                    'entry_time' => $visit->check_in_time,
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
                    'purpose' => 'Delivery (' . $log->personnel->company . ')',
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
     * Show the registration form for Guard.
     */
    public function showRegistration()
    {
        return Inertia::render('Guard/Register');
    }

    /**
     * Register a new visitor and create an instant check-in.
     */
    public function registerVisitor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:visitors',
            'ic_number' => 'required|string|max:255',
            'vehicle_number' => 'required|string|max:20',
            'unit_number' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $parts = explode(' - ', $value);
                    if (count($parts) !== 3) {
                        $fail('The ' . $attribute . ' must be in the format Block - Floor - Number.');
                        return;
                    }

                    foreach ($parts as $part) {
                        if (!ctype_digit(trim($part)) || (int)trim($part) <= 0) {
                            $fail('Each part of the ' . $attribute . ' must be a positive integer.');
                            return;
                        }
                    }

                                        [$block, $floor, $unit] = array_map('trim', $parts);

                    $exists = \App\Models\HouseUnit::where('block', $block)
                        ->where('floor', $floor)
                        ->where('unit_number', $unit)
                        ->exists();

                    if (!$exists) {
                        $fail('The selected house unit does not exist.');
                    }
                },
            ],
            'purpose' => 'required|string',
            'face_descriptor' => 'required',
            'photo' => 'nullable|image|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('visitors', 'public');
        }

        $visitor = Visitor::create([
            'name' => $request->name,
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
            'status' => 'Pending', // Enforce Pending status for guard-registered visitors
            'qr_code_token' => Str::random(40),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Visitor registered successfully. Waiting for resident approval.',
            'redirect' => route('guard.scan.verify', $visit->id)
        ]);
    }

    /**
     * Register a new delivery personnel and create an instant log.
     */
    public function registerDelivery(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:delivery_personnels',
            'company' => 'required|string',
            'vehicle_number' => 'required|string',
            'ic_number' => 'required|string|unique:delivery_personnels',
            'unit_number' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $parts = explode(' - ', $value);
                    if (count($parts) !== 3) {
                        $fail('The ' . $attribute . ' must be in the format Block - Floor - Number.');
                        return;
                    }

                    foreach ($parts as $part) {
                        if (!ctype_digit(trim($part)) || (int)trim($part) <= 0) {
                            $fail('Each part of the ' . $attribute . ' must be a positive integer.');
                            return;
                        }
                    }

                                        [$block, $floor, $unit] = array_map('trim', $parts);

                    $exists = \App\Models\HouseUnit::where('block', $block)
                        ->where('floor', $floor)
                        ->where('unit_number', $unit)
                        ->exists();

                    if (!$exists) {
                        $fail('The selected house unit does not exist.');
                    }
                },
            ],
            'face_descriptor' => 'required',
            'photo' => 'nullable|image|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('delivery_photos', 'public');
        }

        $delivery = DeliveryPersonnel::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'company' => $request->company,
            'vehicle_type' => 'Other', // Default or could be a field
            'vehicle_number' => $request->vehicle_number,
            'ic_number' => $request->ic_number,
            'face_descriptor' => json_encode($request->face_descriptor),
            'photo' => $photoPath,
            'status' => 'Active',
        ]);

        $parts = explode(' - ', $request->unit_number);
        [$block, $floor, $unit] = array_map('trim', $parts);
        $houseUnit = \App\Models\HouseUnit::where('block', $block)
            ->where('floor', $floor)
            ->where('unit_number', $unit)
            ->first();

        // Check auto-approve toggle for any resident in this unit
        $hasAutoApprove = $houseUnit 
            ? $houseUnit->residents()->where('auto_approve_deliveries', true)->exists() 
            : false;

        $status = $hasAutoApprove ? 'Approved' : 'Pending';

        $log = DeliveryLog::create([
            'delivery_personnel_id' => $delivery->id,
            'destination' => $request->unit_number ?? 'N/A',
            'status' => $status,
        ]);

        $message = $hasAutoApprove 
            ? 'Delivery registered successfully. Auto-approved by resident!' 
            : 'Delivery registered successfully. Waiting for resident approval.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'redirect' => route('guard.scan.verify-delivery', $log->id)
        ]);
    }

    /**
     * Automatically check out stale visits and delivery logs that are older than 24 hours (or 6 hours for temporary leave).
     */
    protected function autoFinalizeOldVisits()
    {
        $cutoff = now()->subHours(24);
        $tempCutoff = now()->subHours(6);

        // Auto check-out visits older than 24 hours
        Visit::whereIn('status', ['Checked In', 'Temporarily Out'])
            ->where('check_in_time', '<', $cutoff)
            ->update([
                'status' => 'Checked Out',
                'check_out_time' => now(),
            ]);

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


<?php

namespace App\Http\Controllers;

use App\Events\DeliveryStatusUpdated;
use App\Events\VisitStatusUpdated;
use App\Models\DeliveryLog;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ResidentVisitorController extends Controller
{
    /**
     * Display a listing of the resident's visits.
     */
    public function index()
    {
        $resident = Auth::guard('resident')->user();

        // Get visits where the unit_number matches the resident's unit
        $unitNumber = $resident->houseUnit->formatted_unit;

        $visits = Visit::with(['visitor', 'sessions'])
            ->where('unit_number', $unitNumber)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($visit) {
                $totalSecs = 0;
                foreach ($visit->sessions as $sess) {
                    if ($sess->check_in_time) {
                        $end = $sess->check_out_time ?? ($visit->status === 'Checked In' ? now() : null);
                        if ($end) {
                            $diff = (int) $sess->check_in_time->diffInSeconds($end, false);
                            if ($diff > 0) $totalSecs += $diff;
                        }
                    }
                }
                $visit->sessions_count = $visit->sessions->count();
                $visit->total_duration_minutes = $totalSecs > 0 ? intdiv($totalSecs, 60) : 0;

                $firstSession = $visit->sessions->first();
                $lastSession = $visit->sessions->last();
                if ($firstSession) {
                    $visit->check_in_time = $firstSession->check_in_time;
                }
                if ($lastSession) {
                    $visit->check_out_time = $lastSession->check_out_time;
                }

                return $visit;
            });

        $deliveryUnitNumber = $resident->houseUnit->formatted_unit;

        $deliveries = DeliveryLog::with('personnel')
            ->where('destination', $deliveryUnitNumber)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Resident/Visitors/Index', [
            'visits' => $visits,
            'deliveries' => $deliveries,
        ]);

    }

    /**
     * Show the visitor pre-registration form.
     */
    public function create()
    {
        return Inertia::render('Resident/Visitors/Create');
    }

    /**
     * Store a pre-registered visitor.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'purpose' => 'required|string|max:255',
        ]);

        // Find or create Visitor by email to prevent clashing / duplication
        $visitor = \App\Models\Visitor::where('email', $request->email)->first();

        if ($visitor) {
            // Check if visitor already has an active visit (Pending, Approved, Checked In, Temporarily Out)
            $activeVisit = \App\Models\Visit::where('visitor_id', $visitor->id)
                ->active()
                ->first();

            if ($activeVisit) {
                return back()->withErrors([
                    'email' => 'This visitor already has an active or pending visit request.',
                ]);
            }
        } else {
            // Check cross-role collision with Delivery to prevent errors
            if (\App\Models\DeliveryPersonnel::where('email', $request->email)->exists()) {
                return back()->withErrors([
                    'email' => 'This email address is already registered as a delivery personnel.',
                ]);
            }

            // Create a partially completed visitor profile
            $visitor = \App\Models\Visitor::create([
                'name' => $request->name,
                'phone' => '-', // Default placeholder since phone is not gathered during pre-registration
                'email' => $request->email,
                'vehicle_number' => '-', // Default placeholder to bypass database NOT NULL constraint
                // The remaining fields (ic_number, face_descriptor, photo) are blank
            ]);
        }

        $resident = Auth::guard('resident')->user();
        $resident->loadMissing('houseUnit');
        $unitNumber = $resident->houseUnit->formatted_unit;

        $token = 'PRE_REG_'.Str::random(40);

        $visit = Visit::create([
            'visitor_id' => $visitor->id,
            'unit_number' => $unitNumber,
            'purpose' => $request->purpose,
            'host_name' => $resident->name,
            'status' => 'Approved', // Pre-approved by host!
            'approved_by' => $resident->name,
            'approved_at' => now(),
            'qr_code_token' => $token,
        ]);

        return redirect()->route('resident.visitors.index')->with('success', 'Visitor pre-registered successfully! Click "Share Pass" to copy the guest entry link.');
    }

    /**
     * Show the QR code for a specific visit.
     */
    public function showQr(Visit $visit)
    {
        $resident = Auth::guard('resident')->user();
        $resident->loadMissing('houseUnit');

        // Security: only show QR if it belongs to the resident's unit
        $unitNumber = $resident->houseUnit->formatted_unit;
        if ($visit->unit_number !== $unitNumber) {
            abort(403);
        }

        if ($visit->status === 'Expired') {
            return redirect()->route('resident.visitors.index')->with('error', 'This guest pass QR code has expired.');
        }

        $visit->load('visitor');

        return Inertia::render('Resident/Visitors/ShowQr', [
            'visit' => $visit,
            'qrCodeSvg' => (string) QrCode::size(300)->generate($visit->qr_code_token),
        ]);
    }

    /**
     * Approve a pending visit request.
     */
    public function approve(Visit $visit)
    {
        $resident = Auth::guard('resident')->user();
        $resident->loadMissing('houseUnit');

        $unitNumber = $resident->houseUnit->formatted_unit;
        if ($visit->unit_number !== $unitNumber || $visit->status !== 'Pending') {
            abort(403);
        }

        $visit->update([
            'status' => 'Approved',
            'qr_code_token' => Str::uuid()->toString(),
            'approved_by' => $resident->name,
            'approved_at' => now(),
        ]);

        try {
            broadcast(new VisitStatusUpdated($visit->id, 'Approved', null, $visit->unit_number));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcasting failed: '.$e->getMessage());
        }

        return redirect()->back()->with('success', 'Visit request approved!');
    }

    /**
     * Reject a pending visit request.
     */
    public function reject(Visit $visit)
    {
        $resident = Auth::guard('resident')->user();
        $resident->loadMissing('houseUnit');

        $unitNumber = $resident->houseUnit->formatted_unit;
        if ($visit->unit_number !== $unitNumber || $visit->status !== 'Pending') {
            abort(403);
        }

        $visit->update([
            'status' => 'Rejected',
            'approved_by' => $resident->name,
        ]);

        try {
            broadcast(new VisitStatusUpdated($visit->id, 'Rejected', null, $visit->unit_number));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcasting failed: '.$e->getMessage());
        }

        return redirect()->back()->with('success', 'Visit request rejected.');
    }

    /**
     * Approve a pending delivery request.
     */
    public function approveDelivery(DeliveryLog $log)
    {
        $resident = Auth::guard('resident')->user();
        $resident->loadMissing('houseUnit');

        $deliveryUnitNumber = $resident->houseUnit->formatted_unit;
        if ($log->destination !== $deliveryUnitNumber || $log->status !== 'Pending') {
            abort(403);
        }

        $log->update([
            'status' => 'Approved',
            'approved_by' => $resident->name,
            'approved_at' => now(),
            // Note: entry_time will be set by guard upon check-in
        ]);

        $log->run?->refreshStatus();

        try {
            broadcast(new DeliveryStatusUpdated($log->id, 'Approved'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcasting failed: '.$e->getMessage());
        }

        return redirect()->back()->with('success', 'Delivery request approved!');
    }

    /**
     * Reject a pending delivery request.
     */
    public function rejectDelivery(DeliveryLog $log)
    {
        $resident = Auth::guard('resident')->user();
        $resident->loadMissing('houseUnit');

        $deliveryUnitNumber = $resident->houseUnit->formatted_unit;
        if ($log->destination !== $deliveryUnitNumber || $log->status !== 'Pending') {
            abort(403);
        }

        $log->update([
            'status' => 'Rejected',
            'approved_by' => $resident->name,
        ]);

        $log->run?->refreshStatus();

        try {
            broadcast(new DeliveryStatusUpdated($log->id, 'Rejected'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcasting failed: '.$e->getMessage());
        }

        return redirect()->back()->with('success', 'Delivery request rejected.');
    }

    /**
     * Cancel an approved visit request.
     */
    public function cancelVisit(Visit $visit)
    {
        $resident = Auth::guard('resident')->user();
        $resident->loadMissing('houseUnit');

        $unitNumber = $resident->houseUnit->formatted_unit;
        if ($visit->unit_number !== $unitNumber || $visit->status !== 'Approved') {
            abort(403);
        }

        $visit->update([
            'status' => 'Cancelled',
        ]);

        try {
            broadcast(new VisitStatusUpdated($visit->id, 'Cancelled', null, $visit->unit_number));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcasting failed: '.$e->getMessage());
        }

        return redirect()->back()->with('success', 'Visit request cancelled.');
    }

    /**
     * Cancel an approved delivery request.
     */
    public function cancelDelivery(DeliveryLog $log)
    {
        $resident = Auth::guard('resident')->user();
        $resident->loadMissing('houseUnit');

        $deliveryUnitNumber = $resident->houseUnit->formatted_unit;
        if ($log->destination !== $deliveryUnitNumber || $log->status !== 'Approved') {
            abort(403);
        }

        $log->update([
            'status' => 'Cancelled',
        ]);

        $log->run?->refreshStatus();

        try {
            broadcast(new DeliveryStatusUpdated($log->id, 'Cancelled'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcasting failed: '.$e->getMessage());
        }

        return redirect()->back()->with('success', 'Delivery request cancelled.');
    }
}


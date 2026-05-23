<?php

namespace App\Http\Controllers;

use App\Events\VisitStatusUpdated;
use App\Models\Visit;
use App\Models\Visitor;
use App\Models\DeliveryLog;
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
        $unitNumber = $resident->houseUnit->block . '-' . $resident->houseUnit->floor . '-' . $resident->houseUnit->unit_number;
        
        $visits = Visit::with('visitor')
            ->where('unit_number', $unitNumber)
            ->orderBy('created_at', 'desc')
            ->get();

        $deliveryUnitNumber = $resident->houseUnit->block . ' - ' . $resident->houseUnit->floor . ' - ' . $resident->houseUnit->unit_number;

        $deliveries = DeliveryLog::with('personnel')
            ->where('destination', $deliveryUnitNumber)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return Inertia::render('Resident/Visitors/Index', [
            'visits' => $visits,
            'deliveries' => $deliveries
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
            'phone' => 'required|string|max:20',
            'email' => 'required|email',
            'purpose' => 'required|string|max:255',
        ]);

        // Find or create Visitor by email or phone to prevent clashing / duplication
        $visitor = \App\Models\Visitor::where('email', $request->email)
            ->orWhere('phone', $request->phone)
            ->first();

        if (!$visitor) {
            // Check cross-role collision with Delivery to prevent errors
            if (\App\Models\DeliveryPersonnel::where('email', $request->email)->exists()) {
                return back()->withErrors([
                    'email' => 'This email address is already registered as a delivery personnel.'
                ]);
            }

            // Create a partially completed visitor profile
            $visitor = \App\Models\Visitor::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'vehicle_number' => '-', // Default placeholder to bypass database NOT NULL constraint
                // The remaining fields (ic_number, face_descriptor, photo) are blank
            ]);
        }

        $resident = Auth::guard('resident')->user();
        $resident->loadMissing('houseUnit');
        $unitNumber = $resident->houseUnit->block . '-' . $resident->houseUnit->floor . '-' . $resident->houseUnit->unit_number;

        $token = 'PRE_REG_' . Str::random(40);

        $visit = Visit::create([
            'visitor_id' => $visitor->id,
            'unit_number' => $unitNumber,
            'purpose' => $request->purpose,
            'status' => 'Approved', // Pre-approved by host!
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
        $unitNumber = $resident->houseUnit->block . '-' . $resident->houseUnit->floor . '-' . $resident->houseUnit->unit_number;
        if ($visit->unit_number !== $unitNumber) {
            abort(403);
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
        
        $unitNumber = $resident->houseUnit->block . '-' . $resident->houseUnit->floor . '-' . $resident->houseUnit->unit_number;
        if ($visit->unit_number !== $unitNumber || $visit->status !== 'Pending') {
            abort(403);
        }

        $visit->update([
            'status' => 'Approved',
            'qr_code_token' => Str::uuid()->toString(),
        ]);

        broadcast(new VisitStatusUpdated($visit->id, 'Approved', null, $visit->unit_number));

        return redirect()->back()->with('success', 'Visit request approved!');
    }

    /**
     * Reject a pending visit request.
     */
    public function reject(Visit $visit)
    {
        $resident = Auth::guard('resident')->user();
        $resident->loadMissing('houseUnit');
        
        $unitNumber = $resident->houseUnit->block . '-' . $resident->houseUnit->floor . '-' . $resident->houseUnit->unit_number;
        if ($visit->unit_number !== $unitNumber || $visit->status !== 'Pending') {
            abort(403);
        }

        $visit->update(['status' => 'Rejected']);

        broadcast(new VisitStatusUpdated($visit->id, 'Rejected', null, $visit->unit_number));

        return redirect()->back()->with('success', 'Visit request rejected.');
    }

    /**
     * Approve a pending delivery request.
     */
    public function approveDelivery(DeliveryLog $log)
    {
        $resident = Auth::guard('resident')->user();
        $resident->loadMissing('houseUnit');
        
        $deliveryUnitNumber = $resident->houseUnit->block . ' - ' . $resident->houseUnit->floor . ' - ' . $resident->houseUnit->unit_number;
        if ($log->destination !== $deliveryUnitNumber || $log->status !== 'Pending') {
            abort(403);
        }

        $log->update([
            'status' => 'Approved',
            // Note: entry_time will be set by guard upon check-in
        ]);

        return redirect()->back()->with('success', 'Delivery request approved!');
    }

    /**
     * Reject a pending delivery request.
     */
    public function rejectDelivery(DeliveryLog $log)
    {
        $resident = Auth::guard('resident')->user();
        $resident->loadMissing('houseUnit');
        
        $deliveryUnitNumber = $resident->houseUnit->block . ' - ' . $resident->houseUnit->floor . ' - ' . $resident->houseUnit->unit_number;
        if ($log->destination !== $deliveryUnitNumber || $log->status !== 'Pending') {
            abort(403);
        }

        $log->update(['status' => 'Rejected']);

        return redirect()->back()->with('success', 'Delivery request rejected.');
    }
}

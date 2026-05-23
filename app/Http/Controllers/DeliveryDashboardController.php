<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\DeliveryLog;

class DeliveryDashboardController extends Controller
{
    public function index()
    {
        $delivery = Auth::guard('delivery')->user();
        
        if (!$delivery) {
            return redirect('/');
        }

        $logs = DeliveryLog::where('delivery_personnel_id', $delivery->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $latestActiveLog = DeliveryLog::where('delivery_personnel_id', $delivery->id)
            ->whereNull('exit_time')
            ->orderBy('created_at', 'desc')
            ->first();

        $qrCodeSvg = null;
        if ($latestActiveLog) {
            $qrCodeSvg = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(250)
                ->generate('DELIVERY_LOG:' . $latestActiveLog->id);
        }

        // Build a nested map: block → floor → [unit_numbers]
        $houseUnits = \App\Models\HouseUnit::orderBy('block')->orderBy('floor')->orderBy('unit_number')->get();
        $unitMap = [];
        foreach ($houseUnits as $unit) {
            $unitMap[(string)$unit->block][(string)$unit->floor][] = (string)$unit->unit_number;
        }

        return Inertia::render('Delivery/Dashboard', [
            'delivery' => $delivery,
            'logs' => $logs,
            'activeLog' => $latestActiveLog,
            'qrCodeSvg' => $qrCodeSvg,
            'houseUnits' => $unitMap,
        ]);
    }

    public function createTrip(Request $request)
    {
        $delivery = Auth::guard('delivery')->user();
        if (!$delivery) {
            return redirect('/');
        }

        $request->validate([
            'unit_number' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $parts = explode(' - ', $value);
                    if (count($parts) !== 3) {
                        return $fail('The unit number must be in the format: Block - Floor - House Number');
                    }
                    [$block, $floor, $unit] = array_map('trim', $parts);

                    $exists = \App\Models\HouseUnit::where('block', $block)
                        ->where('floor', $floor)
                        ->where('unit_number', $unit)
                        ->exists();

                    if (!$exists) {
                        $fail('The selected destination unit does not exist in our records.');
                    }
                }
            ]
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

        DeliveryLog::create([
            'delivery_personnel_id' => $delivery->id,
            'destination' => $request->unit_number,
            'status' => $status,
        ]);

        $msg = $hasAutoApprove 
            ? 'Trip created and auto-approved by resident!' 
            : 'Trip created. Waiting for resident approval.';

        return redirect()->back()->with('success', $msg);
    }

    public function register(Request $request)
    {
        return Inertia::render('Delivery/Register', [
            'phone' => $request->query('phone'),
            'email' => $request->query('email')
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:delivery_personnels,email|unique:visitors,email',
            'company' => 'required|string',
            'phone' => 'required|string|unique:delivery_personnels',
            'vehicle_type' => 'required|string',
            'vehicle_number' => 'required|string',
            'ic_number' => 'required|string|unique:delivery_personnels',
            'face_descriptor' => 'required',
            'photo' => 'nullable|image|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('delivery_photos', 'public');
        }

        $delivery = \App\Models\DeliveryPersonnel::create([
            'name' => $request->name,
            'email' => $request->email,
            'company' => $request->company,
            'phone' => $request->phone,
            'vehicle_type' => $request->vehicle_type,
            'vehicle_number' => $request->vehicle_number,
            'ic_number' => $request->ic_number,
            'face_descriptor' => json_encode($request->face_descriptor),
            'photo' => $photoPath,
            'status' => 'Active',
        ]);

        Auth::guard('delivery')->login($delivery);

        return redirect()->route('delivery.dashboard');
    }

    public function profile()
    {
        return Inertia::render('Delivery/Profile', [
            'delivery' => Auth::guard('delivery')->user()
        ]);
    }

    public function updateProfile(Request $request)
    {
        $delivery = Auth::guard('delivery')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:delivery_personnels,phone,' . $delivery->id,
            'vehicle_number' => 'required|string|max:20',
            'face_descriptor' => 'nullable', // Array or JSON string
            'photo' => 'nullable|image|max:2048',
        ]);

        $delivery->name = $request->name;
        $delivery->phone = $request->phone;
        $delivery->vehicle_number = $request->vehicle_number;

        if ($request->has('face_descriptor') && $request->face_descriptor) {
             $descriptor = $request->face_descriptor;
             if (is_array($descriptor)) {
                 $descriptor = json_encode($descriptor);
             }
             $delivery->face_descriptor = $descriptor;
        }

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('delivery_photos', 'public');
            $delivery->photo = $photoPath;
        }

        $delivery->save();

        return redirect()->route('delivery.profile')->with('success', 'Profile updated successfully.');
    }

    public function destroy(Request $request)
    {
        Auth::guard('delivery')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}

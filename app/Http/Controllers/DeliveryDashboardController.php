<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryTripRequest;
use App\Models\DeliveryLog;
use App\Models\DeliveryRun;
use App\Services\DeliveryTripService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DeliveryDashboardController extends Controller
{
    public function index()
    {
        $delivery = Auth::guard('delivery')->user();

        if (! $delivery) {
            return redirect('/');
        }

        $logs = DeliveryLog::where('delivery_personnel_id', $delivery->id)
            ->with('run')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $latestRun = DeliveryRun::query()
            ->where('delivery_personnel_id', $delivery->id)
            ->latest()
            ->first();

        $latestStandaloneLog = DeliveryLog::query()
            ->where('delivery_personnel_id', $delivery->id)
            ->whereNull('delivery_run_id')
            ->latest()
            ->first();

        $latestItem = null;
        if ($latestRun && $latestStandaloneLog) {
            $latestItem = $latestRun->created_at->gt($latestStandaloneLog->created_at) ? $latestRun : $latestStandaloneLog;
        } else {
            $latestItem = $latestRun ?: $latestStandaloneLog;
        }

        $activeRun = null;
        $latestActiveLog = null;

        if ($latestItem instanceof DeliveryRun) {
            if (in_array($latestItem->status, ['Pending', 'Approved', 'Checked In'])) {
                $latestItem->load('logs');
                $hasActiveLogs = $latestItem->logs->contains(fn ($log) => $log->exit_time === null && !in_array($log->status, ['Cancelled', 'Rejected']));
                if ($hasActiveLogs) {
                    $activeRun = $latestItem;
                    $latestActiveLog = $activeRun->logs->first(fn ($log) => $log->exit_time === null && !in_array($log->status, ['Cancelled', 'Rejected']));
                }
            }
        } elseif ($latestItem instanceof DeliveryLog) {
            if (in_array($latestItem->status, ['Pending', 'Approved', 'Checked In']) && is_null($latestItem->exit_time)) {
                $latestActiveLog = $latestItem;
            }
        }

        $qrCodeSvg = null;
        if ($activeRun) {
            $qrCodeSvg = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(250)
                ->generate('DELIVERY_RUN:'.$activeRun->id);
        } elseif ($latestActiveLog) {
            $qrCodeSvg = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(250)
                ->generate('DELIVERY_LOG:'.$latestActiveLog->id);
        }

        // Build a nested map: block → floor → [unit_numbers]
        $houseUnits = \App\Models\HouseUnit::orderBy('block')->orderBy('floor')->orderBy('unit_number')->get();
        $unitMap = [];
        foreach ($houseUnits as $unit) {
            $unitMap[(string) $unit->block][(string) $unit->floor][] = (string) $unit->unit_number;
        }

        return Inertia::render('Delivery/Dashboard', [
            'delivery' => $delivery,
            'logs' => $logs,
            'activeLog' => $latestActiveLog,
            'activeRun' => $activeRun,
            'qrCodeSvg' => $qrCodeSvg,
            'houseUnits' => $unitMap,
        ]);
    }

    public function createTrip(StoreDeliveryTripRequest $request, DeliveryTripService $deliveryTripService)
    {
        $delivery = Auth::guard('delivery')->user();
        if (! $delivery) {
            return redirect('/');
        }

        $hasOpenRun = DeliveryRun::query()
            ->where('delivery_personnel_id', $delivery->id)
            ->whereIn('status', ['Pending', 'Approved', 'Checked In'])
            ->exists();

        $hasOpenStandaloneLog = DeliveryLog::query()
            ->where('delivery_personnel_id', $delivery->id)
            ->whereNull('delivery_run_id')
            ->whereIn('status', ['Pending', 'Approved', 'Checked In'])
            ->whereNull('exit_time')
            ->exists();

        if ($hasOpenRun || $hasOpenStandaloneLog) {
            return redirect()->back()->with('error', 'You already have an active delivery trip. Please complete or check out before starting a new one.');
        }

        $destinations = $request->input('delivery_type') === 'multi'
            ? array_values(array_unique($request->input('unit_numbers', [])))
            : [$request->string('unit_number')->toString()];

        $run = $deliveryTripService->createRun(
            $delivery,
            $request->input('delivery_type'),
            $destinations,
            $request->string('host_name')->toString()
        );

        $approvedCount = $run->logs->where('status', 'Approved')->count();
        $pendingCount = $run->logs->where('status', 'Pending')->count();

        if ($request->input('delivery_type') === 'multi') {
            $msg = "Multi-stop trip created with {$run->logs->count()} units.";
            if ($approvedCount > 0) {
                $msg .= " {$approvedCount} auto-approved.";
            }
            if ($pendingCount > 0) {
                $msg .= " {$pendingCount} waiting for resident approval.";
            }
        } else {
            $msg = $approvedCount > 0
                ? 'Trip created and auto-approved by resident!'
                : 'Trip created. Waiting for resident approval.';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function cancelTrip($id)
    {
        $delivery = Auth::guard('delivery')->user();
        if (! $delivery) {
            return redirect('/');
        }

        // Try to find a run first
        $run = DeliveryRun::where('id', $id)
            ->where('delivery_personnel_id', $delivery->id)
            ->first();

        if ($run) {
            if (! in_array($run->status, ['Pending', 'Approved'])) {
                return redirect()->back()->with('error', 'Only pending or approved trips can be cancelled.');
            }
            $run->logs()->update(['status' => 'Cancelled']);
            $run->update(['status' => 'Cancelled']);
            return redirect()->back()->with('success', 'Trip cancelled successfully.');
        }

        // If no run is found, try to find a standalone log
        $log = DeliveryLog::where('id', $id)
            ->where('delivery_personnel_id', $delivery->id)
            ->whereNull('delivery_run_id')
            ->first();

        if ($log) {
            if (! in_array($log->status, ['Pending', 'Approved'])) {
                return redirect()->back()->with('error', 'Only pending or approved deliveries can be cancelled.');
            }
            $log->update(['status' => 'Cancelled']);
            return redirect()->back()->with('success', 'Delivery request cancelled successfully.');
        }

        return redirect()->back()->with('error', 'Trip not found.');
    }

    public function register(Request $request)
    {
        return Inertia::render('Delivery/Register', [
            'phone' => $request->query('phone'),
            'email' => $request->query('email'),
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
            'delivery' => Auth::guard('delivery')->user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $delivery = Auth::guard('delivery')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:delivery_personnels,phone,'.$delivery->id,
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

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
                $hasActiveLogs = $latestItem->logs->contains(fn ($log) => $log->exit_time === null && !in_array($log->status, ['Cancelled', 'Rejected']) && !$log->is_expired);
                if ($hasActiveLogs) {
                    $activeRun = $latestItem;
                    $latestActiveLog = $activeRun->logs->first(fn ($log) => $log->exit_time === null && !in_array($log->status, ['Cancelled', 'Rejected']) && !$log->is_expired);
                }
            }
        } elseif ($latestItem instanceof DeliveryLog) {
            if (in_array($latestItem->status, ['Pending', 'Approved', 'Checked In']) && is_null($latestItem->exit_time) && !$latestItem->is_expired) {
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
            ->get()
            ->contains(fn ($run) => $run->status === 'Pending' || $run->status === 'Checked In' || $run->logs->contains(fn ($log) => $log->status === 'Approved' && !$log->is_expired));

        $hasOpenStandaloneLog = DeliveryLog::query()
            ->where('delivery_personnel_id', $delivery->id)
            ->whereNull('delivery_run_id')
            ->whereIn('status', ['Pending', 'Approved', 'Checked In'])
            ->whereNull('exit_time')
            ->get()
            ->contains(fn ($log) => $log->status === 'Pending' || $log->status === 'Checked In' || ($log->status === 'Approved' && !$log->is_expired));

        if ($hasOpenRun || $hasOpenStandaloneLog) {
            return redirect()->back()->with('error', 'You already have an active delivery trip. Please complete or check out before starting a new one.');
        }

        $destinations = $request->input('delivery_type') === 'multi'
            ? array_values(array_unique($request->input('unit_numbers', [])))
            : [$request->string('unit_number')->toString()];

        $hostNameParam = $request->input('delivery_type') === 'multi'
            ? $request->input('host_names')
            : $request->string('host_name')->toString();

        $run = $deliveryTripService->createRun(
            $delivery,
            $request->input('delivery_type'),
            $destinations,
            $hostNameParam
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

    public function history()
    {
        $delivery = Auth::guard('delivery')->user();

        $logs = DeliveryLog::where('delivery_personnel_id', $delivery->id)
            ->with('run')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Delivery/History', [
            'delivery' => $delivery,
            'logs' => $logs,
        ]);
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
            'company' => 'required|string',
            'phone' => [
                'required',
                'string',
                'unique:delivery_personnels',
                'regex:/^(?:\+?6)?01[0-9](?:[- ]?\d){7,8}$/'
            ],
            'vehicle_type' => 'required|string',
            'vehicle_number' => 'required|string',
            'ic_number' => [
                'required',
                'string',
                'unique:delivery_personnels',
                'regex:/^(?:\d{6}-\d{2}-\d{4}|\d{12}|[a-zA-Z0-9]{6,20})$/'
            ],
            'face_descriptor' => 'required',
            'photo' => 'nullable|image|max:2048',
        ], [
            'phone.regex' => 'The phone number must be a valid Malaysian mobile number (e.g. 012-3456789 or 011-12345678).',
            'ic_number.regex' => 'The IC Number must be a valid Malaysian IC (e.g. 950101-14-1234) or a valid Passport Number (6-20 alphanumeric characters).',
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
        $delivery = Auth::guard('delivery')->user();
        
        return Inertia::render('Delivery/Profile', [
            'delivery' => $delivery,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $delivery = Auth::guard('delivery')->user();

        $request->validate([
            'name'           => 'required|string|max:255',
            'phone'          => [
                'required',
                'string',
                'max:20',
                'unique:delivery_personnels,phone,'.$delivery->id,
                'regex:/^(?:\+?6)?01[0-9](?:[- ]?\d){7,8}$/'
            ],
            'ic_number'      => [
                'required',
                'string',
                'unique:delivery_personnels,ic_number,' . $delivery->id,
                'regex:/^(?:\d{6}-\d{2}-\d{4}|\d{12}|[a-zA-Z0-9]{6,20})$/'
            ],
            'company'        => 'required|string|max:255',
            'vehicle_type'   => 'required|string|max:50',
            'vehicle_number' => 'required|string|max:20',
            'face_descriptor' => 'nullable', // Array or JSON string
            'photo'          => 'nullable|image|max:2048',
        ], [
            'phone.regex' => 'The phone number must be a valid Malaysian mobile number (e.g. 012-3456789 or 011-12345678).',
            'ic_number.regex' => 'The IC Number must be a valid Malaysian IC (e.g. 950101-14-1234) or a valid Passport Number (6-20 alphanumeric characters).',
        ]);

        $delivery->name           = $request->name;
        $delivery->phone          = $request->phone;
        $delivery->ic_number      = $request->ic_number;
        $delivery->company        = $request->company;
        $delivery->vehicle_type   = $request->vehicle_type;
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

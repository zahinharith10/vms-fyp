<?php

namespace App\Http\Controllers;

use App\Events\NewVisitRequested;
use App\Events\VisitStatusUpdated;
use Illuminate\Http\Request;
use App\Models\Visit;
use App\Models\Resident;
use App\Notifications\VisitRequestNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class VisitController extends Controller
{
    public function store(Request $request)
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

        $visitor = Auth::guard('visitor')->user();
        
        if (!$visitor) {
            return back()->withErrors(['auth' => 'Unauthorized. Please log in.']);
        }
        
        if (empty($visitor->ic_number) || empty($visitor->photo)) {
            return back()->withErrors(['profile' => 'Please complete your profile details and upload a photo before requesting a visit.']);
        }

        $activeVisit = Visit::where('visitor_id', $visitor->id)
            ->whereIn('status', ['Pending', 'Approved', 'Checked In'])
            ->first();

        if ($activeVisit) {
            return back()->withErrors(['active_visit' => 'You already have an active or pending visit request. Please complete it before requesting a new one.']);
        }

        $request->validate([
            'unit_number' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $parts = explode('-', $value);
                    if (count($parts) !== 3) {
                        $fail('The ' . $attribute . ' must be in the format Block-Floor-Number.');
                        return;
                    }

                    foreach ($parts as $part) {
                        if (!ctype_digit($part) || (int)$part <= 0) {
                            $fail('Each part of the ' . $attribute . ' must be a positive integer.');
                            return;
                        }
                    }

                    [$block, $floor, $unit] = $parts;

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
            'host_name' => 'required|string|max:255',
        ]);

        $visit = Visit::create([
            'visitor_id' => Auth::guard('visitor')->id(),
            'unit_number' => $request->unit_number,
            'purpose' => $request->purpose,
            'host_name' => $request->host_name,
            'status' => 'Pending',
        ]);

        // Notify the resident of this unit
        $resident = null;
        $parts = array_map('trim', explode('-', $request->unit_number));
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
            try {
                $resident->notify(new VisitRequestNotification($visit->load('visitor')));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Notification failed: ' . $e->getMessage());
            }

            // Broadcast real-time notification to resident's unit channel
            try {
                broadcast(new NewVisitRequested(
                    $visit->id,
                    $visitor->name,
                    $request->unit_number,
                    $request->purpose
                ));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Broadcasting failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Visit request submitted successfully!');
    }

    public function destroy(Visit $visit)
    {
        $visitor = Auth::guard('visitor')->user();

        // Security check: only allow if it belongs to this visitor
        if ($visit->visitor_id !== $visitor->id) {
            abort(403);
        }

        // Only allow cancellation if status is Pending or Approved
        if (!in_array($visit->status, ['Pending', 'Approved'])) {
            return redirect()->back()->with('error', 'Only pending or approved visits can be canceled.');
        }

        $visit->delete();

        return redirect()->back()->with('success', 'Visit request canceled successfully.');
    }
}

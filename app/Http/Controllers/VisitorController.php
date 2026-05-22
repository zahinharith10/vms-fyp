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
            'logs' => \App\Models\Visit::with('visitor')->latest('updated_at')->get()
        ]);
    }
    /**
     * Export visit logs as CSV.
     */
    public function exportLogs()
    {
        $logs = \App\Models\Visit::with('visitor')->latest()->get();
        $filename = "visit_logs_" . now()->format('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            
            fputcsv($file, [
                'ID', 'Visitor Name', 'Phone', 'IC Number', 'Unit Number', 
                'Purpose', 'Status', 'Check In Time', 'Check Out Time'
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->visitor->name ?? 'N/A',
                    $log->visitor->phone ?? 'N/A',
                    $log->visitor->ic_number ?? 'N/A',
                    $log->unit_number,
                    $log->purpose,
                    $log->status,
                    $log->check_in_time,
                    $log->check_out_time,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

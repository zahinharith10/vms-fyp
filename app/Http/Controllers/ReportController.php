<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\Guard;
use App\Models\DeliveryPersonnel;
use App\Models\HouseUnit;
use App\Models\Visit;
use App\Models\VisitSession;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Reports/Index');
    }

    public function exportUsers()
    {
        $filename = "users_report_" . now()->format('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // Residents
            fputcsv($file, ['--- RESIDENTS ---']);
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Type', 'Unit Number', 'Auto-Approve Delivery']);
            foreach (Resident::with('houseUnit')->get() as $resident) {
                fputcsv($file, [
                    $resident->id,
                    $resident->name,
                    $resident->email,
                    $resident->phone,
                    $resident->type,
                    $resident->houseUnit ? $resident->houseUnit->formatted_unit : '-',
                    $resident->auto_approve_delivery ? 'Yes' : 'No'
                ]);
            }

            fputcsv($file, []);

            // Guards
            fputcsv($file, ['--- GUARDS ---']);
            fputcsv($file, ['ID', 'Name', 'Phone', 'Shift', 'Status']);
            foreach (Guard::all() as $guard) {
                fputcsv($file, [
                    $guard->id,
                    $guard->name,
                    $guard->phone,
                    is_array($guard->shift) ? implode(', ', $guard->shift) : $guard->shift,
                    $guard->status
                ]);
            }

            fputcsv($file, []);

            // Delivery Personnel
            fputcsv($file, ['--- DELIVERY PERSONNEL ---']);
            fputcsv($file, ['ID', 'Name', 'Phone', 'Company', 'Vehicle Number']);
            foreach (DeliveryPersonnel::all() as $personnel) {
                fputcsv($file, [
                    $personnel->id,
                    $personnel->name,
                    $personnel->phone,
                    $personnel->company,
                    $personnel->vehicle_number
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportUnits()
    {
        $filename = "house_units_report_" . now()->format('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Block', 'Floor', 'Unit Number', 'Formatted Unit', 'Total Residents', 'Created At']);

            $units = HouseUnit::withCount('residents')->get();
            foreach ($units as $unit) {
                fputcsv($file, [
                    $unit->id,
                    $unit->block,
                    $unit->floor,
                    $unit->unit_number,
                    $unit->formatted_unit,
                    $unit->residents_count,
                    $unit->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportApplications()
    {
        $filename = "visit_applications_" . now()->format('Y-m-d_His') . ".csv";
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['Application ID', 'Visitor Name', 'Visitor Phone', 'Unit Number', 'Host Name', 'Purpose', 'Status', 'Approved By', 'Created At']);

            $visits = Visit::with('visitor')->latest()->get();
            foreach ($visits as $visit) {
                fputcsv($file, [
                    $visit->id,
                    $visit->visitor ? $visit->visitor->name : 'N/A',
                    $visit->visitor ? $visit->visitor->phone : 'N/A',
                    $visit->unit_number,
                    $visit->host_name,
                    $visit->purpose,
                    $visit->status,
                    $visit->approved_by,
                    $visit->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportVisitRecords()
    {
        $filename = "visit_records_" . now()->format('Y-m-d_His') . ".csv";
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['Record ID', 'Application ID', 'Visitor Name', 'Unit Number', 'Check In Time', 'Check Out Time', 'Duration']);

            $sessions = VisitSession::with(['visit', 'visit.visitor'])->latest()->get();
            foreach ($sessions as $session) {
                $duration = '-';
                if ($session->check_in_time && $session->check_out_time) {
                    $diff = $session->check_in_time->diffInSeconds($session->check_out_time);
                    $h = intdiv($diff, 3600);
                    $m = intdiv($diff % 3600, 60);
                    $duration = ($h > 0 ? "{$h}h " : '') . "{$m}m";
                } elseif ($session->check_in_time) {
                    $duration = 'Active On-Site';
                }

                fputcsv($file, [
                    $session->id,
                    $session->visit_id,
                    $session->visit && $session->visit->visitor ? $session->visit->visitor->name : 'N/A',
                    $session->visit ? $session->visit->unit_number : '-',
                    $session->check_in_time ? $session->check_in_time->format('Y-m-d H:i:s') : '-',
                    $session->check_out_time ? $session->check_out_time->format('Y-m-d H:i:s') : '-',
                    $duration
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

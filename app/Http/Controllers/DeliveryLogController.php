<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeliveryLog;
use Inertia\Inertia;

class DeliveryLogController extends Controller
{
    public function index()
    {
        $logs = DeliveryLog::with('personnel')->orderBy('created_at', 'desc')->get();
        return Inertia::render('Admin/Delivery/Logs/Index', ['logs' => $logs]);
    }

    public function exportLogs()
    {
        $logs = DeliveryLog::with('personnel')->orderBy('created_at', 'desc')->get();
        $filename = "delivery_history_" . now()->format('Y-m-d_His') . ".csv";

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
                'ID', 'Personnel Name', 'Phone', 'Email', 'IC Number', 'Company', 
                'Vehicle Number', 'Destination', 'Status', 'Entry Time', 'Exit Time', 'Duration (Mins)'
            ]);

            foreach ($logs as $log) {
                $duration = '-';
                if ($log->entry_time) {
                    $start = $log->entry_time;
                    $end = $log->exit_time ?? now();
                    $duration = $start->diffInMinutes($end);
                }

                fputcsv($file, [
                    $log->id,
                    $log->personnel->name ?? 'N/A',
                    $log->personnel->phone ?? 'N/A',
                    $log->personnel->email ?? 'N/A',
                    $log->personnel->ic_number ?? 'N/A',
                    $log->personnel->company ?? 'N/A',
                    $log->personnel->vehicle_number ?? 'N/A',
                    $log->destination ?? 'N/A',
                    $log->exit_time ? 'Completed' : ($log->entry_time ? 'On-Site' : $log->status),
                    $log->entry_time ? $log->entry_time->format('Y-m-d H:i:s') : '-',
                    $log->exit_time ? $log->exit_time->format('Y-m-d H:i:s') : '-',
                    $duration,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

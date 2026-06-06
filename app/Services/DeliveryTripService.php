<?php

namespace App\Services;

use App\Models\DeliveryLog;
use App\Models\DeliveryPersonnel;
use App\Models\DeliveryRun;
use App\Models\HouseUnit;

class DeliveryTripService
{
    /**
     * @param  list<string>  $destinations
     */
    public function createRun(DeliveryPersonnel $personnel, string $type, array $destinations, $hostNames = null): DeliveryRun
    {
        $run = DeliveryRun::create([
            'delivery_personnel_id' => $personnel->id,
            'type' => $type,
            'status' => 'Pending',
        ]);

        foreach ($destinations as $index => $destination) {
            $status = $this->resolveInitialStatus($destination);
            
            $logHostName = is_array($hostNames) ? ($hostNames[$index] ?? null) : $hostNames;

            DeliveryLog::create([
                'delivery_personnel_id' => $personnel->id,
                'delivery_run_id' => $run->id,
                'destination' => $destination,
                'status' => $status,
                'host_name' => $logHostName,
            ]);
        }

        $run->refreshStatus();

        return $run->load('logs');
    }

    protected function resolveInitialStatus(string $destination): string
    {
        $parts = preg_split('/\s*-\s*/', trim($destination));

        if (count($parts) !== 3) {
            return 'Pending';
        }

        [$block, $floor, $unit] = $parts;

        $normalize = fn($val) => is_numeric($val) ? (string)(int)$val : trim($val);
        $block = $normalize($block);
        $floor = $normalize($floor);
        $unit = $normalize($unit);

        $houseUnit = HouseUnit::query()
            ->where('block', $block)
            ->where('floor', $floor)
            ->where('unit_number', $unit)
            ->first();

        if (! $houseUnit) {
            return 'Pending';
        }

        $hasAutoApprove = $houseUnit->residents()
            ->where('auto_approve_deliveries', true)
            ->exists();

        return $hasAutoApprove ? 'Approved' : 'Pending';
    }
}

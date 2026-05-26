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
    public function createRun(DeliveryPersonnel $personnel, string $type, array $destinations): DeliveryRun
    {
        $run = DeliveryRun::create([
            'delivery_personnel_id' => $personnel->id,
            'type' => $type,
            'status' => 'Pending',
        ]);

        foreach ($destinations as $destination) {
            $status = $this->resolveInitialStatus($destination);

            DeliveryLog::create([
                'delivery_personnel_id' => $personnel->id,
                'delivery_run_id' => $run->id,
                'destination' => $destination,
                'status' => $status,
            ]);
        }

        $run->refreshStatus();

        return $run->load('logs');
    }

    protected function resolveInitialStatus(string $destination): string
    {
        $parts = array_map('trim', explode(' - ', $destination));

        if (count($parts) !== 3) {
            return 'Pending';
        }

        [$block, $floor, $unit] = $parts;

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

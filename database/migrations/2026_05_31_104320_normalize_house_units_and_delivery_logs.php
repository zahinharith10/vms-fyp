<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function normaliseSegment(string $val): string
    {
        return is_numeric($val) ? (string)(int)$val : trim($val);
    }

    private function canonicalise(string $unitNumber): string
    {
        $parts = preg_split('/\s*-\s*/', trim($unitNumber));

        if (count($parts) !== 3) {
            return $unitNumber;
        }

        return $this->normaliseSegment($parts[0])
             . '-' . $this->normaliseSegment($parts[1])
             . '-' . $this->normaliseSegment($parts[2]);
    }

    public function up(): void
    {
        // 1. Normalise house_units table columns
        $houseUnits = DB::table('house_units')->get();
        foreach ($houseUnits as $unit) {
            $newBlock = $this->normaliseSegment($unit->block);
            $newFloor = $this->normaliseSegment($unit->floor);
            $newUnit = $this->normaliseSegment($unit->unit_number);

            if ($newBlock !== $unit->block || $newFloor !== $unit->floor || $newUnit !== $unit->unit_number) {
                DB::table('house_units')
                    ->where('id', $unit->id)
                    ->update([
                        'block' => $newBlock,
                        'floor' => $newFloor,
                        'unit_number' => $newUnit,
                    ]);
            }
        }

        // 2. Normalise delivery_logs.destination table columns
        $distinctDestinations = DB::table('delivery_logs')
            ->select('destination')
            ->distinct()
            ->pluck('destination');

        foreach ($distinctDestinations as $old) {
            if (empty($old) || $old === 'N/A') continue;
            $new = $this->canonicalise($old);
            if ($new !== $old) {
                DB::table('delivery_logs')
                    ->where('destination', $old)
                    ->update(['destination' => $new]);
            }
        }
    }

    public function down(): void
    {
        // Non-reversible
    }
};

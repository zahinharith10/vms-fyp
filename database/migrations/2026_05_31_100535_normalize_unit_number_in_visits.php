<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normalises visits.unit_number to the canonical "block-floor-unit" format:
 *   - No spaces          ("44 - 1 - 01"  →  "44-1-1")
 *   - No leading zeros   ("44-1-01"       →  "44-1-1")
 *
 * Strategy: for every distinct unit_number currently in visits, build the
 * canonical form and run a single UPDATE for that value.
 */
return new class extends Migration
{
    private function normaliseSegment(string $val): string
    {
        // Strip leading zeros from purely numeric segments; keep "G" etc. intact.
        return is_numeric($val) ? (string)(int)$val : $val;
    }

    private function canonicalise(string $unitNumber): string
    {
        // Accept both "A - B - C" (with spaces) and "A-B-C" (no spaces)
        $parts = preg_split('/\s*-\s*/', trim($unitNumber));

        if (count($parts) !== 3) {
            return $unitNumber; // unrecognised format – leave unchanged
        }

        return $this->normaliseSegment($parts[0])
             . '-' . $this->normaliseSegment($parts[1])
             . '-' . $this->normaliseSegment($parts[2]);
    }

    public function up(): void
    {
        $distinct = DB::table('visits')
            ->select('unit_number')
            ->distinct()
            ->pluck('unit_number');

        foreach ($distinct as $old) {
            $new = $this->canonicalise($old);
            if ($new !== $old) {
                DB::table('visits')
                    ->where('unit_number', $old)
                    ->update(['unit_number' => $new]);
            }
        }
    }

    public function down(): void
    {
        // Non-reversible data normalisation – no rollback needed.
    }
};

<?php

namespace App\Rules;

use App\Models\HouseUnit;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidHouseUnitDestination implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $parts = preg_split('/\s*-\s*/', trim((string) $value));

        if (count($parts) !== 3) {
            $fail('The unit must be in the format: Block-Floor-House Number.');

            return;
        }

        [$block, $floor, $unit] = $parts;

        $normalize = fn($val) => is_numeric($val) ? (string)(int)$val : trim($val);
        $block = $normalize($block);
        $floor = $normalize($floor);
        $unit = $normalize($unit);

        $exists = HouseUnit::query()
            ->where('block', $block)
            ->where('floor', $floor)
            ->where('unit_number', $unit)
            ->exists();

        if (! $exists) {
            $fail('The selected house unit does not exist in our records.');
        }
    }
}

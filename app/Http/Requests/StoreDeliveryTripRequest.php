<?php

namespace App\Http\Requests;

use App\Rules\ValidHouseUnitDestination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('delivery')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'delivery_type' => ['required', Rule::in(['single', 'multi'])],
            'unit_number' => ['nullable', 'string', new ValidHouseUnitDestination],
            'unit_numbers' => ['nullable', 'array'],
            'unit_numbers.*' => ['nullable', 'string', 'distinct', new ValidHouseUnitDestination],
            'host_name' => ['nullable', 'string', 'max:255'],
            'host_names' => ['nullable', 'array'],
            'host_names.*' => ['nullable', 'string', 'max:255'],
        ];

        // Add conditional rules based on delivery_type
        if ($this->input('delivery_type') === 'single') {
            $rules['unit_number'][] = 'required';
            $rules['host_name'][] = 'required';
        } elseif ($this->input('delivery_type') === 'multi') {
            $rules['unit_numbers'][] = 'required';
            $rules['unit_numbers'][] = 'min:2';
            $rules['unit_numbers'][] = 'max:15';
            $rules['host_names'][] = 'required';
            $rules['host_names'][] = 'min:2';
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $normalizeUnit = function ($val) {
            if (empty($val)) return $val;
            $parts = preg_split('/\s*-\s*/', trim((string) $val));
            if (count($parts) !== 3) return $val;
            $normaliseSegment = fn($s) => is_numeric($s) ? (string)(int)$s : trim($s);
            return $normaliseSegment($parts[0]) . '-' . $normaliseSegment($parts[1]) . '-' . $normaliseSegment($parts[2]);
        };

        // If this is a single-stop request, remove any unit_numbers sent by the frontend
        if ($this->input('delivery_type') === 'single') {
            $this->request->remove('unit_numbers');
            $this->request->remove('host_names');
            if ($this->has('unit_number')) {
                $unit = $this->input('unit_number');
                if (is_array($unit)) {
                    $unit = reset($unit);
                }
                $this->merge([
                    'unit_number' => is_null($unit) ? $unit : $normalizeUnit(strval($unit)),
                ]);
            }
        } elseif ($this->has('unit_numbers')) {
            $normalizedUnits = array_map(function($u) use ($normalizeUnit) {
                return $normalizeUnit(strval($u));
            }, array_filter($this->input('unit_numbers')));
            
            $this->merge([
                'unit_numbers' => array_map('strval', $normalizedUnits),
            ]);
        }

        // If this is a multi-stop request, remove any unit_number sent by the frontend
        if ($this->input('delivery_type') === 'multi') {
            $this->request->remove('unit_number');
            $this->request->remove('host_name');
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'unit_numbers.required_if' => 'Add at least two destination units for a multi-stop delivery.',
            'unit_numbers.min' => 'A multi-stop delivery must include at least two units.',
            'unit_numbers.*.distinct' => 'Each destination unit can only be added once.',
        ];
    }
}

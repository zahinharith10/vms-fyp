<?php

namespace App\Http\Controllers;

use App\Models\HouseUnit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class HouseUnitController extends Controller
{
    public function index(Request $request)
    {
        $query = HouseUnit::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                // Allows searching by individual pieces OR the exact formatted string "Block - Floor - Unit"
                $q->where('block', 'like', "%{$search}%")
                  ->orWhere('floor', 'like', "%{$search}%")
                  ->orWhere('unit_number', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(block, ' - ', floor, ' - ', unit_number) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT(block, '-', floor, '-', unit_number) LIKE ?", ["%{$search}%"]);
            });
        }

        return Inertia::render('Admin/HouseUnits/Index', [
            'units' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only('search')
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/HouseUnits/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'block' => ['required', 'regex:/^\d+$/', 'max:255'],
            'floor' => ['required', 'regex:/^\d+$/', 'max:255'],
            'unit_number' => [
                'required', 
                'regex:/^\d+$/', 
                'max:255',
                Rule::unique('house_units')->where(function ($query) use ($request) {
                    return $query->where('block', $request->block)
                                 ->where('floor', $request->floor);
                })
            ],
        ], [
            'block.regex' => 'The block must be a number.',
            'floor.regex' => 'The floor must be a number.',
            'unit_number.regex' => 'The house number must be a number.',
            'unit_number.unique' => 'This house unit already exists.',
        ]);

        HouseUnit::create($request->all());

        return redirect()->route('admin.units.index')->with('success', 'House Unit created successfully.');
    }

    public function edit(HouseUnit $unit)
    {
        return Inertia::render('Admin/HouseUnits/Edit', [
            'unit' => $unit
        ]);
    }

    public function update(Request $request, HouseUnit $unit)
    {
        $request->validate([
            'block' => ['required', 'regex:/^\d+$/', 'max:255'],
            'floor' => ['required', 'regex:/^\d+$/', 'max:255'],
            'unit_number' => [
                'required', 
                'regex:/^\d+$/', 
                'max:255',
                Rule::unique('house_units')->ignore($unit->id)->where(function ($query) use ($request) {
                    return $query->where('block', $request->block)
                                 ->where('floor', $request->floor);
                })
            ],
        ], [
            'block.regex' => 'The block must be a number.',
            'floor.regex' => 'The floor must be a number.',
            'unit_number.regex' => 'The house number must be a number.',
        ]);

        $unit->update($request->all());

        return redirect()->route('admin.units.index')->with('success', 'House Unit updated successfully.');
    }

    public function destroy(HouseUnit $unit)
    {
        $unit->delete();
        return redirect()->route('admin.units.index')->with('success', 'House Unit deleted successfully.');
    }
}

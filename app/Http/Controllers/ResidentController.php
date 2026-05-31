<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\HouseUnit;
use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResidentVerificationMail;
use Illuminate\Validation\Rules\Password;

class ResidentController extends Controller
{
    public function index(Request $request)
    {
        $query = Resident::select('residents.*')
            ->leftJoin('house_units', 'residents.house_unit_id', '=', 'house_units.id')
            ->with('houseUnit');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('residents.name', 'like', "%{$search}%")
                  ->orWhere('residents.email', 'like', "%{$search}%")
                  ->orWhere('residents.ic_number', 'like', "%{$search}%")
                  ->orWhere('residents.phone', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw('house_units.block IS NULL, CAST(house_units.block AS UNSIGNED) ASC')
              ->orderByRaw('house_units.floor IS NULL, CAST(house_units.floor AS UNSIGNED) ASC')
              ->orderByRaw('house_units.unit_number IS NULL, CAST(house_units.unit_number AS UNSIGNED) ASC')
              ->orderByRaw("CASE WHEN residents.type = 'owner' THEN 0 ELSE 1 END")
              ->orderBy('residents.name', 'asc');

        $residents = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Residents/Index', [
            'residents' => $residents,
            'filters'   => $request->only('search'),
        ]);
    }

    /**
     * Show resident profile + visit history for their unit.
     */
    public function show(Resident $resident)
    {
        $resident->load('houseUnit');

        // Co-residents sharing the same unit
        $unitResidents = $resident->house_unit_id
            ? Resident::where('house_unit_id', $resident->house_unit_id)
                ->where('id', '!=', $resident->id)
                ->get(['id', 'name', 'type', 'status', 'phone', 'email'])
            : collect();

        // Visit history for this unit — unit_number in visits is now normalised
        // to match formatted_unit exactly (e.g. "44-1-1").
        $unitNumber = $resident->houseUnit?->formatted_unit ?? null;
        $visits = $unitNumber
            ? Visit::with(['visitor', 'sessions'])
                ->where('unit_number', $unitNumber)
                ->latest('updated_at')
                ->get()
            : collect();

        return Inertia::render('Admin/Residents/Show', [
            'resident'      => $resident,
            'unitResidents' => $unitResidents,
            'visits'        => $visits,
        ]);
    }



    public function create()
    {
        $units = HouseUnit::all();
        return Inertia::render('Admin/Residents/Create', [
            'units' => $units,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => 'required|string|max:20',
            'email'        => 'required|email|max:255|unique:residents',
            'password'     => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'ic_number'    => 'nullable|string|max:20',
            'type'         => [
                'required',
                'string',
                'in:owner,family',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value === 'owner') {
                        $ownerExists = Resident::where('house_unit_id', $request->house_unit_id)
                            ->where('type', 'owner')
                            ->exists();

                        if ($ownerExists) {
                            $fail('This house unit already has an assigned owner. Only one owner is allowed per house unit.');
                        }
                    } elseif ($value === 'family') {
                        $ownerExists = Resident::where('house_unit_id', $request->house_unit_id)
                            ->where('type', 'owner')
                            ->exists();

                        if (!$ownerExists) {
                            $fail('You must register an owner for this house unit before adding family members.');
                        }
                    }
                }
            ],
            'house_unit_id'=> 'required|exists:house_units,id',
        ]);

        $token = Str::random(60);

        $resident = Resident::create([
            'name'                => $request->name,
            'phone'               => $request->phone,
            'email'               => $request->email,
            'password'            => bcrypt($request->password),
            'ic_number'           => $request->ic_number,
            'type'                => $request->type,
            'status'              => 'inactive',
            'verification_token'  => $token,
            'house_unit_id'       => $request->house_unit_id,
        ]);

        try {
            Mail::to($resident->email)->send(new ResidentVerificationMail($resident));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Resident verification email failed to send: ' . $e->getMessage());
        }

        return redirect()->route('admin.residents.index')
            ->with('success', 'Resident registered successfully. Verification email has been sent.');
    }

    public function edit(Resident $resident)
    {
        $units = HouseUnit::all();
        return Inertia::render('Admin/Residents/Edit', [
            'resident' => $resident,
            'units'    => $units,
        ]);
    }

    public function update(Request $request, Resident $resident)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => 'required|string|max:20',
            'email'        => 'nullable|email|max:255',
            'ic_number'    => 'nullable|string|max:20',
            'type'         => [
                'required',
                'string',
                'in:owner,family',
                function ($attribute, $value, $fail) use ($request, $resident) {
                    if ($value === 'owner') {
                        $ownerExists = Resident::where('house_unit_id', $request->house_unit_id)
                            ->where('type', 'owner')
                            ->where('id', '!=', $resident->id)
                            ->exists();

                        if ($ownerExists) {
                            $fail('This house unit already has an assigned owner. Only one owner is allowed per house unit.');
                        }
                    } elseif ($value === 'family') {
                        $ownerExists = Resident::where('house_unit_id', $request->house_unit_id)
                            ->where('type', 'owner')
                            ->exists();

                        if (!$ownerExists) {
                            $fail('You must register an owner for this house unit before adding family members.');
                        }
                    }
                }
            ],
            'status'       => 'required|string|in:active,inactive',
            'house_unit_id'=> 'required|exists:house_units,id',
            'password'     => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $data = $request->except(['password']);
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $resident->update($data);

        return redirect()->route('admin.residents.index')
            ->with('success', 'Resident updated successfully.');
    }

    public function destroy(Resident $resident)
    {
        $resident->delete();
        return redirect()->route('admin.residents.index')
            ->with('success', 'Resident removed successfully.');
    }
}

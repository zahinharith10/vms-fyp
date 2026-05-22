<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\HouseUnit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResidentVerificationMail;

class ResidentController extends Controller
{
    public function index(Request $request)
    {
        $query = Resident::with('houseUnit');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('ic_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $residents = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Residents/Index', [
            'residents' => $residents,
            'filters' => $request->only('search')
        ]);
    }

    public function create()
    {
        // Fetch all units for dropdown
        $units = HouseUnit::all();
        
        return Inertia::render('Admin/Residents/Create', [
            'units' => $units
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:residents',
            'password' => 'required|string|min:8',
            'ic_number' => 'nullable|string|max:20',
            'type' => 'required|string|in:owner,tenant,family',
            'house_unit_id' => 'required|exists:house_units,id',
        ]);

        $token = Str::random(60);

        $resident = Resident::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => bcrypt($request->password), // Hash the password
            'ic_number' => $request->ic_number,
            'type' => $request->type,
            'status' => 'inactive', // Set as inactive on registration
            'verification_token' => $token,
            'house_unit_id' => $request->house_unit_id,
        ]);

        try {
            Mail::to($resident->email)->send(new ResidentVerificationMail($resident));
        } catch (\Exception $e) {
            // Log warning but let account creation succeed
            \Illuminate\Support\Facades\Log::warning('Resident verification email failed to send: ' . $e->getMessage());
        }

        return redirect()->route('admin.residents.index')->with('success', 'Resident registered successfully. Verification email has been sent.');
    }

    public function edit(Resident $resident)
    {
        $units = HouseUnit::all();
        return Inertia::render('Admin/Residents/Edit', [
            'resident' => $resident,
            'units' => $units
        ]);
    }

    public function update(Request $request, Resident $resident)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'ic_number' => 'nullable|string|max:20',
            'type' => 'required|string|in:owner,tenant,family',
            'status' => 'required|string|in:active,inactive',
            'house_unit_id' => 'required|exists:house_units,id',
            'password' => 'nullable|string|min:8',
        ]);

        $data = $request->except(['password']);

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $resident->update($data);

        return redirect()->route('admin.residents.index')->with('success', 'Resident updated successfully.');
    }

    public function destroy(Resident $resident)
    {
        $resident->delete();
        return redirect()->route('admin.residents.index')->with('success', 'Resident removed successfully.');
    }
}

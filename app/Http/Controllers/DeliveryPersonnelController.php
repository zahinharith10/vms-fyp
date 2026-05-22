<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeliveryPersonnel;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class DeliveryPersonnelController extends Controller
{
    public function index()
    {
        $personnel = DeliveryPersonnel::all();
        return Inertia::render('Admin/Delivery/Personnel/Index', ['personnel' => $personnel]);
    }

    public function create()
    {
        return Inertia::render('Admin/Delivery/Personnel/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'required|string', // Grab, Shopee, etc.
            'vehicle_type' => 'required|string',
            'vehicle_number' => 'required|string',
            'phone' => 'required|string',
            'ic_number' => 'required|string|unique:delivery_personnels',
            'photo' => 'nullable|image|max:2048',
            'status' => 'required|string',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('delivery_photos', 'public');
        }

        DeliveryPersonnel::create([
            'name' => $request->name,
            'company' => $request->company,
            'vehicle_type' => $request->vehicle_type,
            'vehicle_number' => $request->vehicle_number,
            'phone' => $request->phone,
            'ic_number' => $request->ic_number,
            'photo' => $photoPath,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.delivery.personnel.index')->with('success', 'Delivery Personnel registered successfully.');
    }

    public function edit(DeliveryPersonnel $personnel)
    {
        return Inertia::render('Admin/Delivery/Personnel/Edit', ['personnel' => $personnel]);
    }

    public function update(Request $request, DeliveryPersonnel $personnel)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'required|string',
            'vehicle_type' => 'required|string',
            'vehicle_number' => 'required|string',
            'phone' => 'required|string',
            'ic_number' => 'required|string|unique:delivery_personnels,ic_number,' . $personnel->id,
            'photo' => 'nullable|image|max:2048',
            'status' => 'required|string',
        ]);

        $data = $request->except(['photo']);

        if ($request->hasFile('photo')) {
            if ($personnel->photo) {
                Storage::disk('public')->delete($personnel->photo);
            }
            $data['photo'] = $request->file('photo')->store('delivery_photos', 'public');
        }

        $personnel->update($data);

        return redirect()->route('admin.delivery.personnel.index')->with('success', 'Details updated successfully.');
    }

    public function destroy(DeliveryPersonnel $personnel)
    {
        if ($personnel->photo) {
            Storage::disk('public')->delete($personnel->photo);
        }
        $personnel->delete();
        return redirect()->route('admin.delivery.personnel.index')->with('success', 'Personnel deleted successfully.');
    }
}

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
            'phone' => [
                'required',
                'string',
                'regex:/^(?:\+?6)?01[0-9](?:[- ]?\d){7,8}$/'
            ],
            'ic_number' => [
                'required',
                'string',
                'unique:delivery_personnels',
                'regex:/^(?:\d{6}-\d{2}-\d{4}|\d{12}|[a-zA-Z0-9]{6,20})$/'
            ],
            'photo' => 'nullable|image|max:2048',
            'status' => 'required|string',
        ], [
            'phone.regex' => 'The phone number must be a valid Malaysian mobile number (e.g. 012-3456789 or 011-12345678).',
            'ic_number.regex' => 'The IC Number must be a valid Malaysian IC (e.g. 950101-14-1234) or a valid Passport Number (6-20 alphanumeric characters).',
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
            'phone' => [
                'required',
                'string',
                'regex:/^(?:\+?6)?01[0-9](?:[- ]?\d){7,8}$/'
            ],
            'ic_number' => [
                'required',
                'string',
                'unique:delivery_personnels,ic_number,' . $personnel->id,
                'regex:/^(?:\d{6}-\d{2}-\d{4}|\d{12}|[a-zA-Z0-9]{6,20})$/'
            ],
            'photo' => 'nullable|image|max:2048',
            'status' => 'required|string',
        ], [
            'phone.regex' => 'The phone number must be a valid Malaysian mobile number (e.g. 012-3456789 or 011-12345678).',
            'ic_number.regex' => 'The IC Number must be a valid Malaysian IC (e.g. 950101-14-1234) or a valid Passport Number (6-20 alphanumeric characters).',
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

    public function show(DeliveryPersonnel $personnel)
    {
        $personnel->load(['logs' => function($q) {
            $q->orderBy('created_at', 'desc');
        }]);

        return Inertia::render('Admin/Delivery/Personnel/Show', [
            'personnel' => $personnel,
            'logs' => $personnel->logs
        ]);
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

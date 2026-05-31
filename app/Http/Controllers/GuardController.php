<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Guard;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class GuardController extends Controller
{
    public function index(Request $request)
    {
        $query = Guard::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('ic_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Admin/Guards/Index', [
            'guards' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only('search')
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Guards/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            // 'employee_id' => 'required|string|unique:guards', // Auto-generated
            'ic_number' => [
                'required',
                'string',
                'unique:guards',
                // Regex: Matches MyKad (12 digits with optional hyphens) OR Passport (6-20 alphanumeric, MUST contain at least one letter)
                'regex:/^(\d{6}-?\d{2}-?\d{4})|(?=.*[A-Z])[A-Z0-9]{6,20}$/'
            ],
            'phone' => 'required|string',
            'email' => 'required|email|unique:guards',
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'shift' => 'required|array',
            'shift.*' => 'required|string|in:Morning,Afternoon,Night',
            'status' => 'required|string',
            'photo' => 'nullable|image|max:2048',
        ], [
            'ic_number.regex' => 'The IC Number must be a valid Malaysian IC (e.g. 900101-14-1234) or a valid Passport Number (must contain letters, e.g. A1234567).'
        ]);

        // Auto-generate Employee ID: G-YYYY-001
        $year = date('Y');
        $latestGuard = Guard::latest('id')->first();
        $nextId = $latestGuard ? $latestGuard->id + 1 : 1;
        $employeeId = 'G-' . $year . '-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('guards', 'public');
        }

        Guard::create([
            'name' => $request->name,
            'employee_id' => $employeeId,
            'ic_number' => $request->ic_number,
            'phone' => $request->phone,
            'address' => $request->address,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'shift' => $request->shift,
            'status' => $request->status,
            'photo' => $photoPath,
        ]);

        return redirect()->route('admin.guards.index')->with('success', 'Guard created successfully. ID: ' . $employeeId);
    }

    public function edit(Guard $guard)
    {
        return Inertia::render('Admin/Guards/Edit', ['guard' => $guard]);
    }

    public function update(Request $request, Guard $guard)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'employee_id' => 'required|string|unique:guards,employee_id,' . $guard->id,
            'ic_number' => [
                'required',
                'string',
                'unique:guards,ic_number,' . $guard->id,
                // Regex: Matches MyKad (12 digits with optional hyphens) OR Passport (6-20 alphanumeric, MUST contain at least one letter)
                'regex:/^(\d{6}-?\d{2}-?\d{4})|(?=.*[A-Z])[A-Z0-9]{6,20}$/'
            ],
            'phone' => 'required|string',
            'email' => 'required|email|unique:guards,email,' . $guard->id,
            'shift' => 'required|array',
            'shift.*' => 'required|string|in:Morning,Afternoon,Night',
            'status' => 'required|string',
            'photo' => 'nullable|image|max:2048',
            'password' => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'ic_number.regex' => 'The IC Number must be a valid Malaysian IC (e.g. 900101-14-1234) or a valid Passport Number (must contain letters, e.g. A1234567).'
        ]);

        $data = $request->except(['password', 'photo']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('photo')) {
            if ($guard->photo) {
                Storage::disk('public')->delete($guard->photo);
            }
            $data['photo'] = $request->file('photo')->store('guards', 'public');
        }

        $guard->update($data);

        return redirect()->route('admin.guards.index')->with('success', 'Guard updated successfully.');
    }

    public function destroy(Guard $guard)
    {
        if ($guard->photo) {
            Storage::disk('public')->delete($guard->photo);
        }
        $guard->delete();
        return redirect()->route('admin.guards.index')->with('success', 'Guard deleted successfully.');
    }
}

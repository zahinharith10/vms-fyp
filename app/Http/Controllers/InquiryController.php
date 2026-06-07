<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InquiryController extends Controller
{
    // ==========================================
    // RESIDENT PORTAL METHODS
    // ==========================================

    public function residentIndex()
    {
        $resident = Auth::guard('resident')->user();
        $inquiries = Inquiry::where('user_type', 'Resident')
            ->where('user_id', $resident->id)
            ->latest()
            ->get();

        return Inertia::render('Resident/Inquiries/Index', [
            'inquiries' => $inquiries,
        ]);
    }

    public function residentCreate()
    {
        $resident = Auth::guard('resident')->user();
        
        return Inertia::render('Resident/Inquiries/Create', [
            'autofill' => [
                'name' => $resident->name,
                'email' => $resident->email,
                'phone' => $resident->phone,
            ]
        ]);
    }

    public function residentStore(Request $request)
    {
        $resident = Auth::guard('resident')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        Inquiry::create([
            'user_type' => 'Resident',
            'user_id' => $resident->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'Pending',
        ]);

        return redirect()->route('resident.inquiries.index')
            ->with('success', 'Your inquiry has been submitted successfully.');
    }

    // ==========================================
    // VISITOR PORTAL METHODS
    // ==========================================

    public function visitorIndex()
    {
        $visitor = Auth::guard('visitor')->user();
        $inquiries = Inquiry::where('user_type', 'Visitor')
            ->where('user_id', $visitor->id)
            ->latest()
            ->get();

        return Inertia::render('Visitor/Inquiries/Index', [
            'inquiries' => $inquiries,
        ]);
    }

    public function visitorCreate()
    {
        $visitor = Auth::guard('visitor')->user();

        return Inertia::render('Visitor/Inquiries/Create', [
            'autofill' => [
                'name' => $visitor->name,
                'email' => $visitor->email,
                'phone' => $visitor->phone,
            ]
        ]);
    }

    public function visitorStore(Request $request)
    {
        $visitor = Auth::guard('visitor')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        Inquiry::create([
            'user_type' => 'Visitor',
            'user_id' => $visitor->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'Pending',
        ]);

        return redirect()->route('visitor.inquiries.index')
            ->with('success', 'Your inquiry has been submitted successfully.');
    }

    // ==========================================
    // DELIVERY PORTAL METHODS
    // ==========================================

    public function deliveryIndex()
    {
        $delivery = Auth::guard('delivery')->user();
        $inquiries = Inquiry::where('user_type', 'Delivery')
            ->where('user_id', $delivery->id)
            ->latest()
            ->get();

        return Inertia::render('Delivery/Inquiries/Index', [
            'inquiries' => $inquiries,
        ]);
    }

    public function deliveryCreate()
    {
        $delivery = Auth::guard('delivery')->user();

        return Inertia::render('Delivery/Inquiries/Create', [
            'autofill' => [
                'name' => $delivery->name,
                'email' => $delivery->email,
                'phone' => $delivery->phone,
            ]
        ]);
    }

    public function deliveryStore(Request $request)
    {
        $delivery = Auth::guard('delivery')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        Inquiry::create([
            'user_type' => 'Delivery',
            'user_id' => $delivery->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'Pending',
        ]);

        return redirect()->route('delivery.inquiries.index')
            ->with('success', 'Your inquiry has been submitted successfully.');
    }

    // ==========================================
    // ADMIN PORTAL METHODS
    // ==========================================

    public function adminIndex(Request $request)
    {
        $query = Inquiry::latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('user_type') && $request->input('user_type') !== 'All') {
            $query->where('user_type', $request->input('user_type'));
        }

        if ($request->filled('status') && $request->input('status') !== 'All') {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Admin/Inquiries/Index', [
            'inquiries' => $query->get(),
            'filters' => $request->only(['search', 'user_type', 'status'])
        ]);
    }

    public function adminResolve(Inquiry $inquiry)
    {
        $inquiry->update(['status' => 'Resolved']);

        return redirect()->back()->with('success', 'Inquiry marked as resolved.');
    }

    public function adminReply(Request $request, Inquiry $inquiry)
    {
        $request->validate([
            'reply' => 'required|string',
        ]);

        $inquiry->update([
            'reply' => $request->reply,
            'replied_at' => now(),
            'status' => 'Resolved',
        ]);

        return redirect()->back()->with('success', 'Reply submitted and inquiry resolved.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Inquiry;
use App\Notifications\InquiryNotification;
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
            ->with('messages')
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

        $inquiry = Inquiry::create([
            'user_type' => 'Resident',
            'user_id' => $resident->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'Pending',
        ]);

        // Notify all admins of the new inquiry
        Admin::all()->each(fn($admin) => $admin->notify(new InquiryNotification($inquiry, 'created')));

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
            ->with('messages')
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

        $inquiry = Inquiry::create([
            'user_type' => 'Visitor',
            'user_id' => $visitor->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'Pending',
        ]);

        // Notify all admins of the new inquiry
        Admin::all()->each(fn($admin) => $admin->notify(new InquiryNotification($inquiry, 'created')));

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
            ->with('messages')
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

        $inquiry = Inquiry::create([
            'user_type' => 'Delivery',
            'user_id' => $delivery->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'Pending',
        ]);

        // Notify all admins of the new inquiry
        Admin::all()->each(fn($admin) => $admin->notify(new InquiryNotification($inquiry, 'created')));

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
            'inquiries' => $query->with('messages')->get(),
            'filters' => $request->only(['search', 'user_type', 'status'])
        ]);
    }

    public function adminReply(Request $request, Inquiry $inquiry)
    {
        $request->validate([
            'reply' => 'required|string',
        ]);

        $inquiry->messages()->create([
            'sender_type' => 'Admin',
            'sender_name' => 'Admin Management',
            'message' => $request->reply,
        ]);

        // Keep it open/pending so user can see and reply back
        $inquiry->update(['status' => 'Pending']);

        // Notify the portal user that admin replied
        $this->notifyPortalUser($inquiry, 'admin_reply');

        return redirect()->back()->with('success', 'Reply sent successfully.');
    }

    // ==========================================
    // PORTAL REPLY & END METHODS
    // ==========================================

    public function residentReply(Request $request, Inquiry $inquiry)
    {
        $resident = Auth::guard('resident')->user();
        if ($inquiry->user_type !== 'Resident' || $inquiry->user_id !== $resident->id) {
            abort(403);
        }
        if ($inquiry->status === 'Resolved') {
            return redirect()->back()->with('error', 'This inquiry has been resolved.');
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        $inquiry->messages()->create([
            'sender_type' => 'User',
            'sender_name' => $resident->name,
            'message' => $request->message,
        ]);

        // Notify all admins of the user reply
        Admin::all()->each(fn($admin) => $admin->notify(new InquiryNotification($inquiry, 'user_reply')));

        return redirect()->back()->with('success', 'Reply sent successfully.');
    }

    public function residentEnd(Inquiry $inquiry)
    {
        $resident = Auth::guard('resident')->user();
        if ($inquiry->user_type !== 'Resident' || $inquiry->user_id !== $resident->id) {
            abort(403);
        }

        $inquiry->update(['status' => 'Resolved']);

        // Notify all admins that the inquiry was resolved
        Admin::all()->each(fn($admin) => $admin->notify(new InquiryNotification($inquiry, 'resolved')));

        return redirect()->back()->with('success', 'Inquiry marked as resolved.');
    }

    public function visitorReply(Request $request, Inquiry $inquiry)
    {
        $visitor = Auth::guard('visitor')->user();
        if ($inquiry->user_type !== 'Visitor' || $inquiry->user_id !== $visitor->id) {
            abort(403);
        }
        if ($inquiry->status === 'Resolved') {
            return redirect()->back()->with('error', 'This inquiry has been resolved.');
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        $inquiry->messages()->create([
            'sender_type' => 'User',
            'sender_name' => $visitor->name,
            'message' => $request->message,
        ]);

        // Notify all admins of the user reply
        Admin::all()->each(fn($admin) => $admin->notify(new InquiryNotification($inquiry, 'user_reply')));

        return redirect()->back()->with('success', 'Reply sent successfully.');
    }

    public function visitorEnd(Inquiry $inquiry)
    {
        $visitor = Auth::guard('visitor')->user();
        if ($inquiry->user_type !== 'Visitor' || $inquiry->user_id !== $visitor->id) {
            abort(403);
        }

        $inquiry->update(['status' => 'Resolved']);

        // Notify all admins that the inquiry was resolved
        Admin::all()->each(fn($admin) => $admin->notify(new InquiryNotification($inquiry, 'resolved')));

        return redirect()->back()->with('success', 'Inquiry marked as resolved.');
    }

    public function deliveryReply(Request $request, Inquiry $inquiry)
    {
        $delivery = Auth::guard('delivery')->user();
        if ($inquiry->user_type !== 'Delivery' || $inquiry->user_id !== $delivery->id) {
            abort(403);
        }
        if ($inquiry->status === 'Resolved') {
            return redirect()->back()->with('error', 'This inquiry has been resolved.');
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        $inquiry->messages()->create([
            'sender_type' => 'User',
            'sender_name' => $delivery->name,
            'message' => $request->message,
        ]);

        // Notify all admins of the user reply
        Admin::all()->each(fn($admin) => $admin->notify(new InquiryNotification($inquiry, 'user_reply')));

        return redirect()->back()->with('success', 'Reply sent successfully.');
    }

    public function deliveryEnd(Inquiry $inquiry)
    {
        $delivery = Auth::guard('delivery')->user();
        if ($inquiry->user_type !== 'Delivery' || $inquiry->user_id !== $delivery->id) {
            abort(403);
        }

        $inquiry->update(['status' => 'Resolved']);

        // Notify all admins that the inquiry was resolved
        Admin::all()->each(fn($admin) => $admin->notify(new InquiryNotification($inquiry, 'resolved')));

        return redirect()->back()->with('success', 'Inquiry marked as resolved.');
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    /**
     * Notify the portal user (Resident / Visitor / Delivery) who owns this inquiry.
     */
    private function notifyPortalUser(Inquiry $inquiry, string $event): void
    {
        $model = match ($inquiry->user_type) {
            'Resident' => \App\Models\Resident::find($inquiry->user_id),
            'Visitor'  => \App\Models\Visitor::find($inquiry->user_id),
            'Delivery' => \App\Models\DeliveryPersonnel::find($inquiry->user_id),
            default    => null,
        };

        if ($model) {
            $model->notify(new InquiryNotification($inquiry, $event));
        }
    }
}

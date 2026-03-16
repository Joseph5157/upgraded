<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RefundController extends Controller
{
    // Client submits refund request
    public function store(Request $request, Order $order)
    {
        $user   = Auth::user();
        $client = $user->client;

        if ($order->client_id !== $client->id || $order->created_by_user_id !== $user->id) {
            abort(403);
        }

        if ($order->status !== OrderStatus::Cancelled) {
            return back()->with('error', 'Refunds can only be requested for cancelled orders.');
        }

        // A vendor already submitted the files to Turnitin (release_count > 0).
        // The Turnitin submission was consumed — automatic credit refund is blocked.
        // The client must contact admin for a manual review.
        if ($order->release_count > 0) {
            return back()->with('error', 'A vendor already processed this order in Turnitin. Automatic credit refund is not available — please contact admin for a manual review.');
        }

        if ($order->refundRequest) {
            return back()->with('error', 'A refund request has already been submitted for this order.');
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        RefundRequest::create([
            'order_id'  => $order->id,
            'client_id' => $client->id,
            'user_id'   => $user->id,
            'status'    => 'pending',
            'reason'    => $request->reason,
        ]);

        return back()->with('success', 'Refund request submitted. Admin will review and refund your credit slot.');
    }

    // Admin approves refund — refunds the credit slot
    public function approve(Request $request, RefundRequest $refundRequest)
    {
        $this->authorize('approve', $refundRequest);

        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        if ($refundRequest->status !== 'pending') {
            return back()->with('error', 'This refund request has already been resolved.');
        }

        $client = $refundRequest->client;

        // Restore the credit slot by decrementing slots_consumed
        $client->decrement('slots_consumed');

        // Reactivate client if they were suspended
        if ($client->status === 'suspended') {
            $client->update(['status' => 'active']);
        }

        $refundRequest->update([
            'status'      => 'approved',
            'admin_note'  => $request->admin_note,
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Refund approved. Credit slot has been returned to the client.');
    }

    // Admin rejects refund
    public function reject(Request $request, RefundRequest $refundRequest)
    {
        $this->authorize('reject', $refundRequest);

        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        if ($refundRequest->status !== 'pending') {
            return back()->with('error', 'This refund request has already been resolved.');
        }

        $refundRequest->update([
            'status'      => 'rejected',
            'admin_note'  => $request->admin_note,
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Refund request rejected.');
    }

    // Admin index — list all refund requests
    public function index()
    {
        $refunds = RefundRequest::with(['order', 'client', 'user'])
            ->latest()
            ->get();

        return view('admin.refunds', compact('refunds'));
    }
}
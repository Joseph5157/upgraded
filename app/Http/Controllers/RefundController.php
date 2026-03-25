<?php

namespace App\Http\Controllers;

use App\Models\RefundRequest;
use Illuminate\Http\Request;

class RefundController extends Controller
{
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
        $this->authorize('viewAny', RefundRequest::class);

        $refunds = RefundRequest::with(['order', 'client', 'user'])
            ->latest()
            ->get();

        return view('admin.refunds', compact('refunds'));
    }
}
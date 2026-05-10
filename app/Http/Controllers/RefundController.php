<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\RefundRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundController extends Controller
{
    // Client submits a refund request against one of their own orders
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'reason'   => 'required|string|max:1000',
        ]);

        $user   = Auth::user();
        $client = $user->client;

        if (! $client) {
            return back()->with('error', 'No client account is linked to your user.');
        }

        $order = Order::find($request->order_id);

        // Ensure the order belongs to this client
        if ((int) $order->client_id !== (int) $client->id) {
            return back()->with('error', 'You can only request a refund for your own orders.');
        }

        // Only allow refunds for orders in a refundable state
        $refundableStatuses = [OrderStatus::Delivered, OrderStatus::Processing, OrderStatus::Claimed];
        if (! in_array($order->status, $refundableStatuses)) {
            return back()->with('error', 'Refunds can only be requested for claimed, processing, or delivered orders.');
        }

        // Prevent duplicate pending refund for the same order
        if (RefundRequest::where('order_id', $order->id)->where('status', 'pending')->exists()) {
            return back()->with('error', 'A refund request for this order is already pending review.');
        }

        RefundRequest::create([
            'order_id'  => $order->id,
            'client_id' => $client->id,
            'user_id'   => $user->id,
            'reason'    => $request->reason,
            'status'    => 'pending',
        ]);

        Cache::forget('admin_nav_pending_refunds');

        return back()->with('success', 'Refund request submitted. The admin will review it shortly.');
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

        DB::transaction(function () use ($request, $refundRequest) {
            $client = $refundRequest->client;

            // Guard: never let slots_consumed go negative. This can happen if an
            // admin approves a refund for an order that was already deleted (which
            // already restored the credit during deletion).
            if ($client->slots_consumed > 0) {
                $client->decrement('slots_consumed');
            } else {
                Log::warning("RefundController: slots_consumed is already 0 for client #{$client->id} — skipping decrement.", [
                    'refund_request_id' => $refundRequest->id,
                ]);
            }

            // Reactivate client if they were suspended and now have capacity.
            if ($client->status === 'suspended' && $client->slots_consumed < $client->slots) {
                $client->update(['status' => 'active']);
            }

            $refundRequest->update([
                'status'      => 'approved',
                'admin_note'  => $request->admin_note,
                'resolved_at' => now(),
            ]);
        });

        // Bust the sidebar badge so the resolved count reflects immediately.
        Cache::forget('admin_nav_pending_refunds');

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

        Cache::forget('admin_nav_pending_refunds');

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
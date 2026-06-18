<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Services\Finance\ClientCreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundController extends Controller
{
    public function __construct(
        protected ClientCreditService $creditService,
    ) {}

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

    // Admin approves refund — restores credits through the new credit ledger
    public function approve(Request $request, RefundRequest $refundRequest)
    {
        $this->authorize('approve', $refundRequest);

        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        if ($refundRequest->status !== 'pending') {
            return back()->with('error', 'This refund request has already been resolved.');
        }

        $creditsRestored = false;

        DB::transaction(function () use ($request, $refundRequest, &$creditsRestored) {
            // Lock both rows to prevent concurrent approve/delete races.
            $client = Client::where('id', $refundRequest->client_id)->lockForUpdate()->firstOrFail();
            $order  = Order::where('id', $refundRequest->order_id)->lockForUpdate()->firstOrFail();

            // Refund credits only if a TYPE_ORDER_DEBIT tx exists for this order.
            // Pre-Phase-4 orders never debited credit_balance, so no refund is issued.
            $creditsRestored = $this->creditService->refundOrderIfDebited(
                $client,
                $order,
                Auth::user(),
                'Admin approved refund request #' . $refundRequest->id . '.',
            );

            // Reactivate a suspended client if credit balance is now positive.
            if ($client->status === 'suspended' && $client->fresh()->credit_balance > 0) {
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

        $message = $creditsRestored
            ? 'Refund approved. Credits have been restored to the client.'
            : 'Refund approved. No credit refund was created because this order did not consume credits from the new ledger.';

        return back()->with('success', $message);
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

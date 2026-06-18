<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TopupRequest;
use App\Services\PortalTelegramAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TopupRequestController extends Controller
{
    /**
     * Admin views all topup requests — standalone page.
     */
    public function index()
    {
        $this->authorize('viewAny', TopupRequest::class);

        $pending  = TopupRequest::with('client')->where('status', 'pending')->latest()->get();
        $resolved = TopupRequest::with('client')
            ->whereIn('status', ['approved', 'rejected'])
            ->latest('reviewed_at')
            ->take(100)
            ->get();

        return view('admin.topups', compact('pending', 'resolved'));
    }

    /**
     * Client top-up requests are disabled.
     *
     * Credits are now added directly by admin via the Client Payments system.
     * Self-service top-up is no longer supported.
     */
    public function store(Request $request)
    {
        return back()->with('error', 'Self-service top-up is no longer available. Please contact the admin to add credits to your account.');
    }

    /**
     * Admin approves a top-up request — adds slots and marks as approved.
     *
     * LEGACY (Phase 10C): This method writes to clients.slots which is a frozen
     * column. It does NOT affect credit_balance. For adding credits, use the
     * Client Payments system (ClientPaymentController) instead. This route is
     * hidden from the admin sidebar but still functional for processing any
     * remaining legacy pending requests.
     */
    public function approve(TopupRequest $topupRequest)
    {
        $this->authorize('approve', $topupRequest);

        if ($topupRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        DB::transaction(function () use ($topupRequest) {
            $client = $topupRequest->client;

            $newSlots = $client->slots + $topupRequest->amount_requested;

            $client->update([
                'slots'  => $newSlots,
                // Reactivate only if they're not user-frozen AND the new slot count
                // gives them actual remaining capacity
                'status' => ($client->status === 'suspended' && $client->slots_consumed < $newSlots)
                    ? 'active'
                    : $client->status,
            ]);

            $topupRequest->update([
                'status'      => 'approved',
                'reviewed_at' => now(),
            ]);
        });

        // Bust the sidebar badge so the pending count drops to the correct value.
        Cache::forget('admin_nav_pending_topups');

        app(PortalTelegramAlertService::class)->notifyTopupApproved($topupRequest);

        return back()->with('success', "Approved! Added {$topupRequest->amount_requested} slots to {$topupRequest->client->name}.");
    }

    /**
     * Admin rejects a topup request — optionally adds a note visible to the client.
     */
    public function reject(Request $request, TopupRequest $topupRequest)
    {
        $this->authorize('reject', $topupRequest);

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        if ($topupRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $topupRequest->update([
            'status'      => 'rejected',
            'notes'       => $request->notes,
            'reviewed_at' => now(),
        ]);

        Cache::forget('admin_nav_pending_topups');

        return back()->with('success', "Rejected the top-up request from {$topupRequest->client->name}.");
    }
}

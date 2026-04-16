<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TopupRequest;
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
     * Client submits a top-up request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount_requested' => 'required|integer|min:1|max:1000',
            'transaction_id'   => 'required|string|max:255|unique:topup_requests,transaction_id',
        ], [
            'transaction_id.unique' => 'This Transaction / UTR ID has already been submitted. If this is an error, please contact admin.',
            'amount_requested.max'  => 'Maximum top-up request is 1000 slots at a time.',
        ]);

        $client = Auth::user()->client;

        if (!$client) {
            return back()->with('error', 'No client account linked to your user.');
        }

        // Prevent duplicate pending requests
        if ($client->topupRequests()->where('status', 'pending')->exists()) {
            return back()->with('error', 'You already have a pending top-up request. Please wait for Admin approval.');
        }

        TopupRequest::create([
            'client_id'        => $client->id,
            'amount_requested' => $request->amount_requested,
            'transaction_id'   => $request->transaction_id,
            'status'           => 'pending',
        ]);

        return back()->with('success', 'Top-up request submitted! The admin will review it shortly.');
    }

    /**
     * Admin approves a top-up request — adds slots and marks as approved.
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

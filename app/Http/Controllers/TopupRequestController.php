<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TopupRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TopupRequestController extends Controller
{
    /**
     * Client submits a top-up request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount_requested' => 'required|integer|min:1',
            'transaction_id' => 'required|string|max:255',
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
            'client_id' => $client->id,
            'amount_requested' => $request->amount_requested,
            'transaction_id' => $request->transaction_id,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Top-up request submitted! The admin will review it shortly.');
    }

    /**
     * Admin approves a top-up request — adds slots and marks as approved.
     */
    public function approve(TopupRequest $topupRequest)
    {
        if ($topupRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        DB::transaction(function () use ($topupRequest) {
            $client = $topupRequest->client;

            // Add slots (same logic as the existing refill method)
            $client->update([
                'slots' => $client->slots + $topupRequest->amount_requested,
                'status' => 'active',
            ]);

            $topupRequest->update([
                'status' => 'approved',
                'reviewed_at' => now(),
            ]);
        });

        return back()->with('success', "Approved! Added {$topupRequest->amount_requested} slots to {$topupRequest->client->name}.");
    }

    /**
     * Admin rejects a top-up request.
     */
    public function reject(TopupRequest $topupRequest)
    {
        if ($topupRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $topupRequest->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "Rejected the top-up request from {$topupRequest->client->name}.");
    }
}

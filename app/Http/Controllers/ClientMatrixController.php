<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TopupRequest;
use Illuminate\Http\Request;

class ClientMatrixController extends Controller
{
    public function index()
    {
        $clients = Client::withCount('orders')->get();
        $pendingTopups = TopupRequest::with('client')->where('status', 'pending')->latest()->get();
        return view('admin.matrix.index', compact('clients', 'pendingTopups'));
    }

    public function update(Request $request, Client $matrix)
    {
        // Parameter is named $matrix based on the resource route convention (admin/matrix/{matrix})
        $request->validate([
            'slots' => 'required|integer|min:0',
            'status' => 'required|in:active,suspended',
            'price_per_file' => 'required|numeric|min:0',
        ]);

        $matrix->update([
            'slots' => $request->slots,
            'status' => $request->status,
            'price_per_file' => $request->price_per_file,
        ]);

        return back()->with('success', 'Client profile updated successfully.');
    }
    public function refill(Request $request, Client $client)
    {
        $request->validate([
            'additional_slots' => 'required|integer|min:1',
        ]);

        // Only reactivate the client's portal status if their linked user account
        // is not frozen by an admin. A frozen user must be unfrozen explicitly —
        // adding credits should not silently undo that administrative decision.
        $userFrozen = $client->user?->status === 'frozen';

        $client->update([
            'slots'  => $client->slots + $request->additional_slots,
            'status' => $userFrozen ? $client->status : 'active',
        ]);

        $note = $userFrozen ? ' (account remains frozen — unfreeze separately)' : '. Account is now Active.';
        return back()->with('success', "Added {$request->additional_slots} slots to {$client->name}{$note}");
    }
}

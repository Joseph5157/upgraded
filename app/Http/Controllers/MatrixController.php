<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TopupRequest;
use Illuminate\Http\Request;

class MatrixController extends Controller
{
    public function index()
    {
        $clients = Client::withCount('orders')->get();
        $pendingTopups = TopupRequest::with('client')->where('status', 'pending')->latest()->get();
        return view('admin.finance.matrix', compact('clients', 'pendingTopups'));
    }

    public function update(Request $request, Client $client)
    {
        $request->validate([
            'slots' => 'required|integer|min:0',
            'status' => 'required|in:active,suspended',
            'price_per_file' => 'required|numeric|min:0',
        ]);

        $client->update([
            'slots' => $request->slots,
            'status' => $request->status,
            'price_per_file' => $request->price_per_file,
        ]);

        return back()->with('success', "Client profile for {$client->name} updated successfully.");
    }

    public function refill(Request $request, Client $client)
    {
        $request->validate([
            'additional_slots' => 'required|integer|min:1',
        ]);

        $client->update([
            'slots' => $client->slots + $request->additional_slots,
            'status' => 'active',
        ]);

        return back()->with('success', "Added {$request->additional_slots} slots to {$client->name}. Account is now Active.");
    }
}

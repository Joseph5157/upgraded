<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        $clients = Client::with('user')
            ->where('status', '!=', 'deleted')
            ->orderBy('name')
            ->get();

        $vendors = User::where('role', 'vendor')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return view('admin.pricing.index', compact('clients', 'vendors'));
    }

    public function updateClient(Request $request, Client $client): RedirectResponse
    {
        $request->validate([
            'price_per_file' => ['required', 'numeric', 'min:0', 'max:99999'],
        ]);

        $client->update(['price_per_file' => $request->price_per_file]);

        return back()->with('success', "Price updated for {$client->name}.");
    }

    public function updateVendor(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role !== 'vendor', 403);

        $request->validate([
            'payout_rate' => ['required', 'numeric', 'min:0', 'max:99999'],
        ]);

        $user->update(['payout_rate' => $request->payout_rate]);

        return back()->with('success', "Payout rate updated for {$user->name}.");
    }
}

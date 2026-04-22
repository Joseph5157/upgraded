<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientLink;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClientLinkController extends Controller
{
    public function index(): View
    {
        $clients = Client::with(['links' => function ($q) {
            $q->latest();
        }])->orderBy('name')->get();

        return view('admin.client-links.index', compact('clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
        ]);

        ClientLink::create([
            'client_id' => $request->client_id,
            'token'     => Str::random(40),
            'is_active' => true,
        ]);

        return back()->with('success', 'Upload link created successfully.');
    }

    public function toggle(ClientLink $clientLink): RedirectResponse
    {
        $clientLink->update(['is_active' => ! $clientLink->is_active]);

        $state = $clientLink->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Link {$state} successfully.");
    }

    public function destroy(ClientLink $clientLink): RedirectResponse
    {
        $clientLink->delete();

        return back()->with('success', 'Link deleted successfully.');
    }

    public function showOrders(ClientLink $clientLink): View
    {
        $clientLink->load(['client', 'orders' => function ($q) {
            $q->with(['files'])->latest();
        }]);

        return view('admin.client-links.orders', compact('clientLink'));
    }

    public function destroyOrder(ClientLink $clientLink, Order $order): RedirectResponse
    {
        abort_if($order->client_link_id !== $clientLink->id, 403);

        $order->files()->each(function ($file) {
            \Storage::disk('public')->delete($file->path);
            $file->delete();
        });

        $order->delete();

        return back()->with('success', 'Order deleted successfully.');
    }

    public function storeClient(Request $request): RedirectResponse
    {
        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'slots' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        $client = \App\Models\Client::create([
            'name'           => $request->name,
            'slots'          => $request->slots,
            'slots_consumed' => 0,
            'status'         => 'active',
        ]);

        ClientLink::create([
            'client_id' => $client->id,
            'token'     => Str::random(40),
            'is_active' => true,
        ]);

        return back()->with('success', "Link client \"{$client->name}\" created with {$client->slots} slots.");
    }
}

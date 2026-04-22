<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientLink;
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
}

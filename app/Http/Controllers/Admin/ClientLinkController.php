<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientLink;
use App\Models\Order;
use App\Services\AuditLogger;
use App\Support\StorageLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ClientLinkController extends Controller
{
    protected function adminLinkRelations(): array
    {
        $relations = [];

        if (Schema::hasColumn('client_links', 'created_by_user_id')) {
            $relations[] = 'createdBy';
        }

        if (Schema::hasColumn('client_links', 'revoked_by_user_id')) {
            $relations[] = 'revokedBy';
        }

        return $relations;
    }

    protected function usableLinkExists(Client $client, ?int $ignoreLinkId = null): bool
    {
        return $client->links()
            ->when($ignoreLinkId !== null, fn ($query) => $query->where('id', '!=', $ignoreLinkId))
            ->usable()
            ->exists();
    }

    public function index(): View
    {
        $clients = Client::with(['user', 'links' => function ($q) {
            $q->latest()->with($this->adminLinkRelations());
        }])->has('links')->orderBy('name')->get();

        return view('admin.client-links.index', compact('clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
        ]);

        $client = Client::with('links')->findOrFail($request->client_id);

        if ($this->usableLinkExists($client)) {
            return back()->withErrors([
                'client_id' => 'This client already has an active guest link. Revoke it before creating another one.',
            ]);
        }

        $link = ClientLink::create([
            'client_id' => $client->id,
            'token' => Str::random(40),
            'is_active' => true,
        ] + ($request->user()?->id && Schema::hasColumn('client_links', 'created_by_user_id')
            ? ['created_by_user_id' => $request->user()->id]
            : []) + (Schema::hasColumn('client_links', 'expires_at')
            ? ['expires_at' => now()->addDay()]
            : []));

        app(AuditLogger::class)->record('client_link.created', $link, [
            'client_id' => $client->id,
            'created_by_user_id' => $request->user()?->id,
            'expires_at' => $link->expires_at?->toIso8601String(),
        ]);

        return back()->with('success', 'Upload link created successfully.');
    }

    public function revoke(Request $request, ClientLink $clientLink): RedirectResponse
    {
        if ($clientLink->isRevoked()) {
            return back()->with('success', 'Link is already revoked.');
        }

        $clientLink->update([
            'is_active' => false,
        ] + (Schema::hasColumn('client_links', 'revoked_at')
            ? ['revoked_at' => now()]
            : []) + ($request->user()?->id && Schema::hasColumn('client_links', 'revoked_by_user_id')
            ? ['revoked_by_user_id' => $request->user()->id]
            : []));

        app(AuditLogger::class)->record('client_link.revoked', $clientLink, [
            'client_id' => $clientLink->client_id,
            'revoked_by_user_id' => $request->user()?->id,
        ]);

        return back()->with('success', 'Link revoked successfully.');
    }

    public function destroy(Request $request, ClientLink $clientLink): RedirectResponse
    {
        app(AuditLogger::class)->record('client_link.deleted', $clientLink, [
            'client_id' => $clientLink->client_id,
            'deleted_by_user_id' => $request->user()?->id,
            'had_orders' => $clientLink->orders()->exists(),
        ]);

        $clientLink->delete();

        return back()->with('success', 'Link deleted successfully.');
    }

    public function showOrders(ClientLink $clientLink): View
    {
        $clientLink->load(array_merge(['client', 'orders' => function ($q) {
            $q->with(['files'])->latest();
        }], $this->adminLinkRelations()));

        return view('admin.client-links.orders', compact('clientLink'));
    }

    public function destroyOrder(ClientLink $clientLink, Order $order): RedirectResponse
    {
        abort_if($order->client_link_id !== $clientLink->id, 403);

        foreach ($order->files as $file) {
            StorageLifecycle::deleteStoredFileIfPresent('public', $file->path);
            $file->delete();
        }

        if ($order->report) {
            StorageLifecycle::deleteStoredFileIfPresent($order->report->ai_report_disk ?: 'r2', $order->report->ai_report_path);
            StorageLifecycle::deleteStoredFileIfPresent($order->report->plag_report_disk ?: 'r2', $order->report->plag_report_path);
            $order->report->delete();
        }

        $order->delete();

        return back()->with('success', 'Order deleted successfully.');
    }

    public function destroyClient(Client $client): RedirectResponse
    {
        // Block deletion if this client has a portal account
        abort_if($client->user !== null, 403, 'Cannot delete a client with a portal account from here.');

        // Delete all links and their orders/files
        foreach ($client->links as $link) {
            foreach ($link->orders as $order) {
                foreach ($order->files as $file) {
                    StorageLifecycle::deleteStoredFileIfPresent($file->disk ?: 'r2', $file->file_path);
                    $file->delete();
                }
                if ($order->report) {
                    StorageLifecycle::deleteStoredFileIfPresent($order->report->ai_report_disk ?: 'r2', $order->report->ai_report_path);
                    StorageLifecycle::deleteStoredFileIfPresent($order->report->plag_report_disk ?: 'r2', $order->report->plag_report_path);
                    $order->report->delete();
                }
                $order->delete();
            }
            $link->delete();
        }

        $name = $client->name;
        $client->delete();

        return redirect()->route('admin.client-links.index')
            ->with('success', "Client \"{$name}\" and all their links have been deleted.");
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

        $link = ClientLink::create([
            'client_id' => $client->id,
            'token' => Str::random(40),
            'is_active' => true,
        ] + ($request->user()?->id && Schema::hasColumn('client_links', 'created_by_user_id')
            ? ['created_by_user_id' => $request->user()->id]
            : []) + (Schema::hasColumn('client_links', 'expires_at')
            ? ['expires_at' => now()->addDay()]
            : []));

        app(AuditLogger::class)->record('client_link.created', $link, [
            'client_id' => $client->id,
            'created_by_user_id' => $request->user()?->id,
            'expires_at' => $link->expires_at?->toIso8601String(),
        ]);

        return back()->with('success', "Link client \"{$client->name}\" created with {$client->slots} slots.");
    }
}

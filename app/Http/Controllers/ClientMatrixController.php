<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TopupRequest;
use App\Support\LogContext;
use App\Support\StorageLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientMatrixController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Client::class);
        $clients = Client::withCount('orders')->get();
        $pendingTopups = TopupRequest::with('client')->where('status', 'pending')->latest()->get();

        return view('admin.matrix.index', compact('clients', 'pendingTopups'));
    }

    public function update(Request $request, Client $matrix)
    {
        $this->authorize('update', $matrix);
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
        $this->authorize('refill', $client);
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

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        abort_if($client->user !== null, 403, 'Cannot delete a client that still has a portal account attached.');

        $name = $client->name;

        DB::transaction(function () use ($client, $request): void {
            $client = Client::with(['links.orders.files', 'links.orders.report', 'orders.files', 'orders.report', 'topupRequests', 'refundRequests'])
                ->whereKey($client->id)
                ->lockForUpdate()
                ->firstOrFail();

            $orders = $client->links
                ->flatMap->orders
                ->merge($client->orders)
                ->unique('id')
                ->values();

            foreach ($orders as $order) {
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

            foreach ($client->links as $link) {
                $link->delete();
            }

            $client->topupRequests()->delete();
            $client->refundRequests()->delete();
            $client->delete();

            Log::info('client.deleted_from_credits', array_merge(
                LogContext::forClient($client, LogContext::currentRequest()),
                ['deleted_by_user_id' => $request->user()?->id]
            ));
        });

        return back()->with('success', "Client \"{$name}\" and all related files have been deleted.");
    }
}

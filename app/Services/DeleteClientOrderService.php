<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Support\LogContext;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteClientOrderService
{
    protected string $storageDisk;

    public function __construct(
        protected AuditLogger $auditLogger,
        string $storageDisk = '',
    ) {
        $this->storageDisk = $storageDisk ?: config('filesystems.default', 'r2');
    }

    /**
     * Permanently delete an unclaimed pending order, its files, and its reports.
     * Restores the client's consumed slots based on the order's file count.
     *
     * @return bool  True if a slot was restored (so the caller can show the appropriate message)
     */
    public function execute(Order $order, Client $client): bool
    {
        $requestContext = LogContext::currentRequest();
        $actor = request()?->user();

        Log::info('client_order.delete_attempted', LogContext::forClient(
            $client,
            LogContext::forOrder($order, $actor ? LogContext::forUser($actor, $requestContext) : $requestContext)
        ));
        $this->auditLogger->record('client_order.delete_attempted', $order, [
            'order_status' => $order->status?->value,
            'claimed_by' => $order->claimed_by,
            'client_id' => $client->id,
        ], $actor?->id);

        if ($order->status !== OrderStatus::Pending || $order->claimed_by !== null) {
            $context = LogContext::forClient(
                $client,
                LogContext::forOrder($order, $actor ? LogContext::forUser($actor, $requestContext) : $requestContext)
            );

            Log::warning('client_order.delete_denied', array_merge($context, [
                'reason' => 'order_not_unclaimed_pending',
            ]));
            $this->auditLogger->record('client_order.delete_denied', $order, [
                'reason' => 'order_not_unclaimed_pending',
                'order_status' => $order->status?->value,
                'claimed_by' => $order->claimed_by,
                'client_id' => $client->id,
            ], $actor?->id);

            throw new Exception('Only unclaimed pending orders can be deleted.');
        }

        DB::transaction(function () use ($order, $client) {
            // Re-read the client row with a row-level lock to prevent concurrent
            // delete/credit-restore races (e.g. two simultaneous delete requests).
            $client = Client::where('id', $client->id)->lockForUpdate()->first();

            // Delete uploaded source files from R2
            foreach ($order->files as $file) {
                Storage::disk($file->disk ?: $this->storageDisk)->delete($file->file_path);
            }

            // Delete the entire order directory
            foreach ($order->files->pluck('disk')->filter()->push($this->storageDisk)->unique() as $disk) {
                Storage::disk($disk)->deleteDirectory('orders/' . $order->id);
            }

            // Delete report PDFs (AI + plagiarism)
            if ($order->report) {
                if ($order->report->ai_report_path) {
                    Storage::disk($order->report->ai_report_disk ?: $this->storageDisk)
                        ->delete($order->report->ai_report_path);
                }
                if ($order->report->plag_report_path) {
                    Storage::disk($order->report->plag_report_disk ?: $this->storageDisk)
                        ->delete($order->report->plag_report_path);
                }
                collect([$order->report->ai_report_disk, $order->report->plag_report_disk, $this->storageDisk])
                    ->filter()
                    ->unique()
                    ->each(fn($disk) => Storage::disk($disk)->deleteDirectory('reports/' . $order->id));
            }

            // Delete database records
            $order->files()->delete();
            $order->report()->delete();
            $order->delete();

            // Restore slot credit if the service was never rendered
            $creditsToRestore = max(0, (int) $order->files_count);
            $newConsumed = max(0, $client->slots_consumed - $creditsToRestore);

            $client->update(['slots_consumed' => $newConsumed]);

            $actor = request()?->user();
            $requestContext = LogContext::currentRequest();

            Log::info('credits.restored', LogContext::forClient(
                $client,
                LogContext::forOrder($order, $actor ? LogContext::forUser($actor, $requestContext) : $requestContext)
            ));
            $this->auditLogger->record('credits.restored', $order, [
                'client_id' => $client->id,
                'credits_restored' => $creditsToRestore,
                'slots_consumed_after' => $newConsumed,
            ], $actor?->id);

            if ($client->status === 'suspended' && $newConsumed < $client->slots) {
                $client->update(['status' => 'active']);
            }
        });

        return true;
    }
}

<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\Order;
use App\Services\Finance\ClientCreditService;
use App\Support\LogContext;
use App\Support\StorageLifecycle;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteClientOrderService
{
    protected string $storageDisk;

    public function __construct(
        protected AuditLogger $auditLogger,
        protected ClientCreditService $creditService,
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

        $creditsRefunded = false;

        DB::transaction(function () use ($order, $client, &$creditsRefunded) {
            // Re-read the client row with a row-level lock to prevent concurrent
            // delete/credit-restore races (e.g. two simultaneous delete requests).
            $client = Client::where('id', $client->id)->lockForUpdate()->first();

            // Guard: only refund credits for Phase 4+ orders that had a debit tx.
            // Pre-Phase-4 orders never debited credit_balance, so no refund is issued.
            $hasDebitTx = ClientCreditTransaction::where('order_id', $order->id)
                ->where('type', ClientCreditTransaction::TYPE_ORDER_DEBIT)
                ->exists();

            // Refund credits BEFORE deleting the order row (refundForOrder updates
            // order.credits_refunded_at and reads order.credits_consumed).
            if ($hasDebitTx) {
                $this->creditService->refundForOrder($client, $order, [
                    'created_by' => request()?->user()?->id,
                ]);
                $creditsRefunded = true;
            }

            // Delete uploaded source files from storage first. Missing files are
            // tolerated, but if a file exists and cannot be removed we abort so
            // the database rows are not removed out from under it.
            foreach ($order->files as $file) {
                StorageLifecycle::deleteStoredFileIfPresent($file->disk ?: $this->storageDisk, $file->file_path);
            }
            collect(StorageLifecycle::uniqueDisks($order->files->pluck('disk')->all(), $this->storageDisk))
                ->each(fn ($disk) => StorageLifecycle::deleteStoredDirectory($disk, 'orders/' . $order->id));

            // Delete report PDFs (AI + plagiarism)
            if ($order->report) {
                StorageLifecycle::deleteStoredFileIfPresent($order->report->ai_report_disk ?: $this->storageDisk, $order->report->ai_report_path);
                StorageLifecycle::deleteStoredFileIfPresent($order->report->plag_report_disk ?: $this->storageDisk, $order->report->plag_report_path);
                collect(StorageLifecycle::uniqueDisks([
                    $order->report->ai_report_disk,
                    $order->report->plag_report_disk,
                ], $this->storageDisk))
                    ->each(fn ($disk) => StorageLifecycle::deleteStoredDirectory($disk, 'reports/' . $order->id));
            }

            // Delete database records
            $order->files()->delete();
            $order->report()->delete();
            $order->delete();

            $actor = request()?->user();
            $requestContext = LogContext::currentRequest();

            Log::info('credits.restored', LogContext::forClient(
                $client,
                LogContext::forOrder($order, $actor ? LogContext::forUser($actor, $requestContext) : $requestContext)
            ));
            $this->auditLogger->record('credits.restored', $order, [
                'client_id'           => $client->id,
                'credits_refunded'    => $creditsRefunded,
                'credit_balance_after' => $client->fresh()->credit_balance,
            ], $actor?->id);

            // Reactivate suspended client if credit balance was restored
            $freshClient = $client->fresh();
            if ($client->status === 'suspended' && $freshClient->credit_balance > 0) {
                $client->update(['status' => 'active']);
            }
        });

        return $creditsRefunded;
    }
}

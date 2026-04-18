<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteClientOrderService
{
    protected string $storageDisk;

    public function __construct(
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
        if ($order->status !== OrderStatus::Pending || $order->claimed_by !== null) {
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

            if ($client->status === 'suspended' && $newConsumed < $client->slots) {
                $client->update(['status' => 'active']);
            }
        });

        return true;
    }
}

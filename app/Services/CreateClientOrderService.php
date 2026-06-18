<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderFile;
use App\Services\Finance\ClientCreditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateClientOrderService
{
    protected string $storageDisk;

    public function __construct(
        protected PortalTelegramAlertService $telegramAlerts,
        protected ClientCreditService $creditService,
        string $storageDisk = '',
    ) {
        $this->storageDisk = $storageDisk ?: config('filesystems.default', 'r2');
    }

    /**
     * Create a new client order, upload its files, and notify vendors.
     *
     * @param  Client          $client
     * @param  UploadedFile[]  $files         Validated uploaded file instances
     * @param  string          $source        'account' or 'link'
     * @param  array           $meta          Optional extra columns: notes, client_link_id, created_by_user_id
     * @return Order
     *
     * @throws \Exception  On plan expiry, quota exceeded, or storage failure
     */
    public function execute(Client $client, array $files, string $source, array $meta = []): Order
    {
        $fileCount = count($files);
        $orderId = null;
        $remainingCreditsAfterUpload = 0;
        $slaMinutes = (int) config('services.portal.default_sla_minutes', 20);
        if ($slaMinutes <= 0) {
            $slaMinutes = 20;
        }

        DB::transaction(function () use ($client, $files, $source, $meta, $fileCount, $slaMinutes, &$orderId, &$remainingCreditsAfterUpload) {
            $client = Client::where('id', $client->id)->lockForUpdate()->first();

            if ($client->plan_expiry && $client->plan_expiry->isPast()) {
                throw new \Exception(
                    'Your plan has expired on ' . $client->plan_expiry->format('d M Y') . '. Please contact Admin to renew.'
                );
            }

            $creditBalance = (int) $client->credit_balance;

            if ($creditBalance <= 0) {
                throw new \Exception('No upload credits remaining. Please contact Admin to add credits.');
            }

            if ($client->status === 'suspended') {
                throw new \Exception('Your account is suspended. Please contact Admin for a refill.');
            }

            if ($creditBalance < $fileCount) {
                throw new \Exception(
                    "Insufficient credits. You selected {$fileCount} file(s), but only {$creditBalance} credit(s) are available."
                );
            }

            $ratePerFile  = (float) ($client->price_per_file ?? 0);
            $clientAmount = round($fileCount * $ratePerFile, 2);

            $order = Order::create([
                'client_id'          => $client->id,
                'token_view'         => Str::random(32),
                'files_count'        => $fileCount,
                'notes'              => $meta['notes'] ?? null,
                'status'             => OrderStatus::Pending,
                'source'             => $source,
                'client_link_id'     => $meta['client_link_id'] ?? null,
                'created_by_user_id' => $meta['created_by_user_id'] ?? null,
                'due_at'             => now()->addMinutes($slaMinutes),
                // Financial snapshot (Phase 4)
                'credits_consumed'     => $fileCount,
                'client_rate_per_file' => $ratePerFile,
                'client_amount'        => $clientAmount,
            ]);

            $uploadedPaths = [];

            try {
                foreach ($files as $file) {
                    $clientOriginalName = basename($file->getClientOriginalName());
                    $storedName = preg_replace('/[^\\x00-\\x7F]+/', '_', $clientOriginalName);
                    $path = $file->storeAs('orders/' . $order->id, $storedName, $this->storageDisk);

                    if ($path === false) {
                        throw new \Exception('Failed to upload file: ' . $clientOriginalName);
                    }

                    $uploadedPaths[] = ['disk' => $this->storageDisk, 'path' => $path];

                    OrderFile::create([
                        'order_id'      => $order->id,
                        'file_path'     => $path,
                        'disk'          => $this->storageDisk,
                        'original_name' => $clientOriginalName,
                    ]);
                }
            } catch (\Exception $e) {
                foreach ($uploadedPaths as $uploaded) {
                    Storage::disk($uploaded['disk'])->delete($uploaded['path']);
                }
                throw $e;
            }

            $this->creditService->debitForOrder($client, $order, [
                'created_by' => $meta['created_by_user_id'] ?? null,
            ]);

            $freshClient = $client->fresh();
            $remainingCreditsAfterUpload = (int) $freshClient->credit_balance;

            if ($freshClient->credit_balance <= 0) {
                $client->update(['status' => 'suspended']);
            }

            $orderId = $order->id;
        });

        $order = Order::findOrFail($orderId);

        $this->telegramAlerts->notifyOrderAccepted($order, $remainingCreditsAfterUpload);

        return $order;
    }
}


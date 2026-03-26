<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateClientOrderService
{
    protected string $storageDisk;

    public function __construct(
        protected PortalTelegramAlertService $telegramAlerts,
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

        DB::transaction(function () use ($client, $files, $source, $meta, $fileCount, &$orderId, &$remainingCreditsAfterUpload) {
            $client = Client::where('id', $client->id)->lockForUpdate()->first();

            if ($client->plan_expiry && $client->plan_expiry->isPast()) {
                throw new \Exception(
                    'Your plan has expired on ' . $client->plan_expiry->format('d M Y') . '. Please contact Admin to renew.'
                );
            }

            $totalSlots = (int) $client->total_slots;
            $consumed = (int) $client->slots_consumed;
            $remainingCredits = $totalSlots - $consumed;

            if ($consumed >= $totalSlots) {
                throw new \Exception('No upload slots remaining.');
            }

            if ($client->status === 'suspended') {
                throw new \Exception('Your account is suspended. Please contact Admin for a refill.');
            }

            if ($remainingCredits < $fileCount) {
                throw new \Exception(
                    "Insufficient credits. You selected {$fileCount} file(s), but only {$remainingCredits} credit(s) are available."
                );
            }

            $order = Order::create([
                'client_id'          => $client->id,
                'token_view'         => Str::random(32),
                'files_count'        => $fileCount,
                'notes'              => $meta['notes'] ?? null,
                'status'             => OrderStatus::Pending,
                'due_at'             => now()->addMinutes(config('services.portal.default_sla_minutes', 20)),
                'source'             => $source,
                'client_link_id'     => $meta['client_link_id'] ?? null,
                'created_by_user_id' => $meta['created_by_user_id'] ?? null,
            ]);

            $uploadedPaths = [];

            try {
                foreach ($files as $file) {
                    $originalName = preg_replace('/[^\\x00-\\x7F]+/', '_', basename($file->getClientOriginalName()));
                    $path = $file->storeAs('orders/' . $order->id, $originalName, $this->storageDisk);

                    if ($path === false) {
                        throw new \Exception('Failed to upload file: ' . $originalName);
                    }

                    $uploadedPaths[] = ['disk' => $this->storageDisk, 'path' => $path];

                    OrderFile::create([
                        'order_id'  => $order->id,
                        'file_path' => $path,
                        'disk'      => $this->storageDisk,
                    ]);
                }
            } catch (\Exception $e) {
                foreach ($uploadedPaths as $uploaded) {
                    Storage::disk($uploaded['disk'])->delete($uploaded['path']);
                }
                throw $e;
            }

            $client->increment('slots_consumed', $fileCount);

            $freshClient = $client->fresh();
            $remainingCreditsAfterUpload = max(0, (int) $freshClient->total_slots - (int) $freshClient->slots_consumed);

            if ($freshClient->slots_consumed >= $totalSlots) {
                $client->update(['status' => 'suspended']);
            }

            $orderId = $order->id;
        });

        $order = Order::findOrFail($orderId);

        $this->telegramAlerts->notifyOrderAccepted($order, $remainingCreditsAfterUpload);

        return $order;
    }
}


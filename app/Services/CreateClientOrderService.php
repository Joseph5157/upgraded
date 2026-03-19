<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Jobs\SendTelegramNotification;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateClientOrderService
{
    protected string $storageDisk;

    public function __construct(string $storageDisk = '')
    {
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
        $orderId = null;

        DB::transaction(function () use ($client, $files, $source, $meta, &$orderId) {
            // Lock the client row to prevent concurrent slot over-use
            $client = Client::where('id', $client->id)->lockForUpdate()->first();

            if ($client->plan_expiry && $client->plan_expiry->isPast()) {
                throw new \Exception(
                    'Your plan has expired on ' . $client->plan_expiry->format('d M Y') . '. Please contact Admin to renew.'
                );
            }

            if ($client->status === 'suspended' || $client->slots_consumed >= $client->slots) {
                throw new \Exception(
                    'Insufficient credits. You have reached your limit of ' . $client->slots . ' files. Please contact Admin for a refill.'
                );
            }

            $order = Order::create([
                'client_id'          => $client->id,
                'token_view'         => Str::random(32),
                'files_count'        => count($files),
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
                    $originalName = preg_replace('/[^\w.\-]/', '_', basename($file->getClientOriginalName()));
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
                // Clean up any files that were successfully uploaded before the failure
                foreach ($uploadedPaths as $uploaded) {
                    Storage::disk($uploaded['disk'])->delete($uploaded['path']);
                }
                throw $e;
            }

            $client->increment('slots_consumed');

            if ($client->fresh()->slots_consumed >= $client->slots) {
                $client->update(['status' => 'suspended']);
            }

            $orderId = $order->id;
        });

        $order = Order::find($orderId);

        SendTelegramNotification::dispatch($order);

        return $order;
    }
}

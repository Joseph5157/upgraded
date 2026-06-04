<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\ClientLink;
use App\Models\RazorpayOrder;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProvisionGuestLinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly int $razorpayOrderId) {}

    public function handle(WhatsappService $whatsapp): void
    {
        $razorpayOrder = RazorpayOrder::find($this->razorpayOrderId);

        if (! $razorpayOrder) {
            Log::error('ProvisionGuestLinkJob: not found.', ['id' => $this->razorpayOrderId]);
            return;
        }

        if ($razorpayOrder->status === 'provisioned') {
            Log::info('ProvisionGuestLinkJob: already provisioned, skipping.');
            return;
        }

        $plan = config('plans.' . $razorpayOrder->plan);

        if (! $plan) {
            $this->fail("Unknown plan: {$razorpayOrder->plan}");
            return;
        }

        DB::transaction(function () use ($razorpayOrder, $plan, $whatsapp) {
            $client = Client::create([
                'name'           => $razorpayOrder->name,
                'slots'          => $razorpayOrder->slots,
                'slots_consumed' => 0,
                'price_per_file' => $plan['price_per_file'],
                'status'         => 'active',
                'plan_expiry'    => now()->addDays($plan['expiry_days']),
            ]);

            $link = ClientLink::create([
                'client_id'  => $client->id,
                'token'      => Str::random(40),
                'is_active'  => true,
                'expires_at' => now()->addDays($plan['expiry_days']),
            ]);

            $uploadUrl = route('client.upload', ['token' => $link->token]);

            $whatsapp->send(
                phone: $razorpayOrder->phone,
                templateName: 'upload_link_ready',
                params: [$razorpayOrder->name, $uploadUrl]
            );

            $razorpayOrder->update([
                'status'         => 'provisioned',
                'client_id'      => $client->id,
                'client_link_id' => $link->id,
            ]);
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProvisionGuestLinkJob: permanently failed.', [
            'id'    => $this->razorpayOrderId,
            'error' => $exception->getMessage(),
        ]);
    }
}

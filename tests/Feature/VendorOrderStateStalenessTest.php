<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Http\Controllers\DashboardController;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VendorOrderStateStalenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_claim_uses_current_database_state_when_route_model_is_stale(): void
    {
        $vendor = $this->makeVendor();
        $otherVendor = $this->makeVendor();
        $order = $this->makePendingOrder();
        $staleOrder = $order->fresh();

        $order->update([
            'status' => OrderStatus::Claimed,
            'claimed_by' => $otherVendor->id,
            'claimed_at' => now(),
        ]);

        $request = Request::create('/orders/' . $order->id . '/claim', 'POST', [], [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->actingAs($vendor);

        try {
            app(DashboardController::class)->claim($request, $staleOrder);
            $this->fail('Expected the stale claim attempt to be rejected by authorization.');
        } catch (AuthorizationException $e) {
            $this->assertTrue(true);
        }

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Claimed->value,
            'claimed_by' => $otherVendor->id,
        ]);
    }

    public function test_update_status_uses_current_database_state_when_route_model_is_stale(): void
    {
        $vendor = $this->makeVendor();
        $order = $this->makeClaimedOrder($vendor);
        $staleOrder = $order->fresh();

        $order->update([
            'status' => OrderStatus::Delivered,
            'delivered_at' => now(),
        ]);

        $request = Request::create('/orders/' . $order->id . '/status', 'POST', [
            'status' => 'delivered',
        ], [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->actingAs($vendor);

        $response = app(DashboardController::class)->updateStatus($request, $staleOrder);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Delivered->value,
            'claimed_by' => $vendor->id,
        ]);
    }

    public function test_vendor_can_claim_when_claimed_at_column_is_missing(): void
    {
        if (Schema::hasColumn('orders', 'claimed_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('claimed_at');
            });
        }

        $vendor = $this->makeVendor();
        $order = $this->makePendingOrder();

        $response = $this->actingAs($vendor)->post(route('orders.claim', $order), [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response->assertOk()->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Claimed->value,
            'claimed_by' => $vendor->id,
        ]);
    }

    protected function makeVendor(): User
    {
        return User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'email_verified_at' => now(),
            'portal_number' => fake()->unique()->numberBetween(100000, 999999),
            'activated_at' => now(),
            'telegram_chat_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
        ]);
    }

    protected function makePendingOrder(): Order
    {
        $client = Client::create([
            'name' => 'Stale State Client',
            'email' => 'stale-state-client@example.com',
        ]);

        $orderData = [
            'client_id' => $client->id,
            'token_view' => 'stale-claim-test',
            'files_count' => 1,
            'status' => OrderStatus::Pending,
            'claimed_by' => null,
            'due_at' => now()->addMinutes(20),
            'source' => 'account',
        ];

        if (Schema::hasColumn('orders', 'claimed_at')) {
            $orderData['claimed_at'] = null;
        }

        return Order::create($orderData);
    }

    protected function makeClaimedOrder(User $vendor): Order
    {
        $client = Client::create([
            'name' => 'Stale Status Client',
            'email' => 'stale-status-client@example.com',
        ]);

        $orderData = [
            'client_id' => $client->id,
            'token_view' => 'stale-status-test',
            'files_count' => 1,
            'status' => OrderStatus::Claimed,
            'claimed_by' => $vendor->id,
            'due_at' => now()->addMinutes(20),
            'source' => 'account',
        ];

        if (Schema::hasColumn('orders', 'claimed_at')) {
            $orderData['claimed_at'] = now();
        }

        return Order::create($orderData);
    }
}

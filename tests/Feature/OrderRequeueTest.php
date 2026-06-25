<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\WorkflowException;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorEarningTransaction;
use App\Policies\OrderPolicy;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 7D — Admin Requeue Failed Orders
 *
 * Rules verified:
 *  - admin can requeue a failed order
 *  - super_admin (is_super_admin=true) can requeue a failed order
 *  - vendor cannot requeue (policy blocks)
 *  - client cannot requeue (policy blocks)
 *  - cannot requeue a delivered order
 *  - cannot requeue a cancelled order
 *  - cannot requeue a pending order
 *  - requeued order status becomes pending
 *  - claimed_by is cleared
 *  - failed_at is preserved (audit history)
 *  - failure_reason is preserved (audit history)
 *  - failed_by is preserved (audit history)
 *  - requeue does not create a vendor earning
 *  - requeue does not refund client credit
 */
class OrderRequeueTest extends TestCase
{
    use RefreshDatabase;

    private int $counter = 0;

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function makeAdmin(array $attrs = []): User
    {
        $this->counter++;
        return User::create(array_merge([
            'name'           => 'Admin',
            'role'           => 'admin',
            'status'         => 'active',
            'portal_number'  => 100000 + $this->counter,
            'email'          => "admin{$this->counter}@test.com",
            'password'       => bcrypt('password'),
            'is_super_admin' => false,
        ], $attrs));
    }

    private function makeVendor(array $attrs = []): User
    {
        $this->counter++;
        return User::create(array_merge([
            'name'          => 'Vendor',
            'role'          => 'vendor',
            'status'        => 'active',
            'portal_number' => 200000 + $this->counter,
            'email'         => "vendor{$this->counter}@test.com",
            'password'      => bcrypt('password'),
            'payout_rate'   => 20.00,
        ], $attrs));
    }

    private function makeClientUser(Client $client): User
    {
        $this->counter++;
        return User::create([
            'name'          => 'Client User',
            'role'          => 'client',
            'status'        => 'active',
            'portal_number' => 300000 + $this->counter,
            'email'         => "clientuser{$this->counter}@test.com",
            'password'      => bcrypt('password'),
            'client_id'     => $client->id,
        ]);
    }

    private function makeClient(array $attrs = []): Client
    {
        $this->counter++;
        return Client::create(array_merge([
            'name'           => 'Test Client',
            'slots'          => 10,
            'slots_consumed' => 0,
            'credit_balance' => 100,
            'price_per_file' => 50.00,
            'status'         => 'active',
        ], $attrs));
    }

    private function makeFailedOrder(User $vendor, Client $client, array $attrs = []): Order
    {
        $this->counter++;
        return Order::create(array_merge([
            'client_id'        => $client->id,
            'token_view'       => 'tok-' . $this->counter,
            'files_count'      => 2,
            'credits_consumed' => 2,
            'client_rate_per_file' => 50.00,
            'client_amount'    => 100.00,
            'status'           => OrderStatus::Failed,
            'claimed_by'       => $vendor->id,
            'failed_at'        => now()->subMinutes(10),
            'failure_reason'   => 'File was corrupted',
            'failed_by'        => $vendor->id,
            'due_at'           => now()->addHour(),
            'source'           => 'account',
        ], $attrs));
    }

    private function service(): OrderWorkflowService
    {
        return app(OrderWorkflowService::class);
    }

    // ─── Tests ──────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_requeue_failed_order(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $order  = $this->makeFailedOrder($vendor, $client);

        $this->service()->requeueFailed($order, $admin);

        $order->refresh();

        $this->assertSame(OrderStatus::Pending, $order->status);
    }

    #[Test]
    public function super_admin_can_requeue_failed_order(): void
    {
        $superAdmin = $this->makeAdmin(['is_super_admin' => true]);
        $vendor     = $this->makeVendor();
        $client     = $this->makeClient();
        $order      = $this->makeFailedOrder($vendor, $client);

        $this->service()->requeueFailed($order, $superAdmin);

        $order->refresh();

        $this->assertSame(OrderStatus::Pending, $order->status);
    }

    #[Test]
    public function requeued_order_becomes_pending(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $order  = $this->makeFailedOrder($vendor, $client);

        $this->service()->requeueFailed($order, $admin, 'Retrying after fix');

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => OrderStatus::Pending->value,
        ]);
    }

    #[Test]
    public function claimed_by_is_cleared_on_requeue(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $order  = $this->makeFailedOrder($vendor, $client);

        $this->assertNotNull($order->claimed_by);

        $this->service()->requeueFailed($order, $admin);

        $this->assertDatabaseHas('orders', [
            'id'         => $order->id,
            'claimed_by' => null,
        ]);
    }

    #[Test]
    public function failure_history_is_preserved_after_requeue(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $order  = $this->makeFailedOrder($vendor, $client);

        $failedAt      = $order->failed_at;
        $failureReason = $order->failure_reason;
        $failedBy      = $order->failed_by;

        $this->service()->requeueFailed($order, $admin);

        $order->refresh();

        $this->assertNotNull($order->failed_at, 'failed_at should be preserved');
        $this->assertSame($failureReason, $order->failure_reason, 'failure_reason should be preserved');
        $this->assertSame($failedBy, $order->failed_by, 'failed_by should be preserved');
    }

    #[Test]
    public function requeue_does_not_create_vendor_earning(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $order  = $this->makeFailedOrder($vendor, $client);

        $this->service()->requeueFailed($order, $admin);

        $this->assertDatabaseCount('vendor_earning_transactions', 0);
    }

    #[Test]
    public function requeue_does_not_refund_client_credit(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor();
        $client = $this->makeClient(['credit_balance' => 50]);
        $order  = $this->makeFailedOrder($vendor, $client);

        $this->service()->requeueFailed($order, $admin);

        // Credit balance must be unchanged
        $this->assertDatabaseHas('clients', [
            'id'             => $client->id,
            'credit_balance' => 50,
        ]);
    }

    #[Test]
    public function cannot_requeue_delivered_order(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor();
        $client = $this->makeClient();

        $order = Order::create([
            'client_id'   => $client->id,
            'token_view'  => 'tok-delivered',
            'files_count' => 1,
            'status'      => OrderStatus::Delivered,
            'claimed_by'  => $vendor->id,
            'due_at'      => now()->addHour(),
            'source'      => 'account',
        ]);

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessageMatches('/only failed orders/i');

        $this->service()->requeueFailed($order, $admin);
    }

    #[Test]
    public function cannot_requeue_cancelled_order(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();

        $order = Order::create([
            'client_id'   => $client->id,
            'token_view'  => 'tok-cancelled',
            'files_count' => 1,
            'status'      => OrderStatus::Cancelled,
            'claimed_by'  => null,
            'due_at'      => now()->addHour(),
            'source'      => 'account',
        ]);

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessageMatches('/only failed orders/i');

        $this->service()->requeueFailed($order, $admin);
    }

    #[Test]
    public function cannot_requeue_pending_order(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();

        $order = Order::create([
            'client_id'   => $client->id,
            'token_view'  => 'tok-pending',
            'files_count' => 1,
            'status'      => OrderStatus::Pending,
            'claimed_by'  => null,
            'due_at'      => now()->addHour(),
            'source'      => 'account',
        ]);

        $this->expectException(WorkflowException::class);

        $this->service()->requeueFailed($order, $admin);
    }

    #[Test]
    public function vendor_cannot_requeue_via_policy(): void
    {
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $order  = $this->makeFailedOrder($vendor, $client);

        $policy = new OrderPolicy();

        $this->assertFalse($policy->requeue($vendor, $order));
    }

    #[Test]
    public function client_cannot_requeue_via_policy(): void
    {
        $admin      = $this->makeAdmin();
        $vendor     = $this->makeVendor();
        $client     = $this->makeClient();
        $clientUser = $this->makeClientUser($client);
        $order      = $this->makeFailedOrder($vendor, $client);

        $policy = new OrderPolicy();

        $this->assertFalse($policy->requeue($clientUser, $order));
    }

    #[Test]
    public function admin_policy_allows_requeue_on_failed_order(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $order  = $this->makeFailedOrder($vendor, $client);

        $policy = new OrderPolicy();

        $this->assertTrue($policy->requeue($admin, $order));
    }

    #[Test]
    public function policy_blocks_requeue_on_non_failed_order(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();

        $order = Order::create([
            'client_id'   => $client->id,
            'token_view'  => 'tok-pending-policy',
            'files_count' => 1,
            'status'      => OrderStatus::Pending,
            'claimed_by'  => null,
            'due_at'      => now()->addHour(),
            'source'      => 'account',
        ]);

        $policy = new OrderPolicy();

        $this->assertFalse($policy->requeue($admin, $order));
    }
}

<?php

namespace Tests\Feature\Finance;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\User;
use App\Services\CreateClientOrderService;
use App\Services\Finance\ClientCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 4B — Refund and Cancellation Cleanup
 *
 * Rules verified:
 *  - Refund approval restores credits for a Phase-4 order (has order_debit tx)
 *  - Refund approval creates a refund_credit ledger row
 *  - Refund approval sets orders.credits_refunded_at
 *  - Refund approval does NOT touch slots
 *  - Refund approval does NOT touch slots_consumed
 *  - Refund approval is idempotent (no double refund)
 *  - Legacy order (no order_debit) approval does not restore credits
 *  - Legacy order approval does not throw / fail
 *  - refundOrderIfDebited() helper: returns true/false correctly
 *  - CleanupLinkOrdersCommand refunds credits for Phase-4 link orders
 *  - CleanupLinkOrdersCommand does not refund for pre-Phase-4 link orders
 */
class Phase4BRefundTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private int $counter = 0;

    private function makeAdmin(array $attrs = []): User
    {
        $this->counter++;
        return User::create(array_merge([
            'name'          => 'Admin',
            'role'          => 'admin',
            'status'        => 'active',
            'portal_number' => 100000 + $this->counter,
            'email'         => "admin{$this->counter}@test.com",
            'password'      => bcrypt('password'),
        ], $attrs));
    }

    private function makeClient(array $attrs = []): Client
    {
        $this->counter++;
        return Client::create(array_merge([
            'name'           => 'Test Client',
            'slots'          => 10,
            'slots_consumed' => 3,   // frozen value from pre-Phase-4 era
            'credit_balance' => 10,
            'price_per_file' => 50.00,
            'status'         => 'active',
        ], $attrs));
    }

    private function makeClientUser(Client $client, array $attrs = []): User
    {
        $this->counter++;
        return User::create(array_merge([
            'name'          => 'Client User',
            'role'          => 'client',
            'status'        => 'active',
            'portal_number' => 300000 + $this->counter,
            'email'         => "client{$this->counter}@test.com",
            'password'      => bcrypt('password'),
            'client_id'     => $client->id,
        ], $attrs));
    }

    /** Create a Phase-4 order (has order_debit tx) via the real service. */
    private function createPhase4Order(Client $client): Order
    {
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);

        return app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('doc.pdf', 100),
            UploadedFile::fake()->create('doc2.pdf', 100),
        ], 'account');
    }

    /** Create a legacy (pre-Phase-4) order — no order_debit tx. */
    private function createLegacyOrder(Client $client): Order
    {
        return Order::create([
            'client_id'   => $client->id,
            'token_view'  => 'legacy' . $this->counter++,
            'files_count' => 3,
            'status'      => OrderStatus::Claimed,
            'due_at'      => now(),
            'source'      => 'account',
        ]);
    }

    /** Create a pending refund request for an order. */
    private function makeRefundRequest(Order $order, Client $client, User $user): RefundRequest
    {
        return RefundRequest::create([
            'order_id'  => $order->id,
            'client_id' => $client->id,
            'user_id'   => $user->id,
            'reason'    => 'Test refund reason',
            'status'    => 'pending',
        ]);
    }

    // -----------------------------------------------------------------------
    // refundOrderIfDebited() unit tests
    // -----------------------------------------------------------------------

    #[Test]
    public function test_refund_order_if_debited_returns_false_for_legacy_order(): void
    {
        $client = $this->makeClient(['credit_balance' => 5]);
        $order  = $this->createLegacyOrder($client);

        $service = app(ClientCreditService::class);

        \Illuminate\Support\Facades\DB::transaction(function () use ($service, $client, $order, &$result) {
            $locked = Client::where('id', $client->id)->lockForUpdate()->first();
            $result = $service->refundOrderIfDebited($locked, $order);
        });

        $this->assertFalse($result);
        $this->assertEquals(5, $client->fresh()->credit_balance);
    }

    #[Test]
    public function test_refund_order_if_debited_returns_true_for_phase4_order(): void
    {
        $client = $this->makeClient(['credit_balance' => 5]);
        $order  = $this->createPhase4Order($client);

        $service = app(ClientCreditService::class);

        \Illuminate\Support\Facades\DB::transaction(function () use ($service, $client, $order, &$result) {
            $locked = Client::where('id', $client->id)->lockForUpdate()->first();
            $result = $service->refundOrderIfDebited($locked, $order);
        });

        $this->assertTrue($result);
    }

    #[Test]
    public function test_refund_order_if_debited_is_idempotent(): void
    {
        $client = $this->makeClient(['credit_balance' => 5]);
        $order  = $this->createPhase4Order($client);

        $service = app(ClientCreditService::class);

        // First call — should refund
        \Illuminate\Support\Facades\DB::transaction(function () use ($service, $client, $order, &$r1) {
            $locked = Client::where('id', $client->id)->lockForUpdate()->first();
            $r1 = $service->refundOrderIfDebited($locked, $order);
        });

        // Second call — already refunded
        \Illuminate\Support\Facades\DB::transaction(function () use ($service, $client, $order, &$r2) {
            $locked = Client::where('id', $client->id)->lockForUpdate()->first();
            $order->refresh();
            $r2 = $service->refundOrderIfDebited($locked, $order);
        });

        $this->assertTrue($r1);
        $this->assertFalse($r2);

        // Only one refund_credit tx should exist
        $this->assertEquals(1, ClientCreditTransaction::where('client_id', $client->id)
            ->where('type', ClientCreditTransaction::TYPE_REFUND_CREDIT)
            ->count());
    }

    // -----------------------------------------------------------------------
    // HTTP — RefundController::approve() — Phase-4 order
    // -----------------------------------------------------------------------

    #[Test]
    public function test_refund_approval_restores_credits_for_phase4_order(): void
    {
        $client  = $this->makeClient(['credit_balance' => 5]);
        $user    = $this->makeClientUser($client);
        $admin   = $this->makeAdmin();
        $order   = $this->createPhase4Order($client);

        // Balance after upload = 5 - 2 = 3
        $this->assertEquals(3, $client->fresh()->credit_balance);

        $refund = $this->makeRefundRequest($order, $client, $user);

        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $refund))
            ->assertRedirect()
            ->assertSessionHas('success');

        // Credits restored: 3 + 2 = 5
        $this->assertEquals(5, $client->fresh()->credit_balance);
    }

    #[Test]
    public function test_refund_approval_creates_refund_credit_ledger_row(): void
    {
        $client = $this->makeClient(['credit_balance' => 5]);
        $user   = $this->makeClientUser($client);
        $admin  = $this->makeAdmin();
        $order  = $this->createPhase4Order($client);
        $refund = $this->makeRefundRequest($order, $client, $user);

        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $refund));

        $tx = ClientCreditTransaction::where('order_id', $order->id)
            ->where('type', ClientCreditTransaction::TYPE_REFUND_CREDIT)
            ->first();

        $this->assertNotNull($tx);
        $this->assertEquals(2, $tx->credits_delta);
        $this->assertEquals($client->id, $tx->client_id);
    }

    #[Test]
    public function test_refund_approval_sets_credits_refunded_at(): void
    {
        $client = $this->makeClient(['credit_balance' => 5]);
        $user   = $this->makeClientUser($client);
        $admin  = $this->makeAdmin();
        $order  = $this->createPhase4Order($client);
        $refund = $this->makeRefundRequest($order, $client, $user);

        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $refund));

        $this->assertNotNull($order->fresh()->credits_refunded_at);
    }

    #[Test]
    public function test_refund_approval_does_not_touch_slots(): void
    {
        $client = $this->makeClient(['credit_balance' => 5, 'slots' => 10]);
        $user   = $this->makeClientUser($client);
        $admin  = $this->makeAdmin();
        $order  = $this->createPhase4Order($client);
        $refund = $this->makeRefundRequest($order, $client, $user);

        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $refund));

        $this->assertEquals(10, $client->fresh()->slots);
    }

    #[Test]
    public function test_refund_approval_does_not_touch_slots_consumed(): void
    {
        $client = $this->makeClient(['credit_balance' => 5, 'slots_consumed' => 3]);
        $user   = $this->makeClientUser($client);
        $admin  = $this->makeAdmin();
        $order  = $this->createPhase4Order($client);
        $refund = $this->makeRefundRequest($order, $client, $user);

        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $refund));

        // slots_consumed must be frozen — never written by Phase 4+
        $this->assertEquals(3, $client->fresh()->slots_consumed);
    }

    #[Test]
    public function test_refund_approval_does_not_refund_twice(): void
    {
        $client = $this->makeClient(['credit_balance' => 5]);
        $user   = $this->makeClientUser($client);
        $admin  = $this->makeAdmin();
        $order  = $this->createPhase4Order($client);
        $refund = $this->makeRefundRequest($order, $client, $user);

        // First approval
        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $refund));

        $balanceAfterFirstApproval = $client->fresh()->credit_balance;

        // Try to approve again (status is now 'approved').
        // The policy requires status='pending' → returns 403, not a redirect.
        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $refund))
            ->assertStatus(403);

        // Balance must not change
        $this->assertEquals($balanceAfterFirstApproval, $client->fresh()->credit_balance);

        // Only one refund_credit tx
        $this->assertEquals(1, ClientCreditTransaction::where('order_id', $order->id)
            ->where('type', ClientCreditTransaction::TYPE_REFUND_CREDIT)
            ->count());
    }

    // -----------------------------------------------------------------------
    // HTTP — RefundController::approve() — legacy (pre-Phase-4) order
    // -----------------------------------------------------------------------

    #[Test]
    public function test_refund_approval_for_legacy_order_does_not_restore_credits(): void
    {
        $client = $this->makeClient(['credit_balance' => 5]);
        $user   = $this->makeClientUser($client);
        $admin  = $this->makeAdmin();
        $order  = $this->createLegacyOrder($client);
        $refund = $this->makeRefundRequest($order, $client, $user);

        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $refund))
            ->assertRedirect()
            ->assertSessionHas('success');

        // No order_debit tx → no credit refund
        $this->assertEquals(5, $client->fresh()->credit_balance);
    }

    #[Test]
    public function test_refund_approval_for_legacy_order_does_not_fail(): void
    {
        $client = $this->makeClient(['credit_balance' => 5, 'slots_consumed' => 0]);
        $user   = $this->makeClientUser($client);
        $admin  = $this->makeAdmin();
        $order  = $this->createLegacyOrder($client);
        $refund = $this->makeRefundRequest($order, $client, $user);

        $response = $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $refund));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Refund status set to approved
        $this->assertEquals('approved', $refund->fresh()->status);
        $this->assertNotNull($refund->fresh()->resolved_at);
    }

    #[Test]
    public function test_refund_approval_for_legacy_order_shows_no_credit_message(): void
    {
        $client = $this->makeClient(['credit_balance' => 5]);
        $user   = $this->makeClientUser($client);
        $admin  = $this->makeAdmin();
        $order  = $this->createLegacyOrder($client);
        $refund = $this->makeRefundRequest($order, $client, $user);

        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $refund))
            ->assertSessionHas('success', fn ($msg) =>
                str_contains($msg, 'No credit refund was created')
            );
    }

    #[Test]
    public function test_refund_approval_for_phase4_order_shows_credits_restored_message(): void
    {
        $client = $this->makeClient(['credit_balance' => 5]);
        $user   = $this->makeClientUser($client);
        $admin  = $this->makeAdmin();
        $order  = $this->createPhase4Order($client);
        $refund = $this->makeRefundRequest($order, $client, $user);

        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $refund))
            ->assertSessionHas('success', fn ($msg) =>
                str_contains($msg, 'Credits have been restored')
            );
    }

    // -----------------------------------------------------------------------
    // Suspended client reactivation on refund approval
    // -----------------------------------------------------------------------

    #[Test]
    public function test_suspended_client_reactivated_when_refund_restores_balance(): void
    {
        // Start with exactly 2 credits, upload 2-file order → balance = 0 → suspended
        $client = $this->makeClient(['credit_balance' => 2]);
        $user   = $this->makeClientUser($client);
        $admin  = $this->makeAdmin();
        $order  = $this->createPhase4Order($client);

        $this->assertEquals('suspended', $client->fresh()->status);

        $refund = $this->makeRefundRequest($order, $client, $user);

        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', $refund));

        // Credits restored → status back to active
        $this->assertEquals('active', $client->fresh()->status);
    }

    // -----------------------------------------------------------------------
    // CleanupLinkOrdersCommand
    // -----------------------------------------------------------------------

    #[Test]
    public function test_cleanup_command_refunds_credits_for_phase4_link_order(): void
    {
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);

        $client = $this->makeClient(['credit_balance' => 5]);

        // Create via service (source='link' so the command picks it up)
        $order = app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('doc.pdf', 100),
        ], 'link');

        // Balance is now 4
        $this->assertEquals(4, $client->fresh()->credit_balance);

        // Make old enough to be cleaned up — bypass fillable via query builder
        \Illuminate\Support\Facades\DB::table('orders')
            ->where('id', $order->id)
            ->update(['created_at' => now()->subHours(25)]);

        $this->artisan('app:cleanup-link-orders', ['--hours' => 24])
            ->assertExitCode(0);

        // Credit should be returned: 4 + 1 = 5
        $this->assertEquals(5, $client->fresh()->credit_balance);
    }

    #[Test]
    public function test_cleanup_command_does_not_refund_for_legacy_link_order(): void
    {
        $client = $this->makeClient(['credit_balance' => 5]);

        // Legacy order: no order_debit tx, source='link'
        $order = Order::create([
            'client_id'   => $client->id,
            'token_view'  => 'legacylink' . $this->counter++,
            'files_count' => 2,
            'status'      => OrderStatus::Pending,
            'due_at'      => now(),
            'source'      => 'link',
            'created_at'  => now()->subHours(25),
            'updated_at'  => now()->subHours(25),
        ]);

        $this->artisan('app:cleanup-link-orders', ['--hours' => 24])
            ->assertExitCode(0);

        // No debit tx → no refund → balance unchanged
        $this->assertEquals(5, $client->fresh()->credit_balance);
    }
}

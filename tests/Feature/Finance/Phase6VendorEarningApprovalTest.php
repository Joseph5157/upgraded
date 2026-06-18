<?php

namespace Tests\Feature\Finance;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderReport;
use App\Models\User;
use App\Models\VendorEarningTransaction;
use App\Services\Finance\VendorEarningService;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 6 — Admin Approval / Rejection of Vendor Pending Earnings
 *
 * Rules verified:
 *  - approveEarning() moves amount from pending_earning_balance → approved_payable_balance
 *  - approveEarning() creates an approve_earning tx with correct balances
 *  - approveEarning() sets orders.vendor_approved_at, gross_profit, financial_locked_at
 *  - approveEarning() returns null when no pending earning exists (no-op)
 *  - approveEarning() is idempotent (second call returns null, no duplicate tx)
 *  - reverseEarning() decreases pending_earning_balance only
 *  - reverseEarning() does NOT touch approved_payable_balance
 *  - reverseEarning() creates a reversal tx with a negative amount_delta
 *  - reverseEarning() sets orders.vendor_rejected_at
 *  - reverseEarning() returns null when no pending earning exists
 *  - reverseEarning() is idempotent (second call returns null)
 *  - reverseEarning() throws LogicException if earning was already approved
 *  - Both methods are safe when nested inside an existing DB::transaction()
 *  - All Phase 5 tests still pass (non-regression)
 */
class Phase6VendorEarningApprovalTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private int $counter = 0;

    private function makeVendor(array $attrs = []): User
    {
        $this->counter++;
        return User::create(array_merge([
            'name'                     => 'Vendor',
            'role'                     => 'vendor',
            'status'                   => 'active',
            'portal_number'            => 200000 + $this->counter,
            'email'                    => "vendor{$this->counter}@test.com",
            'password'                 => bcrypt('password'),
            'payout_rate'              => 20.00,
            'pending_earning_balance'  => 0.00,
            'approved_payable_balance' => 0.00,
        ], $attrs));
    }

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
            'slots_consumed' => 0,
            'credit_balance' => 10,
            'price_per_file' => 50.00,
            'status'         => 'active',
        ], $attrs));
    }

    /**
     * Create a Processing order with an OrderReport so deliver() can succeed.
     */
    private function makeProcessingOrder(User $vendor, Client $client, array $orderAttrs = []): Order
    {
        $this->counter++;
        $order = Order::create(array_merge([
            'client_id'            => $client->id,
            'token_view'           => 'tok' . $this->counter,
            'files_count'          => 2,
            'credits_consumed'     => 2,
            'client_rate_per_file' => 50.00,
            'client_amount'        => 100.00,
            'status'               => OrderStatus::Processing,
            'claimed_by'           => $vendor->id,
            'due_at'               => now()->addHour(),
            'source'               => 'account',
        ], $orderAttrs));

        OrderReport::create([
            'order_id'         => $order->id,
            'ai_skip_reason'   => 'Skipped for test',
            'plag_report_path' => 'reports/' . $order->id . '/plag/test.pdf',
            'plag_report_disk' => 'r2',
        ]);

        return $order;
    }

    /**
     * Deliver an order (creates pending earning via OrderWorkflowService).
     */
    private function deliverOrder(Order $order, User $actor): void
    {
        app(OrderWorkflowService::class)->deliver($order, $actor);
    }

    /**
     * Deliver an order and return the pending earning tx.
     */
    private function deliverAndGetPendingTx(Order $order, User $vendor): VendorEarningTransaction
    {
        $this->deliverOrder($order, $vendor);

        return VendorEarningTransaction::where('order_id', $order->id)
            ->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)
            ->firstOrFail();
    }

    // -----------------------------------------------------------------------
    // approveEarning — no-op paths
    // -----------------------------------------------------------------------

    #[Test]
    public function test_approve_returns_null_when_no_pending_earning_exists(): void
    {
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client);

        // Deliver creates the pending earning; approve it first, then check a fresh call.
        $this->deliverOrder($order, $vendor);

        $service = app(VendorEarningService::class);

        // Approve once — succeeds
        $tx1 = DB::transaction(fn () => $service->approveEarning($order->fresh()));
        $this->assertNotNull($tx1);

        // Approve again — idempotent, returns null
        $tx2 = DB::transaction(fn () => $service->approveEarning($order->fresh()));
        $this->assertNull($tx2);

        // Only one approve_earning tx exists
        $this->assertEquals(1, VendorEarningTransaction::where('order_id', $order->id)
            ->where('type', VendorEarningTransaction::TYPE_APPROVE_EARNING)
            ->count());
    }

    #[Test]
    public function test_approve_returns_null_for_order_with_no_pending_earning(): void
    {
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $this->counter++;
        $order = Order::create([
            'client_id'  => $client->id,
            'token_view' => 'nopending' . $this->counter,
            'files_count' => 1,
            'status'     => OrderStatus::Delivered,
            'claimed_by' => $vendor->id,
            'due_at'     => now(),
            'source'     => 'account',
        ]);

        $service = app(VendorEarningService::class);
        $result  = DB::transaction(fn () => $service->approveEarning($order));

        $this->assertNull($result);
        $this->assertEquals(0, VendorEarningTransaction::where('order_id', $order->id)->count());
    }

    // -----------------------------------------------------------------------
    // approveEarning — core behaviour
    // -----------------------------------------------------------------------

    #[Test]
    public function test_approve_creates_approve_earning_tx(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $this->deliverOrder($order, $vendor);

        $admin   = $this->makeAdmin();
        $service = app(VendorEarningService::class);
        $tx      = DB::transaction(fn () => $service->approveEarning($order->fresh(), $admin, 'Looks good.'));

        $this->assertNotNull($tx);
        $this->assertEquals(VendorEarningTransaction::TYPE_APPROVE_EARNING, $tx->type);
        $this->assertEquals(VendorEarningTransaction::STATUS_POSTED, $tx->status);
        $this->assertEquals($vendor->id, $tx->vendor_id);
        $this->assertEquals($order->id, $tx->order_id);
        $this->assertEquals('Looks good.', $tx->notes);
        $this->assertEquals($admin->id, $tx->created_by);
    }

    #[Test]
    public function test_approve_moves_amount_from_pending_to_approved_balance(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 25.00, 'pending_earning_balance' => 0.00, 'approved_payable_balance' => 50.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $this->deliverOrder($order, $vendor); // adds 2 × 25 = 50 to pending

        $service = app(VendorEarningService::class);
        DB::transaction(fn () => $service->approveEarning($order->fresh()));

        $vendor->refresh();
        // pending: 50 - 50 = 0
        $this->assertEquals('0.00', $vendor->pending_earning_balance);
        // approved: 50 + 50 = 100
        $this->assertEquals('100.00', $vendor->approved_payable_balance);
    }

    #[Test]
    public function test_approve_tx_snapshots_pending_and_approved_balances(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 30.00, 'pending_earning_balance' => 0.00, 'approved_payable_balance' => 10.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $this->deliverOrder($order, $vendor); // adds 60 to pending

        $service = app(VendorEarningService::class);
        $tx      = DB::transaction(fn () => $service->approveEarning($order->fresh()));

        // pending_balance_after = 60 - 60 = 0
        $this->assertEquals('0.00', $tx->pending_balance_after);
        // approved_balance_after = 10 + 60 = 70
        $this->assertEquals('70.00', $tx->approved_balance_after);
    }

    #[Test]
    public function test_approve_sets_vendor_approved_at_on_order(): void
    {
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client);
        $this->deliverOrder($order, $vendor);

        $service = app(VendorEarningService::class);
        DB::transaction(fn () => $service->approveEarning($order->fresh()));

        $this->assertNotNull($order->fresh()->vendor_approved_at);
    }

    #[Test]
    public function test_approve_sets_financial_locked_at_on_order(): void
    {
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client);
        $this->deliverOrder($order, $vendor);

        $service = app(VendorEarningService::class);
        DB::transaction(fn () => $service->approveEarning($order->fresh()));

        $this->assertNotNull($order->fresh()->financial_locked_at);
    }

    #[Test]
    public function test_approve_calculates_gross_profit_correctly(): void
    {
        // client_amount = 2 × 50 = 100; vendor_amount = 2 × 20 = 40; gross_profit = 60
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient(['price_per_file' => 50.00]);
        $order  = $this->makeProcessingOrder($vendor, $client, [
            'files_count'   => 2,
            'client_amount' => 100.00,
        ]);
        $this->deliverOrder($order, $vendor);

        $service = app(VendorEarningService::class);
        DB::transaction(fn () => $service->approveEarning($order->fresh()));

        $this->assertEquals('60.00', $order->fresh()->gross_profit);
    }

    // -----------------------------------------------------------------------
    // reverseEarning — no-op paths
    // -----------------------------------------------------------------------

    #[Test]
    public function test_reverse_returns_null_when_no_pending_earning_exists(): void
    {
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $this->counter++;
        $order = Order::create([
            'client_id'  => $client->id,
            'token_view' => 'noearning' . $this->counter,
            'files_count' => 1,
            'status'     => OrderStatus::Delivered,
            'claimed_by' => $vendor->id,
            'due_at'     => now(),
            'source'     => 'account',
        ]);

        $service = app(VendorEarningService::class);
        $result  = DB::transaction(fn () => $service->reverseEarning($order));

        $this->assertNull($result);
        $this->assertEquals(0, VendorEarningTransaction::where('order_id', $order->id)->count());
    }

    #[Test]
    public function test_reverse_is_idempotent(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $this->deliverOrder($order, $vendor);

        $service = app(VendorEarningService::class);

        $tx1 = DB::transaction(fn () => $service->reverseEarning($order->fresh()));
        $tx2 = DB::transaction(fn () => $service->reverseEarning($order->fresh()));

        $this->assertNotNull($tx1);
        $this->assertNull($tx2); // second call skipped

        $this->assertEquals(1, VendorEarningTransaction::where('order_id', $order->id)
            ->where('type', VendorEarningTransaction::TYPE_REVERSAL)
            ->count());
    }

    // -----------------------------------------------------------------------
    // reverseEarning — core behaviour
    // -----------------------------------------------------------------------

    #[Test]
    public function test_reverse_creates_reversal_tx_with_negative_amount(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $this->deliverOrder($order, $vendor); // +40 pending

        $admin   = $this->makeAdmin();
        $service = app(VendorEarningService::class);
        $tx      = DB::transaction(fn () => $service->reverseEarning($order->fresh(), $admin, 'Quality issues.'));

        $this->assertNotNull($tx);
        $this->assertEquals(VendorEarningTransaction::TYPE_REVERSAL, $tx->type);
        $this->assertEquals(VendorEarningTransaction::STATUS_POSTED, $tx->status);
        $this->assertEquals('-40.00', $tx->amount_delta);
        $this->assertEquals($vendor->id, $tx->vendor_id);
        $this->assertEquals('Quality issues.', $tx->notes);
        $this->assertEquals($admin->id, $tx->created_by);
    }

    #[Test]
    public function test_reverse_decreases_pending_earning_balance(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00, 'pending_earning_balance' => 5.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $this->deliverOrder($order, $vendor); // pending: 5 + 40 = 45

        $service = app(VendorEarningService::class);
        DB::transaction(fn () => $service->reverseEarning($order->fresh()));

        // pending: 45 - 40 = 5 (restored to original)
        $this->assertEquals('5.00', $vendor->fresh()->pending_earning_balance);
    }

    #[Test]
    public function test_reverse_does_not_touch_approved_payable_balance(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00, 'approved_payable_balance' => 200.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $this->deliverOrder($order, $vendor);

        $service = app(VendorEarningService::class);
        DB::transaction(fn () => $service->reverseEarning($order->fresh()));

        $this->assertEquals('200.00', $vendor->fresh()->approved_payable_balance);
    }

    #[Test]
    public function test_reverse_sets_vendor_rejected_at_on_order(): void
    {
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client);
        $this->deliverOrder($order, $vendor);

        $service = app(VendorEarningService::class);
        DB::transaction(fn () => $service->reverseEarning($order->fresh()));

        $this->assertNotNull($order->fresh()->vendor_rejected_at);
    }

    #[Test]
    public function test_reverse_tx_snapshots_pending_balance_after(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 15.00, 'pending_earning_balance' => 10.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $this->deliverOrder($order, $vendor); // pending: 10 + 30 = 40

        $service = app(VendorEarningService::class);
        $tx      = DB::transaction(fn () => $service->reverseEarning($order->fresh()));

        // pending_balance_after = 40 - 30 = 10
        $this->assertEquals('10.00', $tx->pending_balance_after);
        // approved_balance_after is unchanged (0.00)
        $this->assertEquals('0.00', $tx->approved_balance_after);
    }

    // -----------------------------------------------------------------------
    // reverseEarning — block post-approval reversal
    // -----------------------------------------------------------------------

    #[Test]
    public function test_reverse_throws_logic_exception_if_already_approved(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $this->deliverOrder($order, $vendor);

        $service = app(VendorEarningService::class);

        // Approve first
        DB::transaction(fn () => $service->approveEarning($order->fresh()));

        // Now try to reject — must throw
        $this->expectException(\LogicException::class);
        DB::transaction(fn () => $service->reverseEarning($order->fresh()));
    }

    // -----------------------------------------------------------------------
    // approveEarning — vendor_approved_at not set when already approved (idempotent guard)
    // -----------------------------------------------------------------------

    #[Test]
    public function test_approve_vendor_approved_at_is_not_duplicated_on_repeat_call(): void
    {
        $vendor = $this->makeVendor();
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client);
        $this->deliverOrder($order, $vendor);

        $service = app(VendorEarningService::class);
        DB::transaction(fn () => $service->approveEarning($order->fresh()));

        $firstApprovedAt = $order->fresh()->vendor_approved_at;
        $this->assertNotNull($firstApprovedAt);

        // Second call — idempotent, returns null, timestamp not overwritten
        DB::transaction(fn () => $service->approveEarning($order->fresh()));
        $this->assertEquals($firstApprovedAt, $order->fresh()->vendor_approved_at);
    }

    // -----------------------------------------------------------------------
    // Admin HTTP routes
    // -----------------------------------------------------------------------

    #[Test]
    public function test_admin_approve_route_approves_vendor_earning(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $this->deliverOrder($order, $vendor);

        $this->actingAs($admin)
            ->post(route('admin.finance.vendor-earnings.approve', $order))
            ->assertSessionHas('success');

        $this->assertEquals(1, VendorEarningTransaction::where('order_id', $order->id)
            ->where('type', VendorEarningTransaction::TYPE_APPROVE_EARNING)
            ->count());

        $this->assertNotNull($order->fresh()->vendor_approved_at);
    }

    #[Test]
    public function test_admin_reject_route_reverses_vendor_earning(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $this->deliverOrder($order, $vendor);

        $this->actingAs($admin)
            ->post(route('admin.finance.vendor-earnings.reject', $order), ['notes' => 'Test rejection'])
            ->assertSessionHas('success');

        $this->assertEquals(1, VendorEarningTransaction::where('order_id', $order->id)
            ->where('type', VendorEarningTransaction::TYPE_REVERSAL)
            ->count());

        $this->assertNotNull($order->fresh()->vendor_rejected_at);
    }

    #[Test]
    public function test_admin_reject_route_returns_error_if_already_approved(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $this->deliverOrder($order, $vendor);

        // Approve first
        $this->actingAs($admin)
            ->post(route('admin.finance.vendor-earnings.approve', $order));

        // Reject after approve — must return error, not throw
        $this->actingAs($admin)
            ->post(route('admin.finance.vendor-earnings.reject', $order))
            ->assertSessionHas('error');
    }

    #[Test]
    public function test_admin_index_route_renders_pending_earnings_view(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $this->deliverOrder($order, $vendor);

        $this->actingAs($admin)
            ->get(route('admin.finance.vendor-earnings.index'))
            ->assertOk()
            ->assertSee($vendor->name);
    }

    #[Test]
    public function test_non_admin_cannot_access_vendor_earnings_routes(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client);
        $this->deliverOrder($order, $vendor);

        // role:admin middleware redirects non-admin users away (not a 403)
        $this->actingAs($vendor)
            ->get(route('admin.finance.vendor-earnings.index'))
            ->assertRedirect();

        // POST actions also blocked
        $this->actingAs($vendor)
            ->post(route('admin.finance.vendor-earnings.approve', $order))
            ->assertRedirect();
    }
}

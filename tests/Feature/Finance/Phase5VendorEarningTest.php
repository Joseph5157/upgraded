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
 * Phase 5 — Vendor Pending Earning on Report Upload
 *
 * Rules verified:
 *  - Vendor report delivery creates a pending_order_earning transaction
 *  - users.pending_earning_balance increases by vendor_amount
 *  - users.approved_payable_balance is NOT touched
 *  - orders.vendor_rate_per_file and vendor_amount snapshots are stored
 *  - Vendor-specific payout rate is used (not a global rate)
 *  - Two vendors with different rates produce different earning amounts
 *  - createPendingForOrder is idempotent (no duplicate earning for same order)
 *  - No vendor assigned → returns null, no earning created
 *  - slots and slots_consumed are never touched
 *  - All Phase 4 credit ledger tests still pass
 */
class Phase5VendorEarningTest extends TestCase
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
            'name'                    => 'Vendor',
            'role'                    => 'vendor',
            'status'                  => 'active',
            'portal_number'           => 200000 + $this->counter,
            'email'                   => "vendor{$this->counter}@test.com",
            'password'                => bcrypt('password'),
            'payout_rate'             => 20.00,
            'pending_earning_balance' => 0.00,
            'approved_payable_balance'=> 0.00,
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
     * Create an order in Processing state claimed by the given vendor,
     * with an OrderReport (complete) so deliver() can succeed.
     */
    private function makeProcessingOrder(User $vendor, Client $client, array $orderAttrs = []): Order
    {
        $this->counter++;
        $order = Order::create(array_merge([
            'client_id'        => $client->id,
            'token_view'       => 'tok' . $this->counter,
            'files_count'      => 2,
            'credits_consumed' => 2,
            'client_rate_per_file' => 50.00,
            'client_amount'    => 100.00,
            'status'           => OrderStatus::Processing,
            'claimed_by'       => $vendor->id,
            'due_at'           => now()->addHour(),
            'source'           => 'account',
        ], $orderAttrs));

        // Create a complete OrderReport so deliver() can proceed
        OrderReport::create([
            'order_id'          => $order->id,
            'ai_skip_reason'    => 'Skipped for test',
            'plag_report_path'  => 'reports/' . $order->id . '/plag/test.pdf',
            'plag_report_disk'  => 'r2',
        ]);

        return $order;
    }

    private function deliverOrder(Order $order, User $actor): void
    {
        app(OrderWorkflowService::class)->deliver($order, $actor);
    }

    // -----------------------------------------------------------------------
    // createPendingForOrder — unit tests
    // -----------------------------------------------------------------------

    #[Test]
    public function test_create_pending_for_order_returns_null_when_no_vendor(): void
    {
        $client = $this->makeClient();
        $this->counter++;
        $order = Order::create([
            'client_id'   => $client->id,
            'token_view'  => 'novendor' . $this->counter,
            'files_count' => 2,
            'status'      => OrderStatus::Delivered,
            'claimed_by'  => null,
            'due_at'      => now(),
            'source'      => 'account',
        ]);

        $service = app(VendorEarningService::class);
        $result = DB::transaction(fn () => $service->createPendingForOrder($order));

        $this->assertNull($result);
        $this->assertEquals(0, VendorEarningTransaction::where('order_id', $order->id)->count());
    }

    #[Test]
    public function test_create_pending_for_order_is_idempotent(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client);

        $service = app(VendorEarningService::class);

        // Call twice — DB::transaction() returns the closure's return value
        $tx1 = DB::transaction(fn () => $service->createPendingForOrder($order));
        $tx2 = DB::transaction(fn () => $service->createPendingForOrder($order->fresh()));

        $this->assertNotNull($tx1);
        $this->assertNull($tx2);  // second call skipped

        $this->assertEquals(1, VendorEarningTransaction::where('order_id', $order->id)
            ->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)
            ->count());
    }

    // -----------------------------------------------------------------------
    // Delivery integration — pending earning created on deliver()
    // -----------------------------------------------------------------------

    #[Test]
    public function test_deliver_creates_pending_earning_transaction(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);

        $this->deliverOrder($order, $vendor);

        $tx = VendorEarningTransaction::where('order_id', $order->id)
            ->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)
            ->first();

        $this->assertNotNull($tx);
        $this->assertEquals(VendorEarningTransaction::STATUS_POSTED, $tx->status);
        $this->assertEquals($vendor->id, $tx->vendor_id);
    }

    #[Test]
    public function test_deliver_increases_pending_earning_balance(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00, 'pending_earning_balance' => 10.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);

        $this->deliverOrder($order, $vendor);

        // 2 files × ₹20 = ₹40 added. Starting balance ₹10 → ₹50.
        $this->assertEquals('50.00', $vendor->fresh()->pending_earning_balance);
    }

    #[Test]
    public function test_deliver_does_not_touch_approved_payable_balance(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00, 'approved_payable_balance' => 100.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);

        $this->deliverOrder($order, $vendor);

        // approved_payable_balance must be frozen
        $this->assertEquals('100.00', $vendor->fresh()->approved_payable_balance);
    }

    #[Test]
    public function test_deliver_stores_vendor_rate_per_file_on_order(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 25.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 3]);

        $this->deliverOrder($order, $vendor);

        $this->assertEquals('25.00', $order->fresh()->vendor_rate_per_file);
    }

    #[Test]
    public function test_deliver_stores_vendor_amount_on_order(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 30.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);

        $this->deliverOrder($order, $vendor);

        // 2 × ₹30 = ₹60
        $this->assertEquals('60.00', $order->fresh()->vendor_amount);
    }

    #[Test]
    public function test_pending_earning_amount_matches_files_count_times_rate(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 15.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 4]);

        $this->deliverOrder($order, $vendor);

        $tx = VendorEarningTransaction::where('order_id', $order->id)
            ->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)
            ->first();

        // 4 × ₹15 = ₹60
        $this->assertEquals('60.00', $tx->amount_delta);
        $this->assertEquals(4, $tx->files_count);
        $this->assertEquals('15.00', $tx->rate_per_file);
    }

    #[Test]
    public function test_different_vendors_get_different_earning_amounts(): void
    {
        $client  = $this->makeClient();
        $vendor1 = $this->makeVendor(['payout_rate' => 20.00]);
        $vendor2 = $this->makeVendor(['payout_rate' => 35.00]);

        $order1 = $this->makeProcessingOrder($vendor1, $client, ['files_count' => 2]);
        $order2 = $this->makeProcessingOrder($vendor2, $client, ['files_count' => 2]);

        $this->deliverOrder($order1, $vendor1);
        $this->deliverOrder($order2, $vendor2);

        $tx1 = VendorEarningTransaction::where('order_id', $order1->id)->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)->first();
        $tx2 = VendorEarningTransaction::where('order_id', $order2->id)->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)->first();

        $this->assertEquals('40.00', $tx1->amount_delta);  // 2 × ₹20
        $this->assertEquals('70.00', $tx2->amount_delta);  // 2 × ₹35
    }

    #[Test]
    public function test_pending_balance_after_reflects_running_total(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00, 'pending_earning_balance' => 0.00]);
        $client = $this->makeClient(['credit_balance' => 20]);

        $order1 = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);
        $order2 = $this->makeProcessingOrder($vendor, $client, ['files_count' => 3]);

        $this->deliverOrder($order1, $vendor);
        $this->deliverOrder($order2, $vendor);

        $tx1 = VendorEarningTransaction::where('order_id', $order1->id)->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)->first();
        $tx2 = VendorEarningTransaction::where('order_id', $order2->id)->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)->first();

        // After order1: 0 + 40 = 40
        $this->assertEquals('40.00', $tx1->pending_balance_after);
        // After order2: 40 + 60 = 100
        $this->assertEquals('100.00', $tx2->pending_balance_after);
        // Final vendor balance
        $this->assertEquals('100.00', $vendor->fresh()->pending_earning_balance);
    }

    #[Test]
    public function test_deliver_does_not_touch_slots_consumed(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient(['slots_consumed' => 5]);
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);

        $this->deliverOrder($order, $vendor);

        // slots_consumed must remain frozen
        $this->assertEquals(5, $client->fresh()->slots_consumed);
    }

    #[Test]
    public function test_deliver_does_not_touch_slots(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient(['slots' => 10]);
        $order  = $this->makeProcessingOrder($vendor, $client);

        $this->deliverOrder($order, $vendor);

        $this->assertEquals(10, $client->fresh()->slots);
    }

    #[Test]
    public function test_deliver_twice_does_not_duplicate_pending_earning(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);

        $this->deliverOrder($order, $vendor);

        // Try to deliver again — should throw WorkflowException (order already delivered)
        // but NOT create a duplicate earning
        try {
            $this->deliverOrder($order->fresh(), $vendor);
        } catch (\App\Exceptions\WorkflowException $e) {
            // Expected — order already delivered
        }

        $this->assertEquals(1, VendorEarningTransaction::where('order_id', $order->id)
            ->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)
            ->count());
    }

    #[Test]
    public function test_pending_earning_approved_balance_after_is_unchanged_from_current(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 20.00, 'approved_payable_balance' => 75.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);

        $this->deliverOrder($order, $vendor);

        $tx = VendorEarningTransaction::where('order_id', $order->id)
            ->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)
            ->first();

        // approved_balance_after should snapshot the current approved balance (unchanged)
        $this->assertEquals('75.00', $tx->approved_balance_after);
    }

    #[Test]
    public function test_vendor_with_zero_payout_rate_creates_zero_amount_earning(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 0.00]);
        $client = $this->makeClient();
        $order  = $this->makeProcessingOrder($vendor, $client, ['files_count' => 2]);

        $this->deliverOrder($order, $vendor);

        $tx = VendorEarningTransaction::where('order_id', $order->id)
            ->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)
            ->first();

        $this->assertNotNull($tx);
        $this->assertEquals('0.00', $tx->amount_delta);
        $this->assertEquals('0.00', $vendor->fresh()->pending_earning_balance);
    }

    // -----------------------------------------------------------------------
    // Report upload path (via uploadReport)
    // -----------------------------------------------------------------------

    #[Test]
    public function test_upload_report_also_creates_pending_earning(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 25.00]);
        $client = $this->makeClient();

        // Order in Claimed state — uploadReport transitions it to Processing then Delivered
        $this->counter++;
        $order = Order::create([
            'client_id'        => $client->id,
            'token_view'       => 'rptok' . $this->counter,
            'files_count'      => 2,
            'credits_consumed' => 2,
            'status'           => OrderStatus::Claimed,
            'claimed_by'       => $vendor->id,
            'due_at'           => now()->addHour(),
            'source'           => 'account',
        ]);

        app(OrderWorkflowService::class)->uploadReport($order, $vendor, [
            'ai_skip_reason'    => 'No AI report needed',
            'plag_report_path'  => 'reports/' . $order->id . '/plag/test.pdf',
            'plag_report_disk'  => 'r2',
        ]);

        $tx = VendorEarningTransaction::where('order_id', $order->id)
            ->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)
            ->first();

        $this->assertNotNull($tx);
        // 2 × ₹25 = ₹50
        $this->assertEquals('50.00', $tx->amount_delta);
        $this->assertEquals('50.00', $vendor->fresh()->pending_earning_balance);
    }
}

<?php

namespace Tests\Feature\Finance;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderReport;
use App\Models\User;
use App\Models\VendorEarningTransaction;
use App\Models\VendorPayout;
use App\Services\Finance\VendorEarningService;
use App\Services\Finance\VendorPayoutService;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 7 — Vendor Payout System
 *
 * Rules verified:
 *  - recordPayout() creates a vendor_payouts row
 *  - recordPayout() creates a vendor_earning_transactions row with type=payout
 *  - recordPayout() decreases approved_payable_balance by the payout amount
 *  - recordPayout() does NOT touch pending_earning_balance
 *  - recordPayout() does NOT touch client credits
 *  - recordPayout() does NOT touch slots
 *  - payout amount must be > 0
 *  - payout amount cannot exceed approved_payable_balance
 *  - non-vendor user cannot receive a payout
 *  - duplicate transaction_id for same mode is rejected
 *  - cash payout can be recorded without a transaction_id
 *  - ledger tx has correct negative amount_delta and balance snapshots
 *  - admin HTTP approve route records payout
 *  - payout list page loads
 *  - payout detail page loads
 *  - non-admin cannot access payout pages
 */
class Phase7VendorPayoutTest extends TestCase
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

    /** Give a vendor an approved payable balance (Phase 5 + Phase 6 shortcut). */
    private function giveApprovedBalance(User $vendor, float $amount): void
    {
        $vendor->update(['approved_payable_balance' => $amount]);
    }

    private function validPayoutData(float $amount = 100.00): array
    {
        return [
            'amount'         => $amount,
            'payment_mode'   => 'upi',
            'transaction_id' => 'UTR' . $this->counter . rand(1000, 9999),
            'paid_at'        => null,
            'notes'          => 'Test payout',
        ];
    }

    // -----------------------------------------------------------------------
    // Core service tests
    // -----------------------------------------------------------------------

    #[Test]
    public function test_record_payout_creates_vendor_payouts_row(): void
    {
        $vendor = $this->makeVendor(['approved_payable_balance' => 200.00]);
        $admin  = $this->makeAdmin();

        $service = app(VendorPayoutService::class);
        $payout  = $service->recordPayout($vendor, $this->validPayoutData(100.00), $admin);

        $this->assertNotNull($payout->id);
        $this->assertDatabaseHas('vendor_payouts', [
            'id'           => $payout->id,
            'user_id'      => $vendor->id,
            'amount'       => '100.00',
            'payment_mode' => 'upi',
            'paid_by'      => $admin->id,
            'status'       => 'paid',
        ]);
    }

    #[Test]
    public function test_record_payout_creates_earning_transaction_of_type_payout(): void
    {
        $vendor = $this->makeVendor(['approved_payable_balance' => 200.00]);

        $service = app(VendorPayoutService::class);
        $payout  = $service->recordPayout($vendor, $this->validPayoutData(100.00));

        $tx = VendorEarningTransaction::where('vendor_payout_id', $payout->id)->first();

        $this->assertNotNull($tx);
        $this->assertEquals(VendorEarningTransaction::TYPE_PAYOUT, $tx->type);
        $this->assertEquals(VendorEarningTransaction::STATUS_POSTED, $tx->status);
        $this->assertEquals('-100.00', $tx->amount_delta);
        $this->assertEquals($vendor->id, $tx->vendor_id);
        $this->assertNull($tx->order_id);
    }

    #[Test]
    public function test_record_payout_decreases_approved_payable_balance(): void
    {
        $vendor = $this->makeVendor(['approved_payable_balance' => 300.00]);

        $service = app(VendorPayoutService::class);
        $service->recordPayout($vendor, $this->validPayoutData(100.00));

        $this->assertEquals('200.00', $vendor->fresh()->approved_payable_balance);
    }

    #[Test]
    public function test_record_payout_does_not_touch_pending_earning_balance(): void
    {
        $vendor = $this->makeVendor([
            'approved_payable_balance' => 100.00,
            'pending_earning_balance'  => 50.00,
        ]);

        $service = app(VendorPayoutService::class);
        $service->recordPayout($vendor, $this->validPayoutData(100.00));

        $this->assertEquals('50.00', $vendor->fresh()->pending_earning_balance);
    }

    #[Test]
    public function test_payout_tx_snapshot_correct_approved_balance_after(): void
    {
        $vendor = $this->makeVendor(['approved_payable_balance' => 250.00, 'pending_earning_balance' => 30.00]);

        $service = app(VendorPayoutService::class);
        $payout  = $service->recordPayout($vendor, $this->validPayoutData(100.00));

        $tx = VendorEarningTransaction::where('vendor_payout_id', $payout->id)->first();

        // approved_balance_after = 250 - 100 = 150
        $this->assertEquals('150.00', $tx->approved_balance_after);
        // pending_balance_after = 30 (unchanged)
        $this->assertEquals('30.00', $tx->pending_balance_after);
    }

    #[Test]
    public function test_payout_amount_must_be_greater_than_zero(): void
    {
        $vendor  = $this->makeVendor(['approved_payable_balance' => 100.00]);
        $service = app(VendorPayoutService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->recordPayout($vendor, array_merge($this->validPayoutData(), ['amount' => 0]));
    }

    #[Test]
    public function test_payout_amount_cannot_exceed_approved_payable_balance(): void
    {
        $vendor  = $this->makeVendor(['approved_payable_balance' => 50.00]);
        $service = app(VendorPayoutService::class);

        $this->expectException(\RuntimeException::class);
        $service->recordPayout($vendor, $this->validPayoutData(100.00));
    }

    #[Test]
    public function test_non_vendor_cannot_receive_payout(): void
    {
        $client = User::create([
            'name'          => 'NotVendor',
            'role'          => 'client',
            'status'        => 'active',
            'portal_number' => 999001,
            'email'         => 'notvendor@test.com',
            'password'      => bcrypt('password'),
        ]);

        $service = app(VendorPayoutService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->recordPayout($client, $this->validPayoutData(50.00));
    }

    #[Test]
    public function test_duplicate_transaction_id_for_same_mode_is_rejected(): void
    {
        $vendor1 = $this->makeVendor(['approved_payable_balance' => 200.00]);
        $vendor2 = $this->makeVendor(['approved_payable_balance' => 200.00]);

        $service = app(VendorPayoutService::class);

        $data = [
            'amount'         => 100.00,
            'payment_mode'   => 'upi',
            'transaction_id' => 'UTR_DUPLICATE_123',
        ];

        $service->recordPayout($vendor1, $data);

        $this->expectException(\InvalidArgumentException::class);
        $service->recordPayout($vendor2, $data); // same tx ID, same mode
    }

    #[Test]
    public function test_cash_payout_can_be_recorded_without_transaction_id(): void
    {
        $vendor  = $this->makeVendor(['approved_payable_balance' => 100.00]);
        $service = app(VendorPayoutService::class);

        $payout = $service->recordPayout($vendor, [
            'amount'         => 100.00,
            'payment_mode'   => 'cash',
            'transaction_id' => null,
        ]);

        $this->assertNotNull($payout->id);
        $this->assertNull($payout->reference_id);
        $this->assertEquals('cash', $payout->payment_mode);
    }

    #[Test]
    public function test_same_transaction_id_allowed_for_different_payment_modes(): void
    {
        $vendor  = $this->makeVendor(['approved_payable_balance' => 500.00]);
        $service = app(VendorPayoutService::class);

        // UPI payout
        $service->recordPayout($vendor, [
            'amount'         => 100.00,
            'payment_mode'   => 'upi',
            'transaction_id' => 'REF123',
        ]);

        // bank_transfer with same ref — different mode, should NOT throw
        $payout = $service->recordPayout($vendor, [
            'amount'         => 100.00,
            'payment_mode'   => 'bank_transfer',
            'transaction_id' => 'REF123',
        ]);

        $this->assertNotNull($payout->id);
    }

    #[Test]
    public function test_payout_does_not_touch_client_credit_balance(): void
    {
        $client  = $this->makeClient(['credit_balance' => 20]);
        $vendor  = $this->makeVendor(['approved_payable_balance' => 100.00]);
        $service = app(VendorPayoutService::class);

        $service->recordPayout($vendor, $this->validPayoutData(100.00));

        $this->assertEquals(20, $client->fresh()->credit_balance);
    }

    #[Test]
    public function test_payout_does_not_touch_client_slots(): void
    {
        $client  = $this->makeClient(['slots' => 10, 'slots_consumed' => 3]);
        $vendor  = $this->makeVendor(['approved_payable_balance' => 100.00]);
        $service = app(VendorPayoutService::class);

        $service->recordPayout($vendor, $this->validPayoutData(100.00));

        $this->assertEquals(10, $client->fresh()->slots);
        $this->assertEquals(3, $client->fresh()->slots_consumed);
    }

    #[Test]
    public function test_payout_stores_payment_mode_paid_at_and_paid_by(): void
    {
        $vendor = $this->makeVendor(['approved_payable_balance' => 200.00]);
        $admin  = $this->makeAdmin();

        $service = app(VendorPayoutService::class);
        $payout  = $service->recordPayout($vendor, [
            'amount'         => 100.00,
            'payment_mode'   => 'bank_transfer',
            'transaction_id' => 'TXN_BANK_001',
            'paid_at'        => '2026-06-15',
            'notes'          => 'June settlement',
        ], $admin);

        $this->assertEquals('bank_transfer', $payout->payment_mode);
        $this->assertEquals('TXN_BANK_001', $payout->reference_id);
        $this->assertEquals('2026-06-15', $payout->paid_at->format('Y-m-d'));
        $this->assertEquals('June settlement', $payout->notes);
        $this->assertEquals($admin->id, $payout->paid_by);
    }

    // -----------------------------------------------------------------------
    // Admin HTTP routes
    // -----------------------------------------------------------------------

    #[Test]
    public function test_admin_payout_store_route_records_payout(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(['approved_payable_balance' => 500.00]);

        $this->actingAs($admin)
            ->post(route('admin.finance.payouts.store'), [
                'vendor_id'      => $vendor->id,
                'amount'         => 200,
                'payment_mode'   => 'upi',
                'transaction_id' => 'UTR_HTTP_001',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('vendor_payouts', [
            'user_id' => $vendor->id,
            'amount'  => '200.00',
        ]);

        $this->assertEquals('300.00', $vendor->fresh()->approved_payable_balance);
    }

    #[Test]
    public function test_admin_payout_store_fails_when_amount_exceeds_balance(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(['approved_payable_balance' => 50.00]);

        $this->actingAs($admin)
            ->post(route('admin.finance.payouts.store'), [
                'vendor_id'      => $vendor->id,
                'amount'         => 200,
                'payment_mode'   => 'upi',
                'transaction_id' => 'UTR_OVER_001',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('vendor_payouts', 0);
        $this->assertEquals('50.00', $vendor->fresh()->approved_payable_balance);
    }

    #[Test]
    public function test_admin_payout_index_page_loads(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(['approved_payable_balance' => 100.00]);

        $this->actingAs($admin)
            ->get(route('admin.finance.payouts.index'))
            ->assertOk()
            ->assertSee($vendor->name);
    }

    #[Test]
    public function test_admin_payout_show_page_loads(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(['approved_payable_balance' => 200.00]);

        $service = app(VendorPayoutService::class);
        $payout  = $service->recordPayout($vendor, $this->validPayoutData(100.00), $admin);

        $this->actingAs($admin)
            ->get(route('admin.finance.payouts.show', $payout))
            ->assertOk()
            ->assertSee($vendor->name);
    }

    #[Test]
    public function test_non_admin_cannot_access_payout_pages(): void
    {
        $vendor = $this->makeVendor(['approved_payable_balance' => 100.00]);

        $this->actingAs($vendor)
            ->get(route('admin.finance.payouts.index'))
            ->assertRedirect();

        $this->actingAs($vendor)
            ->post(route('admin.finance.payouts.store'), [
                'vendor_id'      => $vendor->id,
                'amount'         => 50,
                'payment_mode'   => 'cash',
            ])
            ->assertRedirect();
    }

    // -----------------------------------------------------------------------
    // Integration: full Phase 5 → 6 → 7 flow
    // -----------------------------------------------------------------------

    #[Test]
    public function test_full_flow_deliver_approve_then_payout(): void
    {
        $vendor = $this->makeVendor(['payout_rate' => 25.00]);
        $client = $this->makeClient();
        $admin  = $this->makeAdmin();

        // Phase 5: deliver creates pending earning
        $this->counter++;
        $order = Order::create([
            'client_id'            => $client->id,
            'token_view'           => 'flow' . $this->counter,
            'files_count'          => 2,
            'credits_consumed'     => 2,
            'client_rate_per_file' => 50.00,
            'client_amount'        => 100.00,
            'status'               => OrderStatus::Processing,
            'claimed_by'           => $vendor->id,
            'due_at'               => now()->addHour(),
            'source'               => 'account',
        ]);
        OrderReport::create([
            'order_id'         => $order->id,
            'ai_skip_reason'   => 'test',
            'plag_report_path' => 'reports/' . $order->id . '/plag/test.pdf',
            'plag_report_disk' => 'r2',
        ]);

        app(OrderWorkflowService::class)->deliver($order, $vendor);

        // Phase 6: approve earning → moves 50 to approved payable
        app(VendorEarningService::class)->approveEarning($order->fresh(), $admin);

        $vendor->refresh();
        $this->assertEquals('0.00', $vendor->pending_earning_balance);
        $this->assertEquals('50.00', $vendor->approved_payable_balance);  // 2 × 25

        // Phase 7: payout 50
        $service = app(VendorPayoutService::class);
        $payout  = $service->recordPayout($vendor, [
            'amount'         => 50.00,
            'payment_mode'   => 'upi',
            'transaction_id' => 'UTR_FLOW_001',
        ], $admin);

        $vendor->refresh();
        $this->assertEquals('0.00', $vendor->approved_payable_balance);
        $this->assertEquals('0.00', $vendor->pending_earning_balance);

        // Verify ledger chain
        $payoutTx = VendorEarningTransaction::where('vendor_payout_id', $payout->id)->first();
        $this->assertNotNull($payoutTx);
        $this->assertEquals('-50.00', $payoutTx->amount_delta);
        $this->assertEquals('0.00', $payoutTx->approved_balance_after);
    }
}

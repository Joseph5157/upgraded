<?php

namespace Tests\Feature\Finance;

use App\Models\BusinessExpense;
use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\ClientPayment;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorEarningTransaction;
use App\Models\VendorPayout;
use App\Services\Finance\FinanceDashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 9 — Finance Dashboard
 *
 * Rules verified:
 *  - admin can view finance dashboard page
 *  - non-admin cannot access finance dashboard
 *  - empty data returns zero metrics without errors
 *  - total_money_received uses confirmed payments only
 *  - credits_added / credits_used / credits_refunded correct
 *  - credits_remaining from clients.credit_balance (current, not filtered)
 *  - vendor_pending / vendor_payable from users (current, not filtered)
 *  - vendor_paid from vendor_payouts
 *  - business_expenses sum correct
 *  - gross_profit = revenue_earned - vendor_cost (approved orders only)
 *  - net_profit = gross_profit - business_expenses
 *  - cash_balance = total_money_received - vendor_paid - business_expenses
 *  - metrics do not use slots / slots_consumed
 *  - date range filter applies to transaction-based fields
 *  - date range filter does NOT affect current-balance fields
 *  - client summary totals correct
 *  - vendor summary totals correct
 *  - expense_by_category totals correct
 */
class Phase9FinanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private int $counter = 0;

    private function makeAdmin(): User
    {
        $this->counter++;
        return User::create([
            'name'          => 'Admin',
            'role'          => 'admin',
            'status'        => 'active',
            'portal_number' => 100000 + $this->counter,
            'email'         => "admin{$this->counter}@test.com",
            'password'      => bcrypt('password'),
        ]);
    }

    private function makeVendor(float $pending = 0, float $approved = 0): User
    {
        $this->counter++;
        return User::create([
            'name'                     => "Vendor {$this->counter}",
            'role'                     => 'vendor',
            'status'                   => 'active',
            'portal_number'            => 200000 + $this->counter,
            'email'                    => "vendor{$this->counter}@test.com",
            'password'                 => bcrypt('password'),
            'pending_earning_balance'  => $pending,
            'approved_payable_balance' => $approved,
            'payout_rate'              => 50,
        ]);
    }

    private function makeClient(int $creditBalance = 0): Client
    {
        $this->counter++;
        $user = User::create([
            'name'          => "Client User {$this->counter}",
            'role'          => 'client',
            'status'        => 'active',
            'portal_number' => 300000 + $this->counter,
            'email'         => "client{$this->counter}@test.com",
            'password'      => bcrypt('password'),
        ]);
        return Client::create([
            'user_id'        => $user->id,
            'name'           => "Client {$this->counter}",
            'price_per_file' => 100,
            'status'         => 'active',
            'credit_balance' => $creditBalance,
        ]);
    }

    private function makeOrder(Client $client, ?User $vendor = null, array $attrs = []): Order
    {
        $this->counter++;
        return Order::create(array_merge([
            'client_id'       => $client->id,
            'token_view'      => 'tok' . $this->counter,
            'files_count'     => 1,
            'status'          => 'delivered',
            'credits_consumed'=> 1,
            'source'          => 'account',
            'due_at'          => now()->addDay(),
            'claimed_by'      => $vendor?->id,
        ], $attrs));
    }

    private function makeClientPayment(Client $client, float $amount, string $status = ClientPayment::STATUS_CONFIRMED, ?Carbon $receivedAt = null): ClientPayment
    {
        $this->counter++;
        return ClientPayment::create([
            'client_id'       => $client->id,
            'amount_received' => $amount,
            'credits_added'   => (int) $amount,
            'rate_per_credit' => 1,
            'payment_mode'    => 'upi',
            'transaction_id'  => 'TX' . $this->counter,
            'received_at'     => $receivedAt ?? now(),
            'status'          => $status,
        ]);
    }

    private function makeCreditTx(Client $client, string $type, int $delta): ClientCreditTransaction
    {
        $this->counter++;
        return ClientCreditTransaction::create([
            'client_id'    => $client->id,
            'type'         => $type,
            'credits_delta'=> $delta,
            'balance_after'=> max(0, $client->credit_balance + $delta),
        ]);
    }

    private function makeVendorPayout(User $vendor, float $amount, ?Carbon $paidAt = null): VendorPayout
    {
        $this->counter++;
        return VendorPayout::create([
            'user_id'      => $vendor->id,
            'amount'       => $amount,
            'payment_mode' => 'upi',
            'reference_id' => 'VP' . $this->counter,
            'paid_at'      => $paidAt ?? now(),
            'status'       => 'paid',
        ]);
    }

    private function makeExpense(float $amount, string $category = BusinessExpense::CATEGORY_SOFTWARE, ?string $date = null): BusinessExpense
    {
        $this->counter++;
        return BusinessExpense::create([
            'category'     => $category,
            'amount'       => $amount,
            'expense_date' => $date ?? today()->toDateString(),
        ]);
    }

    private function service(): FinanceDashboardService
    {
        return app(FinanceDashboardService::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HTTP access
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_view_finance_dashboard(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.finance.dashboard'))
            ->assertOk()
            ->assertViewIs('admin.finance.dashboard');
    }

    #[Test]
    public function non_admin_cannot_view_finance_dashboard(): void
    {
        $vendor = $this->makeVendor();

        $this->actingAs($vendor)
            ->get(route('admin.finance.dashboard'))
            ->assertRedirect();
    }

    #[Test]
    public function empty_data_returns_zero_metrics_without_errors(): void
    {
        $m = $this->service()->metrics();

        $this->assertSame(0.0, $m['total_money_received']);
        $this->assertSame(0,   $m['credits_added']);
        $this->assertSame(0,   $m['credits_used']);
        $this->assertSame(0,   $m['credits_remaining']);
        $this->assertSame(0.0, $m['gross_profit']);
        $this->assertSame(0.0, $m['net_profit']);
        $this->assertSame(0.0, $m['cash_balance']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Money received
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function total_money_received_uses_confirmed_payments_only(): void
    {
        $client = $this->makeClient();
        $this->makeClientPayment($client, 500, ClientPayment::STATUS_CONFIRMED);
        $this->makeClientPayment($client, 200, ClientPayment::STATUS_VOIDED);

        $m = $this->service()->metrics();
        $this->assertSame(500.0, $m['total_money_received']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Credits
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function credits_added_uses_payment_credit_type(): void
    {
        $client = $this->makeClient(10);
        $this->makeCreditTx($client, ClientCreditTransaction::TYPE_PAYMENT_CREDIT, 10);
        $this->makeCreditTx($client, ClientCreditTransaction::TYPE_ORDER_DEBIT, -3);

        $m = $this->service()->metrics();
        $this->assertSame(10, $m['credits_added']);
    }

    #[Test]
    public function credits_used_uses_order_debit_type_absolute(): void
    {
        $client = $this->makeClient();
        $this->makeCreditTx($client, ClientCreditTransaction::TYPE_ORDER_DEBIT, -5);
        $this->makeCreditTx($client, ClientCreditTransaction::TYPE_ORDER_DEBIT, -3);

        $m = $this->service()->metrics();
        $this->assertSame(8, $m['credits_used']);
    }

    #[Test]
    public function credits_refunded_uses_refund_credit_type(): void
    {
        $client = $this->makeClient();
        $this->makeCreditTx($client, ClientCreditTransaction::TYPE_REFUND_CREDIT, 4);

        $m = $this->service()->metrics();
        $this->assertSame(4, $m['credits_refunded']);
    }

    #[Test]
    public function credits_remaining_sums_client_credit_balance(): void
    {
        $this->makeClient(10);
        $this->makeClient(7);

        $m = $this->service()->metrics();
        $this->assertSame(17, $m['credits_remaining']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Vendor balances
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function vendor_pending_sums_pending_earning_balance(): void
    {
        $this->makeVendor(pending: 300, approved: 0);
        $this->makeVendor(pending: 150, approved: 0);

        $m = $this->service()->metrics();
        $this->assertSame(450.0, $m['vendor_pending']);
    }

    #[Test]
    public function vendor_payable_sums_approved_payable_balance(): void
    {
        $this->makeVendor(pending: 0, approved: 200);
        $this->makeVendor(pending: 0, approved: 100);

        $m = $this->service()->metrics();
        $this->assertSame(300.0, $m['vendor_payable']);
    }

    #[Test]
    public function vendor_paid_sums_paid_payouts(): void
    {
        $vendor = $this->makeVendor(approved: 500);
        $this->makeVendorPayout($vendor, 200);
        $this->makeVendorPayout($vendor, 100);

        $m = $this->service()->metrics();
        $this->assertSame(300.0, $m['vendor_paid']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Revenue, cost, profit
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function revenue_uses_approved_orders_only(): void
    {
        $client = $this->makeClient();

        // Approved order
        $this->makeOrder($client, null, [
            'vendor_approved_at' => now(),
            'client_amount'      => 300,
            'vendor_amount'      => 200,
        ]);

        // Unapproved order — should NOT count
        $this->makeOrder($client, null, [
            'vendor_approved_at' => null,
            'client_amount'      => 999,
            'vendor_amount'      => 999,
        ]);

        $m = $this->service()->metrics();
        $this->assertSame(300.0, $m['revenue_earned']);
        $this->assertSame(200.0, $m['vendor_cost']);
    }

    #[Test]
    public function gross_profit_equals_revenue_minus_vendor_cost(): void
    {
        $client = $this->makeClient();
        $this->makeOrder($client, null, [
            'vendor_approved_at' => now(),
            'client_amount'      => 500,
            'vendor_amount'      => 300,
        ]);

        $m = $this->service()->metrics();
        $this->assertSame(200.0, $m['gross_profit']);
    }

    #[Test]
    public function net_profit_equals_gross_profit_minus_expenses(): void
    {
        $client = $this->makeClient();
        $this->makeOrder($client, null, [
            'vendor_approved_at' => now(),
            'client_amount'      => 500,
            'vendor_amount'      => 300,
        ]);
        $this->makeExpense(50, BusinessExpense::CATEGORY_SOFTWARE);

        $m = $this->service()->metrics();
        // gross = 200, expenses = 50 → net = 150
        $this->assertSame(150.0, $m['net_profit']);
    }

    #[Test]
    public function cash_balance_formula_is_correct(): void
    {
        $client = $this->makeClient();
        $this->makeClientPayment($client, 1000);

        $vendor = $this->makeVendor(approved: 300);
        $this->makeVendorPayout($vendor, 300);

        $this->makeExpense(100, BusinessExpense::CATEGORY_HOSTING);

        $m = $this->service()->metrics();
        // 1000 - 300 - 100 = 600
        $this->assertSame(600.0, $m['cash_balance']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Business expenses
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function business_expenses_sum_is_correct(): void
    {
        $this->makeExpense(200, BusinessExpense::CATEGORY_STAFF_SALARY);
        $this->makeExpense(100, BusinessExpense::CATEGORY_SOFTWARE);

        $m = $this->service()->metrics();
        $this->assertSame(300.0, $m['business_expenses']);
    }

    #[Test]
    public function expense_by_category_groups_correctly(): void
    {
        $this->makeExpense(200, BusinessExpense::CATEGORY_STAFF_SALARY);
        $this->makeExpense(150, BusinessExpense::CATEGORY_STAFF_SALARY);
        $this->makeExpense(100, BusinessExpense::CATEGORY_HOSTING);

        $m = $this->service()->metrics();
        $this->assertSame(350.0, $m['expense_by_category'][BusinessExpense::CATEGORY_STAFF_SALARY]);
        $this->assertSame(100.0, $m['expense_by_category'][BusinessExpense::CATEGORY_HOSTING]);
        $this->assertArrayNotHasKey(BusinessExpense::CATEGORY_SOFTWARE, $m['expense_by_category']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Does not use slots
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function metrics_do_not_use_slots_or_slots_consumed(): void
    {
        // Create a client with slots/slots_consumed but zero credit_balance
        $this->counter++;
        $user = User::create([
            'name' => 'SlotClient', 'role' => 'client', 'status' => 'active',
            'portal_number' => 400000 + $this->counter,
            'email' => "slot{$this->counter}@test.com", 'password' => bcrypt('p'),
        ]);
        Client::create([
            'user_id' => $user->id, 'name' => 'SlotClient', 'price_per_file' => 100,
            'status' => 'active', 'slots' => 50, 'slots_consumed' => 30, 'credit_balance' => 0,
        ]);

        $m = $this->service()->metrics();
        // credits_remaining must use credit_balance (0), not slots-slots_consumed (20)
        $this->assertSame(0, $m['credits_remaining']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Date range filter
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function date_range_filters_money_received(): void
    {
        $client = $this->makeClient();
        $this->makeClientPayment($client, 500, ClientPayment::STATUS_CONFIRMED, Carbon::parse('2026-01-10'));
        $this->makeClientPayment($client, 200, ClientPayment::STATUS_CONFIRMED, Carbon::parse('2026-03-15'));

        $from = Carbon::parse('2026-03-01');
        $to   = Carbon::parse('2026-03-31');

        $m = $this->service()->metrics($from, $to);
        $this->assertSame(200.0, $m['total_money_received']);
    }

    #[Test]
    public function date_range_filters_vendor_paid(): void
    {
        $vendor = $this->makeVendor(approved: 700);
        $this->makeVendorPayout($vendor, 500, Carbon::parse('2026-01-05'));
        $this->makeVendorPayout($vendor, 200, Carbon::parse('2026-04-10'));

        $from = Carbon::parse('2026-04-01');
        $to   = Carbon::parse('2026-04-30');

        $m = $this->service()->metrics($from, $to);
        $this->assertSame(200.0, $m['vendor_paid']);
    }

    #[Test]
    public function date_range_filters_business_expenses(): void
    {
        $this->makeExpense(300, BusinessExpense::CATEGORY_HOSTING, '2026-02-01');
        $this->makeExpense(150, BusinessExpense::CATEGORY_SOFTWARE, '2026-05-15');

        $from = Carbon::parse('2026-05-01');
        $to   = Carbon::parse('2026-05-31');

        $m = $this->service()->metrics($from, $to);
        $this->assertSame(150.0, $m['business_expenses']);
    }

    #[Test]
    public function date_range_does_not_affect_current_balance_fields(): void
    {
        $this->makeClient(20);
        $this->makeVendor(pending: 400, approved: 250);

        // Very restrictive date range — nothing falls inside it
        $from = Carbon::parse('2020-01-01');
        $to   = Carbon::parse('2020-01-02');

        $m = $this->service()->metrics($from, $to);

        // These are live balances — must still show current values
        $this->assertSame(20,    $m['credits_remaining']);
        $this->assertSame(400.0, $m['vendor_pending']);
        $this->assertSame(250.0, $m['vendor_payable']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Client summaries
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function client_summaries_totals_are_correct(): void
    {
        $client = $this->makeClient(5);
        $this->makeClientPayment($client, 300);
        $this->makeClientPayment($client, 200);
        $this->makeCreditTx($client, ClientCreditTransaction::TYPE_PAYMENT_CREDIT, 10);
        $this->makeCreditTx($client, ClientCreditTransaction::TYPE_ORDER_DEBIT, -5);

        $summaries = $this->service()->clientBalances();
        $row = $summaries->first(fn ($r) => $r['client']->id === $client->id);

        $this->assertNotNull($row);
        $this->assertSame(500.0, $row['total_paid']);
        $this->assertSame(10,    $row['credits_added']);
        $this->assertSame(5,     $row['credits_used']);
        $this->assertSame(5,     $row['credit_balance']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Vendor summaries
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function vendor_summaries_totals_are_correct(): void
    {
        $vendor = $this->makeVendor(pending: 200, approved: 300);
        $this->makeVendorPayout($vendor, 150);
        $this->makeVendorPayout($vendor, 100);

        $client = $this->makeClient();
        $this->makeOrder($client, $vendor, ['vendor_approved_at' => now()]);
        $this->makeOrder($client, $vendor, ['vendor_approved_at' => now()]);

        $summaries = $this->service()->vendorBalances();
        $row = $summaries->first(fn ($r) => $r['vendor']->id === $vendor->id);

        $this->assertNotNull($row);
        $this->assertSame(200.0, $row['pending_earning']);
        $this->assertSame(300.0, $row['approved_payable']);
        $this->assertSame(250.0, $row['total_paid']);
        $this->assertSame(2,     $row['files_completed']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HTTP date range via controller
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function dashboard_supports_date_range_filter_via_http(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.finance.dashboard', ['from' => '2026-01-01', 'to' => '2026-12-31']))
            ->assertOk()
            ->assertViewIs('admin.finance.dashboard');
    }
}

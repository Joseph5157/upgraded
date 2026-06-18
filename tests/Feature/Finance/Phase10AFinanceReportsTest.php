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
use App\Services\Finance\FinanceReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 10A — Finance Reports and CSV Export
 *
 * Rules verified:
 *  - admin can access reports index
 *  - non-admin cannot access reports
 *  - each HTML report page returns 200
 *  - each CSV route streams a file
 *  - date range filters apply correctly
 *  - client / vendor filter applies
 *  - type / status filter applies
 *  - category filter applies (expenses)
 *  - monthly summary aggregates correctly across all sources
 *  - CSV filename includes date range or "all-time"
 *  - FinanceReportService query methods return Builder
 *  - FinanceReportService monthlySummary returns Collection
 *  - CSV exports contain correct header rows
 *  - Empty result renders empty-state message (not a crash)
 */
class Phase10AFinanceReportsTest extends TestCase
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

    private function makeVendor(): User
    {
        $this->counter++;
        return User::create([
            'name'                     => "Vendor {$this->counter}",
            'role'                     => 'vendor',
            'status'                   => 'active',
            'portal_number'            => 200000 + $this->counter,
            'email'                    => "vendor{$this->counter}@test.com",
            'password'                 => bcrypt('password'),
            'pending_earning_balance'  => 0,
            'approved_payable_balance' => 0,
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

    private function makeCreditTx(Client $client, string $type, int $delta, ?Carbon $createdAt = null): ClientCreditTransaction
    {
        $this->counter++;
        $tx = ClientCreditTransaction::create([
            'client_id'     => $client->id,
            'type'          => $type,
            'credits_delta' => $delta,
            'balance_after' => $client->credit_balance,
        ]);
        if ($createdAt) {
            $tx->created_at = $createdAt;
            $tx->save();
        }
        return $tx;
    }

    private function makeVendorPayout(User $vendor, float $amount, User $admin, string $status = 'paid', ?Carbon $paidAt = null): VendorPayout
    {
        $this->counter++;
        return VendorPayout::create([
            'user_id'      => $vendor->id,
            'amount'       => $amount,
            'payment_mode' => 'upi',
            'reference_id' => 'REF' . $this->counter,
            'paid_at'      => $paidAt ?? now(),
            'paid_by'      => $admin->id,
            'status'       => $status,
        ]);
    }

    private function makeExpense(float $amount, string $category = BusinessExpense::CATEGORY_OTHER, ?Carbon $date = null): BusinessExpense
    {
        $this->counter++;
        return BusinessExpense::create([
            'amount'       => $amount,
            'category'     => $category,
            'payment_mode' => 'cash',
            'expense_date' => ($date ?? now())->toDateString(),
        ]);
    }

    private function makeOrder(Client $client, ?User $vendor = null, array $attrs = []): Order
    {
        $this->counter++;
        return Order::create(array_merge([
            'client_id'        => $client->id,
            'token_view'       => 'tok' . $this->counter,
            'files_count'      => 1,
            'status'           => 'delivered',
            'credits_consumed' => 1,
            'source'           => 'account',
            'due_at'           => now()->addDay(),
            'claimed_by'       => $vendor?->id,
        ], $attrs));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Access control
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_access_reports_index(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get(route('admin.finance.reports.index'))->assertOk();
    }

    #[Test]
    public function non_admin_cannot_access_reports(): void
    {
        $vendor = $this->makeVendor();
        // Role middleware redirects rather than returning 403
        $this->actingAs($vendor)->get(route('admin.finance.reports.index'))->assertRedirect();
    }

    #[Test]
    public function client_cannot_access_reports(): void
    {
        $this->counter++;
        $clientUser = User::create([
            'name'          => 'Client Only',
            'role'          => 'client',
            'status'        => 'active',
            'portal_number' => 400000 + $this->counter,
            'email'         => "clientonly{$this->counter}@test.com",
            'password'      => bcrypt('password'),
        ]);
        $this->actingAs($clientUser)->get(route('admin.finance.reports.index'))->assertRedirect();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HTML page 200 checks
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function client_payments_report_page_returns_200(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get(route('admin.finance.reports.client-payments'))->assertOk();
    }

    #[Test]
    public function client_credit_ledger_report_page_returns_200(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get(route('admin.finance.reports.client-credit-ledger'))->assertOk();
    }

    #[Test]
    public function vendor_earnings_report_page_returns_200(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get(route('admin.finance.reports.vendor-earnings'))->assertOk();
    }

    #[Test]
    public function vendor_payouts_report_page_returns_200(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get(route('admin.finance.reports.vendor-payouts'))->assertOk();
    }

    #[Test]
    public function expenses_report_page_returns_200(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get(route('admin.finance.reports.expenses'))->assertOk();
    }

    #[Test]
    public function order_profit_report_page_returns_200(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get(route('admin.finance.reports.order-profit'))->assertOk();
    }

    #[Test]
    public function monthly_summary_report_page_returns_200(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get(route('admin.finance.reports.monthly-summary'))->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CSV streaming checks
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function client_payments_csv_streams_file(): void
    {
        $admin   = $this->makeAdmin();
        $client  = $this->makeClient();
        $this->makeClientPayment($client, 500);

        $response = $this->actingAs($admin)->get(route('admin.finance.reports.client-payments.csv'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('Payment ID', $content);
        $this->assertStringContainsString('500', $content);
    }

    #[Test]
    public function client_credit_ledger_csv_streams_file(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();
        $this->makeCreditTx($client, ClientCreditTransaction::TYPE_PAYMENT_CREDIT, 100);

        $response = $this->actingAs($admin)->get(route('admin.finance.reports.client-credit-ledger.csv'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('TX ID', $content);
    }

    #[Test]
    public function vendor_earnings_csv_streams_file(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor();
        VendorEarningTransaction::create([
            'vendor_id'              => $vendor->id,
            'type'                   => VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING,
            'status'                 => VendorEarningTransaction::STATUS_POSTED,
            'amount_delta'           => 50,
            'pending_balance_after'  => 50,
            'approved_balance_after' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.finance.reports.vendor-earnings.csv'));
        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('TX ID', $content);
        $this->assertStringContainsString('50', $content);
    }

    #[Test]
    public function vendor_payouts_csv_streams_file(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor();
        $this->makeVendorPayout($vendor, 200, $admin);

        $response = $this->actingAs($admin)->get(route('admin.finance.reports.vendor-payouts.csv'));
        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Payout ID', $content);
        $this->assertStringContainsString('200', $content);
    }

    #[Test]
    public function expenses_csv_streams_file(): void
    {
        $admin = $this->makeAdmin();
        $this->makeExpense(300, BusinessExpense::CATEGORY_SOFTWARE);

        $response = $this->actingAs($admin)->get(route('admin.finance.reports.expenses.csv'));
        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Expense ID', $content);
        $this->assertStringContainsString('300', $content);
    }

    #[Test]
    public function order_profit_csv_streams_file(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();
        $this->makeOrder($client, null, [
            'financial_locked_at' => now(),
            'vendor_approved_at'  => now(),
            'client_amount'       => 100,
            'vendor_amount'       => 60,
            'gross_profit'        => 40,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.finance.reports.order-profit.csv'));
        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Order ID', $content);
    }

    #[Test]
    public function monthly_summary_csv_streams_file(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();
        $this->makeClientPayment($client, 1000);

        $response = $this->actingAs($admin)->get(route('admin.finance.reports.monthly-summary.csv'));
        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Month', $content);
        $this->assertStringContainsString('Money Received', $content);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Date range filter tests
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function client_payments_date_filter_excludes_out_of_range(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();

        $this->makeClientPayment($client, 100, ClientPayment::STATUS_CONFIRMED, Carbon::parse('2024-01-15'));
        $this->makeClientPayment($client, 200, ClientPayment::STATUS_CONFIRMED, Carbon::parse('2024-03-20'));

        $svc     = new FinanceReportService();
        $filters = ['from' => Carbon::parse('2024-03-01')->startOfDay(), 'to' => Carbon::parse('2024-03-31')->endOfDay()];
        $rows    = $svc->clientPaymentsQuery($filters)->get();

        $this->assertCount(1, $rows);
        $this->assertEquals(200, $rows->first()->amount_received);
    }

    #[Test]
    public function vendor_payouts_date_filter_excludes_out_of_range(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor();

        $this->makeVendorPayout($vendor, 100, $admin, 'paid', Carbon::parse('2024-01-10'));
        $this->makeVendorPayout($vendor, 500, $admin, 'paid', Carbon::parse('2024-05-10'));

        $svc     = new FinanceReportService();
        $filters = ['from' => Carbon::parse('2024-05-01')->startOfDay(), 'to' => Carbon::parse('2024-05-31')->endOfDay()];
        $rows    = $svc->vendorPayoutsQuery($filters)->get();

        $this->assertCount(1, $rows);
        $this->assertEquals(500, $rows->first()->amount);
    }

    #[Test]
    public function expenses_date_filter_excludes_out_of_range(): void
    {
        $this->makeExpense(100, BusinessExpense::CATEGORY_OTHER, Carbon::parse('2024-01-15'));
        $this->makeExpense(250, BusinessExpense::CATEGORY_SOFTWARE, Carbon::parse('2024-06-10'));

        $svc     = new FinanceReportService();
        $filters = ['from' => Carbon::parse('2024-06-01')->startOfDay(), 'to' => Carbon::parse('2024-06-30')->endOfDay()];
        $rows    = $svc->expensesQuery($filters)->get();

        $this->assertCount(1, $rows);
        $this->assertEquals(250, $rows->first()->amount);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Client / vendor / category filter tests
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function client_payments_client_filter_works(): void
    {
        $client1 = $this->makeClient();
        $client2 = $this->makeClient();

        $this->makeClientPayment($client1, 100);
        $this->makeClientPayment($client2, 200);

        $svc     = new FinanceReportService();
        $filters = ['client_id' => $client1->id];
        $rows    = $svc->clientPaymentsQuery($filters)->get();

        $this->assertCount(1, $rows);
        $this->assertEquals($client1->id, $rows->first()->client_id);
    }

    #[Test]
    public function vendor_payouts_vendor_filter_works(): void
    {
        $admin   = $this->makeAdmin();
        $vendor1 = $this->makeVendor();
        $vendor2 = $this->makeVendor();

        $this->makeVendorPayout($vendor1, 100, $admin);
        $this->makeVendorPayout($vendor2, 200, $admin);

        $svc     = new FinanceReportService();
        $filters = ['vendor_id' => $vendor2->id];
        $rows    = $svc->vendorPayoutsQuery($filters)->get();

        $this->assertCount(1, $rows);
        $this->assertEquals($vendor2->id, $rows->first()->user_id);
    }

    #[Test]
    public function expenses_category_filter_works(): void
    {
        $this->makeExpense(100, BusinessExpense::CATEGORY_SOFTWARE);
        $this->makeExpense(200, BusinessExpense::CATEGORY_STAFF_SALARY);
        $this->makeExpense(300, BusinessExpense::CATEGORY_SOFTWARE);

        $svc     = new FinanceReportService();
        $filters = ['category' => BusinessExpense::CATEGORY_SOFTWARE];
        $rows    = $svc->expensesQuery($filters)->get();

        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertEquals(BusinessExpense::CATEGORY_SOFTWARE, $row->category);
        }
    }

    #[Test]
    public function client_credit_ledger_type_filter_works(): void
    {
        $client = $this->makeClient();

        $this->makeCreditTx($client, ClientCreditTransaction::TYPE_PAYMENT_CREDIT, 100);
        $this->makeCreditTx($client, ClientCreditTransaction::TYPE_ORDER_DEBIT, -10);
        $this->makeCreditTx($client, ClientCreditTransaction::TYPE_PAYMENT_CREDIT, 50);

        $svc     = new FinanceReportService();
        $filters = ['type' => ClientCreditTransaction::TYPE_PAYMENT_CREDIT];
        $rows    = $svc->clientCreditLedgerQuery($filters)->get();

        $this->assertCount(2, $rows);
    }

    #[Test]
    public function client_payments_status_filter_works(): void
    {
        $client = $this->makeClient();

        $this->makeClientPayment($client, 100, ClientPayment::STATUS_CONFIRMED);
        $this->makeClientPayment($client, 200, ClientPayment::STATUS_VOIDED);

        $svc     = new FinanceReportService();
        $filters = ['status' => ClientPayment::STATUS_VOIDED];
        $rows    = $svc->clientPaymentsQuery($filters)->get();

        $this->assertCount(1, $rows);
        $this->assertEquals(200, $rows->first()->amount_received);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Monthly summary aggregation
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function monthly_summary_aggregates_money_received_by_month(): void
    {
        $client = $this->makeClient();

        $this->makeClientPayment($client, 300, ClientPayment::STATUS_CONFIRMED, Carbon::parse('2024-03-10'));
        $this->makeClientPayment($client, 150, ClientPayment::STATUS_CONFIRMED, Carbon::parse('2024-03-20'));
        $this->makeClientPayment($client, 500, ClientPayment::STATUS_CONFIRMED, Carbon::parse('2024-04-05'));

        $svc  = new FinanceReportService();
        $rows = $svc->monthlySummary([])->keyBy('month');

        $this->assertEquals(450.0, $rows['2024-03']['money_received']);
        $this->assertEquals(500.0, $rows['2024-04']['money_received']);
    }

    #[Test]
    public function monthly_summary_excludes_voided_payments_from_money_received(): void
    {
        $client = $this->makeClient();

        $this->makeClientPayment($client, 1000, ClientPayment::STATUS_CONFIRMED, Carbon::parse('2024-05-01'));
        $this->makeClientPayment($client, 500,  ClientPayment::STATUS_VOIDED,    Carbon::parse('2024-05-15'));

        $svc  = new FinanceReportService();
        $rows = $svc->monthlySummary([])->keyBy('month');

        $this->assertEquals(1000.0, $rows['2024-05']['money_received']);
    }

    #[Test]
    public function monthly_summary_calculates_gross_profit_formula(): void
    {
        $client = $this->makeClient();
        $vendor = $this->makeVendor();
        $month  = Carbon::parse('2024-06-15');

        $this->makeOrder($client, $vendor, [
            'financial_locked_at' => $month,
            'vendor_approved_at'  => $month,
            'client_amount'       => 200,
            'vendor_amount'       => 120,
            'gross_profit'        => 80,
        ]);

        $svc  = new FinanceReportService();
        $rows = $svc->monthlySummary([])->keyBy('month');

        $this->assertArrayHasKey('2024-06', $rows->toArray());
        $row = $rows['2024-06'];
        $this->assertEquals(200.0, $row['revenue_earned']);
        $this->assertEquals(120.0, $row['vendor_cost']);
        $this->assertEquals(80.0,  $row['gross_profit']);
    }

    #[Test]
    public function monthly_summary_calculates_net_profit_formula(): void
    {
        $client = $this->makeClient();
        $vendor = $this->makeVendor();
        $month  = Carbon::parse('2024-07-10');

        $this->makeOrder($client, $vendor, [
            'financial_locked_at' => $month,
            'vendor_approved_at'  => $month,
            'client_amount'       => 300,
            'vendor_amount'       => 180,
            'gross_profit'        => 120,
        ]);
        $this->makeExpense(40, BusinessExpense::CATEGORY_SOFTWARE, $month);

        $svc  = new FinanceReportService();
        $rows = $svc->monthlySummary([])->keyBy('month');
        $row  = $rows['2024-07'];

        // Net profit = gross profit - expenses = 120 - 40 = 80
        $this->assertEquals(120.0, $row['gross_profit']);
        $this->assertEquals(40.0,  $row['business_expenses']);
        $this->assertEquals(80.0,  $row['net_profit']);
    }

    #[Test]
    public function monthly_summary_date_range_filter_works(): void
    {
        $client = $this->makeClient();

        $this->makeClientPayment($client, 100, ClientPayment::STATUS_CONFIRMED, Carbon::parse('2024-01-10'));
        $this->makeClientPayment($client, 200, ClientPayment::STATUS_CONFIRMED, Carbon::parse('2024-03-10'));

        $svc     = new FinanceReportService();
        $filters = [
            'from' => Carbon::parse('2024-03-01')->startOfDay(),
            'to'   => Carbon::parse('2024-03-31')->endOfDay(),
        ];
        $rows = $svc->monthlySummary($filters);

        // Only March should appear
        $this->assertCount(1, $rows);
        $this->assertEquals('2024-03', $rows->first()['month']);
        $this->assertEquals(200.0, $rows->first()['money_received']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CSV filename helper
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function csv_filename_includes_all_time_when_no_dates(): void
    {
        $svc      = new FinanceReportService();
        $filename = $svc->csvFilename('client-payments', []);
        $this->assertStringContainsString('all-time', $filename);
        $this->assertStringEndsWith('.csv', $filename);
    }

    #[Test]
    public function csv_filename_includes_date_range_when_given(): void
    {
        $svc      = new FinanceReportService();
        $filters  = [
            'from' => Carbon::parse('2024-01-01'),
            'to'   => Carbon::parse('2024-03-31'),
        ];
        $filename = $svc->csvFilename('vendor-payouts', $filters);
        $this->assertStringContainsString('2024-01-01', $filename);
        $this->assertStringContainsString('2024-03-31', $filename);
        $this->assertStringEndsWith('.csv', $filename);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Empty state — should not crash
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function client_payments_report_shows_empty_state_with_no_data(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->get(route('admin.finance.reports.client-payments'))
            ->assertOk()
            ->assertSee('No payments match');
    }

    #[Test]
    public function monthly_summary_shows_empty_state_with_no_data(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->get(route('admin.finance.reports.monthly-summary'))
            ->assertOk()
            ->assertSee('No data for');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Service returns correct types
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function report_service_query_methods_return_builder_instances(): void
    {
        $svc = new FinanceReportService();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $svc->clientPaymentsQuery([]));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $svc->clientCreditLedgerQuery([]));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $svc->vendorEarningsQuery([]));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $svc->vendorPayoutsQuery([]));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $svc->expensesQuery([]));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $svc->orderProfitQuery([]));
    }

    #[Test]
    public function monthly_summary_returns_collection(): void
    {
        $svc = new FinanceReportService();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $svc->monthlySummary([]));
    }
}

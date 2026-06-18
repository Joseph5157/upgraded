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
use App\Services\Finance\FinanceVoidService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 10B — Finance Voiding and Reversal System
 *
 * Rules verified:
 *  - admin can void unused client payment
 *  - voiding client payment decreases credit_balance
 *  - voiding client payment creates negative credit transaction (correction type)
 *  - voiding client payment marks payment status as voided with metadata
 *  - voiding already voided payment is idempotent (returns false)
 *  - cannot void client payment if credits already used and balance insufficient
 *  - voided client payment excluded from dashboard money received
 *  - admin can void vendor payout
 *  - voiding vendor payout restores approved payable balance
 *  - voiding vendor payout creates payout reversal transaction
 *  - voiding vendor payout does not touch pending earning balance
 *  - voided vendor payout excluded from dashboard vendor paid
 *  - admin can void business expense
 *  - voided business expense excluded from dashboard expenses and net profit
 *  - non-admin cannot void records (route-level)
 *  - void reason is required
 *  - void metadata (voided_at, voided_by, void_reason) stored correctly
 */
class Phase10BFinanceVoidTest extends TestCase
{
    use RefreshDatabase;

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

    private function makeClientPayment(Client $client, float $amount, int $credits, string $status = ClientPayment::STATUS_CONFIRMED): ClientPayment
    {
        $this->counter++;
        return ClientPayment::create([
            'client_id'       => $client->id,
            'amount_received' => $amount,
            'credits_added'   => $credits,
            'rate_per_credit' => $credits > 0 ? round($amount / $credits, 2) : 0,
            'payment_mode'    => 'upi',
            'transaction_id'  => 'TX' . $this->counter,
            'received_at'     => now(),
            'status'          => $status,
        ]);
    }

    private function makeVendorPayout(User $vendor, float $amount, User $admin): VendorPayout
    {
        $this->counter++;
        return VendorPayout::create([
            'user_id'      => $vendor->id,
            'amount'       => $amount,
            'payment_mode' => 'upi',
            'reference_id' => 'REF' . $this->counter,
            'paid_at'      => now(),
            'paid_by'      => $admin->id,
            'status'       => 'paid',
        ]);
    }

    private function makeExpense(float $amount, string $category = BusinessExpense::CATEGORY_OTHER): BusinessExpense
    {
        $this->counter++;
        return BusinessExpense::create([
            'amount'       => $amount,
            'category'     => $category,
            'payment_mode' => 'cash',
            'expense_date' => now()->toDateString(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Client Payment Voiding — Service layer
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function void_client_payment_marks_status_voided(): void
    {
        $admin   = $this->makeAdmin();
        $client  = $this->makeClient(50);
        $payment = $this->makeClientPayment($client, 500, 50);

        $svc = app(FinanceVoidService::class);
        $result = $svc->voidClientPayment($payment, $admin, 'Wrong amount entered');

        $this->assertTrue($result);

        $payment->refresh();
        $this->assertEquals(ClientPayment::STATUS_VOIDED, $payment->status);
        $this->assertNotNull($payment->voided_at);
        $this->assertEquals($admin->id, $payment->voided_by);
        $this->assertEquals('Wrong amount entered', $payment->void_reason);
    }

    #[Test]
    public function void_client_payment_decreases_credit_balance(): void
    {
        $client  = $this->makeClient(100);
        $payment = $this->makeClientPayment($client, 1000, 100);
        $admin   = $this->makeAdmin();

        $svc = app(FinanceVoidService::class);
        $svc->voidClientPayment($payment, $admin, 'Test void');

        $client->refresh();
        $this->assertEquals(0, $client->credit_balance);
    }

    #[Test]
    public function void_client_payment_creates_negative_correction_transaction(): void
    {
        $client  = $this->makeClient(50);
        $payment = $this->makeClientPayment($client, 500, 50);
        $admin   = $this->makeAdmin();

        $svc = app(FinanceVoidService::class);
        $svc->voidClientPayment($payment, $admin, 'Wrong TX');

        $correction = ClientCreditTransaction::where('client_payment_id', $payment->id)
            ->where('type', ClientCreditTransaction::TYPE_CORRECTION)
            ->first();

        $this->assertNotNull($correction);
        $this->assertEquals(-50, $correction->credits_delta);
        $this->assertEquals(0, $correction->balance_after);
        $this->assertEquals(-500, (float) $correction->money_value);
        $this->assertEquals($admin->id, $correction->created_by);
        $this->assertStringContainsString('Void reversal', $correction->notes);
    }

    #[Test]
    public function voiding_already_voided_payment_is_idempotent(): void
    {
        $admin   = $this->makeAdmin();
        $client  = $this->makeClient(50);
        $payment = $this->makeClientPayment($client, 500, 50);

        $svc = app(FinanceVoidService::class);
        $svc->voidClientPayment($payment, $admin, 'First void');
        $result = $svc->voidClientPayment($payment, $admin, 'Second void');

        $this->assertFalse($result);

        // Should only have one correction transaction
        $corrections = ClientCreditTransaction::where('client_payment_id', $payment->id)
            ->where('type', ClientCreditTransaction::TYPE_CORRECTION)
            ->count();
        $this->assertEquals(1, $corrections);
    }

    #[Test]
    public function cannot_void_payment_if_credits_already_used(): void
    {
        $client  = $this->makeClient(10); // Only 10 left, but payment added 50
        $payment = $this->makeClientPayment($client, 500, 50);
        $admin   = $this->makeAdmin();

        $svc = app(FinanceVoidService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot void this payment automatically because credits have already been used');

        $svc->voidClientPayment($payment, $admin, 'Want to void');
    }

    #[Test]
    public function voided_client_payment_excluded_from_dashboard_money_received(): void
    {
        $client = $this->makeClient(200);
        $p1 = $this->makeClientPayment($client, 1000, 100);
        $p2 = $this->makeClientPayment($client, 2000, 100);
        $admin = $this->makeAdmin();

        // Void p1 — needs balance >= 100
        $svc = app(FinanceVoidService::class);
        $svc->voidClientPayment($p1, $admin, 'Wrong');

        $dashSvc = app(FinanceDashboardService::class);
        $metrics = $dashSvc->metrics();

        // Only p2 should count
        $this->assertEquals(2000.0, $metrics['total_money_received']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Vendor Payout Voiding — Service layer
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function void_vendor_payout_marks_status_voided(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(0, 0);
        $payout = $this->makeVendorPayout($vendor, 500, $admin);

        $svc = app(FinanceVoidService::class);
        $result = $svc->voidVendorPayout($payout, $admin, 'Wrong vendor');

        $this->assertTrue($result);

        $payout->refresh();
        $this->assertEquals('voided', $payout->status);
        $this->assertNotNull($payout->voided_at);
        $this->assertEquals($admin->id, $payout->voided_by);
        $this->assertEquals('Wrong vendor', $payout->void_reason);
    }

    #[Test]
    public function void_vendor_payout_restores_approved_payable_balance(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(100, 200);
        $payout = $this->makeVendorPayout($vendor, 150, $admin);

        $svc = app(FinanceVoidService::class);
        $svc->voidVendorPayout($payout, $admin, 'Test restore');

        $vendor->refresh();
        // Was 200, payout subtracted 150, void restores it → 200 + 150 = 350
        // But the vendor was created with 200 approved, and the payout object set amount=150
        // without actually running VendorPayoutService (which would decrement).
        // Since we're testing just the void service, it adds 150 back to whatever the current balance is.
        $this->assertEquals(350.0, (float) $vendor->approved_payable_balance);
    }

    #[Test]
    public function void_vendor_payout_creates_payout_reversal_transaction(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(50, 100);
        $payout = $this->makeVendorPayout($vendor, 100, $admin);

        $svc = app(FinanceVoidService::class);
        $svc->voidVendorPayout($payout, $admin, 'Wrong amount');

        $reversal = VendorEarningTransaction::where('vendor_payout_id', $payout->id)
            ->where('type', VendorEarningTransaction::TYPE_PAYOUT_REVERSAL)
            ->first();

        $this->assertNotNull($reversal);
        $this->assertEquals(100.0, (float) $reversal->amount_delta);
        $this->assertEquals(VendorEarningTransaction::STATUS_POSTED, $reversal->status);
        $this->assertEquals($admin->id, $reversal->created_by);
        $this->assertStringContainsString('Void reversal', $reversal->notes);
    }

    #[Test]
    public function void_vendor_payout_does_not_touch_pending_balance(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(300, 200);
        $payout = $this->makeVendorPayout($vendor, 100, $admin);

        $svc = app(FinanceVoidService::class);
        $svc->voidVendorPayout($payout, $admin, 'Test');

        $vendor->refresh();
        $this->assertEquals(300.0, (float) $vendor->pending_earning_balance);
    }

    #[Test]
    public function voiding_already_voided_payout_is_idempotent(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(0, 0);
        $payout = $this->makeVendorPayout($vendor, 500, $admin);

        $svc = app(FinanceVoidService::class);
        $svc->voidVendorPayout($payout, $admin, 'First');
        $result = $svc->voidVendorPayout($payout, $admin, 'Second');

        $this->assertFalse($result);

        $reversals = VendorEarningTransaction::where('vendor_payout_id', $payout->id)
            ->where('type', VendorEarningTransaction::TYPE_PAYOUT_REVERSAL)
            ->count();
        $this->assertEquals(1, $reversals);
    }

    #[Test]
    public function voided_vendor_payout_excluded_from_dashboard_vendor_paid(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(0, 0);
        $p1 = $this->makeVendorPayout($vendor, 100, $admin);
        $p2 = $this->makeVendorPayout($vendor, 200, $admin);

        $svc = app(FinanceVoidService::class);
        $svc->voidVendorPayout($p1, $admin, 'Wrong');

        $dashSvc = app(FinanceDashboardService::class);
        $metrics = $dashSvc->metrics();

        $this->assertEquals(200.0, $metrics['vendor_paid']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Business Expense Voiding — Service layer
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function void_business_expense_marks_status_voided(): void
    {
        $admin   = $this->makeAdmin();
        $expense = $this->makeExpense(300, BusinessExpense::CATEGORY_SOFTWARE);

        $svc = app(FinanceVoidService::class);
        $result = $svc->voidBusinessExpense($expense, $admin, 'Duplicate entry');

        $this->assertTrue($result);

        $expense->refresh();
        $this->assertEquals(BusinessExpense::STATUS_VOIDED, $expense->status);
        $this->assertNotNull($expense->voided_at);
        $this->assertEquals($admin->id, $expense->voided_by);
        $this->assertEquals('Duplicate entry', $expense->void_reason);
    }

    #[Test]
    public function voiding_already_voided_expense_is_idempotent(): void
    {
        $admin   = $this->makeAdmin();
        $expense = $this->makeExpense(300);

        $svc = app(FinanceVoidService::class);
        $svc->voidBusinessExpense($expense, $admin, 'First');
        $result = $svc->voidBusinessExpense($expense, $admin, 'Second');

        $this->assertFalse($result);
    }

    #[Test]
    public function voided_expense_excluded_from_dashboard_expenses(): void
    {
        $admin = $this->makeAdmin();
        $e1 = $this->makeExpense(100, BusinessExpense::CATEGORY_SOFTWARE);
        $e2 = $this->makeExpense(200, BusinessExpense::CATEGORY_HOSTING);

        $svc = app(FinanceVoidService::class);
        $svc->voidBusinessExpense($e1, $admin, 'Wrong');

        $dashSvc = app(FinanceDashboardService::class);
        $metrics = $dashSvc->metrics();

        $this->assertEquals(200.0, $metrics['business_expenses']);
    }

    #[Test]
    public function voided_expense_excluded_from_dashboard_net_profit(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();
        $vendor = $this->makeVendor();

        // Create an approved order for revenue
        Order::create([
            'client_id'           => $client->id,
            'token_view'          => 'tok_np1',
            'files_count'         => 1,
            'status'              => 'delivered',
            'credits_consumed'    => 1,
            'source'              => 'account',
            'due_at'              => now()->addDay(),
            'claimed_by'          => $vendor->id,
            'vendor_approved_at'  => now(),
            'financial_locked_at' => now(),
            'client_amount'       => 100,
            'vendor_amount'       => 60,
            'gross_profit'        => 40,
        ]);

        $e1 = $this->makeExpense(30);
        $e2 = $this->makeExpense(10);

        // Void e1
        $svc = app(FinanceVoidService::class);
        $svc->voidBusinessExpense($e1, $admin, 'Wrong');

        $dashSvc = app(FinanceDashboardService::class);
        $metrics = $dashSvc->metrics();

        // Gross = 100 - 60 = 40, Expenses = only e2 = 10, Net = 40 - 10 = 30
        $this->assertEquals(40.0, $metrics['gross_profit']);
        $this->assertEquals(10.0, $metrics['business_expenses']);
        $this->assertEquals(30.0, $metrics['net_profit']);
    }

    #[Test]
    public function voided_expense_excluded_from_category_breakdown(): void
    {
        $admin = $this->makeAdmin();
        $e1 = $this->makeExpense(100, BusinessExpense::CATEGORY_SOFTWARE);
        $e2 = $this->makeExpense(200, BusinessExpense::CATEGORY_SOFTWARE);

        $svc = app(FinanceVoidService::class);
        $svc->voidBusinessExpense($e1, $admin, 'Wrong');

        $dashSvc = app(FinanceDashboardService::class);
        $metrics = $dashSvc->metrics();

        $this->assertEquals(200.0, $metrics['expense_by_category'][BusinessExpense::CATEGORY_SOFTWARE] ?? 0);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HTTP route tests
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_void_client_payment_via_route(): void
    {
        $admin   = $this->makeAdmin();
        $client  = $this->makeClient(50);
        $payment = $this->makeClientPayment($client, 500, 50);

        $this->actingAs($admin)
            ->post(route('admin.finance.client-payments.void', $payment), [
                'void_reason' => 'Wrong transaction ID',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals(ClientPayment::STATUS_VOIDED, $payment->status);
    }

    #[Test]
    public function admin_can_void_vendor_payout_via_route(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(0, 0);
        $payout = $this->makeVendorPayout($vendor, 500, $admin);

        $this->actingAs($admin)
            ->post(route('admin.finance.payouts.void', $payout), [
                'void_reason' => 'Wrong vendor selected',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $payout->refresh();
        $this->assertEquals('voided', $payout->status);
    }

    #[Test]
    public function admin_can_void_expense_via_route(): void
    {
        $admin   = $this->makeAdmin();
        $expense = $this->makeExpense(300);

        $this->actingAs($admin)
            ->post(route('admin.finance.expenses.void', $expense), [
                'void_reason' => 'Duplicate entry',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $expense->refresh();
        $this->assertEquals(BusinessExpense::STATUS_VOIDED, $expense->status);
    }

    #[Test]
    public function void_reason_is_required_for_client_payment(): void
    {
        $admin   = $this->makeAdmin();
        $client  = $this->makeClient(50);
        $payment = $this->makeClientPayment($client, 500, 50);

        $this->actingAs($admin)
            ->post(route('admin.finance.client-payments.void', $payment), [
                'void_reason' => '',
            ])
            ->assertSessionHasErrors('void_reason');

        $payment->refresh();
        $this->assertEquals(ClientPayment::STATUS_CONFIRMED, $payment->status);
    }

    #[Test]
    public function void_reason_is_required_for_vendor_payout(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor(0, 0);
        $payout = $this->makeVendorPayout($vendor, 500, $admin);

        $this->actingAs($admin)
            ->post(route('admin.finance.payouts.void', $payout), [])
            ->assertSessionHasErrors('void_reason');

        $payout->refresh();
        $this->assertEquals('paid', $payout->status);
    }

    #[Test]
    public function void_reason_is_required_for_expense(): void
    {
        $admin   = $this->makeAdmin();
        $expense = $this->makeExpense(300);

        $this->actingAs($admin)
            ->post(route('admin.finance.expenses.void', $expense), [])
            ->assertSessionHasErrors('void_reason');

        $expense->refresh();
        $this->assertNotEquals(BusinessExpense::STATUS_VOIDED, $expense->status ?? 'active');
    }

    #[Test]
    public function non_admin_cannot_void_client_payment(): void
    {
        $vendor  = $this->makeVendor();
        $client  = $this->makeClient(50);
        $payment = $this->makeClientPayment($client, 500, 50);

        $this->actingAs($vendor)
            ->post(route('admin.finance.client-payments.void', $payment), [
                'void_reason' => 'Test',
            ])
            ->assertRedirect();

        $payment->refresh();
        $this->assertEquals(ClientPayment::STATUS_CONFIRMED, $payment->status);
    }

    #[Test]
    public function insufficient_balance_returns_error_flash(): void
    {
        $admin   = $this->makeAdmin();
        $client  = $this->makeClient(5); // Way less than credits added
        $payment = $this->makeClientPayment($client, 500, 50);

        $this->actingAs($admin)
            ->post(route('admin.finance.client-payments.void', $payment), [
                'void_reason' => 'Want to void',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $payment->refresh();
        $this->assertEquals(ClientPayment::STATUS_CONFIRMED, $payment->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Show page displays void status
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function client_payment_show_displays_void_button_when_confirmed(): void
    {
        $admin   = $this->makeAdmin();
        $client  = $this->makeClient(50);
        $payment = $this->makeClientPayment($client, 500, 50);

        $this->actingAs($admin)
            ->get(route('admin.finance.client-payments.show', $payment))
            ->assertOk()
            ->assertSee('Void this payment');
    }

    #[Test]
    public function client_payment_show_displays_void_details_when_voided(): void
    {
        $admin   = $this->makeAdmin();
        $client  = $this->makeClient(50);
        $payment = $this->makeClientPayment($client, 500, 50);

        $svc = app(FinanceVoidService::class);
        $svc->voidClientPayment($payment, $admin, 'Wrong TX ID');

        $this->actingAs($admin)
            ->get(route('admin.finance.client-payments.show', $payment))
            ->assertOk()
            ->assertSee('Wrong TX ID')
            ->assertDontSee('Void this payment');
    }

    #[Test]
    public function expense_show_displays_void_details_when_voided(): void
    {
        $admin   = $this->makeAdmin();
        $expense = $this->makeExpense(300);

        $svc = app(FinanceVoidService::class);
        $svc->voidBusinessExpense($expense, $admin, 'Duplicate');

        $this->actingAs($admin)
            ->get(route('admin.finance.expenses.show', $expense))
            ->assertOk()
            ->assertSee('Voided')
            ->assertSee('Duplicate');
    }
}

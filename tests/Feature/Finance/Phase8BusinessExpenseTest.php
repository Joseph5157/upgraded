<?php

namespace Tests\Feature\Finance;

use App\Models\BusinessExpense;
use App\Models\Client;
use App\Models\User;
use App\Services\Finance\BusinessExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 8 — Business Expense Tracking
 *
 * Rules verified:
 *  - recordExpense() creates a business_expenses row
 *  - amount must be > 0
 *  - category must be valid
 *  - duplicate (payment_mode + reference_id) rejected for non-cash, non-auto_deducted
 *  - cash expense without reference_id is accepted
 *  - auto_deducted without reference_id is accepted
 *  - same reference_id allowed for different payment modes
 *  - all 9 categories can be recorded
 *  - expense does NOT touch client credit_balance
 *  - expense does NOT touch vendor pending_earning_balance
 *  - expense does NOT touch vendor approved_payable_balance
 *  - totalExpenses() sums correctly
 *  - totalByCategory() groups correctly
 *  - admin HTTP store route records expense
 *  - admin HTTP store fails on invalid amount
 *  - admin HTTP store fails on invalid category
 *  - admin HTTP store fails on duplicate reference
 *  - expense list page loads
 *  - expense detail page loads
 *  - non-admin cannot access expense pages
 */
class Phase8BusinessExpenseTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

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
            'name'                     => 'Vendor',
            'role'                     => 'vendor',
            'status'                   => 'active',
            'portal_number'            => 200000 + $this->counter,
            'email'                    => "vendor{$this->counter}@test.com",
            'password'                 => bcrypt('password'),
            'pending_earning_balance'  => 0,
            'approved_payable_balance' => 0,
        ]);
    }

    private function makeClient(int $creditBalance = 5): Client
    {
        $this->counter++;
        $clientUser = User::create([
            'name'          => 'Client User',
            'role'          => 'client',
            'status'        => 'active',
            'portal_number' => 300000 + $this->counter,
            'email'         => "client{$this->counter}@test.com",
            'password'      => bcrypt('password'),
        ]);
        return Client::create([
            'user_id'        => $clientUser->id,
            'name'           => "Client {$this->counter}",
            'price_per_file' => 100,
            'status'         => 'active',
            'credit_balance' => $creditBalance,
        ]);
    }

    private function service(): BusinessExpenseService
    {
        return app(BusinessExpenseService::class);
    }

    private function baseData(array $overrides = []): array
    {
        return array_merge([
            'category'     => BusinessExpense::CATEGORY_SOFTWARE,
            'amount'       => 500.00,
            'payment_mode' => 'upi',
            'reference_id' => 'REF-001',
            'expense_date' => today()->toDateString(),
            'notes'        => 'Monthly subscription',
        ], $overrides);
    }

    // -----------------------------------------------------------------------
    // Service tests — expense creation
    // -----------------------------------------------------------------------

    #[Test]
    public function record_expense_creates_business_expenses_row(): void
    {
        $admin = $this->makeAdmin();
        $expense = $this->service()->recordExpense($this->baseData(), $admin);

        $this->assertDatabaseHas('business_expenses', [
            'id'       => $expense->id,
            'category' => BusinessExpense::CATEGORY_SOFTWARE,
            'amount'   => 500.00,
        ]);
    }

    #[Test]
    public function record_expense_stores_all_fields(): void
    {
        $admin = $this->makeAdmin();
        $data = $this->baseData([
            'payment_mode' => 'bank_transfer',
            'reference_id' => 'TXNXYZ',
            'expense_date' => '2026-06-01',
            'notes'        => 'AWS bill',
        ]);

        $expense = $this->service()->recordExpense($data, $admin);

        $this->assertSame('bank_transfer', $expense->payment_mode);
        $this->assertSame('TXNXYZ', $expense->reference_id);
        $this->assertSame('2026-06-01', $expense->expense_date->toDateString());
        $this->assertSame('AWS bill', $expense->notes);
        $this->assertSame($admin->id, $expense->created_by);
    }

    #[Test]
    public function amount_must_be_greater_than_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service()->recordExpense($this->baseData(['amount' => 0]));
    }

    #[Test]
    public function negative_amount_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service()->recordExpense($this->baseData(['amount' => -100]));
    }

    #[Test]
    public function invalid_category_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service()->recordExpense($this->baseData(['category' => 'invalid_cat']));
    }

    #[Test]
    public function duplicate_reference_id_for_same_mode_is_rejected(): void
    {
        $this->service()->recordExpense($this->baseData([
            'payment_mode' => 'upi',
            'reference_id' => 'DUPE-REF',
        ]));

        $this->expectException(\InvalidArgumentException::class);
        $this->service()->recordExpense($this->baseData([
            'payment_mode' => 'upi',
            'reference_id' => 'DUPE-REF',
        ]));
    }

    #[Test]
    public function cash_expense_without_reference_id_is_accepted(): void
    {
        $expense = $this->service()->recordExpense($this->baseData([
            'payment_mode' => 'cash',
            'reference_id' => null,
        ]));

        $this->assertNotNull($expense->id);
        $this->assertNull($expense->reference_id);
    }

    #[Test]
    public function auto_deducted_expense_without_reference_id_is_accepted(): void
    {
        $expense = $this->service()->recordExpense($this->baseData([
            'payment_mode' => 'auto_deducted',
            'reference_id' => '',
        ]));

        $this->assertNotNull($expense->id);
        $this->assertNull($expense->reference_id);
    }

    #[Test]
    public function same_reference_id_allowed_for_different_payment_modes(): void
    {
        $this->service()->recordExpense($this->baseData([
            'payment_mode' => 'upi',
            'reference_id' => 'SHARED-REF',
        ]));

        $expense2 = $this->service()->recordExpense($this->baseData([
            'payment_mode' => 'bank_transfer',
            'reference_id' => 'SHARED-REF',
        ]));

        $this->assertNotNull($expense2->id);
    }

    // -----------------------------------------------------------------------
    // All 9 categories
    // -----------------------------------------------------------------------

    #[Test]
    public function all_nine_categories_can_be_recorded(): void
    {
        $categories = array_keys(BusinessExpense::categories());
        $this->assertCount(9, $categories);

        foreach ($categories as $i => $cat) {
            $expense = $this->service()->recordExpense([
                'category'     => $cat,
                'amount'       => 100.00,
                'expense_date' => today()->toDateString(),
            ]);
            $this->assertSame($cat, $expense->category, "Failed for category: {$cat}");
        }

        $this->assertSame(9, BusinessExpense::count());
    }

    #[Test]
    public function can_record_staff_salary_expense(): void
    {
        $expense = $this->service()->recordExpense($this->baseData(['category' => BusinessExpense::CATEGORY_STAFF_SALARY]));
        $this->assertSame(BusinessExpense::CATEGORY_STAFF_SALARY, $expense->category);
    }

    #[Test]
    public function can_record_razorpay_charges_expense(): void
    {
        $expense = $this->service()->recordExpense($this->baseData(['category' => BusinessExpense::CATEGORY_RAZORPAY_CHARGES]));
        $this->assertSame(BusinessExpense::CATEGORY_RAZORPAY_CHARGES, $expense->category);
    }

    #[Test]
    public function can_record_hosting_expense(): void
    {
        $expense = $this->service()->recordExpense($this->baseData(['category' => BusinessExpense::CATEGORY_HOSTING]));
        $this->assertSame(BusinessExpense::CATEGORY_HOSTING, $expense->category);
    }

    #[Test]
    public function can_record_internet_expense(): void
    {
        $expense = $this->service()->recordExpense($this->baseData(['category' => BusinessExpense::CATEGORY_INTERNET]));
        $this->assertSame(BusinessExpense::CATEGORY_INTERNET, $expense->category);
    }

    #[Test]
    public function can_record_domain_expense(): void
    {
        $expense = $this->service()->recordExpense($this->baseData(['category' => BusinessExpense::CATEGORY_DOMAIN]));
        $this->assertSame(BusinessExpense::CATEGORY_DOMAIN, $expense->category);
    }

    #[Test]
    public function can_record_office_expense(): void
    {
        $expense = $this->service()->recordExpense($this->baseData(['category' => BusinessExpense::CATEGORY_OFFICE]));
        $this->assertSame(BusinessExpense::CATEGORY_OFFICE, $expense->category);
    }

    #[Test]
    public function can_record_refund_loss_expense(): void
    {
        $expense = $this->service()->recordExpense($this->baseData(['category' => BusinessExpense::CATEGORY_REFUND_LOSS]));
        $this->assertSame(BusinessExpense::CATEGORY_REFUND_LOSS, $expense->category);
    }

    // -----------------------------------------------------------------------
    // Isolation tests — expense must not touch other balances
    // -----------------------------------------------------------------------

    #[Test]
    public function expense_does_not_touch_client_credit_balance(): void
    {
        $client = $this->makeClient(creditBalance: 20);

        $this->service()->recordExpense($this->baseData(['amount' => 999]));

        $this->assertSame(20, $client->fresh()->credit_balance);
    }

    #[Test]
    public function expense_does_not_touch_vendor_pending_earning_balance(): void
    {
        $vendor = $this->makeVendor();
        $vendor->pending_earning_balance = 300;
        $vendor->save();

        $this->service()->recordExpense($this->baseData(['amount' => 999]));

        $this->assertEquals(300.0, (float) $vendor->fresh()->pending_earning_balance);
    }

    #[Test]
    public function expense_does_not_touch_vendor_approved_payable_balance(): void
    {
        $vendor = $this->makeVendor();
        $vendor->approved_payable_balance = 500;
        $vendor->save();

        $this->service()->recordExpense($this->baseData(['amount' => 999]));

        $this->assertEquals(500.0, (float) $vendor->fresh()->approved_payable_balance);
    }

    // -----------------------------------------------------------------------
    // Aggregation tests
    // -----------------------------------------------------------------------

    #[Test]
    public function total_expenses_sums_all_rows(): void
    {
        $this->service()->recordExpense($this->baseData(['amount' => 100, 'reference_id' => 'R1']));
        $this->service()->recordExpense($this->baseData(['amount' => 250, 'reference_id' => 'R2']));

        $this->assertEquals(350.0, $this->service()->totalExpenses());
    }

    #[Test]
    public function total_by_category_groups_correctly(): void
    {
        $this->service()->recordExpense($this->baseData(['category' => BusinessExpense::CATEGORY_SOFTWARE, 'amount' => 200, 'reference_id' => 'S1']));
        $this->service()->recordExpense($this->baseData(['category' => BusinessExpense::CATEGORY_SOFTWARE, 'amount' => 300, 'reference_id' => 'S2']));
        $this->service()->recordExpense($this->baseData(['category' => BusinessExpense::CATEGORY_HOSTING,  'amount' => 150, 'reference_id' => 'H1']));

        $byCategory = $this->service()->totalByCategory();

        $this->assertEquals(500.0, $byCategory[BusinessExpense::CATEGORY_SOFTWARE]);
        $this->assertEquals(150.0, $byCategory[BusinessExpense::CATEGORY_HOSTING]);
        $this->assertArrayNotHasKey(BusinessExpense::CATEGORY_STAFF_SALARY, $byCategory);
    }

    // -----------------------------------------------------------------------
    // HTTP tests
    // -----------------------------------------------------------------------

    #[Test]
    public function admin_expense_store_route_records_expense(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.finance.expenses.store'), [
                'amount'       => '750.00',
                'category'     => BusinessExpense::CATEGORY_STAFF_SALARY,
                'payment_mode' => 'bank_transfer',
                'reference_id' => 'SAL-JUNE',
                'expense_date' => today()->toDateString(),
                'notes'        => 'June salary',
            ])
            ->assertRedirect(route('admin.finance.expenses.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('business_expenses', [
            'category' => BusinessExpense::CATEGORY_STAFF_SALARY,
            'amount'   => 750.00,
        ]);
    }

    #[Test]
    public function admin_expense_store_fails_on_invalid_amount(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.finance.expenses.store'), [
                'amount'       => '0',
                'category'     => BusinessExpense::CATEGORY_OTHER,
                'expense_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors(['amount']);
    }

    #[Test]
    public function admin_expense_store_fails_on_invalid_category(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.finance.expenses.store'), [
                'amount'       => '100',
                'category'     => 'not_a_real_category',
                'expense_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors(['category']);
    }

    #[Test]
    public function admin_expense_store_fails_on_duplicate_reference(): void
    {
        $admin = $this->makeAdmin();

        BusinessExpense::create([
            'category'     => BusinessExpense::CATEGORY_SOFTWARE,
            'amount'       => 100,
            'payment_mode' => 'upi',
            'reference_id' => 'DUP-HTTP',
            'expense_date' => today()->toDateString(),
            'created_by'   => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.finance.expenses.store'), [
                'amount'       => '200',
                'category'     => BusinessExpense::CATEGORY_SOFTWARE,
                'payment_mode' => 'upi',
                'reference_id' => 'DUP-HTTP',
                'expense_date' => today()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    #[Test]
    public function admin_expense_index_page_loads(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.finance.expenses.index'))
            ->assertOk()
            ->assertViewIs('admin.finance.expenses.index');
    }

    #[Test]
    public function admin_expense_show_page_loads(): void
    {
        $admin = $this->makeAdmin();

        $expense = BusinessExpense::create([
            'category'     => BusinessExpense::CATEGORY_HOSTING,
            'amount'       => 300.00,
            'expense_date' => today()->toDateString(),
            'created_by'   => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.finance.expenses.show', $expense))
            ->assertOk()
            ->assertViewIs('admin.finance.expenses.show');
    }

    #[Test]
    public function non_admin_cannot_access_expense_pages(): void
    {
        $vendor = $this->makeVendor();

        $this->actingAs($vendor)
            ->get(route('admin.finance.expenses.index'))
            ->assertRedirect();

        $this->actingAs($vendor)
            ->post(route('admin.finance.expenses.store'), [
                'amount'       => '100',
                'category'     => BusinessExpense::CATEGORY_OTHER,
                'expense_date' => today()->toDateString(),
            ])
            ->assertRedirect();
    }
}

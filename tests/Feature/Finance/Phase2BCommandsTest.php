<?php

namespace Tests\Feature\Finance;

use App\Enums\OrderStatus;
use App\Models\BusinessExpense;
use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\ClientPayment;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorEarningTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Phase2BCommandsTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeClient(array $attrs = []): Client
    {
        return Client::create(array_merge([
            'name'           => 'Test Client',
            'slots'          => 10,
            'slots_consumed' => 3,
            'credit_balance' => 0,
            'status'         => 'active',
        ], $attrs));
    }

    private function makeVendor(array $attrs = []): User
    {
        return User::create(array_merge([
            'name'                     => 'Test Vendor',
            'role'                     => 'vendor',
            'status'                   => 'active',
            'pending_earning_balance'  => 0,
            'approved_payable_balance' => 0,
        ], $attrs));
    }

    private function makeOrder(Client $client, array $attrs = []): Order
    {
        return Order::create(array_merge([
            'client_id'        => $client->id,
            'token_view'       => uniqid('tok_'),
            'files_count'      => 1,
            'credits_consumed' => 1,
            'status'           => OrderStatus::Pending,
            'due_at'           => now()->addMinutes(30),
            'source'           => 'account',
        ], $attrs));
    }

    private function seedFinanceData(Client $client, User $vendor): void
    {
        // Client payment
        $payment = ClientPayment::create([
            'client_id'       => $client->id,
            'amount_received' => '500.00',
            'credits_added'   => 5,
            'rate_per_credit' => '100.00',
            'payment_mode'    => 'cash',
            'received_at'     => now(),
            'status'          => 'confirmed',
        ]);

        // Credit transaction
        ClientCreditTransaction::create([
            'client_id'         => $client->id,
            'client_payment_id' => $payment->id,
            'type'              => ClientCreditTransaction::TYPE_PAYMENT_CREDIT,
            'credits_delta'     => 5,
            'balance_after'     => 5,
        ]);

        // Vendor earning transaction
        VendorEarningTransaction::create([
            'vendor_id'    => $vendor->id,
            'type'         => VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING,
            'status'       => VendorEarningTransaction::STATUS_POSTED,
            'amount_delta' => '200.00',
            'files_count'  => 1,
        ]);

        // Business expense
        BusinessExpense::create([
            'category'     => BusinessExpense::CATEGORY_SOFTWARE,
            'amount'       => '999.00',
            'expense_date' => today(),
        ]);

        // Client balance
        $client->update(['credit_balance' => 5, 'credits_migrated_at' => now()]);

        // Vendor balance
        $vendor->update([
            'pending_earning_balance'  => 200,
            'approved_payable_balance' => 100,
        ]);
    }

    // -----------------------------------------------------------------------
    // finance:migrate-opening-balances — disabled by default
    // -----------------------------------------------------------------------

    #[Test]
    public function test_opening_balance_command_is_blocked_without_legacy_force(): void
    {
        $this->makeClient(['slots' => 10, 'slots_consumed' => 2]);

        $this->artisan('finance:migrate-opening-balances')
            ->assertFailed();

        // Must not have written anything
        $this->assertSame(0, ClientCreditTransaction::count());
        $this->assertSame(0, Client::whereNotNull('credits_migrated_at')->count());
    }

    #[Test]
    public function test_opening_balance_dry_run_also_blocked_without_legacy_force(): void
    {
        $this->makeClient();

        $this->artisan('finance:migrate-opening-balances --dry-run')
            ->assertFailed();

        $this->assertSame(0, ClientCreditTransaction::count());
    }

    #[Test]
    public function test_opening_balance_command_with_legacy_force_dry_run_does_not_write(): void
    {
        $this->makeClient(['slots' => 10, 'slots_consumed' => 3]);

        $this->artisan('finance:migrate-opening-balances --legacy-force=yes --dry-run')
            ->assertSuccessful();

        $this->assertSame(0, ClientCreditTransaction::count());
        $this->assertSame(0, Client::whereNotNull('credits_migrated_at')->count());
    }

    #[Test]
    public function test_opening_balance_command_with_legacy_force_and_confirm_yes_migrates(): void
    {
        $client = $this->makeClient(['slots' => 10, 'slots_consumed' => 3]);

        $this->artisan('finance:migrate-opening-balances --legacy-force=yes --confirm=yes')
            ->assertSuccessful();

        $this->assertSame(7, $client->fresh()->credit_balance);
        $this->assertSame(1, ClientCreditTransaction::count());
    }

    #[Test]
    public function test_opening_balance_command_message_mentions_clean_slate(): void
    {
        $this->artisan('finance:migrate-opening-balances')
            ->expectsOutputToContain('clean-slate')
            ->assertFailed();
    }

    // -----------------------------------------------------------------------
    // finance:reset-clean-slate — confirmation guard
    // -----------------------------------------------------------------------

    #[Test]
    public function test_reset_fails_without_confirm_flag(): void
    {
        $this->artisan('finance:reset-clean-slate')
            ->assertFailed();
    }

    #[Test]
    public function test_reset_fails_with_confirm_no(): void
    {
        $this->artisan('finance:reset-clean-slate --confirm=no')
            ->assertFailed();
    }

    #[Test]
    public function test_reset_succeeds_with_confirm_yes(): void
    {
        $this->artisan('finance:reset-clean-slate --confirm=yes')
            ->assertSuccessful();
    }

    // -----------------------------------------------------------------------
    // finance:reset-clean-slate — production guard
    // -----------------------------------------------------------------------

    #[Test]
    public function test_reset_is_blocked_in_production_without_allow_production(): void
    {
        // Temporarily fake production environment
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('finance:reset-clean-slate --confirm=yes')
            ->assertFailed();

        // Restore test environment
        $this->app->detectEnvironment(fn () => 'testing');
    }

    #[Test]
    public function test_reset_runs_in_production_with_allow_production_yes(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('finance:reset-clean-slate --confirm=yes --allow-production=yes')
            ->assertSuccessful();

        $this->app->detectEnvironment(fn () => 'testing');
    }

    // -----------------------------------------------------------------------
    // finance:reset-clean-slate — data cleared correctly
    // -----------------------------------------------------------------------

    #[Test]
    public function test_reset_clears_all_finance_ledger_tables(): void
    {
        $client = $this->makeClient();
        $vendor = $this->makeVendor();
        $this->seedFinanceData($client, $vendor);

        $this->assertGreaterThan(0, ClientPayment::count());
        $this->assertGreaterThan(0, ClientCreditTransaction::count());
        $this->assertGreaterThan(0, VendorEarningTransaction::count());
        $this->assertGreaterThan(0, BusinessExpense::count());

        $this->artisan('finance:reset-clean-slate --confirm=yes')
            ->assertSuccessful();

        $this->assertSame(0, ClientPayment::count());
        $this->assertSame(0, ClientCreditTransaction::count());
        $this->assertSame(0, VendorEarningTransaction::count());
        $this->assertSame(0, BusinessExpense::count());
    }

    #[Test]
    public function test_reset_zeros_client_credit_balance(): void
    {
        $client = $this->makeClient(['credit_balance' => 42, 'credits_migrated_at' => now()]);

        $this->artisan('finance:reset-clean-slate --confirm=yes')->assertSuccessful();

        $fresh = $client->fresh();
        $this->assertSame(0, $fresh->credit_balance);
        $this->assertNull($fresh->credits_migrated_at);
    }

    #[Test]
    public function test_reset_zeros_vendor_earning_balances(): void
    {
        $vendor = $this->makeVendor([
            'pending_earning_balance'  => 300,
            'approved_payable_balance' => 150,
        ]);

        $this->artisan('finance:reset-clean-slate --confirm=yes')->assertSuccessful();

        $fresh = $vendor->fresh();
        $this->assertEquals('0.00', $fresh->pending_earning_balance);
        $this->assertEquals('0.00', $fresh->approved_payable_balance);
    }

    #[Test]
    public function test_reset_clears_order_financial_snapshot_columns(): void
    {
        $client = $this->makeClient();
        $order  = $this->makeOrder($client, [
            'client_rate_per_file' => '75.00',
            'client_amount'        => '75.00',
            'vendor_rate_per_file' => '30.00',
            'vendor_amount'        => '30.00',
            'gross_profit'         => '45.00',
            'financial_locked_at'  => now(),
            'vendor_submitted_at'  => now(),
            'vendor_approved_at'   => now(),
        ]);

        $this->artisan('finance:reset-clean-slate --confirm=yes')->assertSuccessful();

        $fresh = $order->fresh();
        $this->assertNull($fresh->client_rate_per_file);
        $this->assertNull($fresh->client_amount);
        $this->assertNull($fresh->vendor_rate_per_file);
        $this->assertNull($fresh->vendor_amount);
        $this->assertNull($fresh->gross_profit);
        $this->assertNull($fresh->financial_locked_at);
        $this->assertNull($fresh->vendor_submitted_at);
        $this->assertNull($fresh->vendor_approved_at);
    }

    // -----------------------------------------------------------------------
    // finance:reset-clean-slate — preserved data
    // -----------------------------------------------------------------------

    #[Test]
    public function test_reset_does_not_delete_clients(): void
    {
        $this->makeClient(['name' => 'Alpha']);
        $this->makeClient(['name' => 'Beta']);

        $this->artisan('finance:reset-clean-slate --confirm=yes')->assertSuccessful();

        $this->assertSame(2, Client::count());
        $this->assertTrue(Client::where('name', 'Alpha')->exists());
        $this->assertTrue(Client::where('name', 'Beta')->exists());
    }

    #[Test]
    public function test_reset_does_not_delete_users(): void
    {
        $vendor = $this->makeVendor(['name' => 'VendorA']);

        $this->artisan('finance:reset-clean-slate --confirm=yes')->assertSuccessful();

        $this->assertTrue(User::where('id', $vendor->id)->exists());
    }

    #[Test]
    public function test_reset_does_not_delete_orders(): void
    {
        $client = $this->makeClient();
        $order  = $this->makeOrder($client);

        $this->artisan('finance:reset-clean-slate --confirm=yes')->assertSuccessful();

        $this->assertTrue(Order::where('id', $order->id)->exists());
    }

    #[Test]
    public function test_reset_does_not_touch_old_slots_columns(): void
    {
        $client = $this->makeClient(['slots' => 15, 'slots_consumed' => 7]);

        $this->artisan('finance:reset-clean-slate --confirm=yes')->assertSuccessful();

        $fresh = $client->fresh();
        $this->assertSame(15, $fresh->slots);
        $this->assertSame(7, $fresh->slots_consumed);
    }

    #[Test]
    public function test_reset_resets_multiple_clients_and_vendors(): void
    {
        $c1 = $this->makeClient(['credit_balance' => 10]);
        $c2 = $this->makeClient(['credit_balance' => 25]);
        $v1 = $this->makeVendor(['pending_earning_balance' => 100, 'approved_payable_balance' => 50]);
        $v2 = $this->makeVendor(['pending_earning_balance' => 200, 'approved_payable_balance' => 75]);

        $this->artisan('finance:reset-clean-slate --confirm=yes')->assertSuccessful();

        $this->assertSame(0, $c1->fresh()->credit_balance);
        $this->assertSame(0, $c2->fresh()->credit_balance);
        $this->assertEquals('0.00', $v1->fresh()->pending_earning_balance);
        $this->assertEquals('0.00', $v2->fresh()->approved_payable_balance);
    }

    #[Test]
    public function test_reset_is_idempotent_running_twice(): void
    {
        $client = $this->makeClient(['credit_balance' => 5]);

        $this->artisan('finance:reset-clean-slate --confirm=yes')->assertSuccessful();
        $this->artisan('finance:reset-clean-slate --confirm=yes')->assertSuccessful();

        $this->assertSame(0, $client->fresh()->credit_balance);
        $this->assertSame(0, ClientPayment::count());
    }
}

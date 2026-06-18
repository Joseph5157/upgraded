<?php

namespace Tests\Feature\Finance;

use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\ClientPayment;
use App\Models\User;
use App\Services\Finance\ClientPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Phase3BVisibilityTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private int $userCounter = 0;

    private function makeAdmin(array $attrs = []): User
    {
        $this->userCounter++;
        return User::create(array_merge([
            'name'          => 'Admin',
            'role'          => 'admin',
            'status'        => 'active',
            'portal_number' => 100000 + $this->userCounter,
            'email'         => "admin{$this->userCounter}@test.com",
            'password'      => bcrypt('password'),
        ], $attrs));
    }

    private function makeVendor(array $attrs = []): User
    {
        $this->userCounter++;
        return User::create(array_merge([
            'name'          => 'Vendor',
            'role'          => 'vendor',
            'status'        => 'active',
            'portal_number' => 200000 + $this->userCounter,
            'email'         => "vendor{$this->userCounter}@test.com",
            'password'      => bcrypt('password'),
        ], $attrs));
    }

    private function makeClient(array $attrs = []): Client
    {
        return Client::create(array_merge([
            'name'           => 'Test Client',
            'slots'          => 10,
            'slots_consumed' => 0,
            'credit_balance' => 0,
            'status'         => 'active',
        ], $attrs));
    }

    private function recordPayment(Client $client, User $admin, array $overrides = []): ClientPayment
    {
        return app(ClientPaymentService::class)->record($client, array_merge([
            'amount_received' => '500.00',
            'credits_added'   => 5,
            'payment_mode'    => ClientPayment::MODE_CASH,
            'transaction_id'  => null,
            'received_at'     => now()->toDateString(),
            'notes'           => null,
        ], $overrides), $admin);
    }

    // -----------------------------------------------------------------------
    // Payment detail page
    // -----------------------------------------------------------------------

    #[Test]
    public function test_admin_can_view_payment_detail_page(): void
    {
        $admin   = $this->makeAdmin();
        $client  = $this->makeClient();
        $payment = $this->recordPayment($client, $admin);

        $this->actingAs($admin)
            ->get(route('admin.finance.client-payments.show', $payment))
            ->assertOk()
            ->assertViewIs('admin.finance.client-payments.show')
            ->assertViewHas('clientPayment', fn($p) => $p->id === $payment->id);
    }

    #[Test]
    public function test_payment_detail_shows_linked_credit_transaction(): void
    {
        $admin   = $this->makeAdmin();
        $client  = $this->makeClient();
        $payment = $this->recordPayment($client, $admin);

        $response = $this->actingAs($admin)
            ->get(route('admin.finance.client-payments.show', $payment));

        $response->assertOk();
        $response->assertViewHas('clientPayment', function ($p) use ($payment) {
            return $p->creditTransactions->count() === 1
                && $p->creditTransactions->first()->client_payment_id === $payment->id;
        });
    }

    #[Test]
    public function test_non_admin_cannot_view_payment_detail_page(): void
    {
        $admin   = $this->makeAdmin();
        $client  = $this->makeClient();
        $payment = $this->recordPayment($client, $admin);
        $vendor  = $this->makeVendor();

        // Role middleware logs the user out and redirects to login on role mismatch.
        $this->actingAs($vendor)
            ->get(route('admin.finance.client-payments.show', $payment))
            ->assertRedirectContains('/login');
    }

    #[Test]
    public function test_unauthenticated_user_cannot_view_payment_detail(): void
    {
        $admin   = $this->makeAdmin();
        $client  = $this->makeClient();
        $payment = $this->recordPayment($client, $admin);

        $this->get(route('admin.finance.client-payments.show', $payment))
            ->assertRedirectContains('/login');
    }

    // -----------------------------------------------------------------------
    // Credit transaction list
    // -----------------------------------------------------------------------

    #[Test]
    public function test_admin_can_view_credit_transaction_list(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.finance.client-credit-transactions.index'))
            ->assertOk()
            ->assertViewIs('admin.finance.client-credit-transactions.index');
    }

    #[Test]
    public function test_credit_transaction_list_shows_payment_credit_rows(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();
        $this->recordPayment($client, $admin, ['credits_added' => 7]);

        $response = $this->actingAs($admin)
            ->get(route('admin.finance.client-credit-transactions.index'));

        $response->assertOk();
        $response->assertViewHas('transactions', fn($t) => $t->total() === 1);
    }

    #[Test]
    public function test_non_admin_cannot_view_credit_transaction_list(): void
    {
        $vendor = $this->makeVendor();

        // Role middleware logs the user out and redirects to login on role mismatch.
        $this->actingAs($vendor)
            ->get(route('admin.finance.client-credit-transactions.index'))
            ->assertRedirectContains('/login');
    }

    #[Test]
    public function test_credit_transaction_list_filter_by_client(): void
    {
        $admin   = $this->makeAdmin();
        $clientA = $this->makeClient(['name' => 'Client A']);
        $clientB = $this->makeClient(['name' => 'Client B']);
        $this->recordPayment($clientA, $admin);
        $this->recordPayment($clientB, $admin);

        $response = $this->actingAs($admin)
            ->get(route('admin.finance.client-credit-transactions.index', ['client_id' => $clientA->id]));

        $response->assertOk();
        $response->assertViewHas('transactions', fn($t) => $t->total() === 1
            && $t->first()->client_id === $clientA->id
        );
    }

    #[Test]
    public function test_credit_transaction_list_filter_by_type(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();
        $this->recordPayment($client, $admin); // creates payment_credit tx

        $response = $this->actingAs($admin)
            ->get(route('admin.finance.client-credit-transactions.index', [
                'type' => ClientCreditTransaction::TYPE_PAYMENT_CREDIT,
            ]));

        $response->assertOk();
        $response->assertViewHas('transactions', fn($t) =>
            $t->total() === 1 && $t->first()->type === ClientCreditTransaction::TYPE_PAYMENT_CREDIT
        );
    }

    #[Test]
    public function test_credit_transaction_list_filter_by_type_returns_empty_when_no_match(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();
        $this->recordPayment($client, $admin); // creates payment_credit, not order_debit

        $response = $this->actingAs($admin)
            ->get(route('admin.finance.client-credit-transactions.index', [
                'type' => ClientCreditTransaction::TYPE_ORDER_DEBIT,
            ]));

        $response->assertOk();
        $response->assertViewHas('transactions', fn($t) => $t->total() === 0);
    }

    // -----------------------------------------------------------------------
    // Client balances page
    // -----------------------------------------------------------------------

    #[Test]
    public function test_admin_can_view_client_balances_page(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.finance.client-balances.index'))
            ->assertOk()
            ->assertViewIs('admin.finance.client-balances.index');
    }

    #[Test]
    public function test_client_balance_summary_shows_correct_credit_balance(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient(['credit_balance' => 0]);
        $this->recordPayment($client, $admin, ['credits_added' => 8]);

        $response = $this->actingAs($admin)
            ->get(route('admin.finance.client-balances.index'));

        $response->assertOk();
        $response->assertViewHas('clients', function ($clients) use ($client) {
            $row = $clients->firstWhere(fn($r) => $r['client']->id === $client->id);
            return $row !== null
                && $row['credit_balance'] === 8
                && $row['credits_added'] === 8
                && (float) $row['total_received'] === 500.00;
        });
    }

    #[Test]
    public function test_client_balance_shows_zero_used_when_no_orders(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();
        $this->recordPayment($client, $admin, ['credits_added' => 3]);

        $response = $this->actingAs($admin)
            ->get(route('admin.finance.client-balances.index'));

        $response->assertOk();
        $response->assertViewHas('clients', function ($clients) use ($client) {
            $row = $clients->firstWhere(fn($r) => $r['client']->id === $client->id);
            return $row['credits_used'] === 0 && $row['credits_refunded'] === 0;
        });
    }

    #[Test]
    public function test_non_admin_cannot_view_client_balances_page(): void
    {
        $vendor = $this->makeVendor();

        // Role middleware logs the user out and redirects to login on role mismatch.
        $this->actingAs($vendor)
            ->get(route('admin.finance.client-balances.index'))
            ->assertRedirectContains('/login');
    }

    // -----------------------------------------------------------------------
    // Phase 3 tests still pass
    // -----------------------------------------------------------------------

    #[Test]
    public function test_existing_client_payments_index_still_loads(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.finance.client-payments.index'))
            ->assertOk();
    }

    #[Test]
    public function test_existing_store_payment_still_works(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();

        $this->actingAs($admin)
            ->post(route('admin.finance.client-payments.store'), [
                'client_id'       => $client->id,
                'amount_received' => '100.00',
                'credits_added'   => 1,
                'payment_mode'    => 'cash',
                'received_at'     => today()->toDateString(),
            ])
            ->assertRedirect(route('admin.finance.client-payments.index'))
            ->assertSessionHas('success');

        $this->assertSame(1, $client->fresh()->credit_balance);
    }
}

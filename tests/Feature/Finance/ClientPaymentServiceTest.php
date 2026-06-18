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

class ClientPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClientPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ClientPaymentService::class);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

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

    private function makeAdmin(array $attrs = []): User
    {
        static $counter = 0;
        $counter++;

        return User::create(array_merge([
            'name'           => 'Admin User',
            'role'           => 'admin',
            'status'         => 'active',
            'portal_number'  => 100000 + $counter,
            'email'          => "admin{$counter}@test.com",
            'password'       => bcrypt('password'),
        ], $attrs));
    }

    private function validPaymentData(array $overrides = []): array
    {
        return array_merge([
            'amount_received' => '500.00',
            'credits_added'   => 5,
            'payment_mode'    => ClientPayment::MODE_CASH,
            'transaction_id'  => null,
            'received_at'     => now()->toDateString(),
            'notes'           => null,
        ], $overrides);
    }

    // -----------------------------------------------------------------------
    // record() — core behaviour
    // -----------------------------------------------------------------------

    #[Test]
    public function test_record_creates_payment_row(): void
    {
        $client = $this->makeClient();
        $admin  = $this->makeAdmin();

        $this->service->record($client, $this->validPaymentData(), $admin);

        $this->assertSame(1, ClientPayment::count());

        $payment = ClientPayment::first();
        $this->assertSame($client->id, $payment->client_id);
        $this->assertEquals('500.00', $payment->amount_received);
        $this->assertSame(5, $payment->credits_added);
        $this->assertSame(ClientPayment::STATUS_CONFIRMED, $payment->status);
        $this->assertSame(ClientPayment::MODE_CASH, $payment->payment_mode);
        $this->assertSame($admin->id, $payment->created_by);
    }

    #[Test]
    public function test_record_increases_client_credit_balance(): void
    {
        $client = $this->makeClient(['credit_balance' => 3]);
        $admin  = $this->makeAdmin();

        $this->service->record($client, $this->validPaymentData(['credits_added' => 5]), $admin);

        $this->assertSame(8, $client->fresh()->credit_balance);
    }

    #[Test]
    public function test_record_writes_credit_transaction_linked_to_payment(): void
    {
        $client = $this->makeClient();
        $admin  = $this->makeAdmin();

        $payment = $this->service->record($client, $this->validPaymentData(['credits_added' => 10]), $admin);

        $tx = ClientCreditTransaction::where('client_id', $client->id)->first();
        $this->assertNotNull($tx);
        $this->assertSame(ClientCreditTransaction::TYPE_PAYMENT_CREDIT, $tx->type);
        $this->assertSame(10, $tx->credits_delta);
        $this->assertSame(10, $tx->balance_after);
        $this->assertSame($payment->id, $tx->client_payment_id);
    }

    #[Test]
    public function test_record_calculates_rate_per_credit_correctly(): void
    {
        $client = $this->makeClient();
        $admin  = $this->makeAdmin();

        $payment = $this->service->record($client, $this->validPaymentData([
            'amount_received' => '1000.00',
            'credits_added'   => 4,
        ]), $admin);

        $this->assertEquals('250.00', $payment->fresh()->rate_per_credit);
    }

    #[Test]
    public function test_record_stores_transaction_id_and_notes(): void
    {
        $client = $this->makeClient();
        $admin  = $this->makeAdmin();

        $payment = $this->service->record($client, $this->validPaymentData([
            'transaction_id' => 'UPI12345',
            'notes'          => 'Collected in person',
        ]), $admin);

        $payment->refresh();
        $this->assertSame('UPI12345', $payment->transaction_id);
        $this->assertSame('Collected in person', $payment->notes);
    }

    #[Test]
    public function test_record_stores_payment_mode(): void
    {
        $client = $this->makeClient();
        $admin  = $this->makeAdmin();

        $payment = $this->service->record($client, $this->validPaymentData([
            'payment_mode' => ClientPayment::MODE_UPI,
        ]), $admin);

        $this->assertSame(ClientPayment::MODE_UPI, $payment->fresh()->payment_mode);
    }

    #[Test]
    public function test_record_multiple_payments_accumulate_credits(): void
    {
        $client = $this->makeClient(['credit_balance' => 0]);
        $admin  = $this->makeAdmin();

        $this->service->record($client, $this->validPaymentData(['credits_added' => 5]), $admin);
        $this->service->record($client->fresh(), $this->validPaymentData(['credits_added' => 3]), $admin);

        $this->assertSame(8, $client->fresh()->credit_balance);
        $this->assertSame(2, ClientPayment::count());
        $this->assertSame(2, ClientCreditTransaction::where('client_id', $client->id)->count());
    }

    #[Test]
    public function test_record_ledger_tracks_correct_balance_after(): void
    {
        $client = $this->makeClient(['credit_balance' => 10]);
        $admin  = $this->makeAdmin();

        $this->service->record($client, $this->validPaymentData(['credits_added' => 7]), $admin);

        $tx = ClientCreditTransaction::where('client_id', $client->id)->first();
        $this->assertSame(17, $tx->balance_after);
    }

    // -----------------------------------------------------------------------
    // HTTP — controller integration
    // -----------------------------------------------------------------------

    #[Test]
    public function test_store_endpoint_requires_auth(): void
    {
        $this->post(route('admin.finance.client-payments.store'), [])
            ->assertRedirectContains('/login');
    }

    #[Test]
    public function test_store_endpoint_creates_payment_and_redirects(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient(['credit_balance' => 0]);

        $this->actingAs($admin)
            ->post(route('admin.finance.client-payments.store'), [
                'client_id'       => $client->id,
                'amount_received' => '200.00',
                'credits_added'   => 2,
                'payment_mode'    => 'cash',
                'received_at'     => today()->toDateString(),
            ])
            ->assertRedirect(route('admin.finance.client-payments.index'))
            ->assertSessionHas('success');

        $this->assertSame(1, ClientPayment::count());
        $this->assertSame(2, $client->fresh()->credit_balance);
    }

    #[Test]
    public function test_store_endpoint_validates_required_fields(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.finance.client-payments.store'), [])
            ->assertSessionHasErrors(['client_id', 'amount_received', 'credits_added', 'payment_mode', 'received_at']);
    }

    #[Test]
    public function test_store_endpoint_rejects_zero_credits(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();

        $this->actingAs($admin)
            ->post(route('admin.finance.client-payments.store'), [
                'client_id'       => $client->id,
                'amount_received' => '100.00',
                'credits_added'   => 0,
                'payment_mode'    => 'cash',
                'received_at'     => today()->toDateString(),
            ])
            ->assertSessionHasErrors(['credits_added']);
    }

    #[Test]
    public function test_store_endpoint_rejects_invalid_payment_mode(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();

        $this->actingAs($admin)
            ->post(route('admin.finance.client-payments.store'), [
                'client_id'       => $client->id,
                'amount_received' => '100.00',
                'credits_added'   => 1,
                'payment_mode'    => 'bitcoin',
                'received_at'     => today()->toDateString(),
            ])
            ->assertSessionHasErrors(['payment_mode']);
    }

    #[Test]
    public function test_index_page_loads_for_admin(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.finance.client-payments.index'))
            ->assertOk()
            ->assertViewIs('admin.finance.client-payments.index');
    }

    #[Test]
    public function test_index_shows_payments_and_totals(): void
    {
        $admin  = $this->makeAdmin();
        $client = $this->makeClient();

        $this->service->record($client, $this->validPaymentData([
            'amount_received' => '300.00',
            'credits_added'   => 3,
        ]), $admin);

        $response = $this->actingAs($admin)
            ->get(route('admin.finance.client-payments.index'));

        $response->assertOk();
        $response->assertViewHas('totals', fn($t) => $t['credits'] == 3 && $t['amount'] == '300.00');
    }
}

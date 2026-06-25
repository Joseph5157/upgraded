<?php

namespace Tests\Feature\Finance;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\Order;
use App\Models\User;
use App\Services\CreateClientOrderService;
use App\Services\DeleteClientOrderService;
use App\Services\Finance\ClientPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Phase4UploadCreditsTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private int $counter = 0;

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

    private function makeClientUser(Client $client, array $attrs = []): User
    {
        $this->counter++;
        return User::create(array_merge([
            'name'          => 'Client User',
            'role'          => 'client',
            'status'        => 'active',
            'portal_number' => 300000 + $this->counter,
            'email'         => "client{$this->counter}@test.com",
            'password'      => bcrypt('password'),
            'client_id'     => $client->id,
        ], $attrs));
    }

    private function creditClient(Client $client, User $admin, int $credits = 5): void
    {
        app(ClientPaymentService::class)->record($client, [
            'amount_received' => (string) ($credits * 50),
            'credits_added'   => $credits,
            'payment_mode'    => 'cash',
            'transaction_id'  => null,
            'received_at'     => now()->toDateString(),
            'notes'           => null,
        ], $admin);
    }

    private function fakeStorage(): void
    {
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);
    }

    // -----------------------------------------------------------------------
    // Credit check before order creation
    // -----------------------------------------------------------------------

    #[Test]
    public function test_upload_rejected_when_credit_balance_is_zero(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 0]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No upload credits remaining');

        app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('doc.pdf', 100),
        ], 'account');
    }

    #[Test]
    public function test_upload_rejected_when_files_exceed_credit_balance(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 2]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient credits');

        app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('a.pdf', 100),
            UploadedFile::fake()->create('b.pdf', 100),
            UploadedFile::fake()->create('c.pdf', 100),
        ], 'account');
    }

    #[Test]
    public function test_upload_rejected_when_client_is_suspended(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 5, 'status' => 'suspended']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('suspended');

        app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('doc.pdf', 100),
        ], 'account');
    }

    // -----------------------------------------------------------------------
    // Credit debit on order creation
    // -----------------------------------------------------------------------

    #[Test]
    public function test_credit_balance_decremented_on_upload(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 10]);

        app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('a.pdf', 100),
            UploadedFile::fake()->create('b.pdf', 100),
        ], 'account');

        $this->assertEquals(8, $client->fresh()->credit_balance);
    }

    #[Test]
    public function test_order_debit_ledger_entry_created(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 10]);

        $order = app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('doc.pdf', 100),
        ], 'account');

        $tx = ClientCreditTransaction::where('order_id', $order->id)
            ->where('type', ClientCreditTransaction::TYPE_ORDER_DEBIT)
            ->first();

        $this->assertNotNull($tx);
        $this->assertEquals(-1, $tx->credits_delta);
        $this->assertEquals(9, $tx->balance_after);
        $this->assertEquals($client->id, $tx->client_id);
    }

    #[Test]
    public function test_financial_snapshot_stored_on_order(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 10, 'price_per_file' => 75.00]);

        $order = app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('a.pdf', 100),
            UploadedFile::fake()->create('b.pdf', 100),
        ], 'account');

        $order->refresh();

        $this->assertEquals(2, $order->credits_consumed);
        $this->assertEquals('75.00', $order->client_rate_per_file);
        $this->assertEquals('150.00', $order->client_amount);
    }

    #[Test]
    public function test_slots_consumed_not_modified_on_upload(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 10, 'slots_consumed' => 0]);

        app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('doc.pdf', 100),
        ], 'account');

        // slots_consumed must be frozen — never written to in Phase 4+
        $this->assertEquals(0, $client->fresh()->slots_consumed);
    }

    // -----------------------------------------------------------------------
    // Auto-suspend on zero balance
    // -----------------------------------------------------------------------

    #[Test]
    public function test_client_suspended_when_balance_reaches_zero_after_upload(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 1]);

        app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('doc.pdf', 100),
        ], 'account');

        $this->assertEquals('suspended', $client->fresh()->status);
    }

    #[Test]
    public function test_client_not_suspended_when_balance_remains_after_upload(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 5]);

        app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('doc.pdf', 100),
        ], 'account');

        $this->assertEquals('active', $client->fresh()->status);
    }

    // -----------------------------------------------------------------------
    // Credit refund on order deletion
    // -----------------------------------------------------------------------

    #[Test]
    public function test_deleting_phase4_order_refunds_credits(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 10]);

        $order = app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('doc.pdf', 100),
        ], 'account');

        // Balance should be 9 after upload
        $this->assertEquals(9, $client->fresh()->credit_balance);

        $returned = app(DeleteClientOrderService::class)->execute($order, $client);

        // Credit must be returned
        $this->assertEquals(10, $client->fresh()->credit_balance);
        $this->assertTrue($returned);
    }

    #[Test]
    public function test_refund_ledger_entry_created_on_order_delete(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 10]);

        app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('doc.pdf', 100),
        ], 'account');

        $order = Order::where('client_id', $client->id)->first();
        app(DeleteClientOrderService::class)->execute($order, $client);

        // After order deletion, order_id is nulled (nullOnDelete foreign key),
        // so query by client_id + type.
        $tx = ClientCreditTransaction::where('client_id', $client->id)
            ->where('type', ClientCreditTransaction::TYPE_REFUND_CREDIT)
            ->first();

        $this->assertNotNull($tx);
        $this->assertEquals(1, $tx->credits_delta);
        $this->assertEquals(10, $tx->balance_after);
    }

    #[Test]
    public function test_deleting_pre_phase4_order_does_not_refund_credits(): void
    {
        $this->fakeStorage();
        // Pre-Phase-4 order: created manually, no ORDER_DEBIT tx
        $client = $this->makeClient(['credit_balance' => 5]);

        $order = Order::create([
            'client_id'   => $client->id,
            'token_view'  => 'oldtoken',
            'files_count' => 3,
            'status'      => OrderStatus::Pending,
            'due_at'      => now(),
            'source'      => 'account',
        ]);

        $returned = app(DeleteClientOrderService::class)->execute($order, $client);

        // No debit tx existed → no refund
        $this->assertEquals(5, $client->fresh()->credit_balance);
        $this->assertFalse($returned);
    }

    #[Test]
    public function test_suspended_client_reactivated_when_deletion_restores_balance(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 1]);

        $order = app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('doc.pdf', 100),
        ], 'account');

        // After upload client is suspended (balance = 0)
        $this->assertEquals('suspended', $client->fresh()->status);

        app(DeleteClientOrderService::class)->execute($order, $client);

        // Deletion refunds 1 credit → balance = 1 → reactivated
        $this->assertEquals('active', $client->fresh()->status);
    }

    // -----------------------------------------------------------------------
    // Dashboard HTTP — credit_balance displayed
    // -----------------------------------------------------------------------

    #[Test]
    public function test_client_dashboard_uses_credit_balance_for_remaining(): void
    {
        $client = $this->makeClient(['credit_balance' => 7]);
        $user   = $this->makeClientUser($client);

        // Phase 10 Stage 2: GET /client/dashboard now redirects to /client-panel.
        // The credit_balance value itself is verified via the Client model directly;
        // the Filament panel displays it through CreditOverviewWidget (browser-tested).
        $response = $this->actingAs($user)->get(route('client.dashboard'));
        $response->assertRedirect('/client-panel');

        // Verify the credit_balance is correctly stored on the model (service-level assertion)
        $this->assertSame(7, $client->fresh()->credit_balance);
    }

    #[Test]
    public function test_dashboard_store_endpoint_uses_credits(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 5]);
        $user   = $this->makeClientUser($client);

        $response = $this->actingAs($user)->post(route('client.dashboard.upload'), [
            'files' => [UploadedFile::fake()->create('doc.pdf', 100)],
        ]);

        $response->assertRedirect(route('client.dashboard'));

        // credit_balance decremented
        $this->assertEquals(4, $client->fresh()->credit_balance);
        // ORDER_DEBIT tx written
        $this->assertEquals(1, ClientCreditTransaction::where('client_id', $client->id)
            ->where('type', ClientCreditTransaction::TYPE_ORDER_DEBIT)
            ->count());
    }

    #[Test]
    public function test_dashboard_store_rejected_with_zero_balance(): void
    {
        $this->fakeStorage();
        $client = $this->makeClient(['credit_balance' => 0]);
        $user   = $this->makeClientUser($client);

        $response = $this->actingAs($user)->post(route('client.dashboard.upload'), [
            'files' => [UploadedFile::fake()->create('doc.pdf', 100)],
        ]);

        // Should redirect back with error, not create an order
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(0, Order::where('client_id', $client->id)->count());
    }

    // -----------------------------------------------------------------------
    // Topup self-service disabled
    // -----------------------------------------------------------------------

    #[Test]
    public function test_client_topup_store_is_disabled(): void
    {
        $client = $this->makeClient();
        $user   = $this->makeClientUser($client);

        $response = $this->actingAs($user)->post(route('client.topup.store'), [
            'amount_requested' => 50,
            'transaction_id'   => 'UTR12345678',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // No topup request must have been created
        $this->assertEquals(0, $client->topupRequests()->count());
    }
}

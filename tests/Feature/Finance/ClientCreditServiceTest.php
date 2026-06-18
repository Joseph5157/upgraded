<?php

namespace Tests\Feature\Finance;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\Order;
use App\Services\Finance\ClientCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientCreditServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClientCreditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ClientCreditService::class);
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

    private function lockedClient(Client $client): Client
    {
        return Client::where('id', $client->id)->lockForUpdate()->first();
    }

    // -----------------------------------------------------------------------
    // Opening balance
    // -----------------------------------------------------------------------

    #[Test]
    public function test_opening_balance_sets_credit_balance_and_writes_ledger(): void
    {
        $client = $this->makeClient(['slots' => 8, 'slots_consumed' => 3]);

        DB::transaction(function () use ($client) {
            $locked = $this->lockedClient($client);
            $this->service->createOpeningBalance($locked, 5);
        });

        $client->refresh();

        $this->assertSame(5, $client->credit_balance);
        $this->assertNotNull($client->credits_migrated_at);

        $tx = ClientCreditTransaction::where('client_id', $client->id)->first();
        $this->assertNotNull($tx);
        $this->assertSame(ClientCreditTransaction::TYPE_OPENING_BALANCE, $tx->type);
        $this->assertSame(5, $tx->credits_delta);
        $this->assertSame(5, $tx->balance_after);
    }

    #[Test]
    public function test_opening_balance_is_idempotent(): void
    {
        $client = $this->makeClient(['slots' => 5, 'slots_consumed' => 0]);

        DB::transaction(fn () => $this->service->createOpeningBalance(
            $this->lockedClient($client), 5
        ));

        // Second call — must return null and not create a second row
        DB::transaction(function () use ($client) {
            $locked = $this->lockedClient($client);
            $result = $this->service->createOpeningBalance($locked, 5);
            $this->assertNull($result);
        });

        $this->assertSame(1, ClientCreditTransaction::where('client_id', $client->id)->count());
        $this->assertSame(5, $client->fresh()->credit_balance);
    }

    #[Test]
    public function test_opening_balance_clamps_to_zero_when_slots_negative(): void
    {
        // slots_consumed > slots — edge case on legacy data
        $client = $this->makeClient(['slots' => 3, 'slots_consumed' => 5]);

        DB::transaction(fn () => $this->service->createOpeningBalance(
            $this->lockedClient($client), max(0, $client->slots - $client->slots_consumed)
        ));

        $client->refresh();
        $this->assertSame(0, $client->credit_balance);

        $tx = ClientCreditTransaction::where('client_id', $client->id)->first();
        $this->assertSame(0, $tx->credits_delta);
        $this->assertSame(0, $tx->balance_after);
    }

    // -----------------------------------------------------------------------
    // creditClient
    // -----------------------------------------------------------------------

    #[Test]
    public function test_credit_client_increases_balance_and_writes_ledger(): void
    {
        $client = $this->makeClient(['credit_balance' => 5]);

        DB::transaction(fn () => $this->service->creditClient(
            $this->lockedClient($client), 10, ['notes' => 'UPI payment']
        ));

        $client->refresh();
        $this->assertSame(15, $client->credit_balance);

        $tx = ClientCreditTransaction::where('client_id', $client->id)->first();
        $this->assertSame(ClientCreditTransaction::TYPE_PAYMENT_CREDIT, $tx->type);
        $this->assertSame(10, $tx->credits_delta);
        $this->assertSame(15, $tx->balance_after);
    }

    #[Test]
    public function test_credit_client_links_client_payment_id(): void
    {
        $client = $this->makeClient(['credit_balance' => 0]);

        $payment = \App\Models\ClientPayment::create([
            'client_id'      => $client->id,
            'amount_received' => '500.00',
            'credits_added'  => 5,
            'rate_per_credit' => '100.00',
            'payment_mode'   => 'cash',
            'received_at'    => now(),
            'status'         => 'confirmed',
        ]);

        DB::transaction(fn () => $this->service->creditClient(
            $this->lockedClient($client), 5, ['client_payment_id' => $payment->id]
        ));

        $tx = ClientCreditTransaction::where('client_id', $client->id)->first();
        $this->assertSame($payment->id, $tx->client_payment_id);
    }

    #[Test]
    public function test_credit_client_rejects_zero(): void
    {
        $client = $this->makeClient();

        $this->expectException(\InvalidArgumentException::class);

        DB::transaction(fn () => $this->service->creditClient(
            $this->lockedClient($client), 0
        ));
    }

    #[Test]
    public function test_credit_client_rejects_negative(): void
    {
        $client = $this->makeClient();

        $this->expectException(\InvalidArgumentException::class);

        DB::transaction(fn () => $this->service->creditClient(
            $this->lockedClient($client), -5
        ));
    }

    // -----------------------------------------------------------------------
    // debitForOrder
    // -----------------------------------------------------------------------

    #[Test]
    public function test_debit_for_order_reduces_balance_and_writes_ledger(): void
    {
        $client = $this->makeClient(['credit_balance' => 10]);
        $order  = $this->makeOrder($client, ['credits_consumed' => 1]);

        DB::transaction(fn () => $this->service->debitForOrder(
            $this->lockedClient($client), $order
        ));

        $client->refresh();
        $this->assertSame(9, $client->credit_balance);

        $tx = ClientCreditTransaction::where('client_id', $client->id)->first();
        $this->assertSame(ClientCreditTransaction::TYPE_ORDER_DEBIT, $tx->type);
        $this->assertSame(-1, $tx->credits_delta);
        $this->assertSame(9, $tx->balance_after);
        $this->assertSame($order->id, $tx->order_id);
    }

    #[Test]
    public function test_debit_for_order_prevents_negative_balance(): void
    {
        $client = $this->makeClient(['credit_balance' => 0]);
        $order  = $this->makeOrder($client, ['credits_consumed' => 1]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/[Ii]nsufficient/');

        DB::transaction(fn () => $this->service->debitForOrder(
            $this->lockedClient($client), $order
        ));
    }

    #[Test]
    public function test_debit_for_order_prevents_duplicate_debit(): void
    {
        $client = $this->makeClient(['credit_balance' => 10]);
        $order  = $this->makeOrder($client, ['credits_consumed' => 1]);

        DB::transaction(fn () => $this->service->debitForOrder(
            $this->lockedClient($client), $order
        ));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/already been debited/');

        DB::transaction(fn () => $this->service->debitForOrder(
            Client::where('id', $client->id)->lockForUpdate()->first(), $order
        ));
    }

    #[Test]
    public function test_debit_uses_credits_consumed_not_files_count(): void
    {
        // credits_consumed defaults to 1 even if files_count differs
        $client = $this->makeClient(['credit_balance' => 5]);
        $order  = $this->makeOrder($client, ['files_count' => 3, 'credits_consumed' => 3]);

        DB::transaction(fn () => $this->service->debitForOrder(
            $this->lockedClient($client), $order
        ));

        $this->assertSame(2, $client->fresh()->credit_balance);

        $tx = ClientCreditTransaction::where('client_id', $client->id)->first();
        $this->assertSame(-3, $tx->credits_delta);
    }

    // -----------------------------------------------------------------------
    // refundForOrder
    // -----------------------------------------------------------------------

    #[Test]
    public function test_refund_for_order_restores_credits_and_writes_ledger(): void
    {
        $client = $this->makeClient(['credit_balance' => 4]);
        $order  = $this->makeOrder($client, ['credits_consumed' => 1]);

        DB::transaction(fn () => $this->service->refundForOrder(
            $this->lockedClient($client), $order
        ));

        $client->refresh();
        $this->assertSame(5, $client->credit_balance);
        $this->assertNotNull($order->fresh()->credits_refunded_at);

        $tx = ClientCreditTransaction::where('client_id', $client->id)->first();
        $this->assertSame(ClientCreditTransaction::TYPE_REFUND_CREDIT, $tx->type);
        $this->assertSame(1, $tx->credits_delta);
        $this->assertSame(5, $tx->balance_after);
        $this->assertSame($order->id, $tx->order_id);
    }

    #[Test]
    public function test_refund_for_order_is_idempotent_by_timestamp(): void
    {
        $client = $this->makeClient(['credit_balance' => 4]);
        $order  = $this->makeOrder($client, ['credits_consumed' => 1, 'credits_refunded_at' => now()]);

        DB::transaction(function () use ($client, $order) {
            $result = $this->service->refundForOrder(
                $this->lockedClient($client), $order
            );
            $this->assertNull($result);
        });

        // Balance must not change
        $this->assertSame(4, $client->fresh()->credit_balance);
        $this->assertSame(0, ClientCreditTransaction::where('client_id', $client->id)->count());
    }

    #[Test]
    public function test_refund_for_order_is_idempotent_by_ledger_row(): void
    {
        $client = $this->makeClient(['credit_balance' => 4]);
        $order  = $this->makeOrder($client, ['credits_consumed' => 1]);

        // First refund
        DB::transaction(fn () => $this->service->refundForOrder(
            $this->lockedClient($client), $order
        ));

        $balanceAfterFirst = $client->fresh()->credit_balance;

        // Second refund — must be a no-op
        DB::transaction(fn () => $this->service->refundForOrder(
            $this->lockedClient($client->fresh()), $order->fresh()
        ));

        $this->assertSame($balanceAfterFirst, $client->fresh()->credit_balance);
        $this->assertSame(
            1,
            ClientCreditTransaction::where('client_id', $client->id)
                ->where('type', ClientCreditTransaction::TYPE_REFUND_CREDIT)
                ->count()
        );
    }

    // -----------------------------------------------------------------------
    // adjustCredits
    // -----------------------------------------------------------------------

    #[Test]
    public function test_adjust_credits_positive_delta_increases_balance(): void
    {
        $client = $this->makeClient(['credit_balance' => 5]);

        DB::transaction(fn () => $this->service->adjustCredits(
            $this->lockedClient($client), 3, 'Admin bonus'
        ));

        $this->assertSame(8, $client->fresh()->credit_balance);

        $tx = ClientCreditTransaction::where('client_id', $client->id)->first();
        $this->assertSame(ClientCreditTransaction::TYPE_MANUAL_ADJUSTMENT, $tx->type);
        $this->assertSame(3, $tx->credits_delta);
        $this->assertSame(8, $tx->balance_after);
        $this->assertSame('Admin bonus', $tx->notes);
    }

    #[Test]
    public function test_adjust_credits_negative_delta_decreases_balance(): void
    {
        $client = $this->makeClient(['credit_balance' => 10]);

        DB::transaction(fn () => $this->service->adjustCredits(
            $this->lockedClient($client), -4, 'Correction for duplicate credit', []
        ));

        $this->assertSame(6, $client->fresh()->credit_balance);

        $tx = ClientCreditTransaction::where('client_id', $client->id)->first();
        $this->assertSame(-4, $tx->credits_delta);
        $this->assertSame(6, $tx->balance_after);
    }

    #[Test]
    public function test_adjust_credits_rejects_zero_delta(): void
    {
        $client = $this->makeClient();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot be zero/');

        DB::transaction(fn () => $this->service->adjustCredits(
            $this->lockedClient($client), 0, 'some note'
        ));
    }

    #[Test]
    public function test_adjust_credits_rejects_empty_notes(): void
    {
        $client = $this->makeClient();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/[Nn]otes are required/');

        DB::transaction(fn () => $this->service->adjustCredits(
            $this->lockedClient($client), 5, '   '
        ));
    }

    #[Test]
    public function test_adjust_credits_prevents_negative_balance(): void
    {
        $client = $this->makeClient(['credit_balance' => 3]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/negative balance/');

        DB::transaction(fn () => $this->service->adjustCredits(
            $this->lockedClient($client), -10, 'Trying to over-deduct'
        ));
    }

    // -----------------------------------------------------------------------
    // getBalance
    // -----------------------------------------------------------------------

    #[Test]
    public function test_get_balance_returns_cached_credit_balance(): void
    {
        $client = $this->makeClient(['credit_balance' => 42]);

        $this->assertSame(42, $this->service->getBalance($client));
    }

    // -----------------------------------------------------------------------
    // Ledger integrity: balance_after always reflects running total
    // -----------------------------------------------------------------------

    #[Test]
    public function test_ledger_balance_after_tracks_running_total(): void
    {
        $client = $this->makeClient(['credit_balance' => 0]);

        // +10
        DB::transaction(fn () => $this->service->createOpeningBalance(
            $this->lockedClient($client), 10
        ));

        // +5
        DB::transaction(fn () => $this->service->creditClient(
            $this->lockedClient($client->fresh()), 5
        ));

        // -3
        $order = $this->makeOrder($client->fresh(), ['credits_consumed' => 3]);
        DB::transaction(fn () => $this->service->debitForOrder(
            $this->lockedClient($client->fresh()), $order
        ));

        $txs = ClientCreditTransaction::where('client_id', $client->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $txs);
        $this->assertSame(10, $txs[0]->balance_after);
        $this->assertSame(15, $txs[1]->balance_after);
        $this->assertSame(12, $txs[2]->balance_after);
        $this->assertSame(12, $client->fresh()->credit_balance);
    }
}

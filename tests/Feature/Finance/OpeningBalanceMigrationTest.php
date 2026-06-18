<?php

namespace Tests\Feature\Finance;

use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Services\Finance\OpeningBalanceMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OpeningBalanceMigrationTest extends TestCase
{
    use RefreshDatabase;

    private OpeningBalanceMigrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OpeningBalanceMigrationService::class);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeClient(string $name, int $slots, int $consumed, array $extra = []): Client
    {
        return Client::create(array_merge([
            'name'           => $name,
            'slots'          => $slots,
            'slots_consumed' => $consumed,
            'credit_balance' => 0,
            'status'         => 'active',
        ], $extra));
    }

    // -----------------------------------------------------------------------
    // dryRun()
    // -----------------------------------------------------------------------

    #[Test]
    public function test_dry_run_returns_all_clients_without_writing(): void
    {
        $this->makeClient('Alpha', slots: 10, consumed: 3);
        $this->makeClient('Beta',  slots: 5,  consumed: 5);

        $items = $this->service->dryRun();

        $this->assertCount(2, $items);
        $this->assertSame(0, ClientCreditTransaction::count(), 'dry-run must not write ledger rows');
        $this->assertSame(0, Client::whereNotNull('credits_migrated_at')->count(), 'dry-run must not set migrated_at');
    }

    #[Test]
    public function test_dry_run_shows_correct_remaining_slots(): void
    {
        $this->makeClient('Alpha', slots: 10, consumed: 3);

        $item = $this->service->dryRun()->first();

        $this->assertSame(10, $item['slots']);
        $this->assertSame(3, $item['slots_consumed']);
        $this->assertSame(7, $item['remaining_slots']);
        $this->assertSame('migrate', $item['action']);
        $this->assertFalse($item['already_migrated']);
    }

    #[Test]
    public function test_dry_run_marks_already_migrated_as_skip(): void
    {
        $this->makeClient('Already', slots: 5, consumed: 2, extra: ['credits_migrated_at' => now()]);

        $item = $this->service->dryRun()->first();

        $this->assertSame('skip', $item['action']);
        $this->assertTrue($item['already_migrated']);
    }

    // -----------------------------------------------------------------------
    // execute()
    // -----------------------------------------------------------------------

    #[Test]
    public function test_migration_sets_credit_balance_to_remaining_slots(): void
    {
        $client = $this->makeClient('Alpha', slots: 10, consumed: 3);

        $this->service->execute();

        $this->assertSame(7, $client->fresh()->credit_balance);
    }

    #[Test]
    public function test_migration_creates_opening_balance_ledger_row(): void
    {
        $client = $this->makeClient('Alpha', slots: 10, consumed: 3);

        $this->service->execute();

        $tx = ClientCreditTransaction::where('client_id', $client->id)->first();
        $this->assertNotNull($tx);
        $this->assertSame(ClientCreditTransaction::TYPE_OPENING_BALANCE, $tx->type);
        $this->assertSame(7, $tx->credits_delta);
        $this->assertSame(7, $tx->balance_after);
    }

    #[Test]
    public function test_migration_sets_credits_migrated_at(): void
    {
        $client = $this->makeClient('Alpha', slots: 10, consumed: 3);

        $this->service->execute();

        $this->assertNotNull($client->fresh()->credits_migrated_at);
    }

    #[Test]
    public function test_migration_skips_already_migrated_clients(): void
    {
        $already  = $this->makeClient('Done', slots: 5, consumed: 2, extra: ['credits_migrated_at' => now(), 'credit_balance' => 3]);
        $pending  = $this->makeClient('New',  slots: 8, consumed: 1);

        $result = $this->service->execute();

        $this->assertSame(1, $result['migrated']);
        $this->assertSame(1, $result['skipped']);

        // Already-migrated client must be untouched
        $this->assertSame(3, $already->fresh()->credit_balance);
        $this->assertSame(0, ClientCreditTransaction::where('client_id', $already->id)->count());

        // New client migrated correctly
        $this->assertSame(7, $pending->fresh()->credit_balance);
    }

    #[Test]
    public function test_migration_is_idempotent_running_twice(): void
    {
        $client = $this->makeClient('Alpha', slots: 10, consumed: 3);

        $this->service->execute();
        $this->service->execute(); // second run

        // Only one ledger row must exist
        $this->assertSame(1, ClientCreditTransaction::where('client_id', $client->id)->count());
        $this->assertSame(7, $client->fresh()->credit_balance);
    }

    #[Test]
    public function test_migration_handles_fully_consumed_client(): void
    {
        $client = $this->makeClient('Full', slots: 5, consumed: 5);

        $this->service->execute();

        $this->assertSame(0, $client->fresh()->credit_balance);

        $tx = ClientCreditTransaction::where('client_id', $client->id)->first();
        $this->assertSame(0, $tx->credits_delta);
        $this->assertSame(0, $tx->balance_after);
    }

    #[Test]
    public function test_migration_clamps_to_zero_when_consumed_exceeds_slots(): void
    {
        // Defensive: shouldn't happen in normal operation but must be safe
        $client = $this->makeClient('Broken', slots: 3, consumed: 6);

        $this->service->execute();

        $this->assertSame(0, $client->fresh()->credit_balance);
    }

    #[Test]
    public function test_migration_processes_multiple_clients(): void
    {
        $a = $this->makeClient('Alpha', slots: 10, consumed: 2);
        $b = $this->makeClient('Beta',  slots: 8,  consumed: 5);
        $c = $this->makeClient('Gamma', slots: 4,  consumed: 0);

        $result = $this->service->execute();

        $this->assertSame(3, $result['migrated']);
        $this->assertSame(0, $result['skipped']);

        $this->assertSame(8, $a->fresh()->credit_balance);
        $this->assertSame(3, $b->fresh()->credit_balance);
        $this->assertSame(4, $c->fresh()->credit_balance);
    }

    #[Test]
    public function test_migration_reports_migrated_and_skipped_counts(): void
    {
        $this->makeClient('New1', slots: 5, consumed: 0);
        $this->makeClient('New2', slots: 5, consumed: 0);
        $this->makeClient('Done', slots: 5, consumed: 0, extra: ['credits_migrated_at' => now()]);

        $result = $this->service->execute();

        $this->assertSame(2, $result['migrated']);
        $this->assertSame(1, $result['skipped']);
        $this->assertEmpty($result['errors']);
    }

    // -----------------------------------------------------------------------
    // Artisan command
    // -----------------------------------------------------------------------

    #[Test]
    public function test_artisan_command_dry_run_does_not_write(): void
    {
        $this->makeClient('Alpha', slots: 10, consumed: 3);

        $this->artisan('finance:migrate-opening-balances --legacy-force=yes --dry-run')
            ->assertSuccessful();

        $this->assertSame(0, ClientCreditTransaction::count());
        $this->assertSame(0, Client::whereNotNull('credits_migrated_at')->count());
    }

    #[Test]
    public function test_artisan_command_fails_without_confirm_flag(): void
    {
        $this->artisan('finance:migrate-opening-balances')
            ->assertFailed();
    }

    #[Test]
    public function test_artisan_command_with_confirm_yes_migrates(): void
    {
        $client = $this->makeClient('Alpha', slots: 6, consumed: 1);

        $this->artisan('finance:migrate-opening-balances --legacy-force=yes --confirm=yes')
            ->assertSuccessful();

        $this->assertSame(5, $client->fresh()->credit_balance);
        $this->assertSame(1, ClientCreditTransaction::count());
    }

    #[Test]
    public function test_artisan_command_confirm_no_does_not_write(): void
    {
        $this->makeClient('Alpha', slots: 6, consumed: 1);

        $this->artisan('finance:migrate-opening-balances --confirm=no')
            ->assertFailed();

        $this->assertSame(0, ClientCreditTransaction::count());
    }
}

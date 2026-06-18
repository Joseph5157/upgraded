<?php

namespace App\Services\Finance;

use App\Models\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * OpeningBalanceMigrationService
 *
 * Converts the old slots-based balance into the new credit ledger.
 *
 * Migration logic per client:
 *   remaining = max(0, slots - slots_consumed)
 *   → Set clients.credit_balance = remaining
 *   → Create client_credit_transactions row (type = opening_balance)
 *   → Set clients.credits_migrated_at = now()
 *
 * Idempotent: clients with credits_migrated_at already set are skipped.
 * Running the migration twice is safe and produces no duplicate rows.
 *
 * The old slots / slots_consumed columns are NOT modified or removed.
 * They remain as the source of truth for the existing upload flow until
 * Phase 4 explicitly switches the order creation to use credit_balance.
 */
class OpeningBalanceMigrationService
{
    public function __construct(
        protected ClientCreditService $creditService,
    ) {}

    /**
     * Preview what the migration would do — no writes.
     *
     * Each item in the returned collection has:
     *   client_id, client_name, slots, slots_consumed, remaining_slots,
     *   current_credit_balance, already_migrated, action
     *
     * @return Collection<int, array>
     */
    public function dryRun(): Collection
    {
        return Client::orderBy('id')->get()->map(function (Client $client) {
            $remaining = max(0, (int) $client->slots - (int) $client->slots_consumed);
            $migrated  = $client->credits_migrated_at !== null;

            return [
                'client_id'              => $client->id,
                'client_name'            => $client->name,
                'slots'                  => (int) $client->slots,
                'slots_consumed'         => (int) $client->slots_consumed,
                'remaining_slots'        => $remaining,
                'current_credit_balance' => (int) $client->credit_balance,
                'already_migrated'       => $migrated,
                'action'                 => $migrated ? 'skip' : 'migrate',
            ];
        });
    }

    /**
     * Execute the migration.
     *
     * Each client is processed in its own transaction so one failure
     * does not roll back other clients.
     *
     * @return array{migrated: int, skipped: int, errors: array}
     */
    public function execute(): array
    {
        $migrated = 0;
        $skipped  = 0;
        $errors   = [];

        // Use cursor() to avoid loading all clients into memory at once.
        foreach (Client::orderBy('id')->cursor() as $client) {
            // Quick pre-check before entering a transaction.
            if ($client->credits_migrated_at !== null) {
                $skipped++;
                continue;
            }

            try {
                $result = DB::transaction(function () use ($client): bool {
                    // Re-fetch with a row lock inside the transaction to guard
                    // against concurrent migration runs on the same client.
                    $locked = Client::where('id', $client->id)->lockForUpdate()->first();

                    if ($locked === null || $locked->credits_migrated_at !== null) {
                        return false; // already done or deleted since pre-check
                    }

                    $remaining = max(0, (int) $locked->slots - (int) $locked->slots_consumed);

                    $this->creditService->createOpeningBalance($locked, $remaining);

                    return true;
                });

                if ($result) {
                    $migrated++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $errors[] = [
                    'client_id'   => $client->id,
                    'client_name' => $client->name,
                    'error'       => $e->getMessage(),
                ];
            }
        }

        return compact('migrated', 'skipped', 'errors');
    }
}

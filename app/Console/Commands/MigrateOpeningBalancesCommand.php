<?php

namespace App\Console\Commands;

use App\Services\Finance\OpeningBalanceMigrationService;
use Illuminate\Console\Command;

/**
 * DEPRECATED FOR THIS PROJECT DIRECTION.
 *
 * This command converts old client slot balances into opening-balance
 * credit transactions. It is disabled by default because this project
 * is using a clean-slate credit approach:
 *
 *   - No old slot data will be migrated into credits.
 *   - Admin will manually record payments and add credits from scratch.
 *   - clients.credit_balance starts at 0 for all clients.
 *
 * The command is retained in case it is needed for emergency/manual use,
 * but it requires --legacy-force=yes to run even a dry-run.
 *
 * For a clean finance data reset, use instead:
 *   php artisan finance:reset-clean-slate --confirm=yes
 */
class MigrateOpeningBalancesCommand extends Command
{
    protected $signature = 'finance:migrate-opening-balances
                            {--dry-run : Preview what would be migrated without writing}
                            {--confirm=no : Set to "yes" to execute the migration}
                            {--legacy-force=no : Set to "yes" to bypass the clean-slate block (emergency only)}';

    protected $description = '[DISABLED] Convert old client slot balances into the new credit ledger (legacy migration — not used in clean-slate mode)';

    public function handle(OpeningBalanceMigrationService $service): int
    {
        // Block the command unless the caller explicitly acknowledges the deprecation.
        if ($this->option('legacy-force') !== 'yes') {
            $this->newLine();
            $this->line('<fg=yellow;options=bold>⚠  DISABLED — Clean-Slate Finance Mode</>');
            $this->newLine();
            $this->line('Opening balance migration is disabled because this project is using');
            $this->line('clean-slate credits. No old slots will be migrated.');
            $this->newLine();
            $this->line('Admin will manually add credits via the finance payment form (Phase 3).');
            $this->line('All clients start with credit_balance = 0.');
            $this->newLine();
            $this->line('To reset finance ledger data, use:');
            $this->line('  <comment>php artisan finance:reset-clean-slate --confirm=yes</comment>');
            $this->newLine();
            $this->line('To run this command anyway (emergency/manual use only):');
            $this->line('  <comment>php artisan finance:migrate-opening-balances --legacy-force=yes --dry-run</comment>');
            $this->newLine();
            return Command::FAILURE;
        }

        $this->newLine();
        $this->warn('LEGACY FORCE MODE — proceeding with slot-to-credit migration.');
        $this->newLine();

        if ($this->option('dry-run')) {
            return $this->runDryRun($service);
        }

        if ($this->option('confirm') !== 'yes') {
            $this->error('No action taken. Add --confirm=yes to execute.');
            $this->line('  php artisan finance:migrate-opening-balances --legacy-force=yes --confirm=yes');
            return Command::FAILURE;
        }

        return $this->runMigration($service);
    }

    private function runDryRun(OpeningBalanceMigrationService $service): int
    {
        $this->info('DRY RUN — no changes will be written to the database.');
        $this->newLine();

        $items = $service->dryRun();

        if ($items->isEmpty()) {
            $this->warn('No clients found.');
            return Command::SUCCESS;
        }

        $headers = ['ID', 'Client', 'Slots', 'Consumed', 'Remaining', 'Credit Balance', 'Action'];

        $rows = $items->map(fn (array $item) => [
            $item['client_id'],
            $item['client_name'],
            $item['slots'],
            $item['slots_consumed'],
            $item['remaining_slots'],
            $item['current_credit_balance'],
            strtoupper($item['action']),
        ])->all();

        $this->table($headers, $rows);

        $toMigrate = $items->where('action', 'migrate')->count();
        $toSkip    = $items->where('action', 'skip')->count();

        $this->newLine();
        $this->line("  Would migrate : <info>{$toMigrate}</info> client(s)");
        $this->line("  Would skip    : <comment>{$toSkip}</comment> client(s) (already migrated)");
        $this->newLine();
        $this->line('Run with <comment>--legacy-force=yes --confirm=yes</comment> to apply.');

        return Command::SUCCESS;
    }

    private function runMigration(OpeningBalanceMigrationService $service): int
    {
        $this->info('Running opening balance migration...');
        $this->newLine();

        $result = $service->execute();

        $this->line("  Migrated : <info>{$result['migrated']}</info>");
        $this->line("  Skipped  : <comment>{$result['skipped']}</comment>");

        if (! empty($result['errors'])) {
            $this->newLine();
            $this->error('The following clients encountered errors:');
            foreach ($result['errors'] as $err) {
                $this->warn("  Client #{$err['client_id']} ({$err['client_name']}): {$err['error']}");
            }
            $this->newLine();
            $this->error('Migration completed with errors. Review above before running again.');
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Opening balance migration completed successfully.');

        return Command::SUCCESS;
    }
}

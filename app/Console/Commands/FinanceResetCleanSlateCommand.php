<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * FinanceResetCleanSlateCommand
 *
 * Resets all finance ledger data while preserving users, clients, vendors,
 * orders, and uploaded files.
 *
 * What is cleared:
 *   - client_payments (all rows)
 *   - client_credit_transactions (all rows)
 *   - vendor_earning_transactions (all rows)
 *   - business_expenses (all rows)
 *   - clients.credit_balance → 0
 *   - clients.credits_migrated_at → null
 *   - users.pending_earning_balance → 0
 *   - users.approved_payable_balance → 0
 *   - orders financial snapshot columns → null/default
 *
 * What is NOT cleared:
 *   - users, clients, vendors (accounts preserved)
 *   - orders (order records preserved)
 *   - order_files, order_reports (uploaded files preserved)
 *   - vendor_payouts (preserved; void manually if needed)
 *   - topup_requests (preserved for history)
 *   - old clients.slots, clients.slots_consumed (not touched)
 *
 * Usage:
 *   php artisan finance:reset-clean-slate --confirm=yes
 *   php artisan finance:reset-clean-slate --confirm=yes --allow-production=yes
 */
class FinanceResetCleanSlateCommand extends Command
{
    protected $signature = 'finance:reset-clean-slate
                            {--confirm=no : Must be set to "yes" to execute}
                            {--allow-production=no : Must be set to "yes" to run in production environment}';

    protected $description = 'Reset all finance ledger data (keeps users/clients/vendors/orders/files)';

    public function handle(): int
    {
        $this->newLine();

        // Production guard
        if (app()->environment('production') && $this->option('allow-production') !== 'yes') {
            $this->error('BLOCKED: This command is running in the production environment.');
            $this->newLine();
            $this->line('If you really want to reset production finance data, add:');
            $this->line('  <comment>--allow-production=yes</comment>');
            $this->newLine();
            $this->warn('Make sure you have a database backup before proceeding.');
            $this->newLine();
            return Command::FAILURE;
        }

        // Confirmation guard
        if ($this->option('confirm') !== 'yes') {
            $this->line('<fg=yellow;options=bold>⚠  Finance Clean-Slate Reset</>');
            $this->newLine();
            $this->line('This command will permanently delete:');
            $this->line('  • All client_payments rows');
            $this->line('  • All client_credit_transactions rows');
            $this->line('  • All vendor_earning_transactions rows');
            $this->line('  • All business_expenses rows');
            $this->line('  • Reset clients.credit_balance to 0');
            $this->line('  • Reset clients.credits_migrated_at to null');
            $this->line('  • Reset users.pending_earning_balance to 0');
            $this->line('  • Reset users.approved_payable_balance to 0');
            $this->line('  • Clear order financial snapshot columns');
            $this->newLine();
            $this->line('This will NOT delete:');
            $this->line('  • Users, clients, vendors');
            $this->line('  • Orders (records kept, snapshots cleared)');
            $this->line('  • Uploaded files on disk');
            $this->line('  • Old slots/slots_consumed values');
            $this->newLine();

            if (app()->environment('production')) {
                $this->error('You are on PRODUCTION. Also add --allow-production=yes.');
            }

            $this->line('To run, add: <comment>--confirm=yes</comment>');
            $this->newLine();
            return Command::FAILURE;
        }

        // Double-check environment warning even if confirmed
        if (app()->environment('production')) {
            $this->warn('Running in PRODUCTION environment!');
            $this->newLine();
        }

        $this->info('Starting finance clean-slate reset...');
        $this->newLine();

        try {
            DB::transaction(function () {
                $this->resetFinanceLedgerTables();
                $this->resetClientBalances();
                $this->resetVendorBalances();
                $this->resetOrderSnapshots();
            });
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Reset failed and was rolled back: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Finance clean-slate reset completed successfully.');
        $this->newLine();
        $this->line('All ledger tables cleared. All credit/earning balances reset to 0.');
        $this->line('Users, clients, vendors, orders, and files are untouched.');
        $this->newLine();
        $this->line('Next step: use the admin finance panel to record client payments and add credits.');

        return Command::SUCCESS;
    }

    private function resetFinanceLedgerTables(): void
    {
        // Delete child tables before parents to respect FK constraints.
        // vendor_earning_transactions → vendor_payouts (FK vendor_payout_id)
        // client_credit_transactions  → client_payments (FK client_payment_id)

        $this->line('  Clearing vendor_earning_transactions...');
        DB::table('vendor_earning_transactions')->delete();

        $this->line('  Clearing client_credit_transactions...');
        DB::table('client_credit_transactions')->delete();

        $this->line('  Clearing client_payments...');
        DB::table('client_payments')->delete();

        $this->line('  Clearing business_expenses...');
        DB::table('business_expenses')->delete();
    }

    private function resetClientBalances(): void
    {
        $this->line('  Resetting clients.credit_balance and credits_migrated_at...');
        DB::table('clients')->update([
            'credit_balance'      => 0,
            'credits_migrated_at' => null,
        ]);
    }

    private function resetVendorBalances(): void
    {
        $this->line('  Resetting vendor balance columns on users...');
        DB::table('users')->update([
            'pending_earning_balance'  => 0,
            'approved_payable_balance' => 0,
        ]);
    }

    private function resetOrderSnapshots(): void
    {
        $this->line('  Clearing order financial snapshot columns...');
        DB::table('orders')->update([
            'credits_consumed'     => 1,   // restore default
            'client_rate_per_file' => null,
            'client_amount'        => null,
            'vendor_rate_per_file' => null,
            'vendor_amount'        => null,
            'gross_profit'         => null,
            'financial_locked_at'  => null,
            'vendor_submitted_at'  => null,
            'vendor_approved_at'   => null,
            'vendor_rejected_at'   => null,
            'credits_refunded_at'  => null,
        ]);
    }
}

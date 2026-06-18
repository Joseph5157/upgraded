<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — vendor_earning_transactions
 *
 * Ledger of every vendor earning event. The balances cached on
 * users.pending_earning_balance and users.approved_payable_balance must
 * equal the running sums of amount_delta for the relevant type groupings.
 *
 * type values:
 *   pending_order_earning — created when vendor uploads completed report
 *   approve_earning       — created when admin approves vendor work
 *   payout                — negative delta created when payout is recorded
 *   reversal              — negative delta when report is rejected/cancelled
 *   manual_adjustment     — admin one-off correction (requires notes)
 *   correction            — system-generated correction (requires notes)
 *
 * status values:
 *   posted — normal live row
 *   voided — nullified without deletion (preferred over hard delete)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_earning_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('order_id')
                  ->nullable()
                  ->constrained('orders')
                  ->nullOnDelete();

            $table->foreignId('vendor_payout_id')
                  ->nullable()
                  ->constrained('vendor_payouts')
                  ->nullOnDelete();

            // pending_order_earning | approve_earning | payout
            // | reversal | manual_adjustment | correction
            $table->string('type');

            // posted | voided
            $table->string('status')->default('posted');

            // Positive = earning added, negative = earning deducted
            $table->decimal('amount_delta', 12, 2);

            // Running snapshots after this transaction
            $table->decimal('pending_balance_after', 12, 2)->nullable();
            $table->decimal('approved_balance_after', 12, 2)->nullable();

            $table->unsignedInteger('files_count')->default(1);
            $table->decimal('rate_per_file', 10, 2)->nullable();

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
            $table->index('order_id');
            $table->index('vendor_payout_id');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_earning_transactions');
    }
};

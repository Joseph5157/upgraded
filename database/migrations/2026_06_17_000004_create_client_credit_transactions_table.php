<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — client_credit_transactions
 *
 * Ledger of every credit movement for a client. The balance cached on
 * clients.credit_balance must always equal the running sum of credits_delta
 * in this table for that client.
 *
 * type values:
 *   opening_balance   — migrated from old slots balance
 *   payment_credit    — credits added when admin records a client payment
 *   order_debit       — credits consumed when client uploads an order
 *   refund_credit     — credits returned when order is cancelled
 *   manual_adjustment — admin one-off correction (requires notes)
 *   correction        — system-generated correction entry (requires notes)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_credit_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                  ->constrained('clients')
                  ->cascadeOnDelete();

            $table->foreignId('order_id')
                  ->nullable()
                  ->constrained('orders')
                  ->nullOnDelete();

            $table->foreignId('client_payment_id')
                  ->nullable()
                  ->constrained('client_payments')
                  ->nullOnDelete();

            // opening_balance | payment_credit | order_debit | refund_credit
            // | manual_adjustment | correction
            $table->string('type');

            // Positive = credits added, negative = credits consumed
            $table->integer('credits_delta');

            // Running balance after this transaction (snapshot for audit)
            $table->unsignedInteger('balance_after');

            $table->decimal('rate_per_credit', 10, 2)->nullable();
            $table->decimal('money_value', 12, 2)->nullable();

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['client_id', 'created_at']);
            $table->index('order_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_credit_transactions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — client_payments
 *
 * Records every money-in event from a client: UPI, bank transfer, cash,
 * or Razorpay. Each payment credits a number of file-check credits.
 *
 * FK references clients.id (not users.id) because the existing project
 * keeps client entities in a separate `clients` table and links users to
 * clients via users.client_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                  ->constrained('clients')
                  ->cascadeOnDelete();

            $table->decimal('amount_received', 12, 2);
            $table->unsignedInteger('credits_added');
            $table->decimal('rate_per_credit', 10, 2);

            // upi | bank_transfer | cash | razorpay
            $table->string('payment_mode');

            $table->string('transaction_id')->nullable();
            $table->timestamp('received_at');

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->text('notes')->nullable();

            // confirmed | voided | refunded
            $table->string('status')->default('confirmed');

            $table->timestamps();

            $table->index(['client_id', 'received_at']);
            $table->index(['payment_mode', 'received_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_payments');
    }
};

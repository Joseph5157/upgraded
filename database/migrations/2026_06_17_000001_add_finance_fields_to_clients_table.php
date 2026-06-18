<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Add credit balance and migration tracking to clients.
 *
 * credit_balance : denormalised cache of the credit ledger balance.
 *                  Source of truth is client_credit_transactions; this is
 *                  kept in sync by ClientCreditService. Default 0 so
 *                  existing rows are valid immediately after migration.
 *
 * credits_migrated_at : set by the artisan command that converts old slots
 *                        into opening-balance credit transactions. Used to
 *                        make the command idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedInteger('credit_balance')->default(0)->after('slots_consumed');
            $table->timestamp('credits_migrated_at')->nullable()->after('credit_balance');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['credit_balance', 'credits_migrated_at']);
        });
    }
};

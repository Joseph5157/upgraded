<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Extend vendor_payouts with payment_mode, paid_by, and status.
 *
 * payment_mode — how the payout was made: upi | bank_transfer | cash
 * paid_by      — admin user who recorded the payout (nullable; nullOnDelete
 *                so payout records survive admin account deletion)
 * status       — paid | voided (never hard-delete payout records)
 *
 * All columns are nullable / have defaults so existing rows remain valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_payouts', function (Blueprint $table) {
            $table->string('payment_mode')->nullable()->after('reference_id');

            $table->foreignId('paid_by')
                  ->nullable()
                  ->after('payment_mode')
                  ->constrained('users')
                  ->nullOnDelete();

            // paid | voided
            $table->string('status')->default('paid')->after('paid_by');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_payouts', function (Blueprint $table) {
            $table->dropForeign(['paid_by']);
            $table->dropColumn(['payment_mode', 'paid_by', 'status']);
        });
    }
};

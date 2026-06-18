<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Add vendor payable balance columns to users.
 *
 * These columns are relevant only when users.role = 'vendor'.
 * They are denormalised caches maintained by VendorEarningService.
 *
 * pending_earning_balance  : sum of earnings awaiting admin approval.
 * approved_payable_balance : sum of approved earnings not yet paid out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('pending_earning_balance', 12, 2)->default(0)->after('payout_rate');
            $table->decimal('approved_payable_balance', 12, 2)->default(0)->after('pending_earning_balance');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pending_earning_balance', 'approved_payable_balance']);
        });
    }
};

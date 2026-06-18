<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Financial snapshot columns on orders.
 *
 * These values are written once at order creation / vendor submission /
 * admin approval and must never be recalculated from current rates.
 * Rate changes after the fact must not affect these stored snapshots.
 *
 * credits_consumed      — how many credits this order used (default 1)
 * client_rate_per_file  — snapshot of client.price_per_file at creation time
 * client_amount         — credits_consumed × client_rate_per_file
 * vendor_rate_per_file  — snapshot of vendor.payout_rate at submission time
 * vendor_amount         — files_count × vendor_rate_per_file
 * gross_profit          — client_amount − vendor_amount (set at approval)
 * financial_locked_at   — when gross_profit was finalised (order delivered/approved)
 * vendor_submitted_at   — when vendor uploaded completed report
 * vendor_approved_at    — when admin approved vendor report
 * vendor_rejected_at    — when admin rejected vendor report
 * credits_refunded_at   — when credits were refunded on cancellation (idempotency guard)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('credits_consumed')->default(1)->after('files_count');
            $table->decimal('client_rate_per_file', 10, 2)->nullable()->after('credits_consumed');
            $table->decimal('client_amount', 12, 2)->nullable()->after('client_rate_per_file');
            $table->decimal('vendor_rate_per_file', 10, 2)->nullable()->after('client_amount');
            $table->decimal('vendor_amount', 12, 2)->nullable()->after('vendor_rate_per_file');
            $table->decimal('gross_profit', 12, 2)->nullable()->after('vendor_amount');
            $table->timestamp('financial_locked_at')->nullable()->after('gross_profit');
            $table->timestamp('vendor_submitted_at')->nullable()->after('financial_locked_at');
            $table->timestamp('vendor_approved_at')->nullable()->after('vendor_submitted_at');
            $table->timestamp('vendor_rejected_at')->nullable()->after('vendor_approved_at');
            $table->timestamp('credits_refunded_at')->nullable()->after('vendor_rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'credits_consumed',
                'client_rate_per_file',
                'client_amount',
                'vendor_rate_per_file',
                'vendor_amount',
                'gross_profit',
                'financial_locked_at',
                'vendor_submitted_at',
                'vendor_approved_at',
                'vendor_rejected_at',
                'credits_refunded_at',
            ]);
        });
    }
};

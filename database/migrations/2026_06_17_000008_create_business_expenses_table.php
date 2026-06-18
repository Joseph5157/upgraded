<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — business_expenses
 *
 * Tracks non-vendor outgoings: salaries, software subscriptions, payment
 * gateway fees, hosting, etc.  Expenses reduce net profit and cash balance
 * on the finance dashboard.
 *
 * category values:
 *   staff_salary | software | razorpay_charges | hosting
 *   | internet_domain | other
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_expenses', function (Blueprint $table) {
            $table->id();

            // staff_salary | software | razorpay_charges | hosting
            // | internet_domain | other
            $table->string('category');

            $table->decimal('amount', 12, 2);

            // upi | bank_transfer | cash | card | auto_deducted
            $table->string('payment_mode')->nullable();

            $table->string('reference_id')->nullable();
            $table->date('expense_date');

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['category', 'expense_date']);
            $table->index('expense_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_expenses');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_ledgers', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('vendor_payouts', 12, 2)->default(0);
            $table->decimal('operational_costs', 12, 2)->default(0);
            $table->decimal('net_profit', 12, 2)->default(0);
            $table->json('client_breakdown')->nullable();
            $table->json('vendor_breakdown')->nullable();
            $table->integer('total_orders')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_ledgers');
    }
};

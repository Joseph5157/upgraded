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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('ai_percentage', 5, 2)->nullable()->after('status');
            $table->decimal('plag_percentage', 5, 2)->nullable()->after('ai_percentage');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->integer('slots')->default(0)->after('name');
            $table->timestamp('plan_expiry')->nullable()->after('slots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['ai_percentage', 'plag_percentage']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['slots', 'plan_expiry']);
        });
    }
};

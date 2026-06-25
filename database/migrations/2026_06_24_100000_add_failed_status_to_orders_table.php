<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite uses a plain string column — no ENUM to alter.
            // The new columns are still added below.
        } else {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','claimed','processing','delivered','cancelled','failed') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('orders', function ($table) {
            $table->timestamp('failed_at')->nullable()->after('delivered_at');
            $table->string('failure_reason', 500)->nullable()->after('failed_at');
            $table->unsignedBigInteger('failed_by')->nullable()->after('failure_reason');

            $table->foreign('failed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function ($table) {
            $table->dropForeign(['failed_by']);
            $table->dropColumn(['failed_at', 'failure_reason', 'failed_by']);
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','claimed','processing','delivered','cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};

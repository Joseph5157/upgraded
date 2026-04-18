<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('delivered_orders_count')->default(0)->after('role');
        });

        // Backfill existing vendors with their current delivered count.
        // Using DB::table() rather than Eloquent models so this migration remains
        // stable even if the models are renamed, removed, or their casts change.
        DB::table('users')->where('role', 'vendor')->orderBy('id')->each(function ($vendor) {
            $count = DB::table('orders')
                ->where('claimed_by', $vendor->id)
                ->where('status', 'delivered')
                ->count();
            DB::table('users')
                ->where('id', $vendor->id)
                ->update(['delivered_orders_count' => $count]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('delivered_orders_count');
        });
    }
};

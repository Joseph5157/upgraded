<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Order;
use App\Enums\OrderStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('delivered_orders_count')->default(0)->after('role');
        });

        // Backfill existing vendors with their current delivered count
        User::where('role', 'vendor')->each(function ($vendor) {
            $count = Order::where('claimed_by', $vendor->id)
                ->where('status', OrderStatus::Delivered)
                ->count();
            $vendor->update(['delivered_orders_count' => $count]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('delivered_orders_count');
        });
    }
};

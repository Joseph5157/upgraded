<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Tracks how many times a vendor was released off this order.
            // If > 0, a vendor already submitted the files to Turnitin —
            // no automatic credit-slot refund should be issued.
            $table->unsignedTinyInteger('release_count')->default(0)->after('claimed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('release_count');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateOrderIds = DB::table('order_reports')
            ->select('order_id')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('order_id');

        foreach ($duplicateOrderIds as $orderId) {
            $duplicateIds = DB::table('order_reports')
                ->where('order_id', $orderId)
                ->orderByDesc('id')
                ->skip(1)
                ->pluck('id');

            if ($duplicateIds->isNotEmpty()) {
                DB::table('order_reports')->whereIn('id', $duplicateIds)->delete();
            }
        }

        Schema::table('order_reports', function (Blueprint $table) {
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
        });
    }
};

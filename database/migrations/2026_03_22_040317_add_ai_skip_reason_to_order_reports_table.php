<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->string('ai_skip_reason')->nullable()->after('ai_report_disk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->dropColumn('ai_skip_reason');
        });
    }
};

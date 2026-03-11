<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->renameColumn('report_path', 'ai_report_path');
            $table->string('plag_report_path')->nullable()->after('ai_report_path');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['ai_percentage', 'plag_percentage']);
        });
    }

    public function down(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->renameColumn('ai_report_path', 'report_path');
            $table->dropColumn('plag_report_path');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('ai_percentage', 5, 2)->nullable();
            $table->decimal('plag_percentage', 5, 2)->nullable();
        });
    }
};

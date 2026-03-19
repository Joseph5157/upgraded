<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_files', function (Blueprint $table) {
            $table->string('disk')->default('r2')->after('file_path');
        });

        Schema::table('order_reports', function (Blueprint $table) {
            $table->string('ai_report_disk')->default('r2')->after('ai_report_path');
            $table->string('plag_report_disk')->default('r2')->after('plag_report_path');
        });

        DB::table('order_files')->whereNull('disk')->update(['disk' => 'r2']);
        DB::table('order_reports')->whereNull('ai_report_disk')->update(['ai_report_disk' => 'r2']);
        DB::table('order_reports')->whereNull('plag_report_disk')->update(['plag_report_disk' => 'r2']);
    }

    public function down(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->dropColumn(['ai_report_disk', 'plag_report_disk']);
        });

        Schema::table('order_files', function (Blueprint $table) {
            $table->dropColumn('disk');
        });
    }
};
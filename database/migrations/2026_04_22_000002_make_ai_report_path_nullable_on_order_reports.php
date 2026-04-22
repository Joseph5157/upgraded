<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->string('ai_report_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_reports', function (Blueprint $table) {
            $table->string('ai_report_path')->nullable(false)->change();
        });
    }
};

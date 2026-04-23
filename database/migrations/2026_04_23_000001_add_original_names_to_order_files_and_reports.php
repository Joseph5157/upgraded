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
            $table->string('original_name')->nullable()->after('file_path');
        });

        Schema::table('order_reports', function (Blueprint $table) {
            $table->string('ai_report_original_name')->nullable()->after('ai_report_path');
            $table->string('plag_report_original_name')->nullable()->after('plag_report_path');
        });

        DB::table('order_files')
            ->select('id', 'file_path')
            ->whereNull('original_name')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('order_files')
                        ->where('id', $row->id)
                        ->update(['original_name' => basename((string) $row->file_path)]);
                }
            });

        DB::table('order_reports')
            ->select('id', 'ai_report_path', 'plag_report_path')
            ->whereNull('ai_report_original_name')
            ->orWhereNull('plag_report_original_name')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('order_reports')
                        ->where('id', $row->id)
                        ->update([
                            'ai_report_original_name' => $row->ai_report_path ? basename((string) $row->ai_report_path) : null,
                            'plag_report_original_name' => $row->plag_report_path ? basename((string) $row->plag_report_path) : null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('order_files', function (Blueprint $table) {
            $table->dropColumn('original_name');
        });

        Schema::table('order_reports', function (Blueprint $table) {
            $table->dropColumn(['ai_report_original_name', 'plag_report_original_name']);
        });
    }
};

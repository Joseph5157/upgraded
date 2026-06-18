<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_expenses', function (Blueprint $table) {
            $table->string('status')->default('active')->after('notes');
            $table->timestamp('voided_at')->nullable()->after('status');
            $table->foreignId('voided_by')->nullable()->after('voided_at')
                  ->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable()->after('voided_by');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('business_expenses', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'voided_at', 'voided_by', 'void_reason']);
        });
    }
};

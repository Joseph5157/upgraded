<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_invites', function (Blueprint $table) {
            $table->decimal('price_per_file', 8, 2)->nullable()->after('slots');
        });
    }

    public function down(): void
    {
        Schema::table('pending_invites', function (Blueprint $table) {
            $table->dropColumn('price_per_file');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('portal_number')->nullable()->unique()->after('id');
            $table->string('otp')->nullable()->after('remember_token');
            $table->timestamp('otp_expires_at')->nullable()->after('otp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['portal_number']);
            $table->dropColumn(['portal_number', 'otp', 'otp_expires_at']);
        });
    }
};

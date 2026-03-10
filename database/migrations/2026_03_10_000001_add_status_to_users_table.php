<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['active', 'frozen'])->default('active')->after('role');
            $table->timestamp('frozen_at')->nullable()->after('status');
            $table->string('frozen_reason', 255)->nullable()->after('frozen_at');
            $table->softDeletes()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'frozen_at', 'frozen_reason', 'deleted_at']);
        });
    }
};

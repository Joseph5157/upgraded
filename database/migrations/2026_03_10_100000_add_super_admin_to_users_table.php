<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('role');
            $table->unsignedBigInteger('admin_created_by')->nullable()->after('is_super_admin');
            $table->string('admin_creation_token')->nullable()->after('admin_created_by');
            $table->timestamp('admin_token_expires_at')->nullable()->after('admin_creation_token');
            $table->timestamp('last_login_at')->nullable()->after('admin_token_expires_at');
            $table->string('last_login_ip')->nullable()->after('last_login_at');

            $table->foreign('admin_created_by')
                  ->references('id')->on('users')
                  ->nullOnDelete();
        });

        Schema::create('admin_creation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('target_user_id')->nullable();
            $table->enum('action', ['created', 'deleted', 'frozen', 'unfrozen']);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('created_by_user_id')
                  ->references('id')->on('users')
                  ->nullOnDelete();
            $table->foreign('target_user_id')
                  ->references('id')->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['admin_created_by']);
            $table->dropColumn([
                'is_super_admin',
                'admin_created_by',
                'admin_creation_token',
                'admin_token_expires_at',
                'last_login_at',
                'last_login_ip',
            ]);
        });

        Schema::dropIfExists('admin_creation_logs');
    }
};

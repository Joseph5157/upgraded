<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('claimed_by');
            $table->index('due_at');
            $table->index('delivered_at');
            $table->index('source');
            $table->index('created_by_user_id');
        });

        Schema::table('client_links', function (Blueprint $table) {
            // token is queried on every public upload — ensure it is indexed
            $table->index('token');
            $table->index('is_active');
        });

        Schema::table('topup_requests', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['claimed_by']);
            $table->dropIndex(['due_at']);
            $table->dropIndex(['delivered_at']);
            $table->dropIndex(['source']);
            $table->dropIndex(['created_by_user_id']);
        });

        Schema::table('client_links', function (Blueprint $table) {
            $table->dropIndex(['token']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('topup_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};

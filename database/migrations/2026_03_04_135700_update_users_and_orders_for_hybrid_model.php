<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('role')->constrained()->onDelete('set null');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('source', ['account', 'link'])->default('link')->after('client_id');
            $table->foreignId('created_by_user_id')->nullable()->after('source')->constrained('users')->onDelete('set null');
            $table->foreignId('client_link_id')->nullable()->after('created_by_user_id')->constrained('client_links')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropForeign(['client_link_id']);
            $table->dropColumn(['source', 'created_by_user_id', 'client_link_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};

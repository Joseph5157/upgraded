<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('client_links', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->after('client_id')->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by_user_id')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable()->after('revoked_by_user_id');
            $table->timestamp('expires_at')->nullable()->after('revoked_at');
            $table->timestamp('last_used_at')->nullable()->after('expires_at');
        });

        DB::table('client_links')
            ->orderBy('id')
            ->select(['id', 'created_at'])
            ->chunkById(100, function ($links) {
                foreach ($links as $link) {
                    if ($link->created_at !== null) {
                        DB::table('client_links')
                            ->where('id', $link->id)
                            ->update(['expires_at' => Carbon::parse($link->created_at)->addDay()]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('client_links', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropConstrainedForeignId('revoked_by_user_id');
            $table->dropColumn(['revoked_at', 'expires_at', 'last_used_at']);
        });
    }
};

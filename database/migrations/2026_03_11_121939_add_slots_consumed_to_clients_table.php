<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Client;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->integer('slots_consumed')->default(0)->after('slots');
        });

        // Backfill: set slots_consumed = current order count for all clients
        Client::each(function ($client) {
            $client->update([
                'slots_consumed' => $client->orders()->count()
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('slots_consumed');
        });
    }
};

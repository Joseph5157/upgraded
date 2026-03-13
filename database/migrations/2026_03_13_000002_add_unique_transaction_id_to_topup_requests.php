<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topup_requests', function (Blueprint $table) {
            // Prevents the same UTR/transaction reference being submitted twice.
            // A legitimate UPI payment always has a unique UTR number.
            $table->unique('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('topup_requests', function (Blueprint $table) {
            $table->dropUnique(['transaction_id']);
        });
    }
};

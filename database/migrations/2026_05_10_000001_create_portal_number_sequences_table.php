<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_number_sequences', function (Blueprint $table) {
            $table->string('role', 20)->primary();
            $table->unsignedInteger('next_number')->default(1);
        });

        // Seed starting values that match the historical defaults used in BotController.
        // next_number is the value that will be assigned to the NEXT activated user.
        // We derive sensible defaults from the MAX already in the users table, falling
        // back to the floor values used before this migration.
        $roles = [
            'client' => 1000,
            'vendor' => 5000,
            'admin'  => 9000,
        ];

        foreach ($roles as $role => $floor) {
            $max = DB::table('users')
                ->where('role', $role)
                ->whereNotNull('portal_number')
                ->max('portal_number');

            $nextNumber = $max !== null ? (int) $max + 1 : $floor;

            DB::table('portal_number_sequences')->insert([
                'role'        => $role,
                'next_number' => $nextNumber,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_number_sequences');
    }
};

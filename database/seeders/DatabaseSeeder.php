<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientLink;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('Starting database seeding...');

        // ── Wipe users ───────────────────────────────────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Admin ────────────────────────────────────────────────────────────
        User::create([
            'name'              => 'Admin',
            'role'              => 'admin',
            'email'             => null,
            'password'          => null,
            'telegram_chat_id'  => '8421545440',
            'activated_at'      => now(),
            'is_super_admin'    => true,
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        $this->command->info('Admin seeded (telegram_chat_id: 8421545440).');

        // ── Default client + upload link (dev convenience) ───────────────────
        $client = Client::firstOrCreate(
            ['name' => 'Default Client'],
            ['name' => 'Default Client']
        );

        ClientLink::firstOrCreate(
            ['token' => 'test-token'],
            [
                'client_id' => $client->id,
                'token'     => 'test-token',
                'is_active' => true,
            ]
        );

        $this->command->info('Default client + upload link ready (token: test-token).');

        // ── Promote super admin ──────────────────────────────────────────────
        $this->call([
            SuperAdminSeeder::class,
        ]);

        $this->command->info('Seeding complete.');
    }
}

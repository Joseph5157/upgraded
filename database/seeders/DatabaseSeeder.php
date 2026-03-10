<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\ClientLink;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        // Create Vendor
        User::factory()->create([
            'name' => 'Vendor User',
            'email' => 'vendor@example.com',
            'role' => 'vendor',
        ]);

        // Create Client and Link
        $client = Client::create(['name' => 'Default Client']);
        ClientLink::create([
            'client_id' => $client->id,
            'token' => 'test-token',
            'is_active' => 1
        ]);
    }
}

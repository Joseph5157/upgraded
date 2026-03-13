<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\ClientLink;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        // Create Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'role' => 'admin',
                'password' => 'password',
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );
        $this->command->info("✅ Admin created: {$admin->email} (password: password)");

        // Create Vendor User
        $vendor = User::updateOrCreate(
            ['email' => 'vendor@example.com'],
            [
                'name' => 'Vendor User',
                'role' => 'vendor',
                'password' => 'password',
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );
        $this->command->info("✅ Vendor created: {$vendor->email} (password: password)");

        // Create Client and Link
        $client = Client::firstOrCreate(
            ['name' => 'Default Client'],
            ['name' => 'Default Client']
        );
        $this->command->info("✅ Client created: {$client->name}");

        $link = ClientLink::firstOrCreate(
            ['token' => 'test-token'],
            [
                'client_id' => $client->id,
                'token' => 'test-token',
                'is_active' => 1
            ]
        );
        $this->command->info("✅ Client link created with token: {$link->token}");

        // Promote admin to super admin
        $this->call([
            SuperAdminSeeder::class,
        ]);

        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->warn('📧 Login credentials:');
        $this->command->line("   Email: admin@example.com");
        $this->command->line("   Password: password");
        $this->command->newLine();
    }
}

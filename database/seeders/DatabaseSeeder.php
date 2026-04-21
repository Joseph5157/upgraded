<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('🌱 Seeding admin account...');

        $admin = User::updateOrCreate(
            ['portal_number' => 9001],
            [
                'name'              => 'Admin',
                'role'              => 'admin',
                'email'             => null,
                'password'          => null,
                'telegram_chat_id'  => 8421545440,
                'portal_number'     => 9001,
                'activated_at'      => now(),
                'is_super_admin'    => true,
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Admin created. Portal ID: 9001');
        $this->command->info('🎉 Seeding complete. Login at /login with Portal ID 9001.');
    }
}

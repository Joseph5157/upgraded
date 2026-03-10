<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        if (! $admin) {
            $this->command->error('No admin user found. Create an admin account first.');
            return;
        }

        if ($admin->is_super_admin) {
            $this->command->warn("User [{$admin->email}] is already SYSTEM_ROOT.");
            return;
        }

        $admin->update(['is_super_admin' => true]);

        $this->command->info("Promoted [{$admin->email}] to SYSTEM_ROOT (super admin).");
    }
}

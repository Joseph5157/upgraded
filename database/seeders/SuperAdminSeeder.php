<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $admin = User::where('role', 'admin')->first();

            if (! $admin) {
                $this->command->warn('No admin user found — skipping super admin promotion.');
                return;
            }

            if ($admin->is_super_admin) {
                $this->command->info("Admin [{$admin->name}] is already super admin.");
                return;
            }

            $admin->update(['is_super_admin' => true]);

            $this->command->info("Promoted [{$admin->name}] to super admin.");
        } catch (\Exception $e) {
            $this->command->error("Failed to promote super admin: {$e->getMessage()}");
        }
    }
}

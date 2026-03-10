<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteSuperAdmin extends Command
{
    protected $signature = 'admin:promote-super {email : The email address of the admin to promote}';

    protected $description = 'Promote an admin user to SYSTEM_ROOT (super admin)';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email: {$email}");
            return Command::FAILURE;
        }

        if ($user->role !== 'admin') {
            $this->error("User [{$email}] is not an admin. Only admins can be promoted to SYSTEM_ROOT.");
            return Command::FAILURE;
        }

        if ($user->is_super_admin) {
            $this->warn("User [{$email}] is already SYSTEM_ROOT.");
            return Command::SUCCESS;
        }

        $existing = User::where('is_super_admin', true)->first();
        if ($existing) {
            $this->error("SYSTEM_ROOT already exists: [{$existing->email}]. Only one super admin is allowed.");
            $this->line("To transfer SYSTEM_ROOT, first demote the current super admin.");
            return Command::FAILURE;
        }

        if (! $this->confirm("Promote [{$email}] to SYSTEM_ROOT? This grants full system privileges.")) {
            $this->info('Aborted.');
            return Command::SUCCESS;
        }

        $user->update(['is_super_admin' => true]);

        $this->info("Successfully promoted [{$email}] to SYSTEM_ROOT.");
        return Command::SUCCESS;
    }
}

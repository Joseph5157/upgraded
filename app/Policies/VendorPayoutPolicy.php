<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorPayout;

class VendorPayoutPolicy
{
    /**
     * Determine if the user can view the payout list.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can create (record) a new payout.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can view a specific payout record.
     */
    public function view(User $user, VendorPayout $vendorPayout): bool
    {
        // Admin can view all; vendors can view their own
        return $user->role === 'admin'
            || $user->id === $vendorPayout->user_id;
    }
}

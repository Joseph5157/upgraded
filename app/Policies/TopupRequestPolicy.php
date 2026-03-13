<?php

namespace App\Policies;

use App\Models\TopupRequest;
use App\Models\User;

class TopupRequestPolicy
{
    /**
     * Only admins can approve a topup request.
     */
    public function approve(User $user, TopupRequest $topupRequest): bool
    {
        return $user->role === 'admin' && $topupRequest->status === 'pending';
    }

    /**
     * Only admins can reject a topup request.
     */
    public function reject(User $user, TopupRequest $topupRequest): bool
    {
        return $user->role === 'admin' && $topupRequest->status === 'pending';
    }

    /**
     * Only admins can view the topup request list.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }
}

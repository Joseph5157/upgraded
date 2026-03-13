<?php

namespace App\Policies;

use App\Models\RefundRequest;
use App\Models\User;

class RefundRequestPolicy
{
    /**
     * Determine if the user can view all refund requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if the client user can create a refund request for their own order.
     * The order ownership check is enforced in the controller.
     */
    public function create(User $user): bool
    {
        return $user->role === 'client';
    }

    /**
     * Determine if the admin can approve a refund request.
     */
    public function approve(User $user, RefundRequest $refundRequest): bool
    {
        return $user->role === 'admin' && $refundRequest->status === 'pending';
    }

    /**
     * Determine if the admin can reject a refund request.
     */
    public function reject(User $user, RefundRequest $refundRequest): bool
    {
        return $user->role === 'admin' && $refundRequest->status === 'pending';
    }
}

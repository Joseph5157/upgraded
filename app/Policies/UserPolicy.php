<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the authenticated user can create an account with the given role.
     * Usage: $this->authorize('create', [User::class, $role])
     */
    public function create(User $authenticatedUser, string $role): bool
    {
        return match ($role) {
            'admin'          => $authenticatedUser->isSuperAdmin(),
            'vendor', 'client' => $authenticatedUser->role === 'admin',
            default          => false,
        };
    }

    /**
     * Determine if the authenticated user can freeze the target user's account.
     */
    public function freeze(User $authenticatedUser, User $targetUser): bool
    {
        // Cannot freeze yourself
        if ($authenticatedUser->id === $targetUser->id) {
            return false;
        }

        // Cannot freeze the super admin
        if ($targetUser->isSuperAdmin()) {
            return false;
        }

        // Only super admin can freeze other admin accounts
        if ($targetUser->role === 'admin') {
            return $authenticatedUser->isSuperAdmin();
        }

        // Any admin can freeze vendors and clients
        return $authenticatedUser->role === 'admin';
    }

    /**
     * Determine if the authenticated user can unfreeze the target user's account.
     */
    public function unfreeze(User $authenticatedUser, User $targetUser): bool
    {
        // Reuse the same permission logic as freeze
        return $this->freeze($authenticatedUser, $targetUser);
    }

    /**
     * Determine if the authenticated user can soft-delete the target user's account.
     */
    public function delete(User $authenticatedUser, User $targetUser): bool
    {
        // Cannot delete yourself
        if ($authenticatedUser->id === $targetUser->id) {
            return false;
        }

        // Cannot delete the super admin
        if ($targetUser->isSuperAdmin()) {
            return false;
        }

        // Only super admin can delete other admin accounts
        if ($targetUser->role === 'admin') {
            return $authenticatedUser->isSuperAdmin();
        }

        // Any admin can delete vendors and clients
        return $authenticatedUser->role === 'admin';
    }

    /**
     * Determine if the authenticated user can restore a soft-deleted account.
     */
    public function restore(User $authenticatedUser, User $targetUser): bool
    {
        return $this->delete($authenticatedUser, $targetUser);
    }

    /**
     * Determine if the authenticated user can permanently delete an account.
     * Admin accounts can never be permanently deleted, even by super admin.
     */
    public function forceDelete(User $authenticatedUser, User $targetUser): bool
    {
        if ($authenticatedUser->id === $targetUser->id) {
            return false;
        }

        // Admin accounts (including super admin) can never be permanently deleted
        if ($targetUser->role === 'admin' || $targetUser->isSuperAdmin()) {
            return false;
        }

        return $authenticatedUser->role === 'admin';
    }
}

<?php

namespace App\Services\Telegram;

use App\Models\TelegramActionToken;
use App\Models\User;

class TelegramPermissionService
{
    /**
     * Resolve a Telegram user ID to a portal User model.
     *
     * Returns null if no user has linked this Telegram account.
     */
    public function resolveUser(string $telegramUserId): ?User
    {
        return User::where('telegram_chat_id', $telegramUserId)->first();
    }

    /**
     * Check whether a portal user is allowed to redeem a given action token.
     *
     * Checks:
     *  1. User's portal role matches token's required_role (if set)
     *  2. If the token is Telegram-user-restricted, the Telegram user ID matches
     *  3. Role-specific subject ownership (client/vendor can only act on their own data)
     *
     * Returns true if permitted, false otherwise.
     */
    public function canRedeem(User $user, TelegramActionToken $token, string $telegramUserId): bool
    {
        // If the token is locked to a specific Telegram user, enforce it
        if ($token->telegram_user_id && $token->telegram_user_id !== $telegramUserId) {
            return false;
        }

        // Role-based permission
        if ($token->required_role) {
            if (! $this->userHasRole($user, $token->required_role)) {
                return false;
            }
        }

        // Subject ownership enforcement for non-admin roles
        if ($user->role === 'client') {
            return $this->clientOwnsSubject($user, $token);
        }

        if ($user->role === 'vendor') {
            return $this->vendorOwnsSubject($user, $token);
        }

        // Admin/staff: check permission flag for sensitive actions
        if (in_array($user->role, ['admin', 'staff'], true)) {
            return $this->adminCanPerform($user, $token->action_type);
        }

        return false;
    }

    /**
     * Quick role check: does the user satisfy the required role?
     *
     * Admin can perform any role-restricted action (admin > staff > vendor/client).
     */
    public function userHasRole(User $user, string $requiredRole): bool
    {
        return match ($requiredRole) {
            'admin'  => $user->role === 'admin',
            'staff'  => in_array($user->role, ['admin', 'staff'], true),
            'vendor' => $user->role === 'vendor',
            'client' => $user->role === 'client',
            default  => false,
        };
    }

    /**
     * Check whether a client user owns the token's subject.
     *
     * Clients may only act on their own orders/reports.
     */
    protected function clientOwnsSubject(User $user, TelegramActionToken $token): bool
    {
        if (! $token->subject_type || ! $token->subject_id) {
            return false;
        }

        $subject = $token->subject;

        if (! $subject) {
            return false;
        }

        // Orders and related subjects must belong to the client's client_id
        if (method_exists($subject, 'client_id')) {
            return (int) $subject->client_id === (int) $user->client_id;
        }

        if (isset($subject->client_id)) {
            return (int) $subject->client_id === (int) $user->client_id;
        }

        return false;
    }

    /**
     * Check whether a vendor user owns the token's subject.
     *
     * Vendors may only act on orders/assignments assigned to them.
     */
    protected function vendorOwnsSubject(User $user, TelegramActionToken $token): bool
    {
        if (! $token->subject_type || ! $token->subject_id) {
            return false;
        }

        $subject = $token->subject;

        if (! $subject) {
            return false;
        }

        // Check vendor_id or assigned_vendor_id presence
        foreach (['vendor_id', 'assigned_vendor_id', 'user_id'] as $key) {
            if (isset($subject->{$key})) {
                return (int) $subject->{$key} === (int) $user->id;
            }
        }

        return false;
    }

    /**
     * Check if an admin/staff user may perform a specific action type.
     *
     * Admins can do everything. Staff require matching permission flags.
     * Permission flag names mirror portal staff permission conventions.
     */
    protected function adminCanPerform(User $user, string $actionType): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Staff — check permission flags stored in the permissions JSON column if it exists
        $permissionMap = [
            'payment.approve'            => 'payments.approve',
            'payment.reject'             => 'payments.approve',
            'vendor.report.approve'      => 'vendor_reports.review',
            'vendor.report.fail'         => 'vendor_reports.review',
            'vendor.report.rework'       => 'vendor_reports.review',
            'vendor.payout.mark_paid'    => 'vendor_payouts.manage',
            'order.'                     => 'orders.manage',
        ];

        foreach ($permissionMap as $actionPrefix => $permissionFlag) {
            if (str_starts_with($actionType, $actionPrefix)) {
                return $this->staffHasPermission($user, $permissionFlag);
            }
        }

        // Safe/view-only actions are allowed for all staff
        if (str_starts_with($actionType, 'order.view')) {
            return true;
        }

        return false;
    }

    /**
     * Check a staff user's permission flag.
     *
     * Expects the User model to have a `permissions` JSON column or similar.
     * Falls back to false if the column doesn't exist.
     */
    protected function staffHasPermission(User $user, string $flag): bool
    {
        if (! isset($user->permissions)) {
            return false;
        }

        $permissions = is_array($user->permissions) ? $user->permissions : [];

        return in_array($flag, $permissions, true);
    }
}

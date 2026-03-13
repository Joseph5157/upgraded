<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Determine if the user can view the client list.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can view a specific client record.
     */
    public function view(User $user, Client $client): bool
    {
        // Admins can view any client; client users can only view their own
        return $user->role === 'admin'
            || $user->client_id === $client->id;
    }

    /**
     * Determine if the admin can update a client's slot quota.
     */
    public function updateSlots(User $user, Client $client): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if the admin can refill (top up) client credits.
     */
    public function refill(User $user, Client $client): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if the admin can update client details.
     */
    public function update(User $user, Client $client): bool
    {
        return $user->role === 'admin';
    }
}

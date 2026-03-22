<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the vendor can claim the order.
     */
    public function claim(User $user, Order $order): bool
    {
        return $user->role === 'vendor'
            && $user->isActive()
            && $order->status === OrderStatus::Pending
            && $order->claimed_by === null;
    }

    /**
     * Determine if the user can unclaim the order.
     */
    public function unclaim(User $user, Order $order): bool
    {
        return (int) $user->id === (int) $order->claimed_by
            && in_array($order->status, [OrderStatus::Pending, OrderStatus::Processing]);
    }

    /**
     * Determine if the user can start processing the order.
     */
    public function process(User $user, Order $order): bool
    {
        $isOwner = (int) $user->id === (int) $order->claimed_by;
        $isAdmin = $user->role === 'admin';

        return ($isOwner || $isAdmin)
            && $order->status !== OrderStatus::Cancelled
            && $order->status !== OrderStatus::Delivered;
    }

    /**
     * Determine if the user can upload a report for the order.
     */
    public function uploadReport(User $user, Order $order): bool
    {
        $isOwner = (int) $user->id === (int) $order->claimed_by;
        $isAdmin = $user->role === 'admin';

        return ($isOwner || $isAdmin)
            && $order->status !== OrderStatus::Cancelled
            && $order->status !== OrderStatus::Delivered;
    }

    /**
     * Determine if the user can mark the order as delivered.
     */
    public function deliver(User $user, Order $order): bool
    {
        $isOwner = (int) $user->id === (int) $order->claimed_by;
        $isAdmin = $user->role === 'admin';

        return ($isOwner || $isAdmin)
            && $order->status !== OrderStatus::Cancelled
            && $order->status !== OrderStatus::Delivered;
    }

    /**
     * Determine if the client user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        return $user->role === 'client'
            && (int) $user->client_id === (int) $order->client_id;
    }

    /**
     * Determine if an admin can force delete the order.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return $user->role === 'admin';
    }
}

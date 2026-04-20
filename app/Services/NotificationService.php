<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Notify relevant parties when an order's status changes.
     *
     * @param  Order   $order
     * @param  string  $fromStatus  e.g. 'pending', 'processing'
     * @param  string  $toStatus    e.g. 'processing', 'delivered'
     */
    public function notifyOrderStatusChange(Order $order, string $fromStatus, string $toStatus): void
    {
        Log::info('order.status_change_notification', [
            'order_id'    => $order->id,
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
        ]);
    }
}

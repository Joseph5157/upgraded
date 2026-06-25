@props(['order'])

@php
    $class = match (true) {
        $order->computed_status === 'overdue'   => 'badge-error',
        $order->status->value === 'failed'      => 'badge-error',
        $order->status->value === 'cancelled'   => 'badge-error',
        $order->status->value === 'delivered'   => 'badge-success',
        $order->status->value === 'processing'  => 'badge-info',
        $order->status->value === 'claimed'     => 'badge-warning',
        default => 'badge-ghost',
    };
@endphp

<span class="badge badge-sm {{ $class }}">{{ ucfirst($order->status->value) }}</span>

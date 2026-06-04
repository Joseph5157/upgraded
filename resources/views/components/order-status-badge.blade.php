@props(['order'])

@php
    $class = match (true) {
        $order->computed_status === 'overdue' => 'badge-error',
        $order->status->value === 'delivered' => 'badge-success',
        $order->status->value === 'processing' => 'badge-info',
        default => 'badge-ghost',
    };
@endphp

<span class="badge badge-sm {{ $class }}">{{ ucfirst($order->status->value) }}</span>

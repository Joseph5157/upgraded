<x-filament-widgets::widget>
    <style>
        .orders-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .orders-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .orders-title {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .orders-title svg {
            width: 18px;
            height: 18px;
            color: #6b7280;
        }
        .orders-view-all {
            font-size: 13px;
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.15s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .orders-view-all:hover {
            color: #4f46e5;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .orders-table thead th {
            padding: 10px 16px;
            text-align: left;
            font-weight: 600;
            color: #9ca3af;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #fafbfc;
            border-bottom: 1px solid #f3f4f6;
            white-space: nowrap;
        }
        .orders-table tbody tr {
            border-bottom: 1px solid #f9fafb;
            transition: background 0.15s;
        }
        .orders-table tbody tr:hover {
            background: #f9fafb;
        }
        .orders-table tbody tr:last-child {
            border-bottom: none;
        }
        .orders-table tbody td {
            padding: 12px 16px;
            color: #374151;
            vertical-align: middle;
        }
        .order-id {
            font-weight: 600;
            color: #6366f1;
        }
        .order-client {
            font-weight: 500;
            color: #111827;
        }
        .order-vendor {
            color: #6b7280;
        }
        .order-files {
            text-align: center;
            font-weight: 500;
        }
        .order-date {
            color: #9ca3af;
            white-space: nowrap;
            font-size: 12px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 6px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
            letter-spacing: 0.01em;
        }
        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #9ca3af;
            font-size: 14px;
        }

        /* Dark mode */
        [data-theme="dark"] .orders-card,
        .dark .orders-card {
            background: #1e2030;
            border-color: #2e3148;
        }
        [data-theme="dark"] .orders-header,
        .dark .orders-header {
            border-color: #2e3148;
        }
        [data-theme="dark"] .orders-title,
        .dark .orders-title {
            color: #f3f4f6;
        }
        [data-theme="dark"] .orders-table thead th,
        .dark .orders-table thead th {
            background: #191b28;
            color: #6b7280;
            border-color: #2e3148;
        }
        [data-theme="dark"] .orders-table tbody tr,
        .dark .orders-table tbody tr {
            border-color: #252840;
        }
        [data-theme="dark"] .orders-table tbody tr:hover,
        .dark .orders-table tbody tr:hover {
            background: #252840;
        }
        [data-theme="dark"] .orders-table tbody td,
        .dark .orders-table tbody td {
            color: #d1d5db;
        }
        [data-theme="dark"] .order-client,
        .dark .order-client {
            color: #f3f4f6;
        }
        [data-theme="dark"] .order-vendor,
        .dark .order-vendor {
            color: #9ca3af;
        }
        [data-theme="dark"] .order-date,
        .dark .order-date {
            color: #6b7280;
        }
    </style>

    <div class="orders-card">
        <div class="orders-header">
            <span class="orders-title">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
                </svg>
                Recent Orders
            </span>
            <a href="{{ route('filament.admin.resources.orders.index') }}" class="orders-view-all">
                View all
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                </svg>
            </a>
        </div>
        <div style="overflow-x:auto;">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Vendor</th>
                        <th style="text-align:center;">Files</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $statusStyles = [
                                'pending'    => ['bg'=>'#fef3c7','color'=>'#92400e','dot'=>'#f59e0b'],
                                'claimed'    => ['bg'=>'#dbeafe','color'=>'#1e40af','dot'=>'#3b82f6'],
                                'processing' => ['bg'=>'#e0e7ff','color'=>'#3730a3','dot'=>'#6366f1'],
                                'delivered'  => ['bg'=>'#d1fae5','color'=>'#065f46','dot'=>'#10b981'],
                                'cancelled'  => ['bg'=>'#fee2e2','color'=>'#991b1b','dot'=>'#ef4444'],
                                'failed'     => ['bg'=>'#fee2e2','color'=>'#991b1b','dot'=>'#ef4444'],
                            ];
                            $sc = $statusStyles[$order->status->value ?? $order->status] ?? ['bg'=>'#f3f4f6','color'=>'#374151','dot'=>'#9ca3af'];
                        @endphp
                        <tr>
                            <td><span class="order-id">{{ $order->id }}</span></td>
                            <td><span class="order-client">{{ $order->client->name ?? '—' }}</span></td>
                            <td><span class="order-vendor">{{ $order->vendor->name ?? 'Unassigned' }}</span></td>
                            <td class="order-files">{{ $order->files_count }}</td>
                            <td>
                                <span class="status-badge" style="background:{{ $sc['bg'] }};color:{{ $sc['color'] }};">
                                    <span class="status-dot" style="background:{{ $sc['dot'] }};"></span>
                                    {{ ucfirst($order->status->value ?? $order->status) }}
                                </span>
                            </td>
                            <td><span class="order-date">{{ $order->created_at->format('d M Y') }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">No orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-widgets::widget>

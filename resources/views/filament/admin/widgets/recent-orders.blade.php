<x-filament-widgets::widget>
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;">
        <div style="padding:14px 16px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:14px;font-weight:600;color:#111827;">Recent Orders</span>
            <a href="{{ route('filament.admin.resources.orders.index') }}" style="font-size:12px;color:#6366f1;text-decoration:none;font-weight:500;">View all →</a>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f9fafb;">
                        <th style="padding:8px 12px;text-align:left;font-weight:600;color:#6b7280;white-space:nowrap;">#</th>
                        <th style="padding:8px 12px;text-align:left;font-weight:600;color:#6b7280;white-space:nowrap;">Client</th>
                        <th style="padding:8px 12px;text-align:left;font-weight:600;color:#6b7280;white-space:nowrap;">Vendor</th>
                        <th style="padding:8px 12px;text-align:left;font-weight:600;color:#6b7280;white-space:nowrap;">Files</th>
                        <th style="padding:8px 12px;text-align:left;font-weight:600;color:#6b7280;white-space:nowrap;">Status</th>
                        <th style="padding:8px 12px;text-align:left;font-weight:600;color:#6b7280;white-space:nowrap;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $statusColors = [
                                'pending'    => ['bg'=>'#fef3c7','color'=>'#92400e'],
                                'claimed'    => ['bg'=>'#dbeafe','color'=>'#1e40af'],
                                'processing' => ['bg'=>'#e0e7ff','color'=>'#3730a3'],
                                'delivered'  => ['bg'=>'#d1fae5','color'=>'#065f46'],
                                'cancelled'  => ['bg'=>'#fee2e2','color'=>'#991b1b'],
                                'failed'     => ['bg'=>'#fee2e2','color'=>'#991b1b'],
                            ];
                            $sc = $statusColors[$order->status->value ?? $order->status] ?? ['bg'=>'#f3f4f6','color'=>'#374151'];
                        @endphp
                        <tr style="border-top:1px solid #f3f4f6;">
                            <td style="padding:9px 12px;color:#374151;font-weight:500;">{{ $order->id }}</td>
                            <td style="padding:9px 12px;color:#374151;">{{ $order->client->name ?? '—' }}</td>
                            <td style="padding:9px 12px;color:#6b7280;">{{ $order->vendor->name ?? 'Unassigned' }}</td>
                            <td style="padding:9px 12px;color:#374151;">{{ $order->files_count }}</td>
                            <td style="padding:9px 12px;">
                                <span style="background:{{ $sc['bg'] }};color:{{ $sc['color'] }};border-radius:99px;padding:2px 10px;font-size:11px;font-weight:600;text-transform:capitalize;">
                                    {{ ucfirst($order->status->value ?? $order->status) }}
                                </span>
                            </td>
                            <td style="padding:9px 12px;color:#6b7280;white-space:nowrap;">{{ $order->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:20px 12px;text-align:center;color:#9ca3af;">No orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-widgets::widget>

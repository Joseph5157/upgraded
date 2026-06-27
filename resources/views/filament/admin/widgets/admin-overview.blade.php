<x-filament-widgets::widget>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;" class="sm:grid-cols-3">
        {{-- Total Orders --}}
        <div style="background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:12px;padding:16px;color:#fff;">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;opacity:.85;margin-bottom:6px;">Total Orders</div>
            <div style="font-size:28px;font-weight:700;line-height:1;">{{ $totalOrders }}</div>
        </div>

        {{-- Pending Orders --}}
        <div style="background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;padding:16px;color:#fff;">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;opacity:.85;margin-bottom:6px;">Pending Orders</div>
            <div style="font-size:28px;font-weight:700;line-height:1;">{{ $pendingOrders }}</div>
        </div>

        {{-- Pending Requests --}}
        <div style="background:linear-gradient(135deg,#ef4444,#dc2626);border-radius:12px;padding:16px;color:#fff;">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;opacity:.85;margin-bottom:6px;">Pending Requests</div>
            <div style="font-size:28px;font-weight:700;line-height:1;">{{ $pendingRequests }}</div>
        </div>

        {{-- Active Clients --}}
        <div style="background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;padding:16px;color:#fff;">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;opacity:.85;margin-bottom:6px;">Active Clients</div>
            <div style="font-size:28px;font-weight:700;line-height:1;">{{ $activeClients }}</div>
        </div>

        {{-- Active Vendors --}}
        <div style="background:linear-gradient(135deg,#6366f1,#4f46e5);border-radius:12px;padding:16px;color:#fff;">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;opacity:.85;margin-bottom:6px;">Active Vendors</div>
            <div style="font-size:28px;font-weight:700;line-height:1;">{{ $activeVendors }}</div>
        </div>

        {{-- Delivered Today --}}
        <div style="background:linear-gradient(135deg,#14b8a6,#0d9488);border-radius:12px;padding:16px;color:#fff;">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;opacity:.85;margin-bottom:6px;">Delivered Today</div>
            <div style="font-size:28px;font-weight:700;line-height:1;">{{ $deliveredToday }}</div>
        </div>
    </div>
</x-filament-widgets::widget>

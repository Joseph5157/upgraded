<x-filament-widgets::widget>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

        {{-- Credit Balance — Blue --}}
        <div style="background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:12px;padding:14px;">
            <p style="font-size:11px;color:rgba(255,255,255,0.8);font-weight:500;">Credit Balance</p>
            <p style="font-size:22px;font-weight:700;color:#fff;margin-top:4px;line-height:1.2;">{{ $balance }}</p>
            <p style="font-size:11px;color:rgba(255,255,255,0.7);margin-top:4px;">Available credits</p>
        </div>

        {{-- Files Submitted — Indigo --}}
        <div style="background:linear-gradient(135deg,#6366f1,#4f46e5);border-radius:12px;padding:14px;">
            <p style="font-size:11px;color:rgba(255,255,255,0.8);font-weight:500;">Files Submitted</p>
            <p style="font-size:22px;font-weight:700;color:#fff;margin-top:4px;line-height:1.2;">{{ $total }}</p>
            <p style="font-size:11px;color:rgba(255,255,255,0.7);margin-top:4px;">Total orders</p>
        </div>

        {{-- In Progress — Amber --}}
        <div style="background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;padding:14px;">
            <p style="font-size:11px;color:rgba(255,255,255,0.8);font-weight:500;">In Progress</p>
            <p style="font-size:22px;font-weight:700;color:#fff;margin-top:4px;line-height:1.2;">{{ $inProgress }}</p>
            <p style="font-size:11px;color:rgba(255,255,255,0.7);margin-top:4px;">Being processed</p>
        </div>

        {{-- Completed — Green --}}
        <div style="background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;padding:14px;">
            <p style="font-size:11px;color:rgba(255,255,255,0.8);font-weight:500;">Completed</p>
            <p style="font-size:22px;font-weight:700;color:#fff;margin-top:4px;line-height:1.2;">{{ $completed }}</p>
            <p style="font-size:11px;color:rgba(255,255,255,0.7);margin-top:4px;">Delivered</p>
        </div>

    </div>
</x-filament-widgets::widget>

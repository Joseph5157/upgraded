<x-filament-widgets::widget>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

        {{-- Pending Earnings — Amber --}}
        <div style="background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;padding:14px;">
            <p style="font-size:11px;color:rgba(255,255,255,0.8);font-weight:500;">Pending Earnings</p>
            <p style="font-size:22px;font-weight:700;color:#fff;margin-top:4px;line-height:1.2;">{{ $pending }} ₹</p>
            <p style="font-size:11px;color:rgba(255,255,255,0.7);margin-top:4px;">Awaiting approval</p>
        </div>

        {{-- Approved Payable — Green --}}
        <div style="background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;padding:14px;">
            <p style="font-size:11px;color:rgba(255,255,255,0.8);font-weight:500;">Approved Payable</p>
            <p style="font-size:22px;font-weight:700;color:#fff;margin-top:4px;line-height:1.2;">{{ $approved }} ₹</p>
            <p style="font-size:11px;color:rgba(255,255,255,0.7);margin-top:4px;">Ready to pay out</p>
        </div>

        {{-- Delivered Today — Blue --}}
        <div style="background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:12px;padding:14px;">
            <p style="font-size:11px;color:rgba(255,255,255,0.8);font-weight:500;">Delivered Today</p>
            <p style="font-size:22px;font-weight:700;color:#fff;margin-top:4px;line-height:1.2;">{{ $today }}</p>
            <p style="font-size:11px;color:rgba(255,255,255,0.7);margin-top:4px;">Today's completions</p>
        </div>

        {{-- Total Delivered — Indigo --}}
        <div style="background:linear-gradient(135deg,#6366f1,#4f46e5);border-radius:12px;padding:14px;">
            <p style="font-size:11px;color:rgba(255,255,255,0.8);font-weight:500;">Total Delivered</p>
            <p style="font-size:22px;font-weight:700;color:#fff;margin-top:4px;line-height:1.2;">{{ $total }}</p>
            <p style="font-size:11px;color:rgba(255,255,255,0.7);margin-top:4px;">All time</p>
        </div>

    </div>
</x-filament-widgets::widget>

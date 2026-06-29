<x-filament-widgets::widget>
    <style>
        .earnings-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        @media (max-width: 1024px) {
            .earnings-stats-grid {
                display: flex;
                gap: 12px;
                overflow-x: auto;
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 8px;
            }
            .earnings-stats-grid::-webkit-scrollbar {
                height: 4px;
            }
            .earnings-stats-grid::-webkit-scrollbar-track {
                background: #f3f4f6;
                border-radius: 2px;
            }
            .earnings-stats-grid::-webkit-scrollbar-thumb {
                background: #d1d5db;
                border-radius: 2px;
            }
            .earnings-stats-grid::-webkit-scrollbar-thumb:hover {
                background: #9ca3af;
            }
        }
        .earnings-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            flex-shrink: 0;
            min-width: 260px;
        }
        @media (min-width: 1025px) {
            .earnings-card {
                min-width: auto;
            }
        }
        .earnings-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            transform: translateY(-1px);
        }
        .earnings-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .earnings-icon svg {
            width: 22px;
            height: 22px;
        }
        .earnings-content {
            flex: 1;
            min-width: 0;
        }
        .earnings-label {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
            line-height: 1;
        }
        .earnings-value {
            font-size: 26px;
            font-weight: 700;
            color: #111827;
            line-height: 1;
        }
        .earnings-desc {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 4px;
        }

        /* Dark mode */
        [data-theme="dark"] .earnings-card,
        .dark .earnings-card {
            background: #1e2030;
            border-color: #2e3148;
        }
        [data-theme="dark"] .earnings-card:hover,
        .dark .earnings-card:hover {
            border-color: #3e4268;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        [data-theme="dark"] .earnings-label,
        .dark .earnings-label {
            color: #9ca3af;
        }
        [data-theme="dark"] .earnings-value,
        .dark .earnings-value {
            color: #f3f4f6;
        }
        [data-theme="dark"] .earnings-desc,
        .dark .earnings-desc {
            color: #6b7280;
        }
        [data-theme="dark"] .earnings-stats-grid::-webkit-scrollbar-track,
        .dark .earnings-stats-grid::-webkit-scrollbar-track {
            background: #252840;
        }
        [data-theme="dark"] .earnings-stats-grid::-webkit-scrollbar-thumb,
        .dark .earnings-stats-grid::-webkit-scrollbar-thumb {
            background: #3e4268;
        }
        [data-theme="dark"] .earnings-stats-grid::-webkit-scrollbar-thumb:hover,
        .dark .earnings-stats-grid::-webkit-scrollbar-thumb:hover {
            background: #4e5580;
        }
    </style>

    <div class="earnings-stats-grid">
        {{-- Pending Earnings --}}
        <div class="earnings-card">
            <div class="earnings-icon" style="background: #fffbeb;">
                <svg fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="earnings-content">
                <div class="earnings-label">Pending Earnings</div>
                <div class="earnings-value">{{ $pending }} ₹</div>
                <div class="earnings-desc">Awaiting approval</div>
            </div>
        </div>

        {{-- Approved Payable --}}
        <div class="earnings-card">
            <div class="earnings-icon" style="background: #ecfdf5;">
                <svg fill="none" stroke="#10b981" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="earnings-content">
                <div class="earnings-label">Approved Payable</div>
                <div class="earnings-value">{{ $approved }} ₹</div>
                <div class="earnings-desc">Ready to pay out</div>
            </div>
        </div>

        {{-- Delivered Today --}}
        <div class="earnings-card">
            <div class="earnings-icon" style="background: #eff6ff;">
                <svg fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="earnings-content">
                <div class="earnings-label">Delivered Today</div>
                <div class="earnings-value">{{ $today }}</div>
                <div class="earnings-desc">Today's completions</div>
            </div>
        </div>

        {{-- Total Delivered --}}
        <div class="earnings-card">
            <div class="earnings-icon" style="background: #eef2ff;">
                <svg fill="none" stroke="#6366f1" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.5H21a2.25 2.25 0 012.25 2.25v13.5A2.25 2.25 0 0121 18.75H3a2.25 2.25 0 01-2.25-2.25V5.25A2.25 2.25 0 013 3h18z"/>
                </svg>
            </div>
            <div class="earnings-content">
                <div class="earnings-label">Total Delivered</div>
                <div class="earnings-value">{{ $total }}</div>
                <div class="earnings-desc">All time</div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>

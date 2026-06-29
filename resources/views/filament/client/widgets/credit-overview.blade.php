<x-filament-widgets::widget>
    <style>
        .credit-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        @media (max-width: 1024px) {
            .credit-stats-grid {
                display: flex;
                gap: 12px;
                overflow-x: auto;
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 8px;
            }
            .credit-stats-grid::-webkit-scrollbar {
                height: 4px;
            }
            .credit-stats-grid::-webkit-scrollbar-track {
                background: #f3f4f6;
                border-radius: 2px;
            }
            .credit-stats-grid::-webkit-scrollbar-thumb {
                background: #d1d5db;
                border-radius: 2px;
            }
            .credit-stats-grid::-webkit-scrollbar-thumb:hover {
                background: #9ca3af;
            }
        }
        .credit-card {
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
            .credit-card {
                min-width: auto;
            }
        }
        .credit-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            transform: translateY(-1px);
        }
        .credit-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .credit-icon svg {
            width: 22px;
            height: 22px;
        }
        .credit-content {
            flex: 1;
            min-width: 0;
        }
        .credit-label {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
            line-height: 1;
        }
        .credit-value {
            font-size: 26px;
            font-weight: 700;
            color: #111827;
            line-height: 1;
        }
        .credit-desc {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 4px;
        }

        /* Dark mode */
        [data-theme="dark"] .credit-card,
        .dark .credit-card {
            background: #1e2030;
            border-color: #2e3148;
        }
        [data-theme="dark"] .credit-card:hover,
        .dark .credit-card:hover {
            border-color: #3e4268;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        [data-theme="dark"] .credit-label,
        .dark .credit-label {
            color: #9ca3af;
        }
        [data-theme="dark"] .credit-value,
        .dark .credit-value {
            color: #f3f4f6;
        }
        [data-theme="dark"] .credit-desc,
        .dark .credit-desc {
            color: #6b7280;
        }
        [data-theme="dark"] .credit-stats-grid::-webkit-scrollbar-track,
        .dark .credit-stats-grid::-webkit-scrollbar-track {
            background: #252840;
        }
        [data-theme="dark"] .credit-stats-grid::-webkit-scrollbar-thumb,
        .dark .credit-stats-grid::-webkit-scrollbar-thumb {
            background: #3e4268;
        }
        [data-theme="dark"] .credit-stats-grid::-webkit-scrollbar-thumb:hover,
        .dark .credit-stats-grid::-webkit-scrollbar-thumb:hover {
            background: #4e5580;
        }
    </style>

    <div class="credit-stats-grid">
        {{-- Credit Balance --}}
        <div class="credit-card">
            <div class="credit-icon" style="background: #eff6ff;">
                <svg fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="credit-content">
                <div class="credit-label">Credit Balance</div>
                <div class="credit-value">{{ $balance }}</div>
                <div class="credit-desc">Available credits</div>
            </div>
        </div>

        {{-- Files Submitted --}}
        <div class="credit-card">
            <div class="credit-icon" style="background: #eef2ff;">
                <svg fill="none" stroke="#6366f1" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                </svg>
            </div>
            <div class="credit-content">
                <div class="credit-label">Files Submitted</div>
                <div class="credit-value">{{ $total }}</div>
                <div class="credit-desc">Total orders</div>
            </div>
        </div>

        {{-- In Progress --}}
        <div class="credit-card">
            <div class="credit-icon" style="background: #fffbeb;">
                <svg fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="credit-content">
                <div class="credit-label">In Progress</div>
                <div class="credit-value">{{ $inProgress }}</div>
                <div class="credit-desc">Being processed</div>
            </div>
        </div>

        {{-- Completed --}}
        <div class="credit-card">
            <div class="credit-icon" style="background: #ecfdf5;">
                <svg fill="none" stroke="#10b981" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="credit-content">
                <div class="credit-label">Completed</div>
                <div class="credit-value">{{ $completed }}</div>
                <div class="credit-desc">Delivered</div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>

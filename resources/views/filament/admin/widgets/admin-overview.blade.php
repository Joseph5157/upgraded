<x-filament-widgets::widget>
    <style>
        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        @media (max-width: 1024px) {
            .admin-stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }
        @media (max-width: 640px) {
            .admin-stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }
        .stat-card-clean {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        @media (max-width: 1024px) {
            .stat-card-clean {
                padding: 16px;
            }
        }
        @media (max-width: 640px) {
            .stat-card-clean {
                padding: 12px;
                gap: 10px;
            }
        }
        .stat-card-clean:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            transform: translateY(-1px);
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon svg {
            width: 22px;
            height: 22px;
        }
        @media (max-width: 1024px) {
            .stat-icon {
                width: 40px;
                height: 40px;
            }
            .stat-icon svg {
                width: 20px;
                height: 20px;
            }
        }
        @media (max-width: 640px) {
            .stat-icon {
                width: 36px;
                height: 36px;
            }
            .stat-icon svg {
                width: 18px;
                height: 18px;
            }
        }
        .stat-content {
            flex: 1;
            min-width: 0;
        }
        .stat-label {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
            line-height: 1;
        }
        .stat-value {
            font-size: 26px;
            font-weight: 700;
            color: #111827;
            line-height: 1;
        }
        @media (max-width: 1024px) {
            .stat-label {
                font-size: 11px;
                margin-bottom: 4px;
            }
            .stat-value {
                font-size: 22px;
            }
        }
        @media (max-width: 640px) {
            .stat-label {
                font-size: 10px;
                margin-bottom: 2px;
            }
            .stat-value {
                font-size: 18px;
            }
        }

        /* Dark mode */
        [data-theme="dark"] .stat-card-clean,
        .dark .stat-card-clean,
        .fi-sidebar-nav ~ * .stat-card-clean {
            background: #1e2030;
            border-color: #2e3148;
        }
        [data-theme="dark"] .stat-card-clean:hover,
        .dark .stat-card-clean:hover {
            border-color: #3e4268;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        [data-theme="dark"] .stat-label,
        .dark .stat-label {
            color: #9ca3af;
        }
        [data-theme="dark"] .stat-value,
        .dark .stat-value {
            color: #f3f4f6;
        }
    </style>

    <div class="admin-stats-grid">
        {{-- Total Orders --}}
        <div class="stat-card-clean">
            <div class="stat-icon" style="background: #eff6ff;">
                <svg fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Orders</div>
                <div class="stat-value">{{ $totalOrders }}</div>
            </div>
        </div>

        {{-- Pending Orders --}}
        <div class="stat-card-clean">
            <div class="stat-icon" style="background: #fffbeb;">
                <svg fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Pending Orders</div>
                <div class="stat-value">{{ $pendingOrders }}</div>
            </div>
        </div>

        {{-- Pending Requests --}}
        <div class="stat-card-clean">
            <div class="stat-icon" style="background: #fef2f2;">
                <svg fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Pending Requests</div>
                <div class="stat-value">{{ $pendingRequests }}</div>
            </div>
        </div>

        {{-- Active Clients --}}
        <div class="stat-card-clean">
            <div class="stat-icon" style="background: #ecfdf5;">
                <svg fill="none" stroke="#10b981" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Active Clients</div>
                <div class="stat-value">{{ $activeClients }}</div>
            </div>
        </div>

        {{-- Active Vendors --}}
        <div class="stat-card-clean">
            <div class="stat-icon" style="background: #eef2ff;">
                <svg fill="none" stroke="#6366f1" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0"/>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Active Vendors</div>
                <div class="stat-value">{{ $activeVendors }}</div>
            </div>
        </div>

        {{-- Delivered Today --}}
        <div class="stat-card-clean">
            <div class="stat-icon" style="background: #f0fdfa;">
                <svg fill="none" stroke="#14b8a6" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Delivered Today</div>
                <div class="stat-value">{{ $deliveredToday }}</div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>

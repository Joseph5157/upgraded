<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {}
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .premium-card {
            background: #FAFBFC;
            border: 1px solid #E2E6EA;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
        }
        .sidebar-link-active { background: rgba(99,102,241,0.1); color:#fff; border-right: 2px solid #6366f1; }
    </style>
</head>
<body class="h-screen flex bg-[#F0F2F5] text-[#111827] overflow-hidden overflow-x-hidden">

    <!-- Sidebar — same as dashboard -->
    <aside class="hidden md:flex w-64 flex-shrink-0 h-full border-r border-[#E2E6EA] flex-col pt-8 bg-[#F7F8FA]">
        <div class="px-8 mb-12">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <i data-lucide="sparkles" class="w-5 h-5 text-[#1A1D23]"></i>
                </div>
                <span class="font-bold text-[#1A1D23] text-lg tracking-tight">PlagExpert</span>
            </div>
        </div>
        <nav class="flex-1 space-y-2">
            <a href="{{ route('client.dashboard') }}" class="flex items-center gap-4 px-8 py-4 text-sm font-medium text-[#6B7280] hover:text-[#1A1D23] transition-all">
                <i data-lucide="layout-grid" class="w-5 h-5"></i> Dashboard
            </a>
            <div class="flex items-center gap-4 px-8 py-4 text-sm font-medium sidebar-link-active">
                <i data-lucide="credit-card" class="w-5 h-5"></i> Subscription
            </div>
            <div class="flex items-center justify-between px-8 py-4 text-sm font-medium text-[#9CA3AF] cursor-not-allowed select-none">
                <div class="flex items-center gap-4"><i data-lucide="history" class="w-5 h-5"></i> Order History</div>
                <span class="text-[8px] font-black uppercase tracking-widest text-indigo-500/50 bg-indigo-500/5 border border-indigo-500/10 px-1.5 py-0.5 rounded">Soon</span>
            </div>
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-4 px-8 py-4 text-sm font-medium text-[#6B7280] hover:text-[#1A1D23] transition-all">
                <i data-lucide="settings" class="w-5 h-5"></i> Settings
            </a>
        </nav>
        <div class="p-6">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 border border-[#E2E6EA] rounded-xl text-xs font-bold text-[#6B7280] hover:text-white hover:bg-red-500/10 hover:border-red-500/20 transition-all">
                    <i data-lucide="log-out" class="w-4 h-4"></i> SIGN OUT
                </button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 overflow-y-auto overflow-x-hidden bg-[#F0F2F5] w-full min-w-0">
        <header class="h-20 border-b border-[#E2E6EA] flex items-center justify-between px-4 sm:px-6 lg:px-10 bg-[#FAFBFC] backdrop-blur-md sticky top-0 z-10">
            {{-- Mobile Menu Button --}}
            <button class="md:hidden w-8 h-8 flex items-center justify-center text-gray-600 hover:text-gray-900 mr-3" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="text-[#1A1D23] font-semibold">Subscription & Credits</h1>
                <p class="text-[10px] text-[#9CA3AF] uppercase tracking-widest mt-0.5">Manage your plan and top-ups</p>
            </div>
            <div class="flex items-center gap-3 pr-6">
                <div class="text-right">
                    <p class="text-[10px] text-[#6B7280] font-bold uppercase tracking-widest">Client ID</p>
                    <p class="text-xs font-mono text-indigo-400">ID-{{ str_pad($client->id, 4, '0', STR_PAD_LEFT) }}</p>
                </div>
                <div class="w-10 h-10 bg-indigo-500/10 rounded-full flex items-center justify-center text-indigo-500 ring-4 ring-indigo-500/5">
                    <i data-lucide="user" class="w-5 h-5"></i>
                </div>
            </div>
        </header>

        {{-- Mobile Menu Dropdown --}}
        <div id="mobile-menu" class="hidden md:hidden bg-[#F7F8FA] border-b border-[#E2E6EA]">
            <nav class="px-4 py-3 space-y-1">
                <a href="{{ route('client.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-[#6B7280] hover:bg-[#ECEEF2]">
                    <i data-lucide="layout-grid" class="w-4 h-4"></i> Dashboard
                </a>
                <div class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-indigo-500/10 text-indigo-600">
                    <i data-lucide="credit-card" class="w-4 h-4"></i> Subscription
                </div>
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-[#6B7280] hover:bg-[#ECEEF2]">
                    <i data-lucide="settings" class="w-4 h-4"></i> Settings
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2 pt-2 border-t border-[#E2E6EA]">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-bold text-[#6B7280] hover:text-red-500 hover:bg-red-50 border border-[#E2E6EA]">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
                    </button>
                </form>
            </nav>
        </div>

        <div class="p-10 max-w-6xl mx-auto space-y-10">

            @if(session('success'))
                <div class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-500 text-sm font-semibold">
                    <i data-lucide="check-circle" class="w-5 h-5"></i> {{ session('success') }}
                </div>
            @endif

            {{-- ── Plan Status Card ───────────────────────────────────────── --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Plan --}}
                <div class="premium-card p-6 rounded-3xl">
                    <div class="flex justify-between items-start mb-4">
                        <p class="text-[10px] font-bold text-[#6B7280] uppercase tracking-[0.2em]">Plan Status</p>
                        <div class="w-8 h-8 {{ $client->plan_expiry && $client->plan_expiry->isPast() ? 'bg-red-500/10 text-red-500' : 'bg-green-500/10 text-green-500' }} rounded-lg flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-[#1A1D23] mb-1">
                        {{ $client->plan_expiry && $client->plan_expiry->isPast() ? 'Expired' : 'Professional' }}
                    </h3>
                    <p class="text-xs text-[#6B7280]">
                        {{ $client->plan_expiry ? 'Expires ' . $client->plan_expiry->format('d M, Y') : 'Perpetual Plan' }}
                    </p>
                    <div class="mt-4 pt-4 border-t border-[#E2E6EA] flex items-center justify-between">
                        <p class="text-[10px] text-[#9CA3AF] uppercase tracking-widest">Price Per File</p>
                        <p class="text-sm font-bold text-indigo-400 font-mono">₹{{ number_format($client->price_per_file, 0) }}</p>
                    </div>
                </div>

                {{-- Slots Remaining --}}
                <div class="premium-card p-6 rounded-3xl">
                    <div class="flex justify-between items-start mb-4">
                        <p class="text-[10px] font-bold text-[#6B7280] uppercase tracking-[0.2em]">Credits Remaining</p>
                        <div class="w-8 h-8 bg-indigo-500/10 rounded-lg flex items-center justify-center text-indigo-500">
                            <i data-lucide="coins" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold text-[#1A1D23] font-mono">{{ $slotsRemaining }}</h3>
                    <p class="text-xs text-[#6B7280] mt-1">of {{ $client->slots }} total slots</p>
                    <div class="mt-4 w-full bg-[#F0F2F5] rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $slotsRemaining > 10 ? 'bg-indigo-500' : ($slotsRemaining > 0 ? 'bg-amber-500' : 'bg-red-500') }}"
                            style="width: {{ $client->slots > 0 ? min(100, ($slotsRemaining / $client->slots) * 100) : 0 }}%"></div>
                    </div>
                </div>

                {{-- Slots Used --}}
                <div class="premium-card p-6 rounded-3xl">
                    <div class="flex justify-between items-start mb-4">
                        <p class="text-[10px] font-bold text-[#6B7280] uppercase tracking-[0.2em]">Credits Used</p>
                        <div class="w-8 h-8 bg-purple-500/10 rounded-lg flex items-center justify-center text-purple-500">
                            <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold text-[#1A1D23] font-mono">{{ $slotsUsed }}</h3>
                    <p class="text-xs text-[#6B7280] mt-1">files processed</p>
                    @if($lastTopup)
                        <div class="mt-4 pt-4 border-t border-[#E2E6EA]">
                            <p class="text-[10px] text-[#9CA3AF] uppercase tracking-widest">Last Top-up</p>
                            <p class="text-xs text-[#6B7280] mt-0.5 font-mono">+{{ $lastTopup->amount_requested }} slots · {{ $lastTopup->created_at->format('d M Y') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Top-up Request ─────────────────────────────────────────── --}}
            <div class="premium-card p-8 rounded-3xl">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-[#1A1D23]">Request Top-up</h2>
                        <p class="text-xs text-[#6B7280] mt-0.5">Add more credits to your account</p>
                    </div>
                    <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                        <i data-lucide="zap" class="w-5 h-5"></i>
                    </div>
                </div>

                <form action="{{ route('client.topup.store') }}" method="POST" class="space-y-6">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold text-[#6B7280] uppercase tracking-widest mb-3">Select Package</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <button type="button" onclick="setSlots(50)"
                                class="py-3 bg-[#F0F2F5] hover:bg-indigo-500/10 border border-[#E2E6EA] hover:border-indigo-500/30 rounded-xl text-xs font-bold text-[#1A1D23] hover:text-indigo-400 transition-all">50 Slots</button>
                            <button type="button" onclick="setSlots(100)"
                                class="py-3 bg-[#F0F2F5] hover:bg-indigo-500/10 border border-[#E2E6EA] hover:border-indigo-500/30 rounded-xl text-xs font-bold text-[#1A1D23] hover:text-indigo-400 transition-all">100 Slots</button>
                            <button type="button" onclick="setSlots(200)"
                                class="py-3 bg-[#F0F2F5] hover:bg-indigo-500/10 border border-[#E2E6EA] hover:border-indigo-500/30 rounded-xl text-xs font-bold text-[#1A1D23] hover:text-indigo-400 transition-all">200 Slots</button>
                        </div>
                        <input type="number" name="amount_requested" id="slot-input" min="1"
                            placeholder="Or enter custom amount..." oninput="updatePrice(this.value)"
                            class="w-full bg-[#F5F6F8] border border-[#E2E6EA] rounded-xl px-4 py-3 text-sm text-[#1A1D23] focus:outline-none focus:border-[#4F6EF7] focus:bg-white transition-colors placeholder-slate-600" required>
                    </div>

                    <div class="p-4 bg-indigo-500/5 border border-indigo-500/10 rounded-2xl flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-[#6B7280] uppercase tracking-widest">Total Payable</p>
                            <p id="price-display" class="text-3xl font-bold text-[#1A1D23] mt-0.5 font-mono">₹0</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold text-[#9CA3AF] uppercase tracking-widest">Rate</p>
                            <p class="text-xs text-indigo-400 font-bold font-mono">₹{{ number_format($client->price_per_file, 0) }} / slot</p>
                        </div>
                    </div>

                    <div class="p-4 bg-[#F5F6F8] border border-[#E2E6EA] rounded-2xl space-y-3">
                        <p class="text-[10px] font-black uppercase tracking-widest text-[#9CA3AF]">Payment Instructions</p>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-500/10 rounded-lg flex items-center justify-center text-green-500 flex-shrink-0">
                                <i data-lucide="smartphone" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-[#6B7280] font-bold uppercase tracking-widest">UPI ID</p>
                                <p class="text-sm font-bold text-[#1A1D23] font-mono">your-upi@ybl</p>
                            </div>
                        </div>
                        <p class="text-[10px] text-[#9CA3AF] leading-relaxed">Send the exact amount to the UPI ID above, then paste your <span class="text-indigo-400">Transaction / UTR Reference Number</span> below.</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-[#6B7280] uppercase tracking-widest mb-2">Transaction / UTR Reference Number</label>
                        <input type="text" name="transaction_id" required placeholder="e.g. 123456789012"
                            class="w-full bg-[#F5F6F8] border border-[#E2E6EA] rounded-xl px-4 py-3 text-sm text-[#1A1D23] focus:outline-none focus:border-[#4F6EF7] focus:bg-white transition-colors placeholder-slate-600">
                    </div>

                    <button type="submit"
                        class="w-full py-4 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-400 text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-indigo-600/30 transition-all flex justify-center items-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i> Submit Top-up Request
                    </button>
                </form>
            </div>

            {{-- ── Top-up History ─────────────────────────────────────────── --}}
            <div>
                <h2 class="text-sm font-bold text-[#1A1D23] uppercase tracking-widest mb-4">Top-up History</h2>
                <div class="premium-card rounded-2xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] text-[#9CA3AF] font-bold uppercase tracking-[0.25em] border-b border-[#E2E6EA]">
                                <th class="px-6 py-4">Date</th>
                                <th class="px-4 py-4">Slots Requested</th>
                                <th class="px-4 py-4">Amount</th>
                                <th class="px-4 py-4">Transaction ID</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E6EA]">
                            @forelse($topupHistory as $topup)
                                <tr class="hover:bg-[#FAFBFC]/[0.01] transition-all">
                                    <td class="px-6 py-4 text-[10px] text-[#6B7280] font-mono">{{ $topup->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="px-4 py-4">
                                        <span class="text-sm font-bold text-[#1A1D23] font-mono">+{{ $topup->amount_requested }}</span>
                                        <span class="text-[10px] text-[#9CA3AF] ml-1">slots</span>
                                    </td>
                                    <td class="px-4 py-4 text-sm font-bold text-indigo-400 font-mono">
                                        ₹{{ number_format($topup->amount_requested * $client->price_per_file, 0) }}
                                    </td>
                                    <td class="px-4 py-4 text-[10px] text-[#6B7280] font-mono">{{ $topup->transaction_id ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        @if($topup->status === 'approved')
                                            <span class="px-2.5 py-1 bg-green-500/10 text-green-400 rounded-lg text-[9px] font-bold border border-green-500/10">Approved</span>
                                        @elseif($topup->status === 'rejected')
                                            <span class="px-2.5 py-1 bg-red-500/10 text-red-400 rounded-lg text-[9px] font-bold border border-red-500/10">Rejected</span>
                                        @else
                                            <span class="px-2.5 py-1 bg-amber-500/10 text-amber-400 rounded-lg text-[9px] font-bold border border-amber-500/10">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-10 text-center text-xs text-[#9CA3AF]">No top-up history yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── Refund History ─────────────────────────────────────────── --}}
            @if($refundHistory->count() > 0)
            <div>
                <h2 class="text-sm font-bold text-[#1A1D23] uppercase tracking-widest mb-4">Refund History</h2>
                <div class="premium-card rounded-2xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] text-[#9CA3AF] font-bold uppercase tracking-[0.25em] border-b border-[#E2E6EA]">
                                <th class="px-6 py-4">Date</th>
                                <th class="px-4 py-4">Order</th>
                                <th class="px-4 py-4">Reason</th>
                                <th class="px-4 py-4">Admin Note</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8ECF0]">
                            @foreach($refundHistory as $refund)
                                <tr class="hover:bg-[#FAFBFC]/[0.01] transition-all">
                                    <td class="px-6 py-4 text-[10px] text-[#6B7280] font-mono">{{ $refund->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-4 text-[10px] text-[#6B7280] font-mono">#{{ $refund->order_id }}</td>
                                    <td class="px-4 py-4 text-[10px] text-[#6B7280] max-w-[160px] truncate">{{ $refund->reason ?? '—' }}</td>
                                    <td class="px-4 py-4 text-[10px] text-[#6B7280] max-w-[160px] truncate">{{ $refund->admin_note ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        @if($refund->status === 'approved')
                                            <span class="px-2.5 py-1 bg-green-500/10 text-green-400 rounded-lg text-[9px] font-bold border border-green-500/10">Approved</span>
                                        @elseif($refund->status === 'rejected')
                                            <span class="px-2.5 py-1 bg-red-500/10 text-red-400 rounded-lg text-[9px] font-bold border border-red-500/10">Rejected</span>
                                        @else
                                            <span class="px-2.5 py-1 bg-amber-500/10 text-amber-400 rounded-lg text-[9px] font-bold border border-amber-500/10">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </main>

    <script>
        lucide.createIcons();
        const pricePerSlot = {{ $client->price_per_file ?? 0 }};
        function setSlots(n) {
            document.getElementById('slot-input').value = n;
            updatePrice(n);
        }
        function updatePrice(val) {
            const n = parseInt(val) || 0;
            document.getElementById('price-display').textContent = '₹' + (n * pricePerSlot).toLocaleString('en-IN');
        }
    </script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary:   '#4F6EF7',
                        surface:   '#F5F7FA',
                        base:      '#FAFBFC',
                        ink:       '#1A1D23',
                        muted:     '#9CA3AF',
                        border:    '#E8ECF0',
                        subtle:    '#F0F3F8',
                    }
                }
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const html = document.documentElement;
            const themeToggle = document.getElementById('theme-toggle');
            const isDark = localStorage.getItem('theme') === 'dark';

            if (isDark) html.classList.add('dark');

            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    const darkMode = html.classList.toggle('dark');
                    localStorage.setItem('theme', darkMode ? 'dark' : 'light');
                });
            }
        });
    </script>
</body>
</html>

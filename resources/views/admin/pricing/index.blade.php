<x-admin-layout>

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Pricing</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">Client rates &amp; vendor payout rates</p>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-400 text-sm font-semibold mb-6">
            <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-400 text-sm font-semibold mb-6">
            <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i> {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-400 text-sm font-semibold mb-6">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div x-data="{ tab: 'clients' }" class="space-y-5">

        {{-- Tabs --}}
        <div class="flex items-center gap-1 border-b border-[#E2E6EA] dark:border-white/5">
            <button
                @click="tab = 'clients'"
                :class="tab === 'clients'
                    ? 'border-b-2 border-indigo-500 text-indigo-500 font-bold'
                    : 'text-gray-400 hover:text-gray-700 dark:hover:text-slate-300 font-semibold'"
                class="flex items-center gap-2 px-4 py-3 text-[11px] uppercase tracking-widest transition-colors -mb-px">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                Clients
                <span class="text-[9px] font-black bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-1.5 py-0.5 rounded-md leading-none ml-1">{{ $clients->count() }}</span>
            </button>
            <button
                @click="tab = 'vendors'"
                :class="tab === 'vendors'
                    ? 'border-b-2 border-indigo-500 text-indigo-500 font-bold'
                    : 'text-gray-400 hover:text-gray-700 dark:hover:text-slate-300 font-semibold'"
                class="flex items-center gap-2 px-4 py-3 text-[11px] uppercase tracking-widest transition-colors -mb-px">
                <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                Vendors
                <span class="text-[9px] font-black bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-1.5 py-0.5 rounded-md leading-none ml-1">{{ $vendors->count() }}</span>
            </button>
        </div>

        {{-- ── Clients Tab ──────────────────────────────────────────────────── --}}
        <div x-show="tab === 'clients'" x-cloak>
            <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.25em] border-b border-gray-100 dark:border-white/[0.05]">
                                <th class="px-6 py-4">Client</th>
                                <th class="px-4 py-4 text-center">Current Rate</th>
                                <th class="px-4 py-4 text-center">Slots</th>
                                <th class="px-6 py-4 text-right">Update</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                            @forelse($clients as $client)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-all">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-[11px] font-bold flex-shrink-0">
                                                {{ strtoupper(substr($client->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $client->name }}</p>
                                                <p class="text-[10px] font-mono text-gray-400 dark:text-slate-500">ID {{ $client->id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="text-sm font-mono font-bold text-gray-900 dark:text-white">₹{{ number_format($client->price_per_file, 0) }}</span>
                                        <span class="text-[9px] text-gray-400 dark:text-slate-500 ml-1">/ file</span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="text-xs font-mono text-gray-500 dark:text-slate-400">{{ $client->slots }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="openClientModal({{ $client->id }}, '{{ addslashes($client->name) }}', {{ $client->price_per_file }})"
                                            class="px-3 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-indigo-500/20 transition-all">
                                            Set Rate
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-14 text-center text-xs text-gray-400 dark:text-slate-500">No clients found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Vendors Tab ──────────────────────────────────────────────────── --}}
        <div x-show="tab === 'vendors'" x-cloak>
            <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.25em] border-b border-gray-100 dark:border-white/[0.05]">
                                <th class="px-6 py-4">Vendor</th>
                                <th class="px-4 py-4 text-center">Payout Rate</th>
                                <th class="px-4 py-4 text-center">Orders Done</th>
                                <th class="px-6 py-4 text-right">Update</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                            @forelse($vendors as $vendor)
                                @php
                                    $rate = $vendor->payout_rate ?? config('services.portal.vendor_payout_per_order', 0);
                                    $isCustom = $vendor->payout_rate !== null;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-all">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-xl bg-violet-500/10 text-violet-400 flex items-center justify-center text-[11px] font-bold flex-shrink-0">
                                                {{ strtoupper(substr($vendor->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $vendor->name }}</p>
                                                <p class="text-[10px] font-mono text-gray-400 dark:text-slate-500">ID {{ $vendor->portal_number }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="text-sm font-mono font-bold {{ $isCustom ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 dark:text-slate-500' }}">
                                            ₹{{ number_format($rate, 0) }}
                                        </span>
                                        <span class="text-[9px] text-gray-400 dark:text-slate-500 ml-1">/ order</span>
                                        @if(!$isCustom)
                                            <span class="ml-1 text-[8px] uppercase tracking-widest text-gray-400 dark:text-slate-600">(default)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="text-xs font-mono text-gray-500 dark:text-slate-400">{{ $vendor->orders()->where('status', 'delivered')->count() }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="openVendorModal({{ $vendor->id }}, '{{ addslashes($vendor->name) }}', {{ $rate }})"
                                            class="px-3 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-indigo-500/20 transition-all">
                                            Set Rate
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-14 text-center text-xs text-gray-400 dark:text-slate-500">No vendors found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Client Rate Modal ────────────────────────────────────────────────── --}}
    <div id="client-rate-modal"
        class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.08] rounded-2xl w-full max-w-sm p-8 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-7">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                        <i data-lucide="indian-rupee" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-900 dark:text-white font-bold">Set Client Rate</h3>
                        <p id="client-modal-name" class="text-[10px] text-gray-500 dark:text-slate-400 uppercase tracking-widest mt-0.5 font-mono truncate max-w-[160px]"></p>
                    </div>
                </div>
                <button onclick="document.getElementById('client-rate-modal').classList.add('hidden')"
                    class="text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="client-rate-form" method="POST" action="" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">Price Per File (₹)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-400 dark:text-slate-500">₹</span>
                        <input type="number" name="price_per_file" id="client-rate-input"
                            min="0" max="99999" step="1" required
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl pl-8 pr-4 py-3 text-sm font-mono text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100 dark:border-white/[0.05]">
                    <button type="submit"
                        class="w-full py-3 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-indigo-600/20 transition-all flex justify-center items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Save Rate
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Vendor Rate Modal ────────────────────────────────────────────────── --}}
    <div id="vendor-rate-modal"
        class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.08] rounded-2xl w-full max-w-sm p-8 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-7">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-violet-500/10 rounded-xl flex items-center justify-center text-violet-400 border border-violet-500/20">
                        <i data-lucide="indian-rupee" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-900 dark:text-white font-bold">Set Payout Rate</h3>
                        <p id="vendor-modal-name" class="text-[10px] text-gray-500 dark:text-slate-400 uppercase tracking-widest mt-0.5 font-mono truncate max-w-[160px]"></p>
                    </div>
                </div>
                <button onclick="document.getElementById('vendor-rate-modal').classList.add('hidden')"
                    class="text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="vendor-rate-form" method="POST" action="" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">Payout Per Order (₹)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-400 dark:text-slate-500">₹</span>
                        <input type="number" name="payout_rate" id="vendor-rate-input"
                            min="0" max="99999" step="1" required
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl pl-8 pr-4 py-3 text-sm font-mono text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
                    </div>
                    <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-1.5">Overrides the global default payout rate for this vendor only.</p>
                </div>
                <div class="pt-4 border-t border-gray-100 dark:border-white/[0.05]">
                    <button type="submit"
                        class="w-full py-3 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-indigo-600/20 transition-all flex justify-center items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Save Rate
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openClientModal(id, name, currentRate) {
            document.getElementById('client-modal-name').textContent = name;
            document.getElementById('client-rate-input').value = currentRate;
            document.getElementById('client-rate-form').action = '/admin/pricing/client/' + id;
            document.getElementById('client-rate-modal').classList.remove('hidden');
        }

        function openVendorModal(id, name, currentRate) {
            document.getElementById('vendor-modal-name').textContent = name;
            document.getElementById('vendor-rate-input').value = currentRate;
            document.getElementById('vendor-rate-form').action = '/admin/pricing/vendor/' + id;
            document.getElementById('vendor-rate-modal').classList.remove('hidden');
        }
    </script>

</x-admin-layout>

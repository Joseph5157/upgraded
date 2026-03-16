<x-admin-layout>

    {{-- Flash Banners --}}
    @if(session('success'))
        <div
            class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-400 text-sm font-semibold mb-2">
            <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-400 text-sm font-semibold mb-2">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-2">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">System Overview</h1>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mt-1">ACTIVE_NODE_01 &bull; {{ now()->format('d M Y, H:i') }}</p>
        </div>
        <button onclick="document.getElementById('create-account-modal').classList.remove('hidden')"
            class="flex items-center gap-2 px-4 py-2 bg-red-600/10 hover:bg-red-600/20 text-red-400 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-red-600/20 transition-all">
            <i data-lucide="user-plus" class="w-3.5 h-3.5"></i> Issue Account
        </button>
    </div>

    {{-- System Pulse Metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div
            class="bg-[#0a0a0c] border border-white/5 p-6 rounded-2xl group hover:border-green-500/20 transition-all">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Pulse &bull; Files Today</p>
            <div class="flex items-end justify-between">
                <h2 class="text-4xl font-extrabold text-white font-mono tracking-tight">
                    {{ $stats['total_processed_today'] }}</h2>
                <div class="w-8 h-8 bg-green-500/15 rounded-xl flex items-center justify-center text-green-500">
                    <i data-lucide="trending-up" class="w-4 h-4"></i>
                </div>
            </div>
        </div>

        <div
            class="bg-[#0a0a0c] border border-white/5 p-6 rounded-2xl group hover:border-amber-500/20 transition-all">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Pending &bull; Pool Size</p>
            <div class="flex items-end justify-between">
                <h2 class="text-4xl font-extrabold text-white font-mono tracking-tight">{{ $stats['pending_pool'] }}</h2>
                <div class="w-8 h-8 bg-amber-500/15 rounded-xl flex items-center justify-center text-amber-500">
                    <i data-lucide="database" class="w-4 h-4"></i>
                </div>
            </div>
        </div>

        <div
            class="bg-[#0a0a0c] border border-white/5 p-6 rounded-2xl group hover:border-indigo-500/20 transition-all">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Active &bull; Workforce</p>
            <div class="flex items-end justify-between">
                <h2 class="text-4xl font-extrabold text-white font-mono tracking-tight">{{ $stats['active_vendors'] }}</h2>
                <div class="w-8 h-8 bg-indigo-500/15 rounded-xl flex items-center justify-center text-indigo-500">
                    <i data-lucide="users-2" class="w-4 h-4"></i>
                </div>
            </div>
        </div>

        <div
            class="bg-[#0a0a0c] border border-white/5 p-6 rounded-2xl group hover:border-purple-500/20 transition-all">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Growth &bull; New Clients</p>
            <div class="flex items-end justify-between">
                <h2 class="text-4xl font-extrabold text-white font-mono tracking-tight">{{ $stats['new_clients_today'] }}
                </h2>
                <div class="w-8 h-8 bg-purple-500/15 rounded-xl flex items-center justify-center text-purple-500">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Grid: Vendor Table + System Pulse --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Vendor Performance --}}
        <div class="lg:col-span-8">
            <div class="bg-[#0a0a0c] border border-white/5 rounded-2xl p-7">
                <div class="flex justify-between items-center mb-7">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-red-500/15 rounded-xl flex items-center justify-center text-red-500">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-white">Vendor Performance</h2>
                            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-widest">Top contributors today</p>
                        </div>
                    </div>
                    <span class="text-[9px] font-mono text-slate-500 uppercase tracking-widest">PHASE_01</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="text-[10px] text-slate-600 font-bold uppercase tracking-widest border-b border-white/[0.04] bg-white/[0.02]">
                                <th class="pb-4 px-3">Vendor</th>
                                <th class="pb-4 text-center">Files Today</th>
                                <th class="pb-4 text-center">Lifetime</th>
                                <th class="pb-4 text-right">Efficiency</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.04]">
                            @forelse($vendorPerformance as $vendor)
                                <tr class="group hover:bg-white/[0.03] transition-all">
                                    <td class="py-4 px-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 bg-white/[0.05] rounded-xl flex items-center justify-center text-slate-400 group-hover:bg-red-500/10 group-hover:text-red-500 transition-all border border-white/[0.05] flex-shrink-0">
                                                <i data-lucide="user" class="w-4 h-4"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-300">{{ $vendor->name }}</p>
                                                <p class="text-[11px] text-slate-600 font-mono">{{ $vendor->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <span
                                            class="px-2.5 py-1 bg-green-500/10 text-green-500 rounded-lg text-xs font-bold font-mono border border-green-500/10">{{ $vendor->today_jobs }}</span>
                                    </td>
                                    <td class="py-4 text-center text-sm font-bold text-slate-300 font-mono">
                                        {{ $vendor->total_jobs }}</td>
                                    <td class="py-4 text-right">
                                        <div class="flex justify-end items-center gap-2">
                                            <div class="w-20 h-1 bg-white/[0.06] rounded-full overflow-hidden">
                                                <div class="bg-[#4F6EF7] h-full rounded-full"
                                                    style="width: {{ min(100, $vendor->today_jobs * 10) }}%"></div>
                                            </div>
                                            <span
                                                class="text-[9px] font-mono text-slate-600">{{ min(100, $vendor->today_jobs * 10) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-xs text-slate-500">No vendor activity
                                        today.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- System Pulse Feed --}}
        <div class="lg:col-span-4">
            <div class="bg-[#0a0a0c] border border-white/5 rounded-2xl p-7 h-full flex flex-col">
                <div class="flex items-center justify-between mb-7">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest">System Pulse</h2>
                    <span class="w-2 h-2 bg-[#4F6EF7] rounded-full animate-ping"></span>
                </div>

                <div class="space-y-6 flex-1">
                    @forelse($recentOrders as $order)
                        <div class="relative pl-7 group">
                            <div class="absolute left-0 top-1 w-1.5 h-1.5 rounded-full z-10
                                    @if($order->status->value === 'delivered') bg-green-500
                                    @elseif($order->status->value === 'pending') bg-amber-500
                                    @else bg-red-500 @endif">
                            </div>
                            <div class="absolute left-[2px] top-4 w-px h-full bg-white/[0.05] group-last:hidden"></div>
                            <div class="space-y-0.5">
                                <div class="flex justify-between items-start gap-2">
                                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-tight">
                                        @if($order->status->value === 'delivered') Result Uploaded @else Processing Stream @endif
                                    </p>
                                    <span
                                        class="text-[10px] text-slate-500 font-mono flex-shrink-0">{{ $order->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-[10px] text-slate-400 font-mono line-clamp-1">{{ $order->client?->name ?? 'Unknown' }} &bull; {{ $order->files_count }} files</p>
                                @if($order->vendor)
                                    <p class="text-[9px] text-red-500/50 font-bold uppercase tracking-widest">&rarr; {{ $order->vendor->name }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-8">No recent activity.</p>
                    @endforelse
                </div>

                <div class="mt-6 pt-6 border-t border-white/[0.05]">
                    <button
                        class="w-full py-2.5 bg-indigo-600/10 hover:bg-indigo-600/20 text-[10px] font-bold text-indigo-400 uppercase tracking-widest rounded-xl border border-indigo-500/20 transition-all">
                        View Security Logs
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <button onclick="document.getElementById('create-account-modal').classList.remove('hidden')"
            class="bg-white/[0.02] border border-white/5 p-6 rounded-2xl space-y-3 hover:border-red-500/20 transition-all cursor-pointer group text-left w-full">
            <div
                class="w-10 h-10 bg-white/[0.05] rounded-xl flex items-center justify-center text-slate-400 group-hover:text-red-500 group-hover:bg-red-500/10 transition-colors">
                <i data-lucide="user-plus" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-white">Issue Account</h3>
                <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">Provision vendor, client, or admin.</p>
            </div>
        </button>

        <a href="{{ route('admin.finance.matrix') }}"
            class="bg-white/[0.02] border border-white/5 p-6 rounded-2xl space-y-3 hover:border-red-500/20 transition-all group block">
            <div
                class="w-10 h-10 bg-white/[0.05] rounded-xl flex items-center justify-center text-slate-400 group-hover:text-red-500 group-hover:bg-red-500/10 transition-colors">
                <i data-lucide="building" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-white">Client Matrix</h3>
                <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">Audit credit usage and limits.</p>
            </div>
        </a>

        <a href="{{ route('admin.finance.ledger') }}"
            class="bg-white/[0.02] border border-white/5 p-6 rounded-2xl space-y-3 hover:border-green-500/20 transition-all group block">
            <div
                class="w-10 h-10 bg-white/[0.05] rounded-xl flex items-center justify-center text-slate-400 group-hover:text-green-500 group-hover:bg-green-500/10 transition-colors">
                <i data-lucide="trending-up" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-white">Ledger History</h3>
                <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">Daily P&L snapshots.</p>
            </div>
        </a>

        <div
            class="bg-white/[0.02] border border-white/5 p-6 rounded-2xl space-y-3 opacity-40 cursor-not-allowed group">
            <div class="w-10 h-10 bg-white/[0.05] rounded-xl flex items-center justify-center text-slate-400">
                <i data-lucide="settings-2" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-white flex items-center gap-2">System Overlays
                    <span
                        class="text-[7px] text-red-500/50 bg-red-500/5 border border-red-500/10 px-1.5 py-0.5 rounded font-black uppercase tracking-widest">Soon</span>
                </h3>
                <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">Config, limits, and API keys.</p>
            </div>
        </div>
    </div>

    {{-- Issue New Account Modal --}}
    <div id="create-account-modal"
        class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-[#0a0a0c] border border-white/10 rounded-2xl w-full max-w-md p-8 shadow-2xl"
            onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-7">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-red-500/10 rounded-xl flex items-center justify-center text-red-500 border border-red-500/20">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-bold">Provision Account</h3>
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest mt-0.5">System Access</p>
                    </div>
                </div>
                <button onclick="document.getElementById('create-account-modal').classList.add('hidden')"
                    class="text-slate-500 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('admin.accounts.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Account
                        Type</label>
                    <select name="role" id="modal-role" onchange="toggleRoleFields()" required
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/50 appearance-none">
                        @can('create-admin')
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }} class="bg-[#0d0d0f]">System Admin</option>
                        @endcan
                        <option value="vendor" {{ old('role') === 'vendor' ? 'selected' : '' }} class="bg-[#0d0d0f]">
                            Processing Vendor</option>
                        <option value="client" {{ old('role') === 'client' ? 'selected' : '' }} class="bg-[#0d0d0f]">
                            Client Organization</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Full Name /
                        Org Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/50 transition-colors placeholder-slate-700">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Email
                        Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/50 transition-colors placeholder-slate-700">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Temporary
                        Password</label>
                    <input type="password" name="password" required
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/50 transition-colors placeholder-slate-700">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/50 transition-colors placeholder-slate-700">
                </div>
                <div id="super-password-section" class="hidden">
                    <div class="p-4 bg-red-500/5 border border-red-500/20 rounded-xl space-y-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-red-400"></i>
                            <label class="text-[10px] font-bold text-red-400 uppercase tracking-widest">SYSTEM_ROOT Authorization Required</label>
                        </div>
                        <input type="password" name="super_password" id="super_password"
                            class="w-full bg-white/5 border border-red-500/20 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/50 transition-colors font-mono placeholder-slate-700"
                            placeholder="Enter your SYSTEM_ROOT password">
                        @error('super_password')
                            <p class="text-[10px] text-red-400">{{ $message }}</p>
                        @enderror
                        <ul class="text-[9px] text-slate-500 space-y-1 list-disc list-inside">
                            <li>Admin accounts have full portal access. This action is logged.</li>
                            <li>Maximum 5 admin accounts are allowed system-wide.</li>
                            <li>Enter your own password to authorize this creation.</li>
                        </ul>
                    </div>
                </div>
                <div id="slots-field" class="{{ old('role', 'vendor') === 'client' ? '' : 'hidden' }}">
                    <div class="p-4 bg-amber-500/5 border border-amber-500/20 rounded-xl space-y-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="files" class="w-3.5 h-3.5 text-amber-400"></i>
                            <label class="text-[10px] font-bold text-amber-400 uppercase tracking-widest">File Credit Limit</label>
                        </div>
                        <input type="number" name="slots" value="{{ old('slots', 50) }}" min="1" max="10000"
                            class="w-full bg-white/5 border border-amber-500/20 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/50 transition-colors font-mono placeholder-slate-700"
                            placeholder="e.g. 50">
                        <p class="text-[9px] text-slate-500">Number of files this client is allowed to submit. Can be refilled later from the Client Matrix.</p>
                    </div>
                </div>
                <div class="pt-4 border-t border-white/[0.05]">
                    <button type="submit"
                        class="w-full py-3.5 bg-red-600/10 hover:bg-red-600/20 text-red-500 text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-red-600/20 transition-all flex justify-center items-center gap-2">
                        <i data-lucide="zap" class="w-4 h-4"></i> Execute Provisioning
                    </button>
                </div>
                <script>
                    function toggleRoleFields() {
                        const role = document.getElementById('modal-role').value;
                        document.getElementById('slots-field').classList.toggle('hidden', role !== 'client');
                        const superSection = document.getElementById('super-password-section');
                        superSection.classList.toggle('hidden', role !== 'admin');
                        if (role !== 'admin') {
                            document.getElementById('super_password').value = '';
                        }
                    }
                    toggleRoleFields();
                </script>
            </form>
        </div>
    </div>

    {{-- Re-open modal on validation failure --}}
    @if($errors->any())
        <script>document.getElementById('create-account-modal').classList.remove('hidden');</script>
    @endif

</x-admin-layout>

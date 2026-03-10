<x-admin-layout>

    @if(session('success'))
        <div
            class="bg-green-500/10 border border-green-500/20 text-green-500 px-6 py-4 rounded-2xl text-sm font-bold flex items-center gap-3 mb-6">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-6 py-4 rounded-2xl text-sm font-bold mb-6">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header Content Area --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-xl font-bold text-white tracking-tight">Client Matrix</h1>
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.3em] font-mono mt-0.5">Auditing &
                Credits</p>
        </div>
    </div>

    {{-- Main Container - Matches Ledger History Width --}}
    <div class="space-y-8">
        <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl">
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-4">
                    <div
                        class="w-10 h-10 bg-purple-500/10 rounded-2xl flex items-center justify-center text-purple-500">
                        <i data-lucide="building" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Client Organizations</h2>
                        <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest mt-0.5">Manage quotas
                        </p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="text-[9px] text-slate-700 font-bold uppercase tracking-[0.25em] border-b border-white/5">
                            <th class="pb-6 px-4">Client / Org name</th>
                            <th class="pb-6 text-center">Total Slots</th>
                            <th class="pb-6 text-center">Used Slots</th>
                            <th class="pb-6 text-center">Rate/File</th>
                            <th class="pb-6 text-center">Status</th>
                            <th class="pb-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.02]">
                        @foreach($clients as $client)
                            <tr class="group hover:bg-white/[0.01] transition-all">
                                <td class="py-6 px-4">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center text-slate-500 group-hover:bg-purple-500/10 group-hover:text-purple-500 transition-all border border-white/5">
                                            <i data-lucide="building" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-300">{{ $client->name }}</h4>
                                            <p class="text-[9px] text-slate-600 font-mono">ID: {{ $client->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-6 text-center text-sm font-bold text-slate-500 font-mono">
                                    {{ $client->slots }}
                                </td>
                                <td class="py-6 text-center">
                                    <span
                                        class="px-3 py-1 bg-amber-500/10 text-amber-500 rounded-lg text-xs font-bold font-mono border border-amber-500/10">
                                        {{ $client->orders_count }}
                                    </span>
                                </td>
                                <td class="py-6 text-center text-sm font-mono text-slate-400">
                                    ₹{{ number_format($client->price_per_file, 0) }}
                                </td>
                                <td class="py-6 text-center">
                                    @if($client->status === 'active')
                                        <span
                                            class="px-3 py-1 bg-green-500/10 text-green-500 rounded-lg text-xs font-bold border border-green-500/10 uppercase tracking-wider">
                                            Active
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 bg-red-500/10 text-red-500 rounded-lg text-xs font-bold border border-red-500/10 uppercase tracking-wider">
                                            Suspended
                                        </span>
                                    @endif
                                </td>
                                <td class="py-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            onclick="openRefillModal({{ $client->id }}, '{{ addslashes($client->name) }}')"
                                            class="px-4 py-2 text-[10px] font-bold uppercase text-green-500 bg-green-500/10 hover:bg-green-500/20 rounded-xl transition-all border border-green-500/20">
                                            Refill
                                        </button>
                                        <button
                                            onclick="openCreditsModal({{ $client->id }}, {{ $client->slots }}, '{{ addslashes($client->name) }}', '{{ $client->status }}', {{ $client->price_per_file }})"
                                            class="px-4 py-2 text-[10px] font-bold uppercase text-slate-400 bg-white/5 hover:bg-white/10 rounded-xl transition-all border border-white/5">
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pending Top-up Requests --}}
        @if($pendingTopups->count() > 0)
            <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 mt-8 shadow-2xl">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-500">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Pending Top-up Requests</h2>
                        <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest mt-0.5 font-mono">
                            {{ $pendingTopups->count() }} awaiting review
                        </p>
                    </div>
                    <span
                        class="ml-auto px-3 py-1 bg-amber-500/10 text-amber-400 text-xs font-bold font-mono rounded-full border border-amber-500/20 animate-pulse">
                        {{ $pendingTopups->count() }} Pending
                    </span>
                </div>

                <div class="space-y-4">
                    @foreach($pendingTopups as $topup)
                        <div
                            class="flex flex-col sm:flex-row sm:items-center gap-4 p-5 bg-white/[0.02] border border-white/5 rounded-2xl hover:border-amber-500/20 transition-all">
                            <div class="flex items-center gap-4 flex-1">
                                <div
                                    class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center text-slate-400 border border-white/5 flex-shrink-0">
                                    <i data-lucide="building" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-300">{{ $topup->client->name }}</h4>
                                    <p class="text-[10px] text-slate-600 font-mono mt-0.5">Requested: <span
                                            class="text-slate-400">{{ $topup->amount_requested }} slots</span></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6 text-center">
                                <div>
                                    <p class="text-[9px] text-slate-600 font-bold uppercase tracking-widest">Value</p>
                                    <p class="text-sm font-bold text-white font-mono">
                                        ₹{{ number_format($topup->client->price_per_file * $topup->amount_requested, 0) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[9px] text-slate-600 font-bold uppercase tracking-widest">UTR / Txn ID</p>
                                    <p class="text-xs font-mono text-indigo-400">{{ $topup->transaction_id ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] text-slate-600 font-bold uppercase tracking-widest">Submitted</p>
                                    <p class="text-xs text-slate-500 font-mono">{{ $topup->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 sm:ml-4 flex-shrink-0">
                                <form method="POST" action="{{ route('admin.topup.approve', $topup) }}">
                                    @csrf
                                    <button type="submit"
                                        class="px-4 py-2 bg-green-500/10 hover:bg-green-500/20 text-green-400 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-green-500/20 transition-all flex items-center gap-1.5">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.topup.reject', $topup) }}">
                                    @csrf
                                    <button type="submit"
                                        class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-red-500/20 transition-all flex items-center gap-1.5">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i> Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Adjust Credits Modal --}}
    <div id="credits-modal"
        class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-[#0a0a0c] border border-white/10 rounded-[2.5rem] w-full max-w-sm p-8 shadow-2xl"
            onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-500 border border-purple-500/20">
                        <i data-lucide="sliders" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-bold" id="modal-client-name">Adjust Credits</h3>
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest mt-0.5">Quota Management</p>
                    </div>
                </div>
                <button onclick="document.getElementById('credits-modal').classList.add('hidden')"
                    class="text-slate-500 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="credits-form" method="POST" action="" class="space-y-5">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Total
                        Slots</label>
                    <input type="number" name="slots" id="modal-client-slots" required min="0"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white font-mono focus:outline-none focus:border-purple-500/50 transition-colors">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Price Per
                        File (₹)</label>
                    <input type="number" name="price_per_file" id="modal-client-price" required min="0" step="0.01"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white font-mono focus:outline-none focus:border-purple-500/50 transition-colors">
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Status</label>
                    <select name="status" id="modal-client-status" required
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-purple-500/50 appearance-none">
                        <option value="active" class="bg-[#0a0a0c]">Active</option>
                        <option value="suspended" class="bg-[#0a0a0c]">Suspended</option>
                    </select>
                </div>
                <div class="pt-4 mt-8 border-t border-white/5">
                    <button type="submit"
                        class="w-full py-4 bg-purple-600/10 hover:bg-purple-600/20 text-purple-500 text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-purple-600/20 transition-all flex justify-center items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Refill Credits Modal --}}
    <div id="refill-modal"
        class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-[#0a0a0c] border border-white/10 rounded-[2.5rem] w-full max-w-sm p-8 shadow-2xl"
            onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-green-500/10 rounded-xl flex items-center justify-center text-green-500 border border-green-500/20">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-bold" id="modal-refill-name">Refill Client</h3>
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest mt-0.5">Add Slots</p>
                    </div>
                </div>
                <button onclick="document.getElementById('refill-modal').classList.add('hidden')"
                    class="text-slate-500 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="refill-form" method="POST" action="" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Number of
                        files to add</label>
                    <input type="number" name="additional_slots" required min="1"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white font-mono focus:outline-none focus:border-green-500/50 transition-colors">
                </div>
                <div class="pt-4 mt-8 border-t border-white/5">
                    <button type="submit"
                        class="w-full py-4 bg-green-600/10 hover:bg-green-600/20 text-green-500 text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-green-600/20 transition-all flex justify-center items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i> Confirm Top-up
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreditsModal(id, slots, name, status, price) {
            document.getElementById('modal-client-name').innerText = name;
            document.getElementById('modal-client-slots').value = slots;
            document.getElementById('modal-client-status').value = status;
            document.getElementById('modal-client-price').value = price;
            document.getElementById('credits-form').action = '/admin/matrix/' + id;
            document.getElementById('credits-modal').classList.remove('hidden');
        }

        function openRefillModal(id, name) {
            document.getElementById('modal-refill-name').innerText = name;
            document.getElementById('refill-form').action = '/admin/matrix/' + id + '/refill';
            document.getElementById('refill-modal').classList.remove('hidden');
        }
    </script>
</x-admin-layout>
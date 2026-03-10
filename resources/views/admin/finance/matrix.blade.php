<x-admin-layout>
    <div class="min-h-screen bg-[#111113] text-slate-400">

        {{-- Flash --}}
        @if(session('success'))
            <div
                class="mx-10 mt-6 flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-400 text-sm font-semibold">
                <i data-lucide="check-circle" class="w-4 h-4"></i> {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div
                class="mx-10 mt-6 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-400 text-sm font-semibold">
                @foreach($errors->all() as $e) <p>{{ $e }}</p> @endforeach
            </div>
        @endif

        {{-- Header --}}
        <div class="px-10 py-8 border-b border-white/[0.04] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-white tracking-tight">Client Matrix</h1>
                <p class="text-[10px] text-slate-600 uppercase tracking-[0.25em] mt-0.5">Credit Management & Unit
                    Pricing</p>
            </div>
            <span class="text-[10px] font-bold text-slate-600 uppercase tracking-widest">{{ $clients->count() }}
                Clients</span>
        </div>

        <main class="px-10 py-8 space-y-8">

            {{-- Pending Top-ups --}}
            @if($pendingTopups->count() > 0)
                <div class="border border-white/5 bg-[#0d0d0f] rounded-2xl p-8 shadow-2xl">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 bg-amber-500/10 rounded-xl flex items-center justify-center text-amber-500">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-white">Pending Top-up Requests</h2>
                            <p class="text-[9px] text-amber-500/60 uppercase tracking-widest">{{ $pendingTopups->count() }}
                                awaiting approval</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        @foreach($pendingTopups as $topup)
                            <div
                                class="flex flex-wrap items-center gap-4 p-4 bg-white/[0.02] border border-white/5 rounded-2xl">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-300">{{ $topup->client->name }}</p>
                                    <p class="text-[10px] text-slate-600 font-mono">{{ $topup->amount_requested }} slots • UTR:
                                        <span class="text-indigo-400">{{ $topup->transaction_id }}</span> •
                                        {{ $topup->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <p class="text-sm font-bold text-white">
                                    ₹{{ number_format($topup->client->price_per_file * $topup->amount_requested, 0) }}</p>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('admin.topup.approve', $topup) }}">
                                        @csrf
                                        <button
                                            class="px-4 py-2 bg-green-500/10 hover:bg-green-500/20 text-green-400 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-green-500/20 transition-all flex items-center gap-1">
                                            <i data-lucide="check" class="w-3 h-3"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.topup.reject', $topup) }}">
                                        @csrf
                                        <button
                                            class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-red-500/20 transition-all flex items-center gap-1">
                                            <i data-lucide="x" class="w-3 h-3"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Clients Table --}}
            <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-8 shadow-2xl overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="text-[9px] text-slate-700 font-bold uppercase tracking-[0.2em] border-b border-white/[0.04]">
                            <th class="py-5 px-6">Client</th>
                            <th class="py-5 text-center">Total Slots</th>
                            <th class="py-5 text-center">Used</th>
                            <th class="py-5 text-center">Remaining</th>
                            <th class="py-5 text-center">Rate / File</th>
                            <th class="py-5 text-center">Status</th>
                            <th class="py-5 text-right px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.03]">
                        @foreach($clients as $client)
                            @php $remaining = $client->slots - $client->orders_count; @endphp
                            <tr class="group hover:bg-white/[0.01] transition-all">
                                <td class="py-5 px-6">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 bg-white/[0.04] rounded-xl flex items-center justify-center text-slate-500 border border-white/[0.04]">
                                            <i data-lucide="building" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-300">{{ $client->name }}</p>
                                            <p class="text-[9px] text-slate-600 font-mono">ID: {{ $client->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-5 text-center text-sm font-bold text-slate-400 font-mono">{{ $client->slots }}
                                </td>
                                <td class="py-5 text-center">
                                    <span
                                        class="px-2.5 py-1 bg-amber-500/10 text-amber-400 rounded-lg text-xs font-bold font-mono border border-amber-500/10">{{ $client->orders_count }}</span>
                                </td>
                                <td class="py-5 text-center">
                                    <span
                                        class="px-2.5 py-1 text-xs font-bold font-mono rounded-lg border
                                        {{ $remaining <= 0 ? 'bg-red-500/10 text-red-400 border-red-500/10' : ($remaining <= 10 ? 'bg-amber-500/10 text-amber-400 border-amber-500/10' : 'bg-green-500/10 text-green-400 border-green-500/10') }}">
                                        {{ max(0, $remaining) }}
                                    </span>
                                </td>
                                <td class="py-5 text-center text-sm font-mono text-slate-400">
                                    ₹{{ number_format($client->price_per_file, 0) }}</td>
                                <td class="py-5 text-center">
                                    @if($client->status === 'active')
                                        <span
                                            class="px-2.5 py-1 bg-green-500/10 text-green-400 text-[10px] font-bold rounded-lg border border-green-500/10 uppercase tracking-wider">Active</span>
                                    @else
                                        <span
                                            class="px-2.5 py-1 bg-red-500/10 text-red-400 text-[10px] font-bold rounded-lg border border-red-500/10 uppercase tracking-wider">Suspended</span>
                                    @endif
                                </td>
                                <td class="py-5 px-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button onclick="openRefill({{ $client->id }}, '{{ addslashes($client->name) }}')"
                                            class="px-3 py-1.5 bg-green-500/10 hover:bg-green-500/20 text-green-400 text-[9px] font-bold uppercase tracking-widest rounded-xl transition-all border border-green-500/20">Refill</button>
                                        <button
                                            onclick="openEdit({{ $client->id }}, {{ $client->slots }}, '{{ addslashes($client->name) }}', '{{ $client->status }}', {{ $client->price_per_file }})"
                                            class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-slate-400 text-[9px] font-bold uppercase tracking-widest rounded-xl transition-all border border-white/5">Edit</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    {{-- Edit Modal --}}
    <div id="edit-modal"
        class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-[#111113] border border-white/10 rounded-3xl w-full max-w-sm p-8 shadow-2xl"
            onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-7">
                <h3 class="text-white font-bold" id="edit-title">Edit Client</h3>
                <button onclick="document.getElementById('edit-modal').classList.add('hidden')"
                    class="text-slate-500 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form id="edit-form" method="POST" action="" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-[9px] font-bold text-slate-600 uppercase tracking-widest mb-1.5">Total
                        Slots</label>
                    <input type="number" name="slots" id="edit-slots" required min="0"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/40">
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-slate-600 uppercase tracking-widest mb-1.5">Price /
                        File (₹)</label>
                    <input type="number" name="price_per_file" id="edit-price" required min="0" step="0.01"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/40">
                </div>
                <div>
                    <label
                        class="block text-[9px] font-bold text-slate-600 uppercase tracking-widest mb-1.5">Status</label>
                    <select name="status" id="edit-status"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-red-500/40 appearance-none">
                        <option value="active" class="bg-[#111113]">Active</option>
                        <option value="suspended" class="bg-[#111113]">Suspended</option>
                    </select>
                </div>
                <button type="submit"
                    class="w-full py-3.5 mt-4 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-red-500/20 transition-all">Save
                    Changes</button>
            </form>
        </div>
    </div>

    {{-- Refill Modal --}}
    <div id="refill-modal"
        class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-[#111113] border border-white/10 rounded-3xl w-full max-w-sm p-8 shadow-2xl"
            onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-7">
                <h3 class="text-white font-bold" id="refill-title">Refill Slots</h3>
                <button onclick="document.getElementById('refill-modal').classList.add('hidden')"
                    class="text-slate-500 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form id="refill-form" method="POST" action="" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[9px] font-bold text-slate-600 uppercase tracking-widest mb-1.5">Slots to
                        Add</label>
                    <input type="number" name="additional_slots" required min="1"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-green-500/40">
                </div>
                <button type="submit"
                    class="w-full py-3.5 mt-4 bg-green-500/10 hover:bg-green-500/20 text-green-400 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-green-500/20 transition-all">Confirm
                    Refill</button>
            </form>
        </div>
    </div>

    <script>
        function openEdit(id, slots, name, status, price) {
            document.getElementById('edit-title').textContent = name;
            document.getElementById('edit-slots').value = slots;
            document.getElementById('edit-status').value = status;
            document.getElementById('edit-price').value = price;
            document.getElementById('edit-form').action = '/admin/finance/matrix/' + id;
            document.getElementById('edit-modal').classList.remove('hidden');
        }
        function openRefill(id, name) {
            document.getElementById('refill-title').textContent = 'Refill — ' + name;
            document.getElementById('refill-form').action = '/admin/finance/matrix/' + id + '/refill';
            document.getElementById('refill-modal').classList.remove('hidden');
        }
        lucide.createIcons();
    </script>
</x-admin-layout>
<x-admin-layout>

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Client Upload Links</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">Generate and manage shareable upload links per client</p>
        </div>
        <button onclick="document.getElementById('create-link-modal').classList.remove('hidden')"
            class="flex items-center gap-2 px-4 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-indigo-600/20 transition-all">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i> New Link
        </button>
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

    {{-- ── Links Table ──────────────────────────────────────────────────────── --}}
    <div class="space-y-4">
        @forelse($clients as $client)
            @if($client->links->isNotEmpty())
                <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">

                    {{-- Client header --}}
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-[#E8ECF0] dark:border-white/[0.05]">
                        <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-[11px] font-bold flex-shrink-0">
                            {{ strtoupper(substr($client->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $client->name }}</p>
                            <p class="text-[10px] font-mono text-gray-400 dark:text-slate-500">{{ $client->links->count() }} link{{ $client->links->count() !== 1 ? 's' : '' }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.25em] border-b border-gray-100 dark:border-white/[0.05]">
                                    <th class="px-6 py-3">Upload URL</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-center">Created</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                                @foreach($client->links as $link)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-all">

                                        {{-- URL --}}
                                        <td class="px-6 py-3">
                                            <div class="flex items-center gap-2">
                                                <code class="text-[11px] font-mono text-gray-600 dark:text-slate-400 truncate max-w-xs">
                                                    {{ url('/u/' . $link->token) }}
                                                </code>
                                                <button
                                                    onclick="copyLink('{{ url('/u/' . $link->token) }}', this)"
                                                    class="flex-shrink-0 p-1.5 rounded-lg bg-gray-100 dark:bg-white/[0.05] hover:bg-indigo-500/10 text-gray-400 hover:text-indigo-400 transition-all border border-gray-200 dark:border-white/[0.08]"
                                                    title="Copy link">
                                                    <i data-lucide="copy" class="w-3 h-3"></i>
                                                </button>
                                            </div>
                                        </td>

                                        {{-- Status --}}
                                        <td class="px-4 py-3 text-center">
                                            @if($link->is_active)
                                                <span class="px-2.5 py-1 bg-green-500/10 text-green-400 rounded-lg text-[9px] font-bold uppercase tracking-widest border border-green-500/20">Active</span>
                                            @else
                                                <span class="px-2.5 py-1 bg-gray-100 dark:bg-white/[0.05] text-gray-400 dark:text-slate-500 rounded-lg text-[9px] font-bold uppercase tracking-widest border border-gray-200 dark:border-white/[0.08]">Inactive</span>
                                            @endif
                                        </td>

                                        {{-- Created --}}
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-[10px] font-mono text-gray-400 dark:text-slate-500">{{ $link->created_at->format('d M Y') }}</span>
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-6 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">

                                                {{-- Toggle active/inactive --}}
                                                <form method="POST" action="{{ route('admin.client-links.toggle', $link) }}">
                                                    @csrf
                                                    <button type="submit"
                                                        class="px-3 py-1.5 text-[9px] font-bold uppercase tracking-widest rounded-lg border transition-all
                                                            {{ $link->is_active
                                                                ? 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border-amber-500/20'
                                                                : 'bg-green-500/10 hover:bg-green-500/20 text-green-400 border-green-500/20' }}">
                                                        {{ $link->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>

                                                {{-- Open in new tab --}}
                                                @if($link->is_active)
                                                    <a href="{{ url('/u/' . $link->token) }}" target="_blank"
                                                        class="px-3 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-indigo-500/20 transition-all">
                                                        Open
                                                    </a>
                                                @endif

                                                {{-- Delete --}}
                                                <form method="POST" action="{{ route('admin.client-links.destroy', $link) }}"
                                                    onsubmit="return confirm('Delete this link permanently? Clients using it will lose access.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-red-500/20 transition-all">
                                                        Delete
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @empty
            <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl px-6 py-14 text-center">
                <i data-lucide="link" class="w-8 h-8 text-gray-300 dark:text-slate-700 mx-auto mb-3"></i>
                <p class="text-sm text-gray-400 dark:text-slate-500">No clients found.</p>
            </div>
        @endforelse

        {{-- Clients with no links yet --}}
        @php $clientsWithoutLinks = $clients->filter(fn($c) => $c->links->isEmpty()); @endphp
        @if($clientsWithoutLinks->isNotEmpty())
            <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
                <div class="px-6 pt-5 pb-4 border-b border-[#E8ECF0] dark:border-white/[0.05]">
                    <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.3em]">Clients Without Links</p>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                    @foreach($clientsWithoutLinks as $client)
                        <div class="flex items-center justify-between px-6 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-[10px] font-bold flex-shrink-0">
                                    {{ strtoupper(substr($client->name, 0, 2)) }}
                                </div>
                                <p class="text-sm font-semibold text-gray-700 dark:text-slate-300">{{ $client->name }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.client-links.store') }}">
                                @csrf
                                <input type="hidden" name="client_id" value="{{ $client->id }}">
                                <button type="submit"
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-indigo-500/20 transition-all">
                                    <i data-lucide="plus" class="w-3 h-3"></i> Generate Link
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ── Create Link Modal ────────────────────────────────────────────────── --}}
    <div id="create-link-modal"
        class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.08] rounded-2xl w-full max-w-sm p-8 shadow-2xl" onclick="event.stopPropagation()">

            <div class="flex justify-between items-center mb-7">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                        <i data-lucide="link" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-900 dark:text-white font-bold">New Upload Link</h3>
                        <p class="text-[10px] text-gray-500 dark:text-slate-400 uppercase tracking-widest mt-0.5">Select a client</p>
                    </div>
                </div>
                <button onclick="document.getElementById('create-link-modal').classList.add('hidden')"
                    class="text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.client-links.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">Client</label>
                    <select name="client_id" required
                        class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors appearance-none">
                        <option value="" disabled selected>Select a client…</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" class="bg-white dark:bg-[#0d0d10]">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pt-4 border-t border-gray-100 dark:border-white/[0.05]">
                    <button type="submit"
                        class="w-full py-3 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-indigo-600/20 transition-all flex justify-center items-center gap-2">
                        <i data-lucide="link" class="w-4 h-4"></i> Generate Link
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Copy toast --}}
    <div id="copy-toast"
        class="hidden fixed bottom-8 left-1/2 -translate-x-1/2 z-50 px-5 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-xs font-bold rounded-2xl shadow-xl">
        Link copied to clipboard
    </div>

    <script>
        function copyLink(url, btn) {
            navigator.clipboard.writeText(url).then(function () {
                const toast = document.getElementById('copy-toast');
                toast.classList.remove('hidden');
                setTimeout(() => toast.classList.add('hidden'), 2000);
            });
        }
    </script>

</x-admin-layout>

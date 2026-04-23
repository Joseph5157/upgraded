<x-admin-layout>

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.client-links.index') }}"
                class="p-2 rounded-xl bg-white dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] text-gray-400 hover:text-gray-700 dark:hover:text-white transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                    Orders via Guest Link
                </h1>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">
                    {{ $clientLink->client->name }} &mdash; <span class="lowercase">{{ $clientLink->token }}</span>
                </p>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 font-mono mt-1">
                    Created by {{ $clientLink->createdBy?->name ?? 'system' }}
                    @if($clientLink->revokedBy)
                        &middot; Revoked by {{ $clientLink->revokedBy->name }}
                    @endif
                    &middot; Credits used {{ $clientLink->creditsUsed() }}
                    &middot; Expires {{ $clientLink->expires_at?->format('d M Y, H:i') ?? 'n/a' }}
                </p>
            </div>
        </div>

        {{-- Link status badge --}}
        @if($clientLink->is_active)
            <span class="px-3 py-1.5 bg-green-500/10 text-green-400 rounded-xl text-[9px] font-bold uppercase tracking-widest border border-green-500/20">Active</span>
        @else
            <span class="px-3 py-1.5 bg-gray-100 dark:bg-white/[0.05] text-gray-400 dark:text-slate-500 rounded-xl text-[9px] font-bold uppercase tracking-widest border border-gray-200 dark:border-white/[0.08]">Inactive</span>
        @endif
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

    {{-- ── Orders Table ──────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">

        @if($clientLink->orders->isEmpty())
            <div class="px-6 py-14 text-center">
                <i data-lucide="inbox" class="w-8 h-8 text-gray-300 dark:text-slate-700 mx-auto mb-3"></i>
                <p class="text-sm text-gray-400 dark:text-slate-500">No orders submitted via this link yet.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.25em] border-b border-gray-100 dark:border-white/[0.05]">
                            <th class="px-6 py-3">Order ID</th>
                            <th class="px-4 py-3">Tracking ID</th>
                            <th class="px-4 py-3 text-center">Files</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Submitted</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @foreach($clientLink->orders as $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-all">

                                {{-- Order ID --}}
                                <td class="px-6 py-3">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">#{{ $order->id }}</span>
                                </td>

                                {{-- Tracking ID --}}
                                <td class="px-4 py-3">
                                    <code class="text-[11px] font-mono text-gray-500 dark:text-slate-400">{{ $order->token_view }}</code>
                                </td>

                                {{-- Files --}}
                                <td class="px-4 py-3 text-center">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-slate-300">{{ $order->files_count }}</span>
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $statusMap = [
                                            'pending'    => ['bg-yellow-500/10 text-yellow-400 border-yellow-500/20', 'Queued'],
                                            'claimed'    => ['bg-blue-500/10 text-blue-400 border-blue-500/20', 'Reserved'],
                                            'processing' => ['bg-indigo-500/10 text-indigo-400 border-indigo-500/20', 'In progress'],
                                            'delivered'  => ['bg-green-500/10 text-green-400 border-green-500/20', 'Delivered'],
                                        ];
                                        $s = $order->computed_status ?? 'pending';
                                        [$cls, $label] = $statusMap[$s] ?? ['bg-gray-100 dark:bg-white/[0.05] text-gray-400 border-gray-200 dark:border-white/[0.08]', ucfirst($s)];
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase tracking-widest border {{ $cls }}">{{ $label }}</span>
                                </td>

                                {{-- Submitted --}}
                                <td class="px-4 py-3 text-center">
                                    <span class="text-[10px] font-mono text-gray-400 dark:text-slate-500">{{ $order->created_at->format('d M Y, H:i') }}</span>
                                </td>

                                {{-- Actions --}}
                                    <td class="px-6 py-3 text-right">
                                        <form method="POST"
                                            action="{{ route('admin.client-links.orders.destroy', [$clientLink, $order]) }}"
                                            onsubmit="return confirm('Delete order #{{ $order->id }} and all its files permanently?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-red-500/20 transition-all">
                                                Delete
                                            </button>
                                        </form>
                                    </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Footer count --}}
            <div class="px-6 py-3 border-t border-gray-100 dark:border-white/[0.05]">
                <p class="text-[10px] font-mono text-gray-400 dark:text-slate-500">
                    {{ $clientLink->orders->count() }} order{{ $clientLink->orders->count() !== 1 ? 's' : '' }} total
                </p>
            </div>
        @endif

    </div>

</x-admin-layout>

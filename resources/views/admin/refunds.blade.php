<x-admin-layout>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-[#1A1D23] tracking-tight">Refund Requests</h1>
            <p class="text-[10px] text-[#9CA3AF] uppercase tracking-[0.25em] mt-0.5 font-mono">CLIENT CREDIT RECOVERIES</p>
        </div>
    </div>

    @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-400 text-sm font-semibold mb-6">
            <i data-lucide="check-circle" class="w-4 h-4"></i> {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-[#E8ECF0] rounded-2xl overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="text-[9px] text-[#9CA3AF] font-bold uppercase tracking-[0.25em] border-b border-[#E8ECF0]">
                    <th class="pb-4 px-6 pt-5">Client</th>
                    <th class="pb-4 px-4 pt-5">Order</th>
                    <th class="pb-4 px-4 pt-5">Reason</th>
                    <th class="pb-4 px-4 pt-5">Submitted</th>
                    <th class="pb-4 px-4 pt-5">Status</th>
                    <th class="pb-4 px-6 pt-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#E8ECF0]">
                @forelse($refunds as $refund)
                    <tr class="hover:bg-white/[0.01] transition-all">
                        <td class="px-6 py-4">
                            <p class="text-xs font-bold text-[#1A1D23]">{{ $refund->client->name }}</p>
                            <p class="text-[9px] text-[#9CA3AF] font-mono">{{ $refund->user->email }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-[10px] font-mono text-[#6B7280]">#{{ $refund->order_id }}</span>
                        </td>
                        <td class="px-4 py-4 max-w-[200px]">
                            <p class="text-[10px] text-[#6B7280] truncate">{{ $refund->reason ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-[10px] text-[#9CA3AF] font-mono">{{ $refund->created_at->diffForHumans() }}</span>
                        </td>
                        <td class="px-4 py-4">
                            @if($refund->status === 'pending')
                                <span class="px-2.5 py-1 bg-amber-500/10 text-amber-400 rounded-lg text-[9px] font-bold border border-amber-500/10">Pending</span>
                            @elseif($refund->status === 'approved')
                                <span class="px-2.5 py-1 bg-green-500/10 text-green-400 rounded-lg text-[9px] font-bold border border-green-500/10">Approved</span>
                            @else
                                <span class="px-2.5 py-1 bg-red-500/10 text-red-400 rounded-lg text-[9px] font-bold border border-red-500/10">Rejected</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($refund->status === 'pending')
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.refunds.approve', $refund) }}">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-1.5 bg-green-500/10 hover:bg-green-500/20 text-green-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-green-500/20 transition-all">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.refunds.reject', $refund) }}">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-red-500/20 transition-all">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-[9px] text-[#9CA3AF]">{{ $refund->resolved_at?->diffForHumans() }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-14 text-center text-xs text-[#9CA3AF]">No refund requests yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-admin-layout>
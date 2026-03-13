<x-admin-layout>

    {{-- Flash Banners --}}
    @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-400 text-sm font-semibold">
            <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-400 text-sm font-semibold">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight dark:text-white">Account Manager</h1>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mt-1 dark:text-slate-500">Manage vendor and client account access</p>
        </div>
        {{-- Stats badges --}}
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#F0F2F5] border border-[#E2E6EA] text-[10px] font-bold text-gray-500 uppercase tracking-widest dark:bg-white/5 dark:border-white/5 dark:text-slate-400">
                <i data-lucide="shield" class="w-3 h-3"></i>
                {{ $vendors->count() }} Vendors
            </span>
            <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#F0F2F5] border border-[#E2E6EA] text-[10px] font-bold text-gray-500 uppercase tracking-widest dark:bg-white/5 dark:border-white/5 dark:text-slate-400">
                <i data-lucide="users" class="w-3 h-3"></i>
                {{ $clients->count() }} Clients
            </span>
            @if($frozenCount > 0)
                <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-red-500/10 border border-red-500/20 text-[10px] font-bold text-red-500 uppercase tracking-widest">
                    <i data-lucide="lock" class="w-3 h-3"></i>
                    {{ $frozenCount }} Frozen
                </span>
            @endif
        </div>
    </div>

    {{-- Tabs --}}
    <div x-data="{ tab: 'vendors' }">

        <div class="flex items-center gap-1 border-b border-[#E2E6EA] dark:border-white/5">
            <button
                @click="tab = 'vendors'"
                :class="tab === 'vendors'
                    ? 'border-b-2 border-[#4F6EF7] text-[#4F6EF7] font-bold'
                    : 'text-gray-400 hover:text-gray-700 dark:hover:text-slate-300 font-semibold'"
                class="flex items-center gap-2 px-4 py-3 text-[11px] uppercase tracking-widest transition-colors -mb-px">
                <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                Vendors
                <span class="text-[9px] font-black bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-1.5 py-0.5 rounded-md leading-none ml-1">{{ $vendors->count() }}</span>
            </button>
            <button
                @click="tab = 'clients'"
                :class="tab === 'clients'
                    ? 'border-b-2 border-[#4F6EF7] text-[#4F6EF7] font-bold'
                    : 'text-gray-400 hover:text-gray-700 dark:hover:text-slate-300 font-semibold'"
                class="flex items-center gap-2 px-4 py-3 text-[11px] uppercase tracking-widest transition-colors -mb-px">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                Clients
                <span class="text-[9px] font-black bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-1.5 py-0.5 rounded-md leading-none ml-1">{{ $clients->count() }}</span>
            </button>
        </div>

        {{-- ===== VENDORS TAB ===== --}}
        <div x-show="tab === 'vendors'" x-cloak>
            <div class="bg-[#F0F2F5] border border-[#E2E6EA] rounded-2xl overflow-hidden dark:bg-[#0a0a0c] dark:border-white/5">
                <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[#E2E6EA] dark:border-white/5">
                            <th class="text-left px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Operator</th>
                            <th class="text-left px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Status</th>
                            <th class="text-center px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Files Today</th>
                            <th class="text-center px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Lifetime</th>
                            <th class="text-center px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Active Orders</th>
                            <th class="text-left px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Joined</th>
                            <th class="text-right px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E2E6EA] dark:divide-white/5">
                        @forelse($vendors as $vendor)
                            <tr class="hover:bg-[#ECEEF2]/50 dark:hover:bg-white/[0.02] transition-colors @if($vendor->trashed()) opacity-60 @endif">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-[11px] font-bold flex-shrink-0">
                                            {{ strtoupper(substr($vendor->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $vendor->name }}</p>
                                            <p class="text-[10px] font-mono text-gray-400 dark:text-slate-500">{{ $vendor->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="space-y-1">
                                        @if($vendor->trashed())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-gray-400 border border-gray-200 text-[9px] font-bold uppercase dark:bg-white/5 dark:text-slate-500 dark:border-white/10">Deleted</span>
                                        @elseif($vendor->isFrozen())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-red-50 text-red-600 border border-red-200 text-[9px] font-bold uppercase dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20">Frozen</span>
                                            @if($vendor->frozen_reason)
                                                <p class="text-[10px] text-amber-600 dark:text-amber-400 max-w-[180px] truncate" title="{{ $vendor->frozen_reason }}">Frozen: {{ $vendor->frozen_reason }}</p>
                                            @endif
                                            @if($vendor->frozen_at)
                                                <p class="text-[9px] text-gray-400 dark:text-slate-500">Since: {{ $vendor->frozen_at->format('d M Y, H:i') }}</p>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-green-50 text-green-600 border border-green-200 text-[9px] font-bold uppercase dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20">Active</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="text-xs font-mono text-gray-700 dark:text-slate-300">
                                        {{ $vendor->orders()->where('status', 'delivered')->whereDate('delivered_at', today())->count() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="text-xs font-mono font-bold text-gray-900 dark:text-white">{{ $vendor->total_files }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="text-xs font-mono text-gray-700 dark:text-slate-300">{{ $vendor->active_orders }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ $vendor->created_at->format('d M Y') }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="relative" id="dropdown-wrapper-{{ $vendor->id }}">
                                        <button
                                            type="button"
                                            onclick="toggleDropdown({{ $vendor->id }})"
                                            class="p-2 rounded-xl bg-[#F5F6F8] border border-[#E2E6EA] text-gray-400 hover:text-gray-700 hover:bg-[#ECEEF2] transition-all dark:bg-white/5 dark:border-white/10 dark:text-slate-500 dark:hover:text-slate-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="5" cy="12" r="2"/>
                                                <circle cx="12" cy="12" r="2"/>
                                                <circle cx="19" cy="12" r="2"/>
                                            </svg>
                                        </button>
                                        <div
                                            id="dropdown-{{ $vendor->id }}"
                                            class="hidden absolute right-0 top-10 z-50 bg-white border border-[#E2E6EA] rounded-2xl shadow-xl shadow-black/5 py-2 min-w-[180px] dark:bg-[#13151c] dark:border-white/10">
                                            @if($vendor->trashed())
                                                @can('restore', $vendor)
                                                <button type="button" onclick="setRestoreTarget({{ $vendor->id }}, '{{ addslashes($vendor->name) }}')"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-500/10 transition-all">
                                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Restore Account
                                                </button>
                                                @endcan
                                                @can('forceDelete', $vendor)
                                                <button type="button" onclick="setForceDeleteTarget({{ $vendor->id }}, '{{ addslashes($vendor->name) }}')"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                                                    <i data-lucide="skull" class="w-3.5 h-3.5"></i> Permanently Delete
                                                </button>
                                                @endcan
                                            @elseif($vendor->isFrozen())
                                                @can('unfreeze', $vendor)
                                                <button type="button" onclick="submitUnfreeze({{ $vendor->id }})"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-green-600 hover:bg-green-50 dark:hover:bg-green-500/10 transition-all">
                                                    <i data-lucide="unlock" class="w-3.5 h-3.5"></i> Unfreeze Account
                                                </button>
                                                @endcan
                                                @can('delete', $vendor)
                                                <div class="border-t border-[#E8ECF0] dark:border-white/5 my-1"></div>
                                                <button type="button" onclick="setDeleteTarget({{ $vendor->id }}, '{{ addslashes($vendor->name) }}', '{{ addslashes($vendor->email) }}')"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete Account
                                                </button>
                                                @endcan
                                            @else
                                                @can('freeze', $vendor)
                                                <button type="button" onclick="setFreezeTarget({{ $vendor->id }}, '{{ addslashes($vendor->name) }}', '{{ addslashes($vendor->email) }}')"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-500/10 transition-all">
                                                    <i data-lucide="lock" class="w-3.5 h-3.5"></i> Freeze Account
                                                </button>
                                                @endcan
                                                @can('delete', $vendor)
                                                <div class="border-t border-[#E8ECF0] dark:border-white/5 my-1"></div>
                                                <button type="button" onclick="setDeleteTarget({{ $vendor->id }}, '{{ addslashes($vendor->name) }}', '{{ addslashes($vendor->email) }}')"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete Account
                                                </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-xs text-gray-400 dark:text-slate-600">No vendor accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        {{-- ===== CLIENTS TAB ===== --}}
        <div x-show="tab === 'clients'" x-cloak>
            <div class="bg-[#F0F2F5] border border-[#E2E6EA] rounded-2xl overflow-hidden dark:bg-[#0a0a0c] dark:border-white/5">
                <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[#E2E6EA] dark:border-white/5">
                            <th class="text-left px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Operator</th>
                            <th class="text-left px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Status</th>
                            <th class="text-center px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Credits</th>
                            <th class="text-center px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Orders</th>
                            <th class="text-left px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Plan</th>
                            <th class="text-left px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Joined</th>
                            <th class="text-right px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest dark:text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E2E6EA] dark:divide-white/5">
                        @forelse($clients as $client)
                            <tr class="hover:bg-[#ECEEF2]/50 dark:hover:bg-white/[0.02] transition-colors @if($client->trashed()) opacity-60 @endif">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-[11px] font-bold flex-shrink-0">
                                            {{ strtoupper(substr($client->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $client->name }}</p>
                                            <p class="text-[10px] font-mono text-gray-400 dark:text-slate-500">{{ $client->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="space-y-1">
                                        @if($client->trashed())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-gray-400 border border-gray-200 text-[9px] font-bold uppercase dark:bg-white/5 dark:text-slate-500 dark:border-white/10">Deleted</span>
                                        @elseif($client->isFrozen())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-red-50 text-red-600 border border-red-200 text-[9px] font-bold uppercase dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20">Frozen</span>
                                            @if($client->frozen_reason)
                                                <p class="text-[10px] text-amber-600 dark:text-amber-400 max-w-[180px] truncate" title="{{ $client->frozen_reason }}">Frozen: {{ $client->frozen_reason }}</p>
                                            @endif
                                            @if($client->frozen_at)
                                                <p class="text-[9px] text-gray-400 dark:text-slate-500">Since: {{ $client->frozen_at->format('d M Y, H:i') }}</p>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-green-50 text-green-600 border border-green-200 text-[9px] font-bold uppercase dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20">Active</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="text-xs font-mono font-bold text-gray-900 dark:text-white">{{ $client->client?->slots ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="text-xs font-mono text-gray-700 dark:text-slate-300">{{ $client->orders_count }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($client->client?->plan_expiry)
                                        @if($client->client->plan_expiry->isPast())
                                            <span class="text-[10px] text-red-500 font-semibold">Expired</span>
                                        @else
                                            <span class="text-[10px] text-green-500 font-semibold">{{ $client->client->plan_expiry->format('d M Y') }}</span>
                                        @endif
                                    @else
                                        <span class="text-[10px] text-gray-400">Perpetual</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ $client->created_at->format('d M Y') }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="relative" id="dropdown-wrapper-{{ $client->id }}">
                                        <button
                                            type="button"
                                            onclick="toggleDropdown({{ $client->id }})"
                                            class="p-2 rounded-xl bg-[#F5F6F8] border border-[#E2E6EA] text-gray-400 hover:text-gray-700 hover:bg-[#ECEEF2] transition-all dark:bg-white/5 dark:border-white/10 dark:text-slate-500 dark:hover:text-slate-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="5" cy="12" r="2"/>
                                                <circle cx="12" cy="12" r="2"/>
                                                <circle cx="19" cy="12" r="2"/>
                                            </svg>
                                        </button>
                                        <div
                                            id="dropdown-{{ $client->id }}"
                                            class="hidden absolute right-0 top-10 z-50 bg-white border border-[#E2E6EA] rounded-2xl shadow-xl shadow-black/5 py-2 min-w-[180px] dark:bg-[#13151c] dark:border-white/10">
                                            @if($client->trashed())
                                                @can('restore', $client)
                                                <button type="button" onclick="setRestoreTarget({{ $client->id }}, '{{ addslashes($client->name) }}')"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-500/10 transition-all">
                                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Restore Account
                                                </button>
                                                @endcan
                                                @can('forceDelete', $client)
                                                <button type="button" onclick="setForceDeleteTarget({{ $client->id }}, '{{ addslashes($client->name) }}')"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                                                    <i data-lucide="skull" class="w-3.5 h-3.5"></i> Permanently Delete
                                                </button>
                                                @endcan
                                            @elseif($client->isFrozen())
                                                @can('unfreeze', $client)
                                                <button type="button" onclick="submitUnfreeze({{ $client->id }})"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-green-600 hover:bg-green-50 dark:hover:bg-green-500/10 transition-all">
                                                    <i data-lucide="unlock" class="w-3.5 h-3.5"></i> Unfreeze Account
                                                </button>
                                                @endcan
                                                @can('delete', $client)
                                                <div class="border-t border-[#E8ECF0] dark:border-white/5 my-1"></div>
                                                <button type="button" onclick="setDeleteTarget({{ $client->id }}, '{{ addslashes($client->name) }}', '{{ addslashes($client->email) }}')"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete Account
                                                </button>
                                                @endcan
                                            @else
                                                @can('freeze', $client)
                                                <button type="button" onclick="setFreezeTarget({{ $client->id }}, '{{ addslashes($client->name) }}', '{{ addslashes($client->email) }}')"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-500/10 transition-all">
                                                    <i data-lucide="lock" class="w-3.5 h-3.5"></i> Freeze Account
                                                </button>
                                                @endcan
                                                @can('delete', $client)
                                                <div class="border-t border-[#E8ECF0] dark:border-white/5 my-1"></div>
                                                <button type="button" onclick="setDeleteTarget({{ $client->id }}, '{{ addslashes($client->name) }}', '{{ addslashes($client->email) }}')"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete Account
                                                </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-xs text-gray-400 dark:text-slate-600">No client accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>

    </div>{{-- /x-data tabs --}}


    {{-- Hidden unfreeze forms (one per user, submitted directly by JS) --}}
    @foreach($vendors as $vendor)
        <form id="unfreeze-form-{{ $vendor->id }}" action="{{ route('admin.accounts.unfreeze', $vendor->id) }}" method="POST" class="hidden">@csrf</form>
    @endforeach
    @foreach($clients as $client)
        <form id="unfreeze-form-{{ $client->id }}" action="{{ route('admin.accounts.unfreeze', $client->id) }}" method="POST" class="hidden">@csrf</form>
    @endforeach


    {{-- ===== RESTORE MODAL ===== --}}
    <div id="restore-modal" data-modal-overlay class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0a0a0c] border border-[#E2E6EA] dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8 space-y-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-500">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wide">Restore Account</h3>
                    <p class="text-[11px] text-gray-400">This will reactivate the account and restore login access.</p>
                </div>
            </div>
            <div class="p-3 rounded-xl bg-[#F0F2F5] dark:bg-white/5 border border-[#E2E6EA] dark:border-white/5">
                <p class="text-xs font-bold text-gray-900 dark:text-white" id="restore-user-name"></p>
            </div>
            <form id="restore-form" method="POST" action="#" class="space-y-4">
                @csrf
                <div class="flex gap-3 pt-1">
                    <button type="button" onclick="closeModal('restore-modal')"
                        class="flex-1 py-2.5 rounded-xl border border-[#E2E6EA] dark:border-white/5 text-xs font-semibold text-gray-500 hover:bg-[#F0F2F5] dark:text-slate-400 dark:hover:bg-white/5 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-2.5 rounded-xl bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold uppercase tracking-widest transition-colors">
                        Restore Account
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ===== FREEZE MODAL ===== --}}
    <div id="freeze-modal" data-modal-overlay class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0a0a0c] border border-[#E2E6EA] dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8 space-y-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-500/10 rounded-xl flex items-center justify-center text-amber-500">
                    <i data-lucide="lock" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide">Freeze Account</h3>
                    <p class="text-[11px] text-gray-400">The user will be immediately logged out.</p>
                </div>
            </div>

            <div class="p-3 rounded-xl bg-[#F0F2F5] dark:bg-white/5 border border-[#E2E6EA] dark:border-white/5">
                <p class="text-xs font-bold text-gray-900 dark:text-white" id="freeze-user-name"></p>
                <p class="text-[10px] font-mono text-gray-400 dark:text-slate-500" id="freeze-user-email"></p>
            </div>

            <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">
                This user will be immediately logged out and blocked from accessing the portal.
            </p>

            <form id="freeze-form" method="POST" action="#" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-1.5">Reason for Freezing</label>
                    <textarea
                        name="reason"
                        required
                        rows="3"
                        placeholder="Enter reason for freezing this account..."
                        class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 resize-none focus:outline-none focus:ring-2 focus:ring-amber-400/30 focus:border-amber-400 transition-all dark:bg-[#111827] dark:border-white/5 dark:text-slate-200"
                    ></textarea>
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="button" onclick="closeModal('freeze-modal')"
                        class="flex-1 py-2.5 rounded-xl border border-[#E2E6EA] dark:border-white/5 text-xs font-semibold text-gray-500 hover:bg-[#F0F2F5] dark:text-slate-400 dark:hover:bg-white/5 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold uppercase tracking-widest transition-colors">
                        Freeze Account
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ===== DELETE MODAL ===== --}}
    <div id="delete-modal" data-modal-overlay class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0a0a0c] border border-[#E2E6EA] dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8 space-y-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-500/10 rounded-xl flex items-center justify-center text-red-500">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-red-600 dark:text-red-400 uppercase tracking-wide">Delete Account</h3>
                    <p class="text-[11px] text-gray-400">Soft delete — restorable within 30 days.</p>
                </div>
            </div>

            <div class="p-4 rounded-xl border border-red-200 bg-red-50 dark:border-red-500/20 dark:bg-red-500/5 space-y-1.5">
                <p class="text-xs font-semibold text-red-600 dark:text-red-400">⚠ Warning</p>
                <ul class="text-xs text-red-500/80 dark:text-red-400/70 space-y-0.5 list-disc list-inside">
                    <li>This action will soft-delete the account.</li>
                    <li>The user will be logged out immediately.</li>
                    <li>All active orders will be released or cancelled.</li>
                    <li>You can restore this account within 30 days.</li>
                </ul>
            </div>

            <div class="p-3 rounded-xl bg-[#F0F2F5] dark:bg-white/5 border border-[#E2E6EA] dark:border-white/5">
                <p class="text-xs font-bold text-gray-900 dark:text-white" id="delete-user-name"></p>
                <p class="text-[10px] font-mono text-gray-400 dark:text-slate-500" id="delete-user-email"></p>
            </div>

            <form id="delete-form" method="POST" action="#" class="space-y-4">
                @csrf
                @method('DELETE')
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-1.5">Admin Password Confirmation</label>
                    <input
                        type="password"
                        name="password"
                        required
                        placeholder="Enter your admin password to confirm"
                        class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-400/30 focus:border-red-400 transition-all dark:bg-[#111827] dark:border-white/5 dark:text-slate-200"
                    >
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="button" onclick="closeModal('delete-modal')"
                        class="flex-1 py-2.5 rounded-xl border border-[#E2E6EA] dark:border-white/5 text-xs font-semibold text-gray-500 hover:bg-[#F0F2F5] dark:text-slate-400 dark:hover:bg-white/5 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-xs font-bold uppercase tracking-widest transition-colors">
                        Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ===== FORCE DELETE MODAL ===== --}}
    <div id="force-delete-modal" data-modal-overlay class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0a0a0c] border border-[#E2E6EA] dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8 space-y-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-500/10 rounded-xl flex items-center justify-center text-red-500">
                    <i data-lucide="skull" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-red-600 dark:text-red-400 uppercase tracking-wide">Permanently Delete</h3>
                    <p class="text-[11px] text-gray-400">This cannot be undone under any circumstances.</p>
                </div>
            </div>

            <div class="p-3 rounded-xl bg-[#F0F2F5] dark:bg-white/5 border border-[#E2E6EA] dark:border-white/5">
                <p class="text-xs font-bold text-gray-900 dark:text-white" id="force-delete-user-name"></p>
                <p class="text-[10px] font-mono text-gray-400 dark:text-slate-500" id="force-delete-user-email"></p>
            </div>

            <form id="force-delete-form" method="POST" action="#" class="space-y-4">
                @csrf
                @method('DELETE')
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 dark:text-slate-500 uppercase tracking-widest mb-1.5">Admin Password Confirmation</label>
                    <input
                        type="password"
                        name="password"
                        required
                        placeholder="Enter your admin password to confirm"
                        class="w-full px-4 py-2.5 rounded-xl border border-[#E2E6EA] bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-400/30 focus:border-red-400 transition-all dark:bg-[#111827] dark:border-white/5 dark:text-slate-200"
                    >
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="button" onclick="closeModal('force-delete-modal')"
                        class="flex-1 py-2.5 rounded-xl border border-[#E2E6EA] dark:border-white/5 text-xs font-semibold text-gray-500 hover:bg-[#F0F2F5] dark:text-slate-400 dark:hover:bg-white/5 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-widest transition-colors">
                        Permanently Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function closeAllDropdowns() {
            document.querySelectorAll('[id^="dropdown-"]').forEach(function(el) {
                if (!el.id.includes('wrapper') && !el.id.includes('modal')) {
                    el.classList.add('hidden');
                }
            });
        }

        function toggleDropdown(userId) {
            const dropdown = document.getElementById('dropdown-' + userId);
            const isHidden = dropdown.classList.contains('hidden');
            closeAllDropdowns();
            if (isHidden) {
                dropdown.classList.remove('hidden');
            }
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id^="dropdown-wrapper-"]')) {
                closeAllDropdowns();
            }
        });

        function setFreezeTarget(userId, userName, userEmail) {
            document.getElementById('freeze-form').action = '/admin/accounts/' + userId + '/freeze';
            document.getElementById('freeze-user-name').textContent = userName;
            document.getElementById('freeze-user-email').textContent = userEmail;
            closeAllDropdowns();
            document.getElementById('freeze-modal').classList.remove('hidden');
        }

        function setDeleteTarget(userId, userName, userEmail) {
            document.getElementById('delete-form').action = '/admin/accounts/' + userId;
            document.getElementById('delete-user-name').textContent = userName;
            document.getElementById('delete-user-email').textContent = userEmail;
            closeAllDropdowns();
            document.getElementById('delete-modal').classList.remove('hidden');
        }

        function setRestoreTarget(userId, userName) {
            document.getElementById('restore-form').action = '/admin/accounts/' + userId + '/restore';
            document.getElementById('restore-user-name').textContent = userName;
            closeAllDropdowns();
            document.getElementById('restore-modal').classList.remove('hidden');
        }

        function setForceDeleteTarget(userId, userName) {
            document.getElementById('force-delete-form').action = '/admin/accounts/' + userId + '/force';
            document.getElementById('force-delete-user-name').textContent = userName;
            closeAllDropdowns();
            document.getElementById('force-delete-modal').classList.remove('hidden');
        }

        function submitUnfreeze(userId) {
            closeAllDropdowns();
            if (confirm('Unfreeze this account and restore access?')) {
                document.getElementById('unfreeze-form-' + userId).submit();
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            document.querySelectorAll('#' + modalId + ' input[type="password"]').forEach(function(el) {
                el.value = '';
            });
        }

        document.querySelectorAll('[data-modal-overlay]').forEach(function(overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
        });
    </script>

</x-admin-layout>

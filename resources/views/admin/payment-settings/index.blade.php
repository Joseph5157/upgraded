<x-admin-layout>

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Payment Settings</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">Manage UPI payment methods for client top-ups</p>
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
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-6">

        {{-- ── Section 2: Active Payment Method ───────────────────────────── --}}
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-6">
            <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-4">Active Payment Method</p>

            @if($active)
                <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                    @if($active->qr_code_path)
                        <div class="flex-shrink-0">
                            <img src="{{ asset('storage/' . $active->qr_code_path) }}"
                                 alt="QR Code"
                                 width="160"
                                 class="rounded-xl border border-[#E8ECF0] dark:border-white/[0.08] object-contain bg-white p-1">
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-3">
                            <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ $active->upi_name }}</h2>
                            <span class="px-2.5 py-1 bg-green-500/10 text-green-400 rounded-lg text-[9px] font-bold uppercase tracking-widest border border-green-500/20">
                                Active
                            </span>
                        </div>
                        <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-1">UPI ID</p>
                        <p class="text-sm font-mono font-bold text-gray-700 dark:text-slate-200">{{ $active->upi_id }}</p>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3 py-6 text-gray-400 dark:text-slate-500">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <p class="text-sm">No active payment method. Add one below.</p>
                </div>
            @endif
        </div>

        {{-- ── Section 3: Add New Payment Method ──────────────────────────── --}}
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl p-6">
            <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-5">Add Payment Method</p>

            <form method="POST" action="{{ route('admin.payment-settings.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">UPI Name</label>
                        <input type="text" name="upi_name" value="{{ old('upi_name') }}" placeholder="e.g. HDFC Account"
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-600 focus:outline-none focus:border-indigo-500/50 transition-colors"
                            required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">UPI ID</label>
                        <input type="text" name="upi_id" value="{{ old('upi_id') }}" placeholder="e.g. plagexpert@ybl"
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-3 text-sm font-mono text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-600 focus:outline-none focus:border-indigo-500/50 transition-colors"
                            required>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">
                        QR Code <span class="normal-case font-normal tracking-normal text-gray-400 dark:text-slate-600">(optional · jpg/jpeg/png · max 2 MB)</span>
                    </label>
                    <input type="file" name="qr_code" accept="image/*"
                        class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-3 text-sm text-gray-600 dark:text-slate-400 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-500/10 file:text-indigo-400 hover:file:bg-indigo-500/20 focus:outline-none transition-colors cursor-pointer">
                </div>
                <div class="pt-2">
                    <button type="submit"
                        class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-indigo-600/20 transition-all">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Payment Method
                    </button>
                </div>
            </form>
        </div>

        {{-- ── Section 4: All Payment Methods Table ───────────────────────── --}}
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
            <div class="px-6 pt-5 pb-4 border-b border-[#E8ECF0] dark:border-white/[0.05]">
                <p class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.3em]">All Payment Methods</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.25em] border-b border-gray-100 dark:border-white/[0.05]">
                            <th class="px-6 py-4">Name</th>
                            <th class="px-4 py-4">UPI ID</th>
                            <th class="px-4 py-4">QR</th>
                            <th class="px-4 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                        @forelse($settings as $setting)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-all">

                                {{-- Name --}}
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $setting->upi_name }}</p>
                                </td>

                                {{-- UPI ID --}}
                                <td class="px-4 py-4">
                                    <p class="text-xs font-mono text-gray-600 dark:text-slate-400">{{ $setting->upi_id }}</p>
                                </td>

                                {{-- QR thumbnail --}}
                                <td class="px-4 py-4">
                                    @if($setting->qr_code_path)
                                        <img src="{{ asset('storage/' . $setting->qr_code_path) }}"
                                             alt="QR"
                                             width="48"
                                             height="48"
                                             class="rounded-lg border border-[#E8ECF0] dark:border-white/[0.08] object-contain bg-white">
                                    @else
                                        <span class="text-gray-400 dark:text-slate-600 text-sm">—</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-4">
                                    @if($setting->is_active)
                                        <span class="px-2.5 py-1 bg-green-500/10 text-green-400 rounded-lg text-[9px] font-bold uppercase tracking-widest border border-green-500/20">Active</span>
                                    @else
                                        <span class="px-2.5 py-1 bg-gray-100 dark:bg-white/[0.05] text-gray-400 dark:text-slate-500 rounded-lg text-[9px] font-bold uppercase tracking-widest border border-gray-200 dark:border-white/[0.08]">Inactive</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">

                                        {{-- Set Active --}}
                                        @if(!$setting->is_active)
                                            <form method="POST" action="{{ route('admin.payment-settings.activate', $setting) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="px-3 py-1.5 bg-green-500/10 hover:bg-green-500/20 text-green-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-green-500/20 transition-all whitespace-nowrap">
                                                    Set Active
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Edit --}}
                                        <button onclick="document.getElementById('edit-modal-{{ $setting->id }}').classList.remove('hidden')"
                                            class="px-3 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-indigo-500/20 transition-all">
                                            Edit
                                        </button>

                                        {{-- Delete --}}
                                        @if(!$setting->is_active)
                                            <form method="POST" action="{{ route('admin.payment-settings.destroy', $setting) }}"
                                                onsubmit="return confirm('Delete this payment method permanently?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-red-500/20 transition-all">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-14 text-center text-xs text-gray-400 dark:text-slate-500">
                                    No payment methods added yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- ── Edit Modals (one per row) ────────────────────────────────────────── --}}
    @foreach($settings as $setting)
        <div id="edit-modal-{{ $setting->id }}"
            class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.08] rounded-2xl w-full max-w-lg p-8 shadow-2xl" onclick="event.stopPropagation()">

                {{-- Modal header --}}
                <div class="flex justify-between items-center mb-7">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                            <i data-lucide="pencil" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-900 dark:text-white font-bold">Edit Payment Method</h3>
                            <p class="text-[10px] text-gray-500 dark:text-slate-400 uppercase tracking-widest mt-0.5 font-mono">{{ $setting->upi_name }}</p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('edit-modal-{{ $setting->id }}').classList.add('hidden')"
                        class="text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition-colors p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                {{-- Edit form --}}
                <form method="POST" action="{{ route('admin.payment-settings.update', $setting) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">UPI Name</label>
                        <input type="text" name="upi_name" value="{{ $setting->upi_name }}" required
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">UPI ID</label>
                        <input type="text" name="upi_id" value="{{ $setting->upi_id }}" required
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-3 text-sm font-mono text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">
                            Replace QR Code <span class="normal-case font-normal tracking-normal text-gray-400 dark:text-slate-600">(optional · jpg/jpeg/png · max 2 MB)</span>
                        </label>
                        @if($setting->qr_code_path)
                            <div class="mb-3">
                                <img src="{{ asset('storage/' . $setting->qr_code_path) }}"
                                     alt="Current QR"
                                     width="80"
                                     class="rounded-lg border border-[#E8ECF0] dark:border-white/[0.08] object-contain bg-white p-1">
                                <p class="text-[9px] text-gray-400 dark:text-slate-600 mt-1 uppercase tracking-widest">Current QR</p>
                            </div>
                        @endif
                        <input type="file" name="qr_code" accept="image/*"
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-3 text-sm text-gray-600 dark:text-slate-400 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-500/10 file:text-indigo-400 hover:file:bg-indigo-500/20 focus:outline-none transition-colors cursor-pointer">
                    </div>
                    <div class="pt-4 border-t border-gray-100 dark:border-white/[0.05]">
                        <button type="submit"
                            class="w-full py-3 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-indigo-600/20 transition-all flex justify-center items-center gap-2">
                            <i data-lucide="save" class="w-4 h-4"></i> Save Changes
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endforeach

</x-admin-layout>

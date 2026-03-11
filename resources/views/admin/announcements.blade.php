<x-admin-layout>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Announcements</h1>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-[0.25em] mt-0.5 font-mono">BROADCAST TO USERS</p>
        </div>
        <button onclick="document.getElementById('create-announcement-modal').classList.remove('hidden')"
            class="flex items-center gap-2 px-4 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-indigo-600/20 transition-all">
            <i data-lucide="megaphone" class="w-3.5 h-3.5"></i> New Broadcast
        </button>
    </div>

    @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl text-green-400 text-sm font-semibold mb-6">
            <i data-lucide="check-circle" class="w-4 h-4"></i> {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.05] rounded-2xl overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="text-[9px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-[0.25em] border-b border-gray-100 dark:border-white/[0.05]">
                    <th class="pb-4 px-6 pt-5">Title</th>
                    <th class="pb-4 px-4 pt-5">Target</th>
                    <th class="pb-4 px-4 pt-5">Type</th>
                    <th class="pb-4 px-4 pt-5">Status</th>
                    <th class="pb-4 px-4 pt-5">Expires</th>
                    <th class="pb-4 px-6 pt-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                @forelse($announcements as $announcement)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.03] transition-all">
                        <td class="px-6 py-4">
                            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $announcement->title }}</p>
                            <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5 max-w-xs truncate">{{ $announcement->message }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <span class="px-2.5 py-1 bg-gray-100 dark:bg-white/[0.05] text-gray-600 dark:text-slate-400 rounded-lg text-[9px] font-bold uppercase border border-gray-200 dark:border-white/[0.08]">{{ $announcement->target }}</span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase border
                                {{ $announcement->type === 'success' ? 'bg-green-500/15 text-green-600 dark:text-green-400 border-green-500/15' : '' }}
                                {{ $announcement->type === 'warning' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/15' : '' }}
                                {{ $announcement->type === 'danger'  ? 'bg-red-500/15 text-red-600 dark:text-red-400 border-red-500/15' : '' }}
                                {{ $announcement->type === 'info'    ? 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border-indigo-500/15' : '' }}
                            ">{{ $announcement->type }}</span>
                        </td>
                        <td class="px-4 py-4">
                            @if($announcement->active)
                                <span class="px-2.5 py-1 bg-green-500/15 text-green-600 dark:text-green-400 rounded-lg text-[9px] font-bold border border-green-500/15">Live</span>
                            @else
                                <span class="px-2.5 py-1 bg-gray-100 dark:bg-white/[0.05] text-gray-400 dark:text-slate-500 rounded-lg text-[9px] font-bold border border-gray-200 dark:border-white/[0.08]">Off</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-[10px] text-gray-400 dark:text-slate-500 font-mono">
                                {{ $announcement->expires_at ? $announcement->expires_at->format('d M Y') : '—' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="{{ route('admin.announcements.toggle', $announcement) }}">
                                    @csrf
                                    <button type="submit"
                                        class="px-3 py-1.5 bg-gray-100 dark:bg-white/[0.05] hover:bg-gray-200 dark:hover:bg-white/[0.08] text-gray-600 dark:text-slate-400 text-[9px] font-bold uppercase tracking-widest rounded-lg border border-gray-200 dark:border-white/[0.08] transition-all">
                                        {{ $announcement->active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}"
                                    onsubmit="return confirm('Delete this announcement permanently?')">
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
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-14 text-center text-xs text-gray-400 dark:text-slate-500">No announcements yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Create Modal --}}
    <div id="create-announcement-modal"
        class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white dark:bg-[#0d0d10] border border-[#E8ECF0] dark:border-white/[0.08] rounded-2xl w-full max-w-lg p-8 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-7">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                        <i data-lucide="megaphone" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-900 dark:text-white font-bold">New Broadcast</h3>
                        <p class="text-[10px] text-gray-500 dark:text-slate-400 uppercase tracking-widest mt-0.5">Send to users</p>
                    </div>
                </div>
                <button onclick="document.getElementById('create-announcement-modal').classList.add('hidden')"
                    class="text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.announcements.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">Title</label>
                    <input type="text" name="title" required
                        class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">Message</label>
                    <textarea name="message" rows="3" required
                        class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">Target Audience</label>
                        <select name="target" required
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 appearance-none">
                            <option value="all" class="bg-white dark:bg-[#0d0d10]">Everyone</option>
                            <option value="vendor" class="bg-white dark:bg-[#0d0d10]">Vendors Only</option>
                            <option value="client" class="bg-white dark:bg-[#0d0d10]">Clients Only</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">Type</label>
                        <select name="type" required
                            class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 appearance-none">
                            <option value="info" class="bg-white dark:bg-[#0d0d10]">Info</option>
                            <option value="success" class="bg-white dark:bg-[#0d0d10]">Success</option>
                            <option value="warning" class="bg-white dark:bg-[#0d0d10]">Warning</option>
                            <option value="danger" class="bg-white dark:bg-[#0d0d10]">Danger</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-600 dark:text-slate-400 uppercase tracking-widest mb-2">Expires At (Optional)</label>
                    <input type="datetime-local" name="expires_at"
                        class="w-full bg-[#F5F7FA] dark:bg-white/[0.04] border border-[#E8ECF0] dark:border-white/[0.08] rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-indigo-500/50 transition-colors">
                </div>
                <div class="pt-4 border-t border-gray-100 dark:border-white/[0.05]">
                    <button type="submit"
                        class="w-full py-3.5 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl border border-indigo-600/20 transition-all flex justify-center items-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i> Broadcast Now
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-admin-layout>
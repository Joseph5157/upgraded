<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Portal - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0d0d0d;
        }

        .card-glass {
            background: rgba(18, 18, 18, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .text-gradient {
            background: linear-gradient(to right, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>

<body class="text-slate-300 min-h-screen overflow-x-hidden">
    <!-- Navbar -->
    <nav class="flex justify-between items-center px-8 py-4 border-b border-white/5">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center font-bold text-white">T</div>
            <div>
                <h1 class="text-sm font-bold text-white leading-tight">PlagExpert User</h1>
                <p class="text-[10px] text-slate-500 uppercase tracking-tighter">ID: {{ strtoupper($client->name) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-6 text-xs font-medium">
            <a href="#" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
                <i data-lucide="shopping-bag" class="w-4 h-4"></i> SHOP
            </a>
            <a href="#" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
                <i data-lucide="key-round" class="w-4 h-4"></i> CHANGE PIN
            </a>
            <a href="#" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
                <i data-lucide="message-square" class="w-4 h-4"></i> FEEDBACK
            </a>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto py-10 px-6 space-y-8">
        @php
            $totalSlots = (int) $client->total_slots;
            $consumed = (int) $client->slots_consumed;
            $remainingCredits = max(0, $totalSlots - $consumed);
        @endphp

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/20 p-4 rounded-xl">
                <h3 class="text-red-400 text-sm font-bold uppercase tracking-wider">Upload Failed</h3>
                <p class="text-red-300/80 text-xs mt-1">{{ session('error') }}</p>
            </div>
        @endif

        @if($errors->any() && ! $errors->has('cf-turnstile-response'))
            <div class="bg-amber-500/10 border border-amber-500/20 p-4 rounded-xl">
                <h3 class="text-amber-300 text-sm font-bold uppercase tracking-wider">Please Check Your Submission</h3>
                <p class="text-amber-200/80 text-xs mt-1">{{ $errors->first() }}</p>
            </div>
        @endif

        @if($remainingCredits <= 0)
            <!-- Limit Reached Banner -->
            <div class="bg-[#2a1b00] border border-[#ffd700]/10 p-4 rounded-xl flex items-center gap-4">
                <div class="w-10 h-10 bg-[#ffd700]/10 rounded-full flex items-center justify-center text-[#ffd700]">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-[#ffd700] text-sm font-bold uppercase tracking-wider">Limit Reached</h3>
                    <p class="text-[#ffd700]/70 text-xs">You reached your slot limit. Want to buy new slot? Contact us +91
                        8887520480</p>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            <!-- Left Panel: Slots -->
            <div class="md:col-span-4 space-y-6">
                <div class="card-glass p-8 rounded-3xl relative overflow-hidden h-full">
                    <div class="flex justify-between items-start mb-12">
                        <div
                            class="w-10 h-10 bg-indigo-600/20 rounded-xl flex items-center justify-center text-indigo-400">
                            <i data-lucide="hash" class="w-5 h-5"></i>
                        </div>
                        <div class="flex items-center gap-4">
                            <button
                                class="text-[10px] font-bold uppercase tracking-widest text-indigo-400 hover:text-indigo-300">Buy
                                Slots</button>
                            <button
                                class="text-[10px] font-bold uppercase tracking-widest text-indigo-400 hover:text-indigo-300">Redeem</button>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">Credits Used:
                            {{ $consumed }}
                        </p>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">Remaining Slots</p>
                        <h2 class="text-7xl font-bold text-white tracking-tighter">
                            {{ $remainingCredits }}
                        </h2>
                    </div>

                    <div class="mt-8">
                        <span
                            class="px-3 py-1 bg-red-500/10 text-red-500 rounded-full text-[10px] font-bold uppercase tracking-widest border border-red-500/20">
                            {{ $client->plan_expiry && $client->plan_expiry->isPast() ? 'Plan Expired' : 'Active Plan' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Upload -->
            <div class="md:col-span-8">
                <div class="card-glass p-8 rounded-3xl h-full">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-white">Upload Document</h2>
                        <i data-lucide="help-circle" class="w-5 h-5 text-indigo-500/50"></i>
                    </div>
                    <p class="text-[10px] font-bold text-slate-500 mb-6 font-mono">UP TO 20 FILES | 100MB EACH</p>

                    @if($client->plan_expiry && $client->plan_expiry->isPast())
                        <div class="border-2 border-dashed border-red-500/20 rounded-3xl p-12 text-center bg-red-500/[0.03]">
                            <div class="w-16 h-16 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="ban" class="w-8 h-8 text-red-500/60"></i>
                            </div>
                            <h3 class="text-red-400 font-bold mb-1">Plan Expired</h3>
                            <p class="text-[10px] text-slate-500 font-mono uppercase">Contact Admin to renew your plan</p>
                        </div>
                    @else
                        <form action="{{ route('client.store', $link->token) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <label for="files"
                                class="group block border-2 border-dashed border-white/5 rounded-3xl p-12 text-center hover:border-indigo-600/50 transition-all cursor-pointer bg-white/[0.02]">
                                <input type="file" name="files[]" id="files" multiple required class="hidden"
                                    onchange="updateFileCount(this)">
                                <div
                                    class="w-16 h-16 bg-indigo-600/10 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                    <i data-lucide="cloud-upload" class="w-8 h-8 text-indigo-500"></i>
                                </div>
                                <h3 class="text-white font-bold mb-1">Click to Browse</h3>
                                <p class="text-[10px] text-slate-500 font-mono uppercase">Supports PDF, DOC, DOCX, ZIP</p>

                                <div
                                    class="mt-6 inline-flex items-center gap-2 bg-green-500/5 px-4 py-2 rounded-full border border-green-500/10">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                    <span class="text-[10px] text-green-500 font-bold uppercase tracking-widest">100%
                                        Non-Repository</span>
                                </div>
                            </label>

                            <!-- Cloudflare Turnstile -->
                            <div class="mt-4">
                                <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
                                @error('cf-turnstile-response')
                                    <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="file-info" class="hidden mt-4 items-center justify-between gap-3">
                                <span id="file-count-text" class="text-[11px] text-slate-400 font-semibold"></span>
                                <button type="submit"
                                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold rounded-xl transition-all">
                                    Submit Order
                                </button>
                            </div>
                        </form>
                    @endif

                    <div class="mt-8 space-y-4">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Turnitin Scan Filters:
                        </p>
                        <div class="flex gap-3">
                            <span
                                class="px-4 py-2 bg-green-500/5 text-green-500 rounded-xl text-[10px] font-bold border border-green-500/10">Bibliography
                                Excluded</span>
                            <span
                                class="px-4 py-2 bg-green-500/5 text-green-500 rounded-xl text-[10px] font-bold border border-green-500/10">Quotes
                                Excluded</span>
                        </div>
                    </div>

                    <button disabled
                        class="w-full mt-8 py-4 bg-red-600/10 text-red-600 rounded-2xl text-xs font-bold uppercase tracking-widest border border-red-600/20 {{ $remainingCredits > 0 ? 'hidden' : '' }}">
                        Slot Limit Reached
                    </button>
                </div>
            </div>
        </div>

        <!-- History Section -->
        <div class="space-y-6 pt-10">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 bg-indigo-500 rounded-full shadow-[0_0_10px_rgba(99,102,241,0.5)]"></span>
                    <h2 class="text-sm font-bold text-white uppercase tracking-widest">My History</h2>
                </div>
                <div class="relative group">
                    <i data-lucide="search" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                    <input type="text" placeholder="Search documents..."
                        class="bg-white/5 border border-white/5 rounded-full py-2 pl-12 pr-6 text-xs w-64 focus:outline-none focus:ring-1 focus:ring-indigo-500/50 transition-all">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] text-slate-500 uppercase tracking-[0.2em] border-b border-white/5">
                            <th class="py-6 font-bold">Document</th>
                            <th class="py-6 font-bold">Status</th>
                            <th class="py-6 font-bold text-right">Download Reports</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.02]">
                        @forelse($orders as $order)
                            <tr class="group hover:bg-white/[0.01] transition-colors">
                                <td class="py-6">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-10 h-10 bg-indigo-600/5 rounded-xl flex items-center justify-center text-indigo-400 group-hover:bg-indigo-600/10 transition-colors">
                                            <i data-lucide="file-text" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-200">
                                                {{ $order->files->first() ? basename($order->files->first()->file_path) : 'Unnamed Document' }}
                                            </h4>
                                            @if($order->files_count > 1)
                                                <p class="text-[10px] text-indigo-400 mt-1 font-mono uppercase">
                                                    + {{ $order->files_count - 1 }} more file{{ $order->files_count - 1 > 1 ? 's' : '' }}
                                                </p>
                                            @endif
                                            <p class="text-[10px] text-slate-500 mt-1 font-mono uppercase">
                                                {{ $order->created_at->format('d M, h:i A') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-6">
                                    <span
                                        class="px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest border
                                                                @if($order->status->value === 'delivered') bg-green-500/5 text-green-500 border-green-500/10
                                                                @elseif($order->computed_status == 'overdue') bg-red-500/5 text-red-500 border-red-500/10
                                                                @elseif($order->status->value === 'processing') bg-blue-500/5 text-blue-400 border-blue-500/10
                                                                @else bg-slate-500/5 text-slate-400 border-slate-500/10 @endif">
                                        @if($order->status->value === 'delivered')
                                            Ready
                                        @elseif($order->computed_status == 'overdue')
                                            Overdue
                                        @elseif($order->status->value === 'processing')
                                            Processing
                                        @else
                                            Pending
                                        @endif
                                    </span>
                                </td>
                                <td class="py-6">
                                    <div class="flex justify-end gap-3">
                                        @if($order->status->value === 'delivered' && $order->report)
                                            <a href="{{ route('client.download', $order->token_view) }}"
                                                class="px-4 py-1.5 bg-[#ffd700]/5 text-[#ffd700] rounded-xl text-[10px] font-bold border border-[#ffd700]/10 flex items-center gap-2 hover:bg-[#ffd700]/10 transition-all">
                                                <i data-lucide="download" class="w-3 h-3"></i> Report
                                            </a>
                                        @else
                                            <div
                                                class="px-4 py-1.5 bg-white/5 text-slate-500 rounded-xl text-[10px] font-bold border border-white/10 flex items-center gap-2">
                                                <i data-lucide="clock" class="w-3 h-3 text-indigo-400"></i>
                                                <span class="countdown-timer text-[10px] font-mono text-indigo-400"
                                                    data-due="{{ $order->due_at->toIso8601String() }}">--:--</span>
                                            </div>
                                        @endif

                                        <form method="POST"
                                            action="{{ route('client.link.orders.delete', [$link->token, $order]) }}"
                                            onsubmit="return confirm('Delete this order and all its files permanently?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 hover:text-red-500 hover:bg-red-500/10 transition-all">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-20 text-center text-slate-500 text-xs font-medium">No order
                                    history found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer Buttons -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-10">
            <a href="#"
                class="bg-[#00c853] hover:bg-[#00e676] text-white py-4 rounded-2xl flex items-center justify-center gap-3 font-bold text-xs uppercase tracking-widest transition-all shadow-lg shadow-[#00c853]/10">
                <i data-lucide="message-circle" class="w-5 h-5"></i> Join Channel
            </a>
            <a href="#"
                class="bg-indigo-600/5 hover:bg-indigo-600/10 text-white border border-indigo-600/10 py-4 rounded-2xl flex items-center justify-center gap-3 font-bold text-xs uppercase tracking-widest transition-all">
                <i data-lucide="alert-circle" class="w-5 h-5 text-[#ffd700]"></i> Report Issue
            </a>
        </div>

        <!-- FAQ Section -->
        <div class="pt-20 space-y-8">
            <h2 class="text-sm font-bold text-white uppercase tracking-[0.3em] border-l-2 border-indigo-500 pl-4">
                Frequently Asked Questions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php
                    $faqs = [
                        'Is this a Turnitin Report?',
                        'How does the System Work?',
                        'What is Non-Repository?',
                        'Is My Data Safe?',
                        'What means \'Cooling Period\'?',
                        'How to Add Filters?',
                        'Language & Word Limit?',
                        'Why AI Percentage Missing?',
                        'Difference: AI vs Plagiarism?',
                        'How to Reduce AI Score?',
                        'Do you provide API?',
                        'Can I start a Reselling Business?'
                    ];
                @endphp
                @foreach($faqs as $faq)
                    <div
                        class="card-glass p-6 rounded-2xl flex justify-between items-center group cursor-pointer hover:border-indigo-600/20 transition-all">
                        <span
                            class="text-xs font-bold text-slate-400 group-hover:text-white transition-colors">{{ $faq }}</span>
                        <i data-lucide="chevron-down"
                            class="w-4 h-4 text-slate-600 group-hover:text-indigo-500 transition-all"></i>
                    </div>
                @endforeach
            </div>
        </div>

        <footer class="pt-20 pb-10 text-center space-y-8">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Developer - Sandesh Kushwaha</p>
            <p
                class="max-w-4xl mx-auto text-[8px] text-slate-600 leading-relaxed font-medium uppercase tracking-[0.1em]">
                LEGAL NOTICE: PlagExpert is an independent platform provided by DigiSandesh. We operate strictly as a
                third-party intermediary reseller to facilitate access to plagiarism and AI detection services for
                educational and personal use. We are NOT affiliated, associated, authorized, endorsed by, or in any way
                officially connected with Turnitin, LLC, or any of its subsidiaries or its affiliates. Our
                "No-Repository" scanning ensures user data privacy and is not stored in the official Turnitin database.
            </p>
        </footer>
    </main>

    <script>
        lucide.createIcons();

        function updateFileCount(input) {
            const count = input.files.length;
            const info  = document.getElementById('file-info');
            const text  = document.getElementById('file-count-text');
            if (count > 0) {
                text.textContent = count + ' file' + (count > 1 ? 's' : '') + ' selected';
                info.classList.remove('hidden');
                info.classList.add('flex');
            } else {
                info.classList.add('hidden');
                info.classList.remove('flex');
            }
        }

        function updateTimers() {
            const timers = document.querySelectorAll('.countdown-timer');
            timers.forEach(timer => {
                const dueAt = new Date(timer.dataset.due).getTime();
                const now = new Date().getTime();
                const diff = dueAt - now;

                if (diff <= 0) {
                    timer.parentElement.innerHTML = `<i data-lucide="loader-2" class="w-3 h-3 text-amber-500 animate-spin"></i><span class="text-[10px] text-amber-500 font-bold">ETA exceeded — report is being finalized.</span>`;
                    lucide.createIcons();
                    return;
                }

                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                timer.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            });
        }

        setInterval(updateTimers, 1000);
        updateTimers();

        // Auto reload if any pending orders
        const pending = document.querySelector('.countdown-timer');
        if (pending) {
            setTimeout(() => {
                window.location.reload();
            }, 60000); // 1 minute auto refresh
        }
    </script>
</body>

</html>

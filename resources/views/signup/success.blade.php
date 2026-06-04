<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <script>document.documentElement.classList.add('dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful — PlagExpert</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="min-h-screen bg-[#050505] text-white flex flex-col items-center justify-center px-4">

    <div class="text-center max-w-md space-y-6">

        {{-- Success icon --}}
        <div class="w-20 h-20 rounded-full bg-green-500/15 border border-green-500/20 flex items-center justify-center mx-auto">
            <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <div>
            <h1 class="text-2xl font-bold text-white">Payment confirmed!</h1>
            <p class="mt-2 text-slate-400">
                Hi {{ $name }}, your upload link is on its way to your WhatsApp
                @if($phone)
                    (+91 {{ $phone }})
                @endif
                right now.
            </p>
        </div>

        {{-- Steps --}}
        <div class="bg-[#0d0d0f] border border-white/5 rounded-2xl p-6 text-left space-y-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">What happens next</p>

            <div class="flex gap-3">
                <div class="w-6 h-6 rounded-full bg-green-500/15 border border-green-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-3 h-3 text-green-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-white">Payment received</p>
                    <p class="text-xs text-slate-400">Your payment has been confirmed by Razorpay.</p>
                </div>
            </div>

            <div class="flex gap-3">
                <div class="w-6 h-6 rounded-full bg-indigo-500/15 border border-indigo-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <div class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-white">Upload link being generated</p>
                    <p class="text-xs text-slate-400">Usually arrives within 1–2 minutes.</p>
                </div>
            </div>

            <div class="flex gap-3">
                <div class="w-6 h-6 rounded-full bg-white/5 border border-white/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span class="text-[10px] text-slate-500 font-bold">3</span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-300">Click the WhatsApp link → upload your file</p>
                    <p class="text-xs text-slate-400">Your report will be delivered in 10–30 minutes.</p>
                </div>
            </div>
        </div>

        {{-- WhatsApp note --}}
        <div class="bg-green-500/5 border border-green-500/15 rounded-2xl p-4 text-sm text-green-300">
            <strong>Check your WhatsApp!</strong> The link will arrive from our official PlagExpert number within a minute.
        </div>

        <p class="text-xs text-slate-500">
            Didn't receive the link after 5 minutes?
            <a href="https://wa.me/916309872817?text=Hi%2C%20I%20paid%20but%20didn't%20receive%20my%20upload%20link."
               class="text-indigo-400 hover:underline" target="_blank">Contact us on WhatsApp</a>
        </p>

        <a href="https://plagexpert.in" class="inline-block text-xs text-slate-500 hover:text-white transition">← Back to PlagExpert</a>
    </div>

</body>
</html>

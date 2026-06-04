<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <script>document.documentElement.classList.add('dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Started — PlagExpert</title>
    <meta name="description" content="Get your plagiarism or AI detection report in minutes. Pay securely and receive your upload link instantly on WhatsApp.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .plan-card { cursor: pointer; transition: all 0.2s; border: 1.5px solid rgba(255,255,255,0.06); }
        .plan-card.selected { border-color: #6366f1; background: rgba(99,102,241,0.08); }
        .plan-card:not(.selected):hover { border-color: rgba(255,255,255,0.12); }
    </style>
</head>
<body class="min-h-screen bg-[#050505] text-white flex flex-col">

    {{-- Header --}}
    <header class="border-b border-white/5 bg-[#0a0a0c]">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="https://plagexpert.in" class="flex items-center gap-2">
                <img src="/brand/plagexpert-logo.png" alt="PlagExpert" class="h-9 w-auto rounded-lg bg-white px-2 py-1" onerror="this.style.display='none'">
                <span class="font-bold text-white text-lg">PlagExpert</span>
            </a>
            <a href="https://plagexpert.in" class="text-xs text-slate-400 hover:text-white transition">← Back to website</a>
        </div>
    </header>

    {{-- Main --}}
    <main class="flex-1 flex items-start justify-center px-4 py-10">
        <div class="w-full max-w-2xl space-y-6">

            {{-- Heading --}}
            <div class="text-center">
                <h1 class="text-3xl font-bold text-white">Get your report in minutes</h1>
                <p class="mt-2 text-slate-400 text-sm">Pay securely → receive upload link on WhatsApp → upload → done.</p>
            </div>

            {{-- Plan Selector --}}
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-3">Choose a plan</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($allPlans as $slug => $p)
                        <div class="plan-card rounded-2xl p-4 {{ $slug === $planSlug ? 'selected' : '' }}"
                             onclick="selectPlan('{{ $slug }}')">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-semibold text-white text-sm">{{ $p['name'] }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $p['description'] }}</p>
                                </div>
                                <div class="text-right flex-shrink-0 ml-3">
                                    <p class="font-bold text-white font-mono">₹{{ number_format($p['price'] / 100, 0) }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $p['slots'] }} slot{{ $p['slots'] > 1 ? 's' : '' }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <div class="w-3.5 h-3.5 rounded-full border-2 {{ $slug === $planSlug ? 'border-indigo-500 bg-indigo-500' : 'border-white/20' }} flex-shrink-0 flex items-center justify-center plan-radio" id="radio-{{ $slug }}">
                                    @if($slug === $planSlug)
                                        <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                    @endif
                                </div>
                                <span class="text-[10px] text-slate-500">Valid {{ $p['expiry_days'] }} days</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Form --}}
            <div class="bg-[#0d0d0f] border border-white/5 rounded-3xl p-6 sm:p-8 space-y-5">
                <div>
                    <h2 class="font-semibold text-white">Your details</h2>
                    <p class="text-xs text-slate-400 mt-0.5">We send the upload link to your WhatsApp after payment.</p>
                </div>

                @if ($errors->any())
                    <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-sm text-red-400">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div id="api-error" class="hidden p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-sm text-red-400"></div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Full name</label>
                    <input type="text" id="field-name" placeholder="e.g. Rahul Sharma" maxlength="100" required
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-700 focus:outline-none focus:border-indigo-500/50 transition-colors">
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">WhatsApp number</label>
                    <div class="flex items-center gap-2">
                        <span class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-slate-400 flex-shrink-0">+91</span>
                        <input type="tel" id="field-phone" placeholder="10-digit mobile number" maxlength="10" pattern="[0-9]{10}" required
                            class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-700 focus:outline-none focus:border-indigo-500/50 transition-colors">
                    </div>
                    <p class="text-[10px] text-slate-600 mt-1.5">Upload link sent here immediately after payment.</p>
                </div>

                {{-- Price summary --}}
                <div class="p-4 bg-indigo-500/5 border border-indigo-500/10 rounded-2xl flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Total payable</p>
                        <p id="price-display" class="text-2xl font-bold text-white font-mono mt-0.5">
                            ₹{{ number_format($plan['price'] / 100, 0) }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p id="plan-display" class="text-sm font-semibold text-indigo-400">{{ $plan['name'] }} Plan</p>
                        <p id="slots-display" class="text-xs text-slate-500">{{ $plan['slots'] }} slot{{ $plan['slots'] > 1 ? 's' : '' }} · {{ $plan['expiry_days'] }} days</p>
                    </div>
                </div>

                {{-- Pay button --}}
                <button id="pay-btn" onclick="initiatePayment()"
                    class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>
                    </svg>
                    Pay ₹<span id="btn-price">{{ number_format($plan['price'] / 100, 0) }}</span> Securely
                </button>

                <p class="text-center text-[10px] text-slate-600">
                    Secured by Razorpay · No account needed · Upload link sent to WhatsApp
                </p>
            </div>

            {{-- Trust badges --}}
            <div class="flex flex-wrap items-center justify-center gap-4 text-[10px] text-slate-500">
                <span>✓ Non-repository scan</span>
                <span>✓ Files never stored</span>
                <span>✓ Report in 10–30 min</span>
                <span>✓ 50,000+ students trust us</span>
            </div>
        </div>
    </main>

    {{-- Razorpay Checkout.js --}}
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <script>
        // Plan data from PHP
        const plans = @json($allPlans);
        let selectedPlan = '{{ $planSlug }}';

        function selectPlan(slug) {
            // Update visual state
            document.querySelectorAll('.plan-card').forEach(el => el.classList.remove('selected'));
            document.querySelectorAll('.plan-radio').forEach(el => {
                el.classList.remove('border-indigo-500', 'bg-indigo-500');
                el.classList.add('border-white/20');
                el.innerHTML = '';
            });

            const card = document.querySelector(`[onclick="selectPlan('${slug}')"]`);
            if (card) card.classList.add('selected');

            const radio = document.getElementById(`radio-${slug}`);
            if (radio) {
                radio.classList.add('border-indigo-500', 'bg-indigo-500');
                radio.classList.remove('border-white/20');
                radio.innerHTML = '<div class="w-1.5 h-1.5 rounded-full bg-white"></div>';
            }

            selectedPlan = slug;
            const plan = plans[slug];

            // Update price displays
            const price = Math.round(plan.price / 100);
            document.getElementById('price-display').textContent = '₹' + price.toLocaleString('en-IN');
            document.getElementById('btn-price').textContent = price.toLocaleString('en-IN');
            document.getElementById('plan-display').textContent = plan.name + ' Plan';
            document.getElementById('slots-display').textContent = plan.slots + ' slot' + (plan.slots > 1 ? 's' : '') + ' · ' + plan.expiry_days + ' days';
        }

        async function initiatePayment() {
            const name  = document.getElementById('field-name').value.trim();
            const phone = document.getElementById('field-phone').value.trim();

            // Basic validation
            if (!name) { showError('Please enter your name.'); return; }
            if (!/^[0-9]{10}$/.test(phone)) { showError('Please enter a valid 10-digit WhatsApp number.'); return; }

            hideError();
            setLoading(true);

            try {
                const csrfRes = await fetch('/csrf-token-public');
                const csrfData = await csrfRes.json();

                const res = await fetch('/signup/initiate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfData.token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ name, phone, plan: selectedPlan }),
                });

                const data = await res.json();

                if (!res.ok) {
                    showError(data.error || data.message || 'Something went wrong. Please try again.');
                    setLoading(false);
                    return;
                }

                setLoading(false);
                openRazorpay(data);

            } catch (e) {
                showError('Network error. Please check your connection and try again.');
                setLoading(false);
            }
        }

        function openRazorpay({ order_id, key_id, amount, name, phone, plan_name }) {
            const options = {
                key: key_id,
                amount: amount,
                currency: 'INR',
                name: 'PlagExpert',
                description: plan_name + ' Plan',
                order_id: order_id,
                prefill: {
                    name: name,
                    contact: '+91' + phone,
                },
                theme: { color: '#6366f1' },
                modal: {
                    ondismiss: function() {
                        document.getElementById('pay-btn').disabled = false;
                        document.getElementById('pay-btn').textContent = 'Pay ₹' + Math.round(amount / 100).toLocaleString('en-IN') + ' Securely';
                    }
                },
                handler: function(response) {
                    // Payment successful — redirect to success page
                    const params = new URLSearchParams({ name, phone });
                    window.location.href = '/signup/success?' + params.toString();
                },
            };

            const rzp = new Razorpay(options);
            rzp.open();
        }

        function setLoading(loading) {
            const btn = document.getElementById('pay-btn');
            btn.disabled = loading;
            if (loading) {
                btn.innerHTML = '<svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg> Processing…';
            }
        }

        function showError(msg) {
            const el = document.getElementById('api-error');
            el.textContent = msg;
            el.classList.remove('hidden');
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function hideError() {
            document.getElementById('api-error').classList.add('hidden');
        }
    </script>
</body>
</html>

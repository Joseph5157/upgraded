<?php

namespace App\Http\Controllers;

use App\Jobs\ProvisionGuestLinkJob;
use App\Models\RazorpayOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SignupController extends Controller
{
    public function show(Request $request)
    {
        $planSlug = $request->query('plan', 'student');
        $plan     = config('plans.' . $planSlug);

        if (! $plan) {
            $planSlug = 'student';
            $plan     = config('plans.student');
        }

        $allPlans = config('plans');

        return view('signup.show', compact('planSlug', 'plan', 'allPlans'));
    }

    public function initiate(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['required', 'digits:10'],
            'plan'  => ['required', 'string', 'in:' . implode(',', array_keys(config('plans')))],
        ]);

        $plan      = config('plans.' . $data['plan']);
        $keyId     = config('services.razorpay.key_id');
        $keySecret = config('services.razorpay.key_secret');
        $phone     = '91' . $data['phone'];

        $response = Http::withBasicAuth($keyId, $keySecret)
            ->timeout(15)
            ->post('https://api.razorpay.com/v1/orders', [
                'amount'   => $plan['price'],
                'currency' => 'INR',
                'receipt'  => 'pe_' . Str::random(16),
                'notes'    => ['plan' => $data['plan'], 'phone' => $phone, 'name' => $data['name']],
            ]);

        if (! $response->successful()) {
            Log::error('Razorpay order creation failed.', ['body' => $response->body()]);
            return response()->json(['error' => 'Payment gateway error. Please try again.'], 502);
        }

        $rzpOrder = $response->json();

        RazorpayOrder::create([
            'name'              => $data['name'],
            'phone'             => $phone,
            'plan'              => $data['plan'],
            'slots'             => $plan['slots'],
            'amount'            => $plan['price'],
            'razorpay_order_id' => $rzpOrder['id'],
            'status'            => 'pending',
        ]);

        return response()->json([
            'order_id'  => $rzpOrder['id'],
            'key_id'    => $keyId,
            'amount'    => $plan['price'],
            'name'      => $data['name'],
            'phone'     => $data['phone'],
            'plan_name' => $plan['name'],
        ]);
    }

    public function webhook(Request $request)
    {
        $signature = $request->header('X-Razorpay-Signature');
        $payload   = $request->getContent();
        $secret    = config('services.razorpay.webhook_secret');

        if (! $signature || ! $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (! hash_equals(hash_hmac('sha256', $payload, $secret), $signature)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($request->input('event') !== 'payment.captured') {
            return response()->json(['status' => 'ignored']);
        }

        $payment    = $request->input('payload.payment.entity');
        $rzpOrderId = $payment['order_id'] ?? null;

        if (! $rzpOrderId) {
            return response()->json(['error' => 'Missing order_id'], 400);
        }

        $razorpayOrder = RazorpayOrder::where('razorpay_order_id', $rzpOrderId)->first();

        if (! $razorpayOrder) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if (in_array($razorpayOrder->status, ['paid', 'provisioned'])) {
            return response()->json(['status' => 'already_processed']);
        }

        $razorpayOrder->update([
            'razorpay_payment_id' => $payment['id'],
            'status'              => 'paid',
        ]);

        ProvisionGuestLinkJob::dispatch($razorpayOrder->id);

        return response()->json(['status' => 'queued']);
    }

    public function success(Request $request)
    {
        $name  = $request->query('name', 'there');
        $phone = $request->query('phone');

        return view('signup.success', compact('name', 'phone'));
    }
}

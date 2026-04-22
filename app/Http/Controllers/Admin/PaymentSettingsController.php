<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentSettingsController extends Controller
{
    public function index()
    {
        $settings = PaymentSetting::orderByDesc('created_at')->get();
        $active   = PaymentSetting::active()->first();

        return view('admin.payment-settings.index', compact('settings', 'active'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'upi_name' => ['required', 'string', 'max:255'],
            'upi_id'   => ['required', 'string', 'max:255'],
            'qr_code'  => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $qrPath = null;
        if ($request->hasFile('qr_code')) {
            $qrPath = $request->file('qr_code')->store('qr-codes', 'public');
        }

        $isFirst = PaymentSetting::count() === 0;

        $setting = PaymentSetting::create([
            'upi_name'     => $validated['upi_name'],
            'upi_id'       => $validated['upi_id'],
            'qr_code_path' => $qrPath,
            'is_active'    => false,
        ]);

        if ($isFirst) {
            PaymentSetting::setActive($setting->id);
        }

        return back()->with('success', 'Payment method added successfully.');
    }

    public function setActive(PaymentSetting $paymentSetting)
    {
        PaymentSetting::setActive($paymentSetting->id);

        return back()->with('success', 'Payment method activated successfully.');
    }

    public function destroy(PaymentSetting $paymentSetting)
    {
        if ($paymentSetting->is_active) {
            return back()->with('error', 'Cannot delete active payment method. Activate another first.');
        }

        if ($paymentSetting->qr_code_path && Storage::disk('public')->exists($paymentSetting->qr_code_path)) {
            Storage::disk('public')->delete($paymentSetting->qr_code_path);
        }

        $paymentSetting->delete();

        return back()->with('success', 'Payment method deleted.');
    }

    public function update(Request $request, PaymentSetting $paymentSetting)
    {
        $validated = $request->validate([
            'upi_name' => ['required', 'string', 'max:255'],
            'upi_id'   => ['required', 'string', 'max:255'],
            'qr_code'  => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $paymentSetting->upi_name = $validated['upi_name'];
        $paymentSetting->upi_id   = $validated['upi_id'];

        if ($request->hasFile('qr_code')) {
            if ($paymentSetting->qr_code_path && Storage::disk('public')->exists($paymentSetting->qr_code_path)) {
                Storage::disk('public')->delete($paymentSetting->qr_code_path);
            }

            $paymentSetting->qr_code_path = $request->file('qr_code')->store('qr-codes', 'public');
        }

        $paymentSetting->save();

        return back()->with('success', 'Payment method updated successfully.');
    }
}

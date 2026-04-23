<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\Support\StorageLifecycle;
use Illuminate\Http\Request;

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
        try {
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
        } catch (\Throwable $e) {
            if ($qrPath) {
                StorageLifecycle::deleteStoredFileIfPresent('public', $qrPath);
            }

            throw $e;
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

        StorageLifecycle::deleteStoredFileIfPresent('public', $paymentSetting->qr_code_path);

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

        $oldQrPath = $paymentSetting->qr_code_path;
        $paymentSetting->upi_name = $validated['upi_name'];
        $paymentSetting->upi_id   = $validated['upi_id'];

        $newQrPath = null;
        if ($request->hasFile('qr_code')) {
            $newQrPath = $request->file('qr_code')->store('qr-codes', 'public');
            $paymentSetting->qr_code_path = $newQrPath;
        }

        try {
            $paymentSetting->save();
        } catch (\Throwable $e) {
            if ($newQrPath) {
                StorageLifecycle::deleteStoredFileIfPresent('public', $newQrPath);
            }

            throw $e;
        }

        if ($newQrPath) {
            StorageLifecycle::deleteStoredFileIfPresent('public', $oldQrPath);
        }

        return back()->with('success', 'Payment method updated successfully.');
    }
}

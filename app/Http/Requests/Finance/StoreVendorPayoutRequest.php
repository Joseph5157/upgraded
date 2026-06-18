<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorPayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'vendor_id'      => ['required', 'integer', 'exists:users,id'],
            'amount'         => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'payment_mode'   => ['required', Rule::in(['upi', 'bank_transfer', 'cash'])],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'paid_at'        => ['nullable', 'date'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_id.exists'   => 'Select a valid vendor.',
            'amount.min'         => 'Payout amount must be greater than zero.',
            'payment_mode.in'    => 'Payment mode must be UPI, bank transfer, or cash.',
        ];
    }
}

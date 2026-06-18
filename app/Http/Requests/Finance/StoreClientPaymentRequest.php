<?php

namespace App\Http\Requests\Finance;

use App\Models\ClientPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // All users with role=admin may record payments.
        // is_super_admin is a boolean flag on top of role=admin, not a separate role,
        // so checking role=admin is sufficient and covers super admins too.
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'client_id'      => ['required', 'integer', 'exists:clients,id'],
            'amount_received' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'credits_added'   => ['required', 'integer', 'min:1'],
            'payment_mode'    => ['required', Rule::in([
                ClientPayment::MODE_UPI,
                ClientPayment::MODE_BANK_TRANSFER,
                ClientPayment::MODE_CASH,
                ClientPayment::MODE_RAZORPAY,
            ])],
            'transaction_id'  => ['nullable', 'string', 'max:255'],
            'received_at'     => ['required', 'date'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.exists'        => 'Select a valid client.',
            'amount_received.min'     => 'Amount must be greater than zero.',
            'credits_added.min'       => 'Credits added must be at least 1.',
            'payment_mode.in'         => 'Select a valid payment mode.',
        ];
    }
}

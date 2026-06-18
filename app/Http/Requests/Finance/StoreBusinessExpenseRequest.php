<?php

namespace App\Http\Requests\Finance;

use App\Models\BusinessExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'category'     => ['required', Rule::in(array_keys(BusinessExpense::categories()))],
            'payment_mode' => ['nullable', Rule::in(['upi', 'bank_transfer', 'cash', 'card', 'auto_deducted'])],
            'reference_id' => ['nullable', 'string', 'max:255'],
            'expense_date' => ['required', 'date'],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min'       => 'Expense amount must be greater than zero.',
            'category.in'      => 'Select a valid expense category.',
            'payment_mode.in'  => 'Payment mode must be one of: UPI, bank transfer, cash, card, or auto-deducted.',
            'expense_date.required' => 'Expense date is required.',
        ];
    }
}

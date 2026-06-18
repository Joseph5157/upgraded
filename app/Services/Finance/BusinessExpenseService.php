<?php

namespace App\Services\Finance;

use App\Models\BusinessExpense;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class BusinessExpenseService
{
    /**
     * Record a new business expense.
     *
     * Rules:
     *  - amount must be > 0
     *  - category must be a valid BusinessExpense category constant
     *  - duplicate (payment_mode + reference_id) is rejected for non-cash, non-auto_deducted modes
     *  - never touches client credit_balance
     *  - never touches vendor pending_earning_balance or approved_payable_balance
     *  - never touches slots or slots_consumed
     */
    public function recordExpense(array $data, ?User $createdBy = null): BusinessExpense
    {
        $amount = (float) ($data['amount'] ?? 0);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Expense amount must be greater than zero.');
        }

        $category = $data['category'] ?? '';
        $validCategories = array_keys(BusinessExpense::categories());

        if (! in_array($category, $validCategories, true)) {
            throw new \InvalidArgumentException("Invalid expense category: '{$category}'.");
        }

        $paymentMode = $data['payment_mode'] ?? null;
        $referenceId = isset($data['reference_id']) && $data['reference_id'] !== '' ? $data['reference_id'] : null;

        // Duplicate reference ID guard — skip for cash and auto_deducted (no receipt to deduplicate)
        if ($referenceId && $paymentMode && ! in_array($paymentMode, ['cash', 'auto_deducted'], true)) {
            $exists = BusinessExpense::where('payment_mode', $paymentMode)
                ->where('reference_id', $referenceId)
                ->exists();

            if ($exists) {
                throw new \InvalidArgumentException(
                    "An expense with reference ID '{$referenceId}' already exists for payment mode '{$paymentMode}'."
                );
            }
        }

        $expense = BusinessExpense::create([
            'category'     => $category,
            'amount'       => $amount,
            'payment_mode' => $paymentMode ?: null,
            'reference_id' => $referenceId,
            'expense_date' => $data['expense_date'] ?? today()->toDateString(),
            'notes'        => isset($data['notes']) && $data['notes'] !== '' ? $data['notes'] : null,
            'created_by'   => $createdBy?->id,
        ]);

        Log::info('business.expense_recorded', [
            'expense_id'   => $expense->id,
            'category'     => $expense->category,
            'amount'       => (float) $expense->amount,
            'payment_mode' => $expense->payment_mode,
            'created_by'   => $createdBy?->id,
        ]);

        return $expense;
    }

    /**
     * Sum of all expenses, optionally filtered by date range.
     */
    public function totalExpenses(?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): float
    {
        $query = BusinessExpense::where('status', '!=', BusinessExpense::STATUS_VOIDED);

        if ($from) {
            $query->where('expense_date', '>=', $from->toDateString());
        }

        if ($to) {
            $query->where('expense_date', '<=', $to->toDateString());
        }

        return (float) $query->sum('amount');
    }

    /**
     * Sum of expenses grouped by category, optionally filtered by date range.
     *
     * @return array<string, float>  category => total
     */
    public function totalByCategory(?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $query = BusinessExpense::where('status', '!=', BusinessExpense::STATUS_VOIDED)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category');

        if ($from) {
            $query->where('expense_date', '>=', $from->toDateString());
        }

        if ($to) {
            $query->where('expense_date', '<=', $to->toDateString());
        }

        return $query->pluck('total', 'category')
            ->map(fn ($v) => (float) $v)
            ->all();
    }
}

<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreBusinessExpenseRequest;
use App\Models\BusinessExpense;
use App\Services\Finance\BusinessExpenseService;
use App\Services\Finance\FinanceVoidService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessExpenseController extends Controller
{
    public function __construct(
        protected BusinessExpenseService $expenseService,
        protected FinanceVoidService $voidService,
    ) {}

    public function index(): View
    {
        $expenses = BusinessExpense::with('createdBy')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(25);

        $total = (float) BusinessExpense::where('status', '!=', BusinessExpense::STATUS_VOIDED)->sum('amount');

        $byCategory = BusinessExpense::where('status', '!=', BusinessExpense::STATUS_VOIDED)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($v) => (float) $v)
            ->all();

        return view('admin.finance.expenses.index', compact('expenses', 'total', 'byCategory'));
    }

    public function show(BusinessExpense $businessExpense): View
    {
        $businessExpense->load('createdBy');

        return view('admin.finance.expenses.show', compact('businessExpense'));
    }

    public function store(StoreBusinessExpenseRequest $request): RedirectResponse
    {
        try {
            $expense = $this->expenseService->recordExpense(
                $request->validated(),
                $request->user(),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $label = BusinessExpense::categories()[$expense->category] ?? $expense->category;

        return redirect()
            ->route('admin.finance.expenses.index')
            ->with('success', "Expense of ₹" . number_format($expense->amount, 0) . " ({$label}) recorded.");
    }

    public function void(Request $request, BusinessExpense $businessExpense): RedirectResponse
    {
        $request->validate(['void_reason' => 'required|string|max:2000']);

        $voided = $this->voidService->voidBusinessExpense(
            $businessExpense,
            $request->user(),
            $request->input('void_reason'),
        );

        if (! $voided) {
            return back()->with('info', 'Expense was already voided.');
        }

        return back()->with('success', "Expense #{$businessExpense->id} has been voided.");
    }
}

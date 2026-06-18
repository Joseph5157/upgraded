<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function __construct(protected FinanceDashboardService $dashboardService) {}

    public function index(Request $request): View
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $to   = $request->filled('to')   ? Carbon::parse($request->input('to'))->endOfDay()     : null;

        // Swap if reversed
        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $metrics = $this->dashboardService->metrics($from, $to);

        return view('admin.finance.dashboard', array_merge($metrics, [
            'from' => $from,
            'to'   => $to,
        ]));
    }
}

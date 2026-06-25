<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AccountManagerController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\InviteController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Auth\TelegramLoginController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BotController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\ClientMatrixController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\TopupRequestController;
use App\Http\Controllers\VendorPayoutController;
use App\Http\Controllers\ClientSubscriptionController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\VendorEarningsController;
use App\Http\Controllers\Admin\PaymentSettingsController;
use App\Http\Controllers\Admin\ClientLinkController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\Finance\ClientPaymentController;
use App\Http\Controllers\Admin\Finance\ClientCreditTransactionController;
use App\Http\Controllers\Admin\Finance\ClientBalanceController;
use App\Http\Controllers\Admin\Finance\VendorPayoutController as AdminVendorPayoutController;
use App\Http\Controllers\Admin\Finance\BusinessExpenseController;
use App\Http\Controllers\Admin\Finance\FinanceDashboardController;
use App\Http\Controllers\Admin\Finance\FinanceReportController;
use App\Http\Controllers\Admin\VendorEarningController;
use App\Http\Controllers\SignupController;

Route::get('/', function () {
    if (auth()->check()) {
        return match(auth()->user()->role) {
            'admin'  => redirect()->route('admin.dashboard'),
            'vendor' => redirect()->route('dashboard'),
            'client' => redirect()->route('client.dashboard'),
            default  => redirect()->route('login'),
        };
    }
    return redirect()->route('login');
});

// Public CSRF token refresh — used by the client upload page to prevent 419 errors
// Throttled tightly so it can't be abused; returns a fresh CSRF token for the session.
Route::get('/csrf-token-public', function () {
    return response()->json(['token' => csrf_token()]);
})->middleware('throttle:20,1')->name('csrf.token.public');

Route::post('/telegram/webhook/{secret}', [BotController::class, 'webhook'])
    ->middleware('throttle:60,1')
    ->name('telegram.webhook');

Route::middleware('throttle:20,1')->group(function () {
    Route::get('/signup', [SignupController::class, 'show'])->name('signup.show');
    Route::post('/signup/initiate', [SignupController::class, 'initiate'])->name('signup.initiate');
    Route::get('/signup/success', [SignupController::class, 'success'])->name('signup.success');
});

Route::post('/webhooks/razorpay', [SignupController::class, 'webhook'])
    ->middleware('throttle:60,1')
    ->name('webhooks.razorpay');

Route::middleware('guest')->group(function () {
    Route::get('/login', [OtpLoginController::class, 'showLogin'])
        ->name('login');
    Route::post('/login/send-otp', [OtpLoginController::class, 'sendOtp'])
        ->middleware('throttle:3,1')
        ->name('login.send-otp');
    Route::post('/login/verify-otp', [OtpLoginController::class, 'verifyOtp'])
        ->middleware('throttle:5,1')
        ->name('login.verify-otp');
    Route::get('/auth/telegram/{token}', [TelegramLoginController::class, 'authenticate'])
        ->middleware('throttle:10,1')
        ->name('telegram.login');
});

// Client Public Routes — throttled to prevent abuse
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/u/{token}', [OrderController::class, 'showUpload'])->name('client.upload');
    Route::get('/u/{token}/pulse', [OrderController::class, 'guestPulse'])->name('client.link.pulse');
    Route::get('/u/{token}/orders/{order:token_view}/pulse', [OrderController::class, 'guestPulse'])->name('client.link.track.pulse');
    Route::post('/u/{token}', [OrderController::class, 'store'])->name('client.store');
    Route::get('/u/{token}/orders/{order:token_view}', [OrderController::class, 'trackGuest'])->name('client.link.track');
    Route::get('/u/{token}/orders/{order:token_view}/download', [OrderController::class, 'downloadGuest'])->name('client.link.download');
    Route::get('/track/{token_view}', [OrderController::class, 'track'])->name('client.track');
    Route::get('/download/{token_view}', [OrderController::class, 'download'])->name('client.download');
});

Route::middleware(['auth', 'nocache'])->group(function () {
    // CSRF token refresh endpoint — keeps long-lived sessions valid
    Route::get('/csrf-refresh', function () {
        return response()->json(['token' => csrf_token()]);
    })->name('csrf.refresh');

    // Vendor/Admin Dashboard Routes
    Route::middleware(['role:vendor,admin', 'account.status'])->group(function () {
        // Stage 2 redirect — GET /dashboard → Filament panel (Phase 10)
        Route::get('/dashboard', function () {
            return match (auth()->user()?->role) {
                'admin'  => redirect('/filament-admin'),
                default  => redirect('/vendor-panel'),
            };
        })->name('dashboard');
        Route::get('/dashboard/pulse', [DashboardController::class, 'pulse'])->name('dashboard.pulse');
        Route::post('/orders/{order}/claim', [DashboardController::class, 'claim'])->name('orders.claim');
        Route::post('/orders/{order}/unclaim', [DashboardController::class, 'unclaim'])->name('orders.unclaim');
        Route::post('/orders/{order}/status', [DashboardController::class, 'updateStatus'])->name('orders.status');
        Route::post('/orders/{order}/report', [DashboardController::class, 'uploadReport'])->name('orders.report');
        Route::get('/orders/{order}/files/{file}', [DashboardController::class, 'downloadFile'])->name('orders.files.download');
        // Stage 2 redirect — GET /earnings → Filament vendor panel (Phase 10)
        Route::get('/earnings', fn () => redirect('/vendor-panel/earning-history'))->name('vendor.earnings');
        Route::post('/earnings/request-payout', [VendorPayoutController::class, 'requestPayout'])->name('earnings.request-payout');
    });

    // Client Dashboard Routes
    Route::middleware(['role:client', 'account.status'])->prefix('client')->name('client.')->group(function () {
        // Stage 2 redirect — GET /client/dashboard → Filament client panel (Phase 10)
        Route::get('/dashboard', fn () => redirect('/client-panel'))->name('dashboard');
        Route::get('/dashboard/pulse', [ClientDashboardController::class, 'pulse'])->name('dashboard.pulse');
        Route::post('/dashboard/upload', [ClientDashboardController::class, 'store'])->name('dashboard.upload');
        Route::post('/dashboard/telegram/regenerate-link', [ClientDashboardController::class, 'regenerateTelegramLink'])->name('dashboard.telegram.regenerate');
        Route::post('/dashboard/telegram/test', [ClientDashboardController::class, 'sendTelegramTest'])->name('dashboard.telegram.test');
        Route::delete('/orders/{order}/delete', [ClientDashboardController::class, 'destroy'])->name('orders.delete');
        Route::delete('/orders/{order}/files/{file}', [ClientDashboardController::class, 'destroyFile'])->name('orders.files.delete');
        Route::post('/topup', [TopupRequestController::class, 'store'])->name('topup.store');
        Route::post('/refunds', [RefundController::class, 'store'])->name('refunds.store');
        Route::get('/subscription', [ClientSubscriptionController::class, 'index'])->name('subscription');
        Route::get('/downloads', [ClientDashboardController::class, 'downloads'])->name('downloads');
    });

    Route::post('/announcements/{announcement}/dismiss', [AnnouncementController::class, 'dismiss'])->name('announcements.dismiss');

});

// Admin Routes — single consolidated group
Route::middleware(['auth', 'nocache', 'role:admin', 'account.status'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Stage 2 redirect — GET /admin/dashboard → Filament admin panel (Phase 10)
        Route::get('/dashboard', fn () => redirect('/filament-admin'))->name('dashboard');
        Route::post('/accounts/invite', [InviteController::class, 'store'])->name('accounts.invite');
        Route::resource('/matrix', ClientMatrixController::class)->only(['index', 'update']);
        Route::post('/matrix/{client}/refill', [ClientMatrixController::class, 'refill'])->name('matrix.refill');
        Route::delete('/matrix/{client}', [ClientMatrixController::class, 'destroy'])->name('matrix.destroy');
        Route::get('/topup', [TopupRequestController::class, 'index'])->name('topup.index');
        Route::post('/topup/{topupRequest}/approve', [TopupRequestController::class, 'approve'])->name('topup.approve');
        Route::post('/topup/{topupRequest}/reject', [TopupRequestController::class, 'reject'])->name('topup.reject');
        Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::get('/billing/{ledger}', [BillingController::class, 'show'])->name('billing.show');
        Route::prefix('finance')->name('finance.')->group(function () {
            // Stage 2 redirect — GET /admin/finance/dashboard → Filament finance panel (Phase 10)
            Route::get('/dashboard', fn () => redirect('/filament-finance'))->name('dashboard');
            // Phase 10A — finance reports
            Route::get('/reports',                          [FinanceReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/client-payments',          [FinanceReportController::class, 'clientPayments'])->name('reports.client-payments');
            Route::get('/reports/client-payments.csv',      [FinanceReportController::class, 'clientPaymentsCsv'])->name('reports.client-payments.csv');
            Route::get('/reports/client-credit-ledger',     [FinanceReportController::class, 'clientCreditLedger'])->name('reports.client-credit-ledger');
            Route::get('/reports/client-credit-ledger.csv', [FinanceReportController::class, 'clientCreditLedgerCsv'])->name('reports.client-credit-ledger.csv');
            Route::get('/reports/vendor-earnings',          [FinanceReportController::class, 'vendorEarnings'])->name('reports.vendor-earnings');
            Route::get('/reports/vendor-earnings.csv',      [FinanceReportController::class, 'vendorEarningsCsv'])->name('reports.vendor-earnings.csv');
            Route::get('/reports/vendor-payouts',           [FinanceReportController::class, 'vendorPayouts'])->name('reports.vendor-payouts');
            Route::get('/reports/vendor-payouts.csv',       [FinanceReportController::class, 'vendorPayoutsCsv'])->name('reports.vendor-payouts.csv');
            Route::get('/reports/expenses',                 [FinanceReportController::class, 'expenses'])->name('reports.expenses');
            Route::get('/reports/expenses.csv',             [FinanceReportController::class, 'expensesCsv'])->name('reports.expenses.csv');
            Route::get('/reports/order-profit',             [FinanceReportController::class, 'orderProfit'])->name('reports.order-profit');
            Route::get('/reports/order-profit.csv',         [FinanceReportController::class, 'orderProfitCsv'])->name('reports.order-profit.csv');
            Route::get('/reports/monthly-summary',          [FinanceReportController::class, 'monthlySummary'])->name('reports.monthly-summary');
            Route::get('/reports/monthly-summary.csv',      [FinanceReportController::class, 'monthlySummaryCsv'])->name('reports.monthly-summary.csv');
            // Phase 7 — vendor payout ledger (replaces legacy VendorPayoutController for admin)
            Route::get('/payouts', [AdminVendorPayoutController::class, 'index'])->name('payouts.index');
            Route::post('/payouts', [AdminVendorPayoutController::class, 'store'])->name('payouts.store');
            Route::get('/payouts/{vendorPayout}', [AdminVendorPayoutController::class, 'show'])->name('payouts.show');
            Route::post('/payouts/{vendorPayout}/void', [AdminVendorPayoutController::class, 'void'])->name('payouts.void');
            // Phase 3 — client payment ledger
            Route::get('/client-payments', [ClientPaymentController::class, 'index'])->name('client-payments.index');
            Route::post('/client-payments', [ClientPaymentController::class, 'store'])->name('client-payments.store');
            Route::get('/client-payments/{clientPayment}', [ClientPaymentController::class, 'show'])->name('client-payments.show');
            Route::post('/client-payments/{clientPayment}/void', [ClientPaymentController::class, 'void'])->name('client-payments.void');
            // Phase 3B — credit transaction ledger and client balance summary
            Route::get('/client-credit-transactions', [ClientCreditTransactionController::class, 'index'])->name('client-credit-transactions.index');
            Route::get('/client-balances', [ClientBalanceController::class, 'index'])->name('client-balances.index');
            // Phase 8 — business expense tracking
            Route::get('/expenses', [BusinessExpenseController::class, 'index'])->name('expenses.index');
            Route::post('/expenses', [BusinessExpenseController::class, 'store'])->name('expenses.store');
            Route::get('/expenses/{businessExpense}', [BusinessExpenseController::class, 'show'])->name('expenses.show');
            Route::post('/expenses/{businessExpense}/void', [BusinessExpenseController::class, 'void'])->name('expenses.void');
            // Phase 6 — vendor earning approval / rejection
            Route::get('/vendor-earnings', [VendorEarningController::class, 'index'])->name('vendor-earnings.index');
            Route::post('/vendor-earnings/{order}/approve', [VendorEarningController::class, 'approve'])->name('vendor-earnings.approve');
            Route::post('/vendor-earnings/{order}/reject', [VendorEarningController::class, 'reject'])->name('vendor-earnings.reject');
        });
        Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::post('/refunds/{refundRequest}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
        Route::post('/refunds/{refundRequest}/reject', [RefundController::class, 'reject'])->name('refunds.reject');
        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::post('/announcements/{announcement}/toggle', [AnnouncementController::class, 'toggle'])->name('announcements.toggle');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
        Route::get('/accounts', [AccountManagerController::class, 'index'])->name('accounts.index');
        Route::post('/accounts/{user}/freeze', [AccountManagerController::class, 'freeze'])->name('accounts.freeze');
        Route::post('/accounts/{user}/unfreeze', [AccountManagerController::class, 'unfreeze'])->name('accounts.unfreeze');
        Route::delete('/accounts/{user}', [AccountManagerController::class, 'destroy'])->name('accounts.destroy');
        Route::post('/accounts/{id}/restore', [AccountManagerController::class, 'restore'])->name('accounts.restore');
        Route::delete('/accounts/{id}/force', [AccountManagerController::class, 'forceDelete'])->name('accounts.forceDelete');
        Route::prefix('client-links')->name('client-links.')->group(function () {
            Route::get('/', [ClientLinkController::class, 'index'])->name('index');
            Route::post('/', [ClientLinkController::class, 'store'])->name('store');
            Route::post('/clients', [ClientLinkController::class, 'storeClient'])->name('clients.store');
            Route::delete('/clients/{client}', [ClientLinkController::class, 'destroyClient'])->name('clients.destroy');
            Route::post('/{clientLink}/revoke', [ClientLinkController::class, 'revoke'])->name('revoke');
            Route::post('/{clientLink}/toggle', [ClientLinkController::class, 'revoke'])->name('toggle');
            Route::delete('/{clientLink}', [ClientLinkController::class, 'destroy'])->name('destroy');
            Route::get('/{clientLink}/orders', [ClientLinkController::class, 'showOrders'])->name('orders');
            Route::delete('/{clientLink}/orders/{order}', [ClientLinkController::class, 'destroyOrder'])->name('orders.destroy');
        });
        Route::prefix('pricing')->name('pricing.')->group(function () {
            Route::get('/', [PricingController::class, 'index'])->name('index');
            Route::post('/client/{client}', [PricingController::class, 'updateClient'])->name('update-client');
            Route::post('/vendor/{user}', [PricingController::class, 'updateVendor'])->name('update-vendor');
        });
        Route::prefix('payment-settings')->name('payment-settings.')->group(function () {
            Route::get('/', [PaymentSettingsController::class, 'index'])->name('index');
            Route::post('/', [PaymentSettingsController::class, 'store'])->name('store');
            Route::post('/{paymentSetting}/activate', [PaymentSettingsController::class, 'setActive'])->name('activate');
            Route::post('/{paymentSetting}/update', [PaymentSettingsController::class, 'update'])->name('update');
            Route::delete('/{paymentSetting}', [PaymentSettingsController::class, 'destroy'])->name('destroy');
        });
    });

Route::middleware(['auth', 'nocache', 'account.status'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__ . '/auth.php';

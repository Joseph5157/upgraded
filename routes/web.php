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
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/pulse', [DashboardController::class, 'pulse'])->name('dashboard.pulse');
        Route::post('/orders/{order}/claim', [DashboardController::class, 'claim'])->name('orders.claim');
        Route::post('/orders/{order}/unclaim', [DashboardController::class, 'unclaim'])->name('orders.unclaim');
        Route::post('/orders/{order}/status', [DashboardController::class, 'updateStatus'])->name('orders.status');
        Route::post('/orders/{order}/report', [DashboardController::class, 'uploadReport'])->name('orders.report');
        Route::get('/orders/{order}/files/{file}', [DashboardController::class, 'downloadFile'])->name('orders.files.download');
        Route::get('/earnings', [VendorEarningsController::class, 'index'])->name('vendor.earnings');
    });

    // Client Dashboard Routes
    Route::middleware(['role:client', 'account.status'])->prefix('client')->name('client.')->group(function () {
        Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/pulse', [ClientDashboardController::class, 'pulse'])->name('dashboard.pulse');
        Route::post('/dashboard/upload', [ClientDashboardController::class, 'store'])->name('dashboard.upload');
        Route::post('/dashboard/telegram/regenerate-link', [ClientDashboardController::class, 'regenerateTelegramLink'])->name('dashboard.telegram.regenerate');
        Route::post('/dashboard/telegram/test', [ClientDashboardController::class, 'sendTelegramTest'])->name('dashboard.telegram.test');
        Route::delete('/orders/{order}/delete', [ClientDashboardController::class, 'destroy'])->name('orders.delete');
        Route::delete('/orders/{order}/files/{file}', [ClientDashboardController::class, 'destroyFile'])->name('orders.files.delete');
        Route::post('/topup', [TopupRequestController::class, 'store'])->name('topup.store');
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
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
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
            Route::get('/payouts', [VendorPayoutController::class, 'index'])->name('payouts.index');
            Route::post('/payouts', [VendorPayoutController::class, 'store'])->name('payouts.store');
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

Route::middleware(['auth', 'nocache'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__ . '/auth.php';

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TelegramService;
use App\Support\SessionExpiry;
use App\Support\LogContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class OtpLoginController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'portal_number' => ['required', 'integer'],
        ]);

        $user = User::where('portal_number', $request->portal_number)
            ->whereNotNull('activated_at')
            ->whereNull('deleted_at')
            ->first();

        if (! $user) {
            return back()->withErrors([
                'portal_number' => 'No account found with this Portal ID.',
            ]);
        }

        if ($user->isFrozen()) {
            return back()->withErrors([
                'portal_number' => 'Your account is frozen. Contact your admin.',
            ]);
        }

        if (! $user->telegram_chat_id) {
            return back()->withErrors([
                'portal_number' => 'This account is not linked to Telegram yet. Use the invite link or contact your admin.',
            ]);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Clear any existing failure counter so a fresh OTP gives a clean slate.
        RateLimiter::clear('otp:' . $user->portal_number);

        $user->forceFill([
            'otp'            => hash('sha256', $otp),
            'otp_expires_at' => now()->addMinutes(5),
        ])->save();

        $sent = app(TelegramService::class)->sendMessage(
            $user->telegram_chat_id,
            "Your login code is: {$otp}\n\nExpires in 5 minutes.\nDo not share this with anyone."
        );

        if (! $sent) {
            $user->forceFill([
                'otp' => null,
                'otp_expires_at' => null,
            ])->save();

            Log::warning('auth.otp.delivery_failed', array_merge(
                LogContext::currentRequest(),
                LogContext::forUser($user, [
                    'portal_number' => $user->portal_number,
                ])
            ));

            return back()->withErrors([
                'portal_number' => 'We could not send a login code right now. Please try again.',
            ])->withInput(['portal_number' => $request->portal_number]);
        }

        Log::info('auth.otp.sent', array_merge(
            LogContext::currentRequest(),
            LogContext::forUser($user, [
                'portal_number' => $user->portal_number,
                'otp_expires_at' => $user->otp_expires_at?->toIso8601String(),
            ])
        ));

        session()->flash('otp_sent', true);
        session()->flash('portal_number', $request->portal_number);

        return back();
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'portal_number' => ['required', 'integer'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $rateLimiterKey = 'otp:' . $request->portal_number;

        // If the portal number has already burned through its attempt allowance,
        // reject immediately without touching the database.
        if (RateLimiter::tooManyAttempts($rateLimiterKey, 3)) {
            return back()
                ->withErrors(['otp' => 'Too many incorrect attempts. Request a new login code.'])
                ->withInput(['portal_number' => $request->portal_number]);
        }

        $user = DB::transaction(function () use ($request) {
            $user = User::where('portal_number', $request->portal_number)
                ->where('otp', hash('sha256', $request->otp))
                ->where('otp_expires_at', '>', now())
                ->whereNotNull('activated_at')
                ->lockForUpdate()
                ->first();

            if (! $user) {
                return null;
            }

            $user->forceFill([
                'otp'            => null,
                'otp_expires_at' => null,
            ])->save();

            return $user;
        });

        if (! $user) {
            // Count this failure against the per-portal-number budget (3 attempts,
            // decays after 10 minutes — long enough to outlast IP rotation).
            RateLimiter::hit($rateLimiterKey, 600);

            // If the budget is now exhausted, immediately null the OTP so the
            // remaining window cannot be brute-forced from a fresh IP.
            if (RateLimiter::tooManyAttempts($rateLimiterKey, 3)) {
                User::where('portal_number', $request->portal_number)
                    ->whereNotNull('otp')
                    ->update(['otp' => null, 'otp_expires_at' => null]);
            }

            Log::warning('auth.otp.verify_failed', array_merge(
                LogContext::currentRequest(),
                ['portal_number' => $request->portal_number]
            ));

            return back()
                ->withErrors(['otp' => 'Invalid or expired code. Please try again.'])
                ->withInput(['portal_number' => $request->portal_number]);
        }

        RateLimiter::clear($rateLimiterKey);

        Log::info('auth.otp.used', array_merge(
            LogContext::currentRequest(),
            LogContext::forUser($user, [
                'portal_number' => $user->portal_number,
            ])
        ));

        $this->applyMidnightSessionExpiry($user);

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->role === 'admin') {
            return redirect('/admin/dashboard');
        }

        if ($user->role === 'vendor') {
            return redirect('/dashboard');
        }

        if ($user->role === 'client') {
            return redirect('/client/dashboard');
        }

        return redirect('/dashboard');
    }

    protected function applyMidnightSessionExpiry(User $user): void
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $expiresAt = SessionExpiry::nextMidnight($now);

        config([
            'session.lifetime' => SessionExpiry::minutesUntilMidnight($now),
        ]);

        $user->forceFill([
            'session_expires_at' => $expiresAt,
        ])->save();
    }
}

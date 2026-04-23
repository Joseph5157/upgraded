<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TelegramService;
use App\Support\LogContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $user->forceFill([
            'otp' => $otp,
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

        $user = DB::transaction(function () use ($request) {
            $user = User::where('portal_number', $request->portal_number)
                ->where('otp', $request->otp)
                ->where('otp_expires_at', '>', now())
                ->whereNotNull('activated_at')
                ->lockForUpdate()
                ->first();

            if (! $user) {
                return null;
            }

            $user->forceFill([
                'otp' => null,
                'otp_expires_at' => null,
            ])->save();

            return $user;
        });

        if (! $user) {
            Log::warning('auth.otp.verify_failed', array_merge(
                LogContext::currentRequest(),
                ['portal_number' => $request->portal_number]
            ));

            return back()
                ->withErrors(['otp' => 'Invalid or expired code. Please try again.'])
                ->withInput(['portal_number' => $request->portal_number]);
        }

        Log::info('auth.otp.used', array_merge(
            LogContext::currentRequest(),
            LogContext::forUser($user, [
                'portal_number' => $user->portal_number,
            ])
        ));

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
}

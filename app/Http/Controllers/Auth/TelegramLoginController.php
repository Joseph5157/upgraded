<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LogContext;
use App\Support\SessionExpiry;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TelegramLoginController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function authenticate(Request $request, string $token): RedirectResponse
    {
        $user = DB::transaction(function () use ($token) {
            $user = User::where('login_token', $token)
                ->where('login_token_expires_at', '>', now())
                ->whereNotNull('activated_at')
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $user) {
                return null;
            }

            $user->forceFill([
                'login_token' => null,
                'login_token_expires_at' => null,
            ])->save();

            return $user;
        });

        if (! $user) {
            Log::warning('auth.telegram_login.failed', array_merge(
                LogContext::currentRequest(),
                ['token_length' => strlen($token)]
            ));

            return redirect('/login')->withErrors(['link' => 'Invalid or expired link.']);
        }

        Log::info('auth.telegram_login.used', array_merge(
            LogContext::currentRequest(),
            LogContext::forUser($user, [
                'token_length' => strlen($token),
            ])
        ));

        $this->applyMidnightSessionExpiry($user);

        Auth::login($user);

        $request->session()->regenerate();

        return match ($user->role) {
            'admin' => redirect('/admin/dashboard'),
            'client' => redirect('/client/dashboard'),
            default => redirect('/dashboard'),
        };
    }

    protected function applyMidnightSessionExpiry(User $user): void
    {
        $now = CarbonImmutable::now(config('app.timezone'));

        config([
            'session.lifetime' => SessionExpiry::minutesUntilMidnight($now),
        ]);

        $user->forceFill([
            'session_expires_at' => SessionExpiry::nextMidnight($now),
        ])->save();
    }
}

<?php

namespace App\Http\Middleware;

use App\Support\SessionExpiry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionIsFresh
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $now = now(config('app.timezone'));

        if (! $user->session_expires_at) {
            $user->forceFill([
                'session_expires_at' => SessionExpiry::nextMidnight($now->toImmutable()),
            ])->save();
        } elseif ($now->greaterThanOrEqualTo($user->session_expires_at)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Your session expired. Please sign in again.';
            $loginUrl = route('login', ['expired' => 1]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'redirect' => $loginUrl,
                ], 401);
            }

            return redirect()->route('login', ['expired' => 1])->with('error', $message);
        }

        return $next($request);
    }
}

<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionExpiryResponse
{
    public static function message(): string
    {
        return 'Your session expired. Please sign in again.';
    }

    public static function loginUrl(): string
    {
        return route('login', ['expired' => 1]);
    }

    public static function make(Request $request, int $status = 419, ?string $message = null): Response|JsonResponse
    {
        $message ??= static::message();
        $loginUrl = static::loginUrl();

        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()
                ->json([
                    'message' => $message,
                    'redirect' => $loginUrl,
                ], $status)
                ->header('X-Request-Id', (string) $request->attributes->get('request_id', ''));
        }

        return redirect()
            ->route('login', ['expired' => 1])
            ->with('error', $message)
            ->header('X-Request-Id', (string) $request->attributes->get('request_id', ''));
    }
}

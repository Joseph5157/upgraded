<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role'            => \App\Http\Middleware\RoleMiddleware::class,
            'account.status'  => \App\Http\Middleware\CheckAccountStatus::class,
            'nocache'         => \App\Http\Middleware\NoCacheHeaders::class,
            'session.timeout' => \App\Http\Middleware\EnforceSessionTimeout::class,
        ]);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\CheckAccountStatus::class,
            \App\Http\Middleware\EnforceSessionTimeout::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook/*',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to do that.'], 403);
            }

            if (Auth::check()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Your session is invalid or you do not have access. Please log in again.',
            ]);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            $messages = [
                401 => 'Authentication is required to continue.',
                403 => 'You do not have permission to do that.',
                404 => 'We could not find what you were looking for.',
                419 => 'Your session has expired. Please refresh and try again.',
                422 => 'Some submitted data is invalid.',
                429 => 'Too many requests. Please wait and try again.',
                500 => 'Something went wrong on our side. Please try again.',
                503 => 'Service is temporarily unavailable. Please try again soon.',
            ];

            $payload = [
                'message' => $messages[$status] ?? 'An unexpected error occurred.',
            ];

            if (config('app.debug')) {
                $payload['exception'] = class_basename($e);
                $payload['detail'] = $e->getMessage();
            }

            return response()->json($payload, $status);
        });
    })->create();

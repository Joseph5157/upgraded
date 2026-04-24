<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Session\TokenMismatchException;
use App\Support\SessionExpiryResponse;
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
            'role'           => \App\Http\Middleware\RoleMiddleware::class,
            'account.status' => \App\Http\Middleware\CheckAccountStatus::class,
            'session.fresh'   => \App\Http\Middleware\EnsureSessionIsFresh::class,
            'nocache'        => \App\Http\Middleware\NoCacheHeaders::class,
        ]);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\RequestCorrelation::class,
            \App\Http\Middleware\EnsureSessionIsFresh::class,
            \App\Http\Middleware\CheckAccountStatus::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook/*',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return SessionExpiryResponse::make($request);
        });

        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            return SessionExpiryResponse::make($request);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            $message = $e->getMessage() ?: 'You are not authorized to do that.';

            if ($request->expectsJson()) {
                return response()
                    ->json(['message' => $message], 403)
                    ->header('X-Request-Id', (string) $request->attributes->get('request_id', ''));
            }

            return back()
                ->with('error', $message)
                ->header('X-Request-Id', (string) $request->attributes->get('request_id', ''));
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            if ($status === 419) {
                return SessionExpiryResponse::make($request, 419);
            }

            if (! $request->expectsJson()) {
                return null;
            }

            $messages = [
                401 => 'Authentication is required to continue.',
                403 => 'You do not have permission to do that.',
                404 => 'We could not find what you were looking for.',
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

            return response()
                ->json($payload, $status)
                ->header('X-Request-Id', (string) $request->attributes->get('request_id', ''));
        });
    })->create();

<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        ]);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\CheckAccountStatus::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'u/*', // Exempt public link uploads (use token auth)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

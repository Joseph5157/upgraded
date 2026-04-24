<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Support\SessionExpiryResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // TODO: Later you can add Telegram notification or external logging here
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException || $e->getCode() === 419) {
            return SessionExpiryResponse::make($request);
        }

        // 500 Internal Server Error in production - show friendly page
        if (app()->environment('production') &&
            ! $request->expectsJson() &&
            ! $this->isHttpException($e) &&
            ! $e instanceof ValidationException &&
            ! $e instanceof AuthenticationException &&
            ! $e instanceof AuthorizationException) {

            // Log the real error so you can check it later
            Log::error('500 Error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->view('errors.500', [], 500);
        }

        return parent::render($request, $e);
    }
}

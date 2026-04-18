<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    protected function redirectPathForRole(string $role): string
    {
        return match ($role) {
            'admin' => route('admin.dashboard', absolute: false),
            'client' => route('client.dashboard', absolute: false),
            default => route('dashboard', absolute: false),
        };
    }

    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if (! $request->user()->requiresEmailVerification()) {
            return redirect()->intended($this->redirectPathForRole($request->user()->role));
        }

        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended($this->redirectPathForRole($request->user()->role))
                    : view('auth.verify-email');
    }
}

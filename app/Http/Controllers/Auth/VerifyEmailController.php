<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
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
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $redirectPath = $this->redirectPathForRole($user->role);

        if (! $user->requiresEmailVerification()) {
            return redirect()->intended($redirectPath);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($redirectPath . '?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended($redirectPath . '?verified=1');
    }
}

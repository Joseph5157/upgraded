<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
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
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->requiresEmailVerification() || $user->hasVerifiedEmail()) {
            return redirect()->intended($this->redirectPathForRole($user->role));
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}

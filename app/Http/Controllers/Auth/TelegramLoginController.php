<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TelegramLoginController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function authenticate(Request $request, string $token): RedirectResponse
    {
        $user = User::where('login_token', $token)
            ->where('login_token_expires_at', '>', now())
            ->first();

        if (! $user) {
            return redirect('/login')->withErrors(['link' => 'Invalid or expired link.']);
        }

        $user->update([
            'login_token'            => null,
            'login_token_expires_at' => null,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return match ($user->role) {
            'admin'  => redirect('/admin/dashboard'),
            'client' => redirect('/client/dashboard'),
            default  => redirect('/dashboard'),
        };
    }
}

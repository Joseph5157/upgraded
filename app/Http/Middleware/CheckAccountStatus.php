<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    private Redirector $redirector;

    public function __construct(Redirector $redirector)
    {
        $this->redirector = $redirector;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isFrozen()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $redirect = $this->redirector->route('login');
            if (method_exists($redirect, 'withErrors')) {
                return $redirect->withErrors(['telegram' => 'Your account has been frozen. Contact your admin.']);
            }
            return $redirect;
        }

        return $next($request);
    }
}

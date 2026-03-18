<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Allow if the user's role is in the permitted roles list
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // Redirect each role to their own dashboard
        return match ($user->role) {
            'admin'  => redirect()->route('admin.dashboard'),
            'client' => redirect()->route('client.dashboard'),
            'vendor' => redirect()->route('dashboard'),
            default  => abort(403, 'Unauthorized Access'),
        };
    }
}

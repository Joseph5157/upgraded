<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict access to a Filament panel by user role.
 *
 * Usage in panel authMiddleware (runs AFTER Filament's Authenticate):
 *   FilamentPanelRole::class . ':admin'
 *   FilamentPanelRole::class . ':vendor'
 *   FilamentPanelRole::class . ':client'
 *
 * On role mismatch, redirects the authenticated user to their own panel
 * instead of logging them out, preserving their session.
 */
class FilamentPanelRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // User is authenticated but does not have the required role.
        // Send them to their own panel rather than logging them out.
        return redirect($this->correctPanelPath($user->role));
    }

    private function correctPanelPath(string $role): string
    {
        return match ($role) {
            'admin'  => '/filament-admin',
            'client' => '/client-panel',
            'vendor' => '/vendor-panel',
            default  => route('login'),
        };
    }
}

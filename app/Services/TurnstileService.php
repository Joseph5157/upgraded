<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileService
{
    public function verify(string $token, string $ip): bool
    {
        $secret = config('services.turnstile.secret');

        // Skip verification when not configured (local dev)
        if (empty($secret)) {
            return true;
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $ip,
        ]);

        if ($response->failed()) {
            Log::warning('Turnstile: HTTP request to Cloudflare siteverify failed.', [
                'status' => $response->status(),
            ]);
            return false;
        }

        return (bool) $response->json('success', false);
    }
}

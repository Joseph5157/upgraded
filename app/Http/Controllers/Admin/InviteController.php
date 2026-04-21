<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PendingInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InviteController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $role = $request->input('role');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:admin,vendor,client'],
        ];

        if ($role === 'client') {
            $rules['slots'] = ['nullable', 'integer', 'min:1'];
        }

        if ($role === 'vendor') {
            $rules['payout_rate'] = ['nullable', 'numeric', 'min:0'];
        }

        $data = $request->validate($rules);

        $token = Str::random(32);

        PendingInvite::create([
            'name'         => $data['name'],
            'role'         => $data['role'],
            'slots'        => $data['slots'] ?? null,
            'payout_rate'  => $data['payout_rate'] ?? null,
            'invite_token' => $token,
            'expires_at'   => now()->addDays(7),
        ]);

        $botUsername = config('services.telegram.bot_username');
        $link = "https://t.me/{$botUsername}?start=invite_{$token}";

        return redirect()->back()
            ->with('invite_link', $link)
            ->with('invite_name', $data['name']);
    }
}

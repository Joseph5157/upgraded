<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form, routed by role.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return match($user->role) {
            'admin'  => view('admin.profile', ['user' => $user]),
            'client' => view('client.profile', ['user' => $user]),
            'vendor' => view('vendor.profile', [
                'user'           => $user,
                'filesProcessed' => $user->orders()->where('status', 'delivered')->count(),
                'memberSince'    => $user->created_at,
            ]),
            default  => view('profile.edit', ['user' => $user]),
        };
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AdminCreationLog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function storeAccount(Request $request)
    {
        // Authorization: ensure the caller has permission to create an account with this role
        $this->authorize('create', [User::class, $request->role]);

        // SECURITY CHECK: Super admin password confirmation required for admin creation
        if ($request->role === 'admin') {
            $request->validate([
                'super_password' => ['required', 'string'],
            ]);

            if (! Hash::check($request->super_password, auth()->user()->password)) {
                return back()->withErrors([
                    'super_password' => 'Incorrect SYSTEM_ROOT password.',
                ])->withInput();
            }
        }

        // SECURITY CHECK 3: Limit total admin accounts to 5
        if ($request->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount >= 5) {
                return back()->withErrors([
                    'role' => 'Maximum admin limit (5) reached. Contact system administrator.',
                ]);
            }
        }

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone'    => ['nullable', 'string', 'regex:/^\+?[0-9\s\-\(\)]{7,20}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', 'in:vendor,client,admin'],
            'slots'    => ['required_if:role,client', 'nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $userData = [
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $this->normalizePhone($request->phone),
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ];

        if ($request->role === 'admin') {
            $userData['admin_created_by']   = auth()->id();
            $userData['is_super_admin']     = false;
            $userData['email_verified_at']  = now();
        }

        $user = User::create($userData);

        if ($request->role === 'client') {
            $client = Client::create([
                'name'   => $request->name . ' Account',
                'slots'  => $request->slots,
                'status' => 'active',
            ]);
            $user->update(['client_id' => $client->id]);
        }

        AdminCreationLog::create([
            'created_by_user_id' => auth()->id(),
            'target_user_id'     => $user->id,
            'action'             => 'created',
            'ip_address'         => $request->ip(),
            'user_agent'         => $request->userAgent(),
            'metadata'           => [
                'role'       => $request->role,
                'created_at' => now()->toISOString(),
            ],
        ]);

        return back()->with('success', ucfirst($request->role) . ' account created successfully!');
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }

        $hasLeadingPlus = str_starts_with($phone, '+');
        $digitsOnly = preg_replace('/\D+/', '', $phone);

        if (! $digitsOnly) {
            return null;
        }

        return $hasLeadingPlus ? '+' . $digitsOnly : $digitsOnly;
    }

    public function promoteSuperAdmin(Request $request, User $user)
    {
        $this->authorize('create-admin');

        if (User::where('is_super_admin', true)->where('id', '!=', $user->id)->exists()) {
            return back()->withErrors(['error' => 'A SYSTEM_ROOT already exists.']);
        }

        $user->update(['is_super_admin' => true]);

        return back()->with('success', $user->name . ' promoted to SYSTEM_ROOT.');
    }
}

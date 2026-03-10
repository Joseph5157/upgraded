<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function storeAccount(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:vendor,client,admin'],
            'slots' => ['required_if:role,client', 'nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        // Create the standard user account
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // If the role is 'client', automatically provision their Client profile
        if ($request->role === 'client') {
            $client = Client::create([
                'name'   => $request->name . ' Account',
                'slots'  => $request->slots,
                'status' => 'active',
            ]);
            $user->update(['client_id' => $client->id]);
        }

        return back()->with('success', ucfirst($request->role) . ' account created successfully!');
    }
}
<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountManagerController extends Controller
{
    /**
     * List all vendor and client accounts.
     */
    public function index(): View
    {
        $vendors = User::where('role', 'vendor')
            ->withCount([
                'orders as total_files'    => fn($q) => $q->where('status', 'delivered'),
                'orders as active_orders'  => fn($q) => $q->whereIn('status', ['pending', 'processing']),
            ])
            ->get();

        $clients = User::where('role', 'client')
            ->with('client')
            ->withCount('orders')
            ->get();

        $frozenCount = User::whereIn('role', ['vendor', 'client'])
            ->where('status', 'frozen')
            ->count();

        return view('admin.accounts.index', compact('vendors', 'clients', 'frozenCount'));
    }

    /**
     * Freeze a user account.
     */
    public function freeze(Request $request, User $user): RedirectResponse
    {
        $this->authorize('freeze', $user);

        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $user->update([
            'status'        => 'frozen',
            'frozen_at'     => now(),
            'frozen_reason' => $request->reason,
        ]);

        $user->client?->update(['status' => 'suspended']);

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        return back()->with('success', 'Account frozen successfully.');
    }

    /**
     * Unfreeze a user account.
     */
    public function unfreeze(User $user): RedirectResponse
    {
        $this->authorize('unfreeze', $user);

        $user->update([
            'status'        => 'active',
            'frozen_at'     => null,
            'frozen_reason' => null,
        ]);

        $user->client?->update(['status' => 'active']);

        return back()->with('success', 'Account reactivated successfully.');
    }

    /**
     * Soft delete a user account.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if (! Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        if ($user->role === 'client' && $user->client) {
            $client = $user->client;

            $cancelledCount = Order::where('client_id', $client->id)
                ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::Processing->value])
                ->count();

            Order::where('client_id', $client->id)
                ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::Processing->value])
                ->update([
                    'status'     => OrderStatus::Cancelled->value,
                    'claimed_by' => null,
                    'claimed_at' => null,
                ]);

            // Restore consumed slot counter for orders that never completed
            if ($cancelledCount > 0) {
                $client->decrement('slots_consumed', $cancelledCount);
            }
        }

        if ($user->role === 'vendor') {
            Order::where('claimed_by', $user->id)
                ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::Processing->value])
                ->update([
                    'claimed_by' => null,
                    'claimed_at' => null,
                    'status'     => OrderStatus::Pending->value,
                ]);
        }

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        $user->delete();

        return back()->with('success', 'Account deleted successfully.');
    }

    /**
     * Restore a soft-deleted user account.
     */
    public function restore(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $this->authorize('restore', $user);

        $user->restore();

        return back()->with('success', 'Account restored successfully.');
    }

    /**
     * Permanently delete a user account.
     */
    public function forceDelete(Request $request, int $id): RedirectResponse
    {
        if (! Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $user = User::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $user);

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        $user->forceDelete();

        return back()->with('success', 'Account permanently deleted.');
    }
}

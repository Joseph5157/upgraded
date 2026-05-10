<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\LogContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AccountManagerController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {
    }

    /**
     * List all vendor and client accounts.
     */
    public function index(): View
    {
        $vendors = User::where('role', 'vendor')
            ->withCount([
                'orders as total_files'    => fn($q) => $q->where('status', 'delivered'),
                'orders as active_orders'  => fn($q) => $q->whereIn('status', ['claimed', 'processing']),
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

        $this->invalidateUserSessions($user);

        $user->update(['session_expires_at' => null]);

        $context = LogContext::forTargetUser($user, LogContext::forUser($request->user(), LogContext::currentRequest()));
        Log::info('account.frozen', array_merge($context, [
            'reason' => $request->reason,
        ]));
        $this->auditLogger->record('account.frozen', $user, [
            'reason' => $request->reason,
            'client_status' => $user->client?->status,
        ], (int) $request->user()?->id);

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

        DB::transaction(function () use ($request, $user) {

            if ($user->role === 'client' && $user->client) {
                $client = $user->client;

                // Cancel all active orders — credits are forfeited, no slot restoration.
                $activeStatuses = [OrderStatus::Pending, OrderStatus::Claimed, OrderStatus::Processing];
                $orderUpdate    = ['status' => OrderStatus::Cancelled, 'claimed_by' => null];
                if (Order::hasColumn('claimed_at')) {
                    $orderUpdate['claimed_at'] = null;
                }
                Order::where('client_id', $client->id)
                    ->whereIn('status', $activeStatuses)
                    ->update($orderUpdate);

                // Revoke all upload links so guests can no longer submit via them.
                $client->links()->update([
                    'is_active'          => false,
                    'revoked_at'         => now(),
                    'revoked_by_user_id' => $request->user()?->id,
                ]);

                // Auto-reject any pending refund requests — no point leaving them open.
                RefundRequest::where('client_id', $client->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'rejected', 'admin_note' => 'Account deleted.', 'resolved_at' => now()]);
            }

            if ($user->role === 'vendor') {
                // Release claimed/processing orders back to the pending pool.
                $orderUpdate = ['claimed_by' => null, 'status' => OrderStatus::Pending];
                if (Order::hasColumn('claimed_at')) {
                    $orderUpdate['claimed_at'] = null;
                }
                Order::where('claimed_by', $user->id)
                    ->whereIn('status', [OrderStatus::Claimed, OrderStatus::Processing])
                    ->update($orderUpdate);
            }

            $this->invalidateUserSessions($user);

            $context = LogContext::forTargetUser($user, LogContext::forUser($request->user(), LogContext::currentRequest()));
            Log::info('account.deleted', $context);
            $this->auditLogger->record('account.deleted', $user, [], (int) $request->user()?->id);

            $user->delete();
        });

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
        $user = User::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $user);

        $this->invalidateUserSessions($user);

        $context = LogContext::forTargetUser($user, LogContext::forUser($request->user(), LogContext::currentRequest()));
        Log::info('account.deleted', array_merge($context, [
            'force_deleted' => true,
        ]));
        $this->auditLogger->record('account.deleted', $user, [
            'force_deleted' => true,
        ], (int) $request->user()?->id);

        $user->forceDelete();

        return back()->with('success', 'Account permanently deleted.');
    }

    protected function invalidateUserSessions(User $user): void
    {
        if (config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
    }
}

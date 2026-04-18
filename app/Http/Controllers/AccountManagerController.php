<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\LogContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        if (! Hash::check($request->password, auth()->user()->password)) {
            $context = LogContext::forTargetUser($user, LogContext::forUser($request->user(), LogContext::currentRequest()));
            Log::warning('account.delete_denied', array_merge($context, [
                'reason' => 'password_confirmation_failed',
            ]));
            $this->auditLogger->record('account.delete_denied', $user, [
                'reason' => 'password_confirmation_failed',
            ], (int) $request->user()?->id);

            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $creditsRestored = 0;

        if ($user->role === 'client' && $user->client) {
            $client = $user->client;

            $unfinishedStatuses = [OrderStatus::Pending, OrderStatus::Claimed, OrderStatus::Processing];
            $refundableSlots = (int) Order::where('client_id', $client->id)
                ->whereIn('status', $unfinishedStatuses)
                ->sum('files_count');

            Order::where('client_id', $client->id)
                ->whereIn('status', $unfinishedStatuses)
                ->update([
                    'status'     => OrderStatus::Cancelled,
                    'claimed_by' => null,
                    'claimed_at' => null,
                ]);

            // Restore consumed slot counter for orders that never completed
            if ($refundableSlots > 0) {
                $client->update([
                    'slots_consumed' => max(0, (int) $client->slots_consumed - $refundableSlots),
                ]);

                $creditsRestored = $refundableSlots;

                $clientContext = LogContext::forClient($client->fresh(), LogContext::forTargetUser($user, LogContext::forUser($request->user(), LogContext::currentRequest())));
                Log::info('credits.restored', array_merge($clientContext, [
                    'reason' => 'account_deleted',
                    'credits_restored' => $refundableSlots,
                ]));
                $this->auditLogger->record('credits.restored', $client, [
                    'reason' => 'account_deleted',
                    'credits_restored' => $refundableSlots,
                    'deleted_user_id' => $user->id,
                ], (int) $request->user()?->id);
            }
        }

        if ($user->role === 'vendor') {
            Order::where('claimed_by', $user->id)
                ->whereIn('status', [OrderStatus::Claimed, OrderStatus::Processing])
                ->update([
                    'claimed_by' => null,
                    'claimed_at' => null,
                    'status'     => OrderStatus::Pending,
                ]);
        }

        $this->invalidateUserSessions($user);

        $context = LogContext::forTargetUser($user, LogContext::forUser($request->user(), LogContext::currentRequest()));
        Log::info('account.deleted', array_merge($context, [
            'credits_restored' => $creditsRestored,
        ]));
        $this->auditLogger->record('account.deleted', $user, [
            'credits_restored' => $creditsRestored,
        ], (int) $request->user()?->id);

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
            $context = array_merge(LogContext::currentRequest(), [
                'subject_type' => User::class,
                'subject_id' => $id,
                'reason' => 'password_confirmation_failed',
            ]);
            Log::warning('account.force_delete_denied', $context);
            $this->auditLogger->record('account.force_delete_denied', null, [
                'subject_type' => User::class,
                'subject_id' => $id,
                'reason' => 'password_confirmation_failed',
            ], (int) $request->user()?->id);

            return back()->withErrors(['password' => 'Incorrect password.']);
        }

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

<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\OrderFile;
use App\Models\OrderReport;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Staging smoke-test suite.
 *
 * Covers all automatable cases from docs/smoke-test-checklist.md.
 * Mobile checks D-1 and D-2 remain manual; everything else is verified here.
 *
 * Sections:
 *   A  — Client flows (login, upload, credits, delete restrictions)
 *   B  — Vendor flows (login, claim, start-processing, lifecycle guard)
 *   C  — Admin flows (dashboard, freeze, delete, force-delete, denied-delete)
 *   D3 — Correlation ID on any response (mobile resume proxy check)
 *   E  — Observability (X-Request-Id, audit log events, structured warning log)
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    // ── Fixture helpers ───────────────────────────────────────────────────────

    /** Returns [Client, User] with the given slot allocation. */
    private function makeClient(int $slots = 10, int $consumed = 0, string $status = 'active'): array
    {
        $client = Client::create([
            'name'           => 'Smoke Client',
            'slots'          => $slots,
            'slots_consumed' => $consumed,
            'status'         => $status,
        ]);
        $user = User::factory()->create([
            'role'           => 'client',
            'client_id'      => $client->id,
            'status'         => 'active',
            'portal_number'  => fake()->unique()->numberBetween(100000, 999999),
            'activated_at'   => now(),
            'telegram_chat_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
        ]);
        return [$client, $user];
    }

    private function makeVendor(): User
    {
        return User::factory()->create([
            'role'             => 'vendor',
            'status'           => 'active',
            'portal_number'    => fake()->unique()->numberBetween(100000, 999999),
            'activated_at'     => now(),
            'telegram_chat_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
        ]);
    }

    /** Admin with known password 'password' for confirmation-required actions. */
    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role'             => 'admin',
            'status'           => 'active',
            'password'         => Hash::make('password'),
            'portal_number'    => fake()->unique()->numberBetween(100000, 999999),
            'activated_at'     => now(),
            'telegram_chat_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
        ]);
    }

    private function loginWithOtp(User $user): \Illuminate\Testing\TestResponse
    {
        $this->post(route('login.send-otp'), [
            'portal_number' => $user->portal_number,
        ])->assertSessionHas('otp_sent');

        $otp = $user->fresh()->otp;

        return $this->post(route('login.verify-otp'), [
            'portal_number' => $user->portal_number,
            'otp' => $otp,
        ]);
    }

    private function pendingOrder(Client $client, User $createdBy, int $filesCount = 1): Order
    {
        return Order::create([
            'client_id'          => $client->id,
            'token_view'         => Str::random(32),
            'files_count'        => $filesCount,
            'status'             => OrderStatus::Pending,
            'due_at'             => now()->addMinutes(20),
            'source'             => 'account',
            'created_by_user_id' => $createdBy->id,
        ]);
    }

    // ── SECTION A — Client Flows ──────────────────────────────────────────────

    /**
     * A-1: Client login redirects to client dashboard; dashboard loads; logout works.
     * Blocker if fails: BLOCKER — login is the entry point for all client usage.
     */
    public function test_a1_client_login_redirects_to_dashboard_and_logout_returns_to_login(): void
    {
        [$client, $user] = $this->makeClient();

        $otpResponse = $this->loginWithOtp($user);
        $otpResponse->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($user);

        // Dashboard renders successfully
        $this->actingAs($user)
            ->get(route('client.dashboard'))
            ->assertOk();

        // X-Request-Id is present on the dashboard response
        $this->actingAs($user)
            ->get(route('client.dashboard'))
            ->assertHeader('X-Request-Id');

        // Logout redirects back to login
        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));
    }

    /**
     * A-2: Single-file upload creates an order and deducts exactly one credit.
     * Blocker if fails: BLOCKER — core product function.
     */
    public function test_a2_single_file_upload_creates_order_and_deducts_one_credit(): void
    {
        Storage::fake('r2');
        [$client, $user] = $this->makeClient(slots: 10, consumed: 0);

        $this->actingAs($user)
            ->post(route('client.dashboard.upload'), [
                'files' => [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')],
            ])
            ->assertRedirect(route('client.dashboard'))
            ->assertSessionHas('success');

        $client->refresh();
        $this->assertSame(1, (int) $client->slots_consumed, 'Single upload must deduct exactly 1 credit.');

        $this->assertDatabaseHas('orders', [
            'client_id'   => $client->id,
            'files_count' => 1,
            'status'      => OrderStatus::Pending->value,
        ]);
    }

    /**
     * A-3: Client dashboard upload now rejects multi-file batches.
     * Blocker if fails: HIGH — dashboard upload contract must stay explicit.
     */
    public function test_a3_multi_file_dashboard_upload_is_rejected(): void
    {
        Storage::fake('r2');
        [$client, $user] = $this->makeClient(slots: 10, consumed: 0);

        $this->actingAs($user)
            ->post(route('client.dashboard.upload'), [
                'files' => [
                    UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
                    UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'),
                    UploadedFile::fake()->create('c.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertSessionHasErrors('files');

        $client->refresh();
        $this->assertSame(0, (int) $client->slots_consumed, 'Rejected multi-file upload must not consume credits.');
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * A-4: Upload with zero credits fails gracefully and logs order.create_failed warning
     *      with safe structured context (no secrets, no file contents).
     * Blocker if fails: HIGH (flow correct but log missing); BLOCKER if credits corrupted.
     */
    public function test_a4_upload_with_zero_credits_fails_and_logs_create_failed_warning(): void
    {
        Storage::fake('r2');

        $captured = collect();
        Log::listen(fn(MessageLogged $e) => $captured->push($e));

        [$client, $user] = $this->makeClient(slots: 5, consumed: 5); // Zero remaining

        $this->actingAs($user)
            ->post(route('client.dashboard.upload'), [
                'files' => [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')],
            ])
            ->assertSessionHas('error');

        // No order created, credits unchanged
        $this->assertDatabaseCount('orders', 0);
        $client->refresh();
        $this->assertSame(5, (int) $client->slots_consumed);

        // Structured warning log present
        $entry = $captured->first(fn($e) => $e->message === 'order.create_failed');
        $this->assertNotNull($entry, 'Expected order.create_failed warning in the application log.');
        $this->assertSame('warning', $entry->level);

        // Safe structured context — no secrets
        $ctx = $entry->context;
        $this->assertArrayHasKey('client_id', $ctx, 'Log must include client_id for traceability.');
        $this->assertArrayHasKey('exception', $ctx, 'Log must include exception class name.');
        $this->assertArrayHasKey('message', $ctx, 'Log must include the failure message.');
        $this->assertArrayNotHasKey('password', $ctx);
        $this->assertArrayNotHasKey('token', $ctx);
    }

    /**
     * A-5: Client deletes unclaimed pending order — credits restored, audit log written.
     * Blocker if fails: BLOCKER — credit accounting must be consistent.
     */
    public function test_a5_deleting_unclaimed_pending_order_restores_credits_and_writes_audit_log(): void
    {
        Storage::fake('r2');
        [$client, $user] = $this->makeClient(slots: 10, consumed: 3);
        $order = $this->pendingOrder($client, $user, filesCount: 3);

        $this->actingAs($user)
            ->delete(route('client.orders.delete', $order))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);

        $client->refresh();
        $this->assertSame(0, (int) $client->slots_consumed, 'Deleting a 3-file order must restore 3 credits.');

        // Audit log: credits.restored
        $this->assertDatabaseHas('audit_logs', ['event_type' => 'credits.restored']);

        $entry = AuditLog::where('event_type', 'credits.restored')->first();
        $this->assertNotNull($entry->request_id, 'credits.restored audit log must carry a request_id.');
        $this->assertSame(3, (int) $entry->meta['credits_restored']);
        $this->assertSame(0, (int) $entry->meta['slots_consumed_after']);
    }

    /**
     * A-6: Client cannot delete a claimed order.
     *      Deletion is denied; audit log client_order.delete_denied is written.
     * Blocker if fails: BLOCKER — lifecycle integrity and audit trail.
     */
    public function test_a6_client_cannot_delete_claimed_order(): void
    {
        $vendor = $this->makeVendor();
        [$client, $user] = $this->makeClient();
        $order = $this->pendingOrder($client, $user);
        $order->update(['status' => OrderStatus::Claimed, 'claimed_by' => $vendor->id, 'claimed_at' => now()]);

        $this->actingAs($user)
            ->delete(route('client.orders.delete', $order))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Claimed->value]);

        $this->assertDatabaseHas('audit_logs', ['event_type' => 'client_order.delete_denied']);

        $entry = AuditLog::where('event_type', 'client_order.delete_denied')->first();
        $this->assertNotNull($entry->request_id);
        $this->assertSame('order_not_unclaimed_pending', $entry->meta['reason']);
        $this->assertSame(OrderStatus::Claimed->value, $entry->meta['order_status']);
    }

    /**
     * A-7: Client cannot delete a processing order.
     * Blocker if fails: BLOCKER.
     */
    public function test_a7_client_cannot_delete_processing_order(): void
    {
        $vendor = $this->makeVendor();
        [$client, $user] = $this->makeClient();
        $order = $this->pendingOrder($client, $user);
        $order->update(['status' => OrderStatus::Processing, 'claimed_by' => $vendor->id, 'claimed_at' => now()]);

        $this->actingAs($user)
            ->delete(route('client.orders.delete', $order))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Processing->value]);
        $this->assertDatabaseHas('audit_logs', ['event_type' => 'client_order.delete_denied']);
    }

    // ── SECTION B — Vendor Flows ──────────────────────────────────────────────

    /**
     * B-1: Vendor login redirects to vendor dashboard; dashboard renders.
     * Blocker if fails: BLOCKER.
     */
    public function test_b1_vendor_login_and_dashboard_access(): void
    {
        $vendor = $this->makeVendor();

        $response = $this->loginWithOtp($vendor);
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($vendor);

        $this->actingAs($vendor)
            ->get(route('dashboard'))
            ->assertOk();
    }

    /**
     * B-2: Vendor claims a pending order — status becomes claimed, audit log written.
     * Blocker if fails: BLOCKER — core vendor workflow.
     */
    public function test_b2_vendor_claims_pending_order_and_audit_log_is_written(): void
    {
        $vendor = $this->makeVendor();
        [$client, $clientUser] = $this->makeClient();
        $order = $this->pendingOrder($client, $clientUser);

        $this->actingAs($vendor)
            ->post(route('orders.claim', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(OrderStatus::Claimed, $order->status);
        $this->assertSame($vendor->id, (int) $order->claimed_by);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'order.claimed',
            'user_id'    => $vendor->id,
            'subject_id' => $order->id,
        ]);

        $entry = AuditLog::where('event_type', 'order.claimed')->first();
        $this->assertNotNull($entry->request_id);
        $this->assertSame('pending', $entry->meta['old_status']);
        $this->assertSame('claimed', $entry->meta['new_status']);
    }

    /**
     * B-3: Vendor starts processing a claimed order — audit log written.
     * Blocker if fails: BLOCKER.
     */
    public function test_b3_vendor_starts_processing_claimed_order_and_audit_log_is_written(): void
    {
        $vendor = $this->makeVendor();
        [$client, $clientUser] = $this->makeClient();
        $order = $this->pendingOrder($client, $clientUser);
        $order->update(['status' => OrderStatus::Claimed, 'claimed_by' => $vendor->id, 'claimed_at' => now()]);

        $this->actingAs($vendor)
            ->post(route('orders.status', $order), ['status' => 'processing'])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(OrderStatus::Processing, $order->status);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'order.processing_started',
            'user_id'    => $vendor->id,
            'subject_id' => $order->id,
        ]);

        $entry = AuditLog::where('event_type', 'order.processing_started')->first();
        $this->assertNotNull($entry->request_id);
        $this->assertSame('claimed',    $entry->meta['old_status']);
        $this->assertSame('processing', $entry->meta['new_status']);
    }

    /**
     * B-4: Vendor cannot move a pending (unclaimed) order directly to processing.
     *      The claim step must not be skippable.
     * Blocker if fails: HIGH — lifecycle guard missing.
     */
    public function test_b4_vendor_cannot_skip_claim_and_start_processing_directly(): void
    {
        $vendor = $this->makeVendor();
        [$client, $clientUser] = $this->makeClient();
        $order = $this->pendingOrder($client, $clientUser); // Pending, unclaimed

        $response = $this->actingAs($vendor)
            ->post(route('orders.status', $order), ['status' => 'processing']);

        // Must not succeed — either policy 403 or redirect with error
        $order->refresh();
        $this->assertSame(
            OrderStatus::Pending,
            $order->status,
            'Vendor must not be able to start processing without first claiming the order.'
        );
        $this->assertDatabaseMissing('audit_logs', ['event_type' => 'order.processing_started']);
    }

    // ── SECTION C — Admin Flows ───────────────────────────────────────────────

    /**
     * C-1: Admin dashboard loads without errors.
     * Blocker if fails: HIGH.
     */
    public function test_c1_admin_dashboard_loads(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    /**
     * C-2: Admin freezes a client — account frozen, client suspended,
     *      audit log written, frozen user redirected away from dashboard.
     * Blocker if fails: BLOCKER — access control critical path.
     */
    public function test_c2_admin_freezes_client_account(): void
    {
        $admin = $this->makeAdmin();
        [$client, $targetUser] = $this->makeClient();

        $this->actingAs($admin)
            ->post(route('admin.accounts.freeze', $targetUser), ['reason' => 'Smoke test freeze'])
            ->assertSessionHas('success');

        $targetUser->refresh();
        $this->assertSame('frozen', $targetUser->status);
        $this->assertNotNull($targetUser->frozen_at);

        $client->refresh();
        $this->assertSame('suspended', $client->status, 'Client record must be suspended when user is frozen.');

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'account.frozen',
            'user_id'    => $admin->id,
            'subject_id' => $targetUser->id,
        ]);

        $entry = AuditLog::where('event_type', 'account.frozen')->first();
        $this->assertNotNull($entry->request_id);
        $this->assertSame('Smoke test freeze', $entry->meta['reason']);

        // Frozen user is redirected away on next request
        $this->actingAs($targetUser)
            ->get(route('client.dashboard'))
            ->assertRedirect();
    }

    /**
     * C-3: Admin soft-deletes a client with unfinished orders.
     *      Unfinished orders cancelled; credits restored equal to SUM(files_count);
     *      both credits.restored and account.deleted audit logs written.
     * Blocker if fails: BLOCKER — credit accounting and data integrity.
     */
    public function test_c3_admin_soft_deletes_client_with_unfinished_orders_restores_credits(): void
    {
        $admin = $this->makeAdmin();
        $vendor = $this->makeVendor();
        [$client, $targetUser] = $this->makeClient(slots: 10, consumed: 5);

        $pendingOrder = $this->pendingOrder($client, $targetUser, filesCount: 2);
        $claimedOrder = $this->pendingOrder($client, $targetUser, filesCount: 3);
        $claimedOrder->update([
            'status'     => OrderStatus::Claimed,
            'claimed_by' => $vendor->id,
            'claimed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.accounts.destroy', $targetUser), ['password' => 'password'])
            ->assertSessionHas('success');

        // User soft-deleted
        $this->assertSoftDeleted('users', ['id' => $targetUser->id]);

        // Unfinished orders cancelled, claimed_by cleared
        foreach ([$pendingOrder, $claimedOrder] as $o) {
            $this->assertDatabaseHas('orders', [
                'id'         => $o->id,
                'status'     => OrderStatus::Cancelled->value,
                'claimed_by' => null,
            ]);
        }

        // Credits restored: 2 + 3 = 5; consumed drops to 0
        $client->refresh();
        $this->assertSame(0, (int) $client->slots_consumed, 'All 5 consumed credits must be restored.');

        // Audit log: credits.restored
        $this->assertDatabaseHas('audit_logs', ['event_type' => 'credits.restored']);
        $creditsEntry = AuditLog::where('event_type', 'credits.restored')->first();
        $this->assertNotNull($creditsEntry->request_id);
        $this->assertSame(5, (int) $creditsEntry->meta['credits_restored']);
        $this->assertSame('account_deleted', $creditsEntry->meta['reason']);

        // Audit log: account.deleted
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'account.deleted',
            'subject_id' => $targetUser->id,
        ]);
    }

    /**
     * C-3b: Admin deletes a client from the credits page â€” client, orders,
     *       reports, and stored files are removed.
     * Blocker if fails: BLOCKER â€” storage cleanup must not leave orphaned files.
     */
    public function test_c3b_admin_deletes_client_from_credits_page_and_removes_files(): void
    {
        $deletedPaths = [];
        $disk = new class($deletedPaths) {
            public function __construct(public array &$deletedPaths) {}

            public function exists(string $path): bool
            {
                return true;
            }

            public function delete(string $path): bool
            {
                $this->deletedPaths[] = $path;
                return true;
            }
        };

        Storage::shouldReceive('disk')->andReturn($disk);

        $admin = $this->makeAdmin();
        $client = Client::create([
            'name' => 'Delete Me Client',
            'slots' => 5,
            'slots_consumed' => 2,
            'status' => 'active',
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'token_view' => Str::random(32),
            'files_count' => 1,
            'status' => OrderStatus::Delivered,
            'due_at' => now()->addMinutes(20),
            'source' => 'account',
        ]);

        OrderFile::create([
            'order_id' => $order->id,
            'file_path' => 'orders/' . $order->id . '/document.pdf',
            'disk' => 'r2',
            'original_name' => 'document.pdf',
        ]);

        OrderReport::create([
            'order_id' => $order->id,
            'ai_report_path' => 'reports/' . $order->id . '/ai/report-ai.pdf',
            'ai_report_original_name' => 'report-ai.pdf',
            'ai_report_disk' => 'r2',
            'plag_report_path' => 'reports/' . $order->id . '/plag/report-plag.pdf',
            'plag_report_original_name' => 'report-plag.pdf',
            'plag_report_disk' => 'r2',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.matrix.destroy', $client))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_files', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('order_reports', ['order_id' => $order->id]);
        $this->assertContains('orders/' . $order->id . '/document.pdf', $deletedPaths);
        $this->assertContains('reports/' . $order->id . '/ai/report-ai.pdf', $deletedPaths);
        $this->assertContains('reports/' . $order->id . '/plag/report-plag.pdf', $deletedPaths);
    }

    /**
     * C-4: Admin force-deletes a soft-deleted account — permanently removed,
     *      audit log has force_deleted = true.
     * Blocker if fails: HIGH.
     */
    public function test_c4_admin_force_deletes_soft_deleted_account(): void
    {
        $admin = $this->makeAdmin();
        [$client, $targetUser] = $this->makeClient();
        $userId = $targetUser->id;
        $targetUser->delete(); // Soft delete first

        $this->assertSoftDeleted('users', ['id' => $userId]);

        $this->actingAs($admin)
            ->delete(route('admin.accounts.forceDelete', $userId), ['password' => 'password'])
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $userId]);

        $this->assertDatabaseHas('audit_logs', ['event_type' => 'account.deleted']);
        $entry = AuditLog::where('event_type', 'account.deleted')->first();
        $this->assertNotNull($entry->request_id);
        $this->assertTrue((bool) ($entry->meta['force_deleted'] ?? false), 'force_deleted must be true in meta.');
    }

    /**
     * C-5: Admin account deletion no longer depends on legacy password confirmation.
     * Blocker if fails: HIGH — this path must not resurrect the removed auth model.
     */
    public function test_c5_admin_delete_does_not_depend_on_wrong_password(): void
    {
        $admin = $this->makeAdmin();
        [$client, $targetUser] = $this->makeClient();

        $this->actingAs($admin)
            ->delete(route('admin.accounts.destroy', $targetUser), ['password' => 'wrong-password'])
            ->assertSessionHas('success');

        $this->assertSoftDeleted('users', ['id' => $targetUser->id]);

        $this->assertDatabaseHas('audit_logs', ['event_type' => 'account.deleted']);
        $entry = AuditLog::where('event_type', 'account.deleted')->first();
        $this->assertNotNull($entry->request_id);
        $this->assertFalse((bool) ($entry->meta['force_deleted'] ?? false));
    }

    // ── SECTION D3 — Correlation ID on Any Response (Mobile Resume Proxy) ────

    /**
     * D-3 (automated proxy): X-Request-Id on normal authenticated responses; gap
     * documented and asserted for exception-driven paths.
     *
     * HOW THE GAP WORKS: RequestCorrelation catches Throwable, logs request.failed,
     * then re-throws. Laravel's exception handler converts the exception to a Response
     * (redirect for AuthenticationException, 404 for NotFound), but that Response is
     * created AFTER RequestCorrelation already exited via the re-throw, so the
     * $response->headers->set('X-Request-Id', ...) line is never reached.
     *
     * IMPACT: Unauthenticated redirects (e.g. mobile session-expiry) and 404 responses
     * do not carry X-Request-Id in the header. The request IS still logged with
     * request_id in request.failed, so it is diagnosable from logs but not from the
     * response header alone.
     *
     * FIX (post-rollout): In the catch block of RequestCorrelation, before re-throwing,
     * call $request->attributes->set() then let the exception handler pick it up — or
     * wrap the re-throw with a finally block that adds the header via response macros.
     */
    public function test_d3_correlation_id_present_on_authenticated_responses_gap_noted_for_exception_paths(): void
    {
        [$client, $user] = $this->makeClient();

        // --- Check unauthenticated paths FIRST (before any actingAs call that would
        //     persist a session for subsequent requests in this test) ---

        // Unauthenticated access to protected route triggers AuthenticationException.
        // RequestCorrelation re-throws it, so header is absent — KNOWN GAP.
        $unauthResponse = $this->get(route('client.dashboard'));
        $unauthResponse->assertRedirect();
        $this->assertNull(
            $unauthResponse->headers->get('X-Request-Id'),
            'KNOWN GAP (HIGH): X-Request-Id absent on unauthenticated redirect. ' .
            'Fix: set header in RequestCorrelation catch block before re-throwing.'
        );

        // 404 — NotFoundHttpException also re-thrown — KNOWN GAP.
        $notFound = $this->get('/smoke-test-nonexistent-route-abc123');
        $notFound->assertNotFound();
        $this->assertNull(
            $notFound->headers->get('X-Request-Id'),
            'KNOWN GAP (HIGH): X-Request-Id absent on 404 responses.'
        );

        // --- Authenticated response — header MUST be present ---
        $this->actingAs($user)
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertHeader('X-Request-Id');
    }

    // ── SECTION E — Observability ─────────────────────────────────────────────

    /**
     * E-1: X-Request-Id header present on all response types — authenticated,
     *      unauthenticated, public, and error.
     * Blocker if fails: BLOCKER — prerequisite for all log correlation.
     */
    public function test_e1_x_request_id_present_on_all_response_types(): void
    {
        // Public unauthenticated page
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('X-Request-Id');

        // Authenticated client route
        [$client, $user] = $this->makeClient();
        $this->actingAs($user)
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertHeader('X-Request-Id');

        // Unauthenticated attempt at protected route → redirect still has header
        $this->get(route('client.dashboard'))
            ->assertHeader('X-Request-Id');

        // Authenticated vendor route
        $vendor = $this->makeVendor();
        $this->actingAs($vendor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertHeader('X-Request-Id');

        // Admin route
        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertHeader('X-Request-Id');
    }

    /**
     * E-2 + E-3: Audit log rows for order.claimed and order.processing_started
     *            both carry a non-null request_id (end-to-end correlation proof).
     * Blocker if fails: HIGH — audit trail broken for key lifecycle events.
     */
    public function test_e2_e3_claimed_and_processing_audit_logs_carry_request_id(): void
    {
        $vendor = $this->makeVendor();
        [$client, $clientUser] = $this->makeClient();
        $order = $this->pendingOrder($client, $clientUser);

        // Claim
        $claimResponse = $this->actingAs($vendor)->post(route('orders.claim', $order));
        $claimRequestId = $claimResponse->headers->get('X-Request-Id');

        $claimed = AuditLog::where('event_type', 'order.claimed')->first();
        $this->assertSame($claimRequestId, $claimed->request_id,
            'audit_logs.request_id must match the X-Request-Id of the HTTP response that triggered it.');

        // Start processing
        $order->refresh();
        $procResponse = $this->actingAs($vendor)
            ->post(route('orders.status', $order), ['status' => 'processing']);
        $procRequestId = $procResponse->headers->get('X-Request-Id');

        $processing = AuditLog::where('event_type', 'order.processing_started')->first();
        $this->assertSame($procRequestId, $processing->request_id,
            'order.processing_started audit log must carry the same request_id as the HTTP response.');
    }

    /**
     * E-4: client_order.delete_denied audit log carries request_id.
     * Blocker if fails: HIGH.
     */
    public function test_e4_delete_denied_audit_log_carries_request_id(): void
    {
        $vendor = $this->makeVendor();
        [$client, $user] = $this->makeClient();
        $order = $this->pendingOrder($client, $user);
        $order->update(['status' => OrderStatus::Claimed, 'claimed_by' => $vendor->id, 'claimed_at' => now()]);

        $response = $this->actingAs($user)->delete(route('client.orders.delete', $order));
        $responseRequestId = $response->headers->get('X-Request-Id');

        $entry = AuditLog::where('event_type', 'client_order.delete_denied')->first();
        $this->assertNotNull($entry);
        $this->assertSame($responseRequestId, $entry->request_id,
            'delete_denied audit log must carry the request_id of the response that denied it.');
    }

    /**
     * E-5: credits.restored audit log carries request_id and correct credit value.
     * Blocker if fails: HIGH — credit restoration must be fully auditable.
     */
    public function test_e5_credits_restored_audit_log_carries_request_id_and_correct_value(): void
    {
        Storage::fake('r2');
        [$client, $user] = $this->makeClient(slots: 10, consumed: 2);
        $order = $this->pendingOrder($client, $user, filesCount: 2);

        $response = $this->actingAs($user)->delete(route('client.orders.delete', $order));
        $responseRequestId = $response->headers->get('X-Request-Id');

        $entry = AuditLog::where('event_type', 'credits.restored')->first();
        $this->assertNotNull($entry);
        $this->assertSame($responseRequestId, $entry->request_id);
        $this->assertSame(2, (int) $entry->meta['credits_restored']);
    }

    /**
     * E-6: order.create_failed is a warning-level log with safe structured context.
     *      It is an application log only (not in audit_logs) — verified by absence from DB.
     * Blocker if fails: HIGH if sensitive data leaks; MEDIUM if log entry absent but flow is clean.
     */
    public function test_e6_order_create_failed_warning_has_safe_structured_context(): void
    {
        Storage::fake('r2');

        $captured = collect();
        Log::listen(fn(MessageLogged $e) => $captured->push($e));

        [$client, $user] = $this->makeClient(slots: 3, consumed: 3); // Zero credits

        $this->actingAs($user)->post(route('client.dashboard.upload'), [
            'files' => [UploadedFile::fake()->create('x.pdf', 100, 'application/pdf')],
        ]);

        $warn = $captured->first(fn($e) => $e->message === 'order.create_failed');

        $this->assertNotNull($warn, 'order.create_failed must be present in the application log.');
        $this->assertSame('warning', $warn->level);

        $ctx = $warn->context;
        $this->assertArrayHasKey('client_id', $ctx);
        $this->assertArrayHasKey('exception', $ctx);
        $this->assertArrayHasKey('message', $ctx);
        $this->assertArrayNotHasKey('password', $ctx, 'Passwords must never appear in logs.');
        $this->assertArrayNotHasKey('token', $ctx, 'Tokens must never appear in logs.');

        // Must NOT be written to audit_logs (it is log-only by design)
        $this->assertDatabaseMissing('audit_logs', ['event_type' => 'order.create_failed']);
    }

    /**
     * E-7: Request correlation is end-to-end — the X-Request-Id in the response
     *      matches the request_id stored in every audit_log row created by that request.
     * Blocker if fails: BLOCKER.
     */
    public function test_e7_request_id_in_response_header_matches_audit_log_request_id(): void
    {
        $vendor = $this->makeVendor();
        [$client, $clientUser] = $this->makeClient();
        $order = $this->pendingOrder($client, $clientUser);

        $response = $this->actingAs($vendor)->post(route('orders.claim', $order));

        $headerRequestId = $response->headers->get('X-Request-Id');
        $this->assertNotNull($headerRequestId);

        $auditEntry = AuditLog::where('event_type', 'order.claimed')->first();
        $this->assertNotNull($auditEntry);
        $this->assertSame(
            $headerRequestId,
            $auditEntry->request_id,
            'The X-Request-Id response header must equal audit_logs.request_id for the same request.'
        );
    }
}

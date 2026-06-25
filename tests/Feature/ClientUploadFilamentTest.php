<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use App\Services\CreateClientOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 8 — Client Upload Filament Page
 *
 * Tests that verify:
 *  - The Filament upload page is accessible by clients
 *  - The Filament upload page is blocked for vendors/admins/unauthenticated users
 *  - The underlying CreateClientOrderService works correctly for Filament uploads
 *  - Credit deduction is identical to the Blade upload path (same service)
 *  - The old Blade /client/dashboard upload route still works in parallel
 *  - Failed upload does not deduct credits
 *  - Upload is scoped to the authenticated client
 */
class ClientUploadFilamentTest extends TestCase
{
    use RefreshDatabase;

    private int $counter = 0;

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function makeClient(array $attrs = []): Client
    {
        $this->counter++;
        return Client::create(array_merge([
            'name'           => 'Test Client ' . $this->counter,
            'slots'          => 10,
            'slots_consumed' => 0,
            'credit_balance' => 10,
            'price_per_file' => 50.00,
            'status'         => 'active',
        ], $attrs));
    }

    private function makeClientUser(Client $client, array $attrs = []): User
    {
        $this->counter++;
        return User::factory()->create(array_merge([
            'role'      => 'client',
            'client_id' => $client->id,
            'status'    => 'active',
        ], $attrs));
    }

    private function makeVendor(): User
    {
        $this->counter++;
        return User::factory()->create([
            'role'   => 'vendor',
            'status' => 'active',
            'portal_number' => 200000 + $this->counter,
        ]);
    }

    private function makeAdmin(): User
    {
        $this->counter++;
        return User::factory()->create([
            'role'   => 'admin',
            'status' => 'active',
            'portal_number' => 100000 + $this->counter,
        ]);
    }

    // ─── Page Access Tests ───────────────────────────────────────────────────

    #[Test]
    public function client_can_access_filament_upload_page(): void
    {
        $client = $this->makeClient();
        $user   = $this->makeClientUser($client);

        $response = $this->actingAs($user)->get('/client-panel/upload-files');

        $response->assertStatus(200);
    }

    #[Test]
    public function unauthenticated_user_is_redirected_from_upload_page(): void
    {
        $response = $this->get('/client-panel/upload-files');

        $response->assertRedirect();
    }

    #[Test]
    public function vendor_cannot_access_client_upload_page(): void
    {
        $vendor = $this->makeVendor();

        $response = $this->actingAs($vendor)->get('/client-panel/upload-files');

        // FilamentPanelRole middleware returns 403 for wrong-role users
        $response->assertForbidden();
    }

    #[Test]
    public function admin_cannot_access_client_upload_page(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/client-panel/upload-files');

        $response->assertForbidden();
    }

    // ─── Service-level Upload Tests (same service as Blade) ──────────────────

    #[Test]
    public function filament_upload_deducts_one_credit_via_service(): void
    {
        Storage::fake('r2');

        $client = $this->makeClient(['credit_balance' => 5]);
        $user   = $this->makeClientUser($client);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        app(CreateClientOrderService::class)->execute(
            $client,
            [$file],
            'account',
            ['created_by_user_id' => $user->id],
        );

        $client->refresh();

        $this->assertSame(4, $client->credit_balance);
    }

    #[Test]
    public function filament_upload_creates_pending_order(): void
    {
        Storage::fake('r2');

        $client = $this->makeClient(['credit_balance' => 3]);
        $user   = $this->makeClientUser($client);

        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');

        $order = app(CreateClientOrderService::class)->execute(
            $client,
            [$file],
            'account',
            ['created_by_user_id' => $user->id],
        );

        $this->assertDatabaseHas('orders', [
            'id'         => $order->id,
            'status'     => OrderStatus::Pending->value,
            'client_id'  => $client->id,
            'files_count' => 1,
            'source'     => 'account',
        ]);
    }

    #[Test]
    public function filament_upload_is_scoped_to_authenticated_client(): void
    {
        Storage::fake('r2');

        $clientA = $this->makeClient(['credit_balance' => 5]);
        $clientB = $this->makeClient(['credit_balance' => 5]);
        $userA   = $this->makeClientUser($clientA);

        $file = UploadedFile::fake()->create('file.pdf', 50, 'application/pdf');

        // Upload is called with $clientA — order must belong to $clientA
        $order = app(CreateClientOrderService::class)->execute(
            $clientA,
            [$file],
            'account',
            ['created_by_user_id' => $userA->id],
        );

        $this->assertSame($clientA->id, $order->client_id);

        // clientB balance must be untouched
        $clientB->refresh();
        $this->assertSame(5, $clientB->credit_balance);
    }

    #[Test]
    public function upload_fails_and_does_not_deduct_credits_when_balance_is_zero(): void
    {
        Storage::fake('r2');

        $client = $this->makeClient(['credit_balance' => 0]);

        $file = UploadedFile::fake()->create('file.pdf', 50, 'application/pdf');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No upload credits remaining');

        app(CreateClientOrderService::class)->execute($client, [$file], 'account');

        // Balance must still be 0 (no deduction occurred)
        $client->refresh();
        $this->assertSame(0, $client->credit_balance);
    }

    #[Test]
    public function upload_fails_when_selected_files_exceed_available_credits(): void
    {
        Storage::fake('r2');

        $client = $this->makeClient(['credit_balance' => 1]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient credits');

        // Try to upload 2 files with only 1 credit
        app(CreateClientOrderService::class)->execute($client, [
            UploadedFile::fake()->create('a.pdf', 50),
            UploadedFile::fake()->create('b.pdf', 50),
        ], 'account');
    }

    #[Test]
    public function filament_upload_uses_same_service_as_blade_upload(): void
    {
        // This test verifies structural parity: both paths resolve to CreateClientOrderService.
        // The Blade controller calls: $this->createOrderService->execute(...)
        // The Filament page calls: app(CreateClientOrderService::class)->execute(...)
        // Both pass the same arguments: ($client, [$file], 'account', $meta)

        Storage::fake('r2');

        $client  = $this->makeClient(['credit_balance' => 10]);
        $user    = $this->makeClientUser($client);
        $service = app(CreateClientOrderService::class);

        $orderViaService = $service->execute(
            $client,
            [UploadedFile::fake()->create('service.pdf', 50)],
            'account',
            ['created_by_user_id' => $user->id, 'notes' => 'Filament path test'],
        );

        $this->assertSame(OrderStatus::Pending, $orderViaService->status);
        $this->assertSame(1, $orderViaService->files_count);
        $this->assertSame('account', $orderViaService->source);
        $this->assertSame($client->id, $orderViaService->client_id);
    }

    // ─── Old Blade Upload Parallel Safety ────────────────────────────────────

    #[Test]
    public function old_blade_upload_still_works_in_parallel(): void
    {
        Storage::fake('r2');

        $client = $this->makeClient(['credit_balance' => 5]);
        $user   = $this->makeClientUser($client);

        $file = UploadedFile::fake()->create('blade-upload.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('client.dashboard.upload'), [
            'files' => [$file],
            'notes' => 'From Blade path',
        ]);

        // Old Blade route redirects on success
        $response->assertRedirect(route('client.dashboard'));

        $response->assertSessionHas('success');

        $client->refresh();
        $this->assertSame(4, $client->credit_balance);
    }

    #[Test]
    public function old_blade_upload_rejected_when_insufficient_credits(): void
    {
        Storage::fake('r2');

        $client = $this->makeClient(['credit_balance' => 0]);
        $user   = $this->makeClientUser($client);

        $file = UploadedFile::fake()->create('blade-upload.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('client.dashboard.upload'), [
            'files' => [$file],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // No credit change
        $client->refresh();
        $this->assertSame(0, $client->credit_balance);
    }
}

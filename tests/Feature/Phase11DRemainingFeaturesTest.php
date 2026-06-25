<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Announcement;
use App\Models\Client;
use App\Models\ClientLink;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 11D — Remaining Filament Features
 *
 * Tests for:
 *  A. Admin Announcements resource (/filament-admin/announcements)
 *  B. Admin Pricing Management page (/filament-admin/pricing-management)
 *  C. Admin Client Links resource (/filament-admin/client-links)
 *  D. Client Order Deletion action (MyOrdersResource)
 *  E. Blade route regression checks
 */
class Phase11DRemainingFeaturesTest extends TestCase
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

    private function makeVendor(array $attrs = []): User
    {
        $this->counter++;

        return User::factory()->create(array_merge([
            'role'   => 'vendor',
            'status' => 'active',
        ], $attrs));
    }

    private function makeAdmin(): User
    {
        $this->counter++;

        return User::factory()->create([
            'role'   => 'admin',
            'status' => 'active',
        ]);
    }

    private function makeOrder(Client $client, array $attrs = []): Order
    {
        $this->counter++;

        return Order::create(array_merge([
            'client_id'   => $client->id,
            'token_view'  => Str::random(20),
            'status'      => OrderStatus::Pending,
            'files_count' => 1,
            'source'      => 'portal',
            'due_at'      => now()->addHours(2),
        ], $attrs));
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  A. Admin Announcements Resource
    // ═══════════════════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_access_announcements_list(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/filament-admin/announcements');

        $response->assertOk();
    }

    #[Test]
    public function admin_can_access_create_announcement_page(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/filament-admin/announcements/create');

        $response->assertOk();
    }

    #[Test]
    public function vendor_cannot_access_announcements(): void
    {
        $vendor = $this->makeVendor();

        $response = $this->actingAs($vendor)->get('/filament-admin/announcements');

        $response->assertStatus(403);
    }

    #[Test]
    public function client_cannot_access_announcements(): void
    {
        $client = $this->makeClient();
        $clientUser = $this->makeClientUser($client);

        $response = $this->actingAs($clientUser)->get('/filament-admin/announcements');

        $response->assertStatus(403);
    }

    #[Test]
    public function announcement_model_active_for_user_scope_works(): void
    {
        $admin = $this->makeAdmin();
        $vendor = $this->makeVendor();

        Announcement::create([
            'title'      => 'For All',
            'message'    => 'Test all',
            'target'     => 'all',
            'type'       => 'info',
            'active'     => true,
            'created_by' => $admin->id,
        ]);

        Announcement::create([
            'title'      => 'Vendors Only',
            'message'    => 'Test vendor',
            'target'     => 'vendor',
            'type'       => 'warning',
            'active'     => true,
            'created_by' => $admin->id,
        ]);

        Announcement::create([
            'title'      => 'Inactive',
            'message'    => 'Test inactive',
            'target'     => 'all',
            'type'       => 'info',
            'active'     => false,
            'created_by' => $admin->id,
        ]);

        $vendorAnnouncements = Announcement::activeForUser($vendor)->get();
        $this->assertCount(2, $vendorAnnouncements);

        $client = $this->makeClient();
        $clientUser = $this->makeClientUser($client);
        $clientAnnouncements = Announcement::activeForUser($clientUser)->get();
        $this->assertCount(1, $clientAnnouncements);
        $this->assertEquals('For All', $clientAnnouncements->first()->title);
    }

    #[Test]
    public function expired_announcements_are_excluded_from_scope(): void
    {
        $admin = $this->makeAdmin();

        Announcement::create([
            'title'      => 'Expired',
            'message'    => 'Should not show',
            'target'     => 'all',
            'type'       => 'info',
            'active'     => true,
            'expires_at' => now()->subHour(),
            'created_by' => $admin->id,
        ]);

        $vendor = $this->makeVendor();
        $this->assertCount(0, Announcement::activeForUser($vendor)->get());
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  B. Admin Pricing Management Page
    // ═══════════════════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_access_pricing_page(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/filament-admin/pricing-management');

        $response->assertOk();
        $response->assertSeeText('Pricing Management');
    }

    #[Test]
    public function vendor_cannot_access_pricing_page(): void
    {
        $vendor = $this->makeVendor();

        $response = $this->actingAs($vendor)->get('/filament-admin/pricing-management');

        $response->assertStatus(403);
    }

    #[Test]
    public function pricing_page_shows_clients_by_default(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient(['name' => 'Acme Corp']);

        $response = $this->actingAs($admin)->get('/filament-admin/pricing-management');

        $response->assertOk();
        $response->assertSeeText('Client Pricing');
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  C. Admin Client Links Resource
    // ═══════════════════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_access_client_links_list(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/filament-admin/client-links');

        $response->assertOk();
    }

    #[Test]
    public function vendor_cannot_access_client_links(): void
    {
        $vendor = $this->makeVendor();

        $response = $this->actingAs($vendor)->get('/filament-admin/client-links');

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_view_a_client_link(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();

        $link = ClientLink::create([
            'client_id'          => $client->id,
            'token'              => 'test-token-abc123',
            'is_active'          => true,
            'created_by_user_id' => $admin->id,
            'expires_at'         => now()->addDay(),
        ]);

        $response = $this->actingAs($admin)->get("/filament-admin/client-links/{$link->id}");

        $response->assertOk();
    }

    #[Test]
    public function client_link_is_revoked_correctly(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();

        $link = ClientLink::create([
            'client_id'          => $client->id,
            'token'              => 'test-token-revoke',
            'is_active'          => true,
            'created_by_user_id' => $admin->id,
            'expires_at'         => now()->addDay(),
        ]);

        $this->assertTrue($link->isUsable());

        $link->update([
            'is_active'          => false,
            'revoked_at'         => now(),
            'revoked_by_user_id' => $admin->id,
        ]);

        $link->refresh();
        $this->assertTrue($link->isRevoked());
        $this->assertFalse($link->isUsable());
    }

    #[Test]
    public function client_link_usable_scope_excludes_expired(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();

        ClientLink::create([
            'client_id'          => $client->id,
            'token'              => 'expired-link',
            'is_active'          => true,
            'created_by_user_id' => $admin->id,
            'expires_at'         => now()->subHour(),
        ]);

        ClientLink::create([
            'client_id'          => $client->id,
            'token'              => 'active-link',
            'is_active'          => true,
            'created_by_user_id' => $admin->id,
            'expires_at'         => now()->addDay(),
        ]);

        $this->assertEquals(1, $client->links()->usable()->count());
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  D. Client Order Deletion
    // ═══════════════════════════════════════════════════════════════════════

    #[Test]
    public function client_can_access_my_orders(): void
    {
        $client = $this->makeClient();
        $user = $this->makeClientUser($client);

        $response = $this->actingAs($user)->get('/client-panel/my-orders');

        $response->assertOk();
    }

    #[Test]
    public function delete_service_rejects_claimed_order(): void
    {
        $client = $this->makeClient();
        $vendor = $this->makeVendor();

        $order = $this->makeOrder($client, [
            'claimed_by' => $vendor->id,
            'status'     => OrderStatus::Claimed,
        ]);

        $service = app(\App\Services\DeleteClientOrderService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only unclaimed pending orders can be deleted.');

        $service->execute($order, $client);
    }

    #[Test]
    public function delete_service_rejects_delivered_order(): void
    {
        $client = $this->makeClient();

        $order = $this->makeOrder($client, [
            'status' => OrderStatus::Delivered,
        ]);

        $service = app(\App\Services\DeleteClientOrderService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only unclaimed pending orders can be deleted.');

        $service->execute($order, $client);
    }

    #[Test]
    public function delete_service_rejects_processing_order(): void
    {
        $client = $this->makeClient();

        $order = $this->makeOrder($client, [
            'status' => OrderStatus::Processing,
        ]);

        $service = app(\App\Services\DeleteClientOrderService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only unclaimed pending orders can be deleted.');

        $service->execute($order, $client);
    }

    #[Test]
    public function delete_service_rejects_failed_order(): void
    {
        $client = $this->makeClient();

        $order = $this->makeOrder($client, [
            'status' => OrderStatus::Failed,
        ]);

        $service = app(\App\Services\DeleteClientOrderService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only unclaimed pending orders can be deleted.');

        $service->execute($order, $client);
    }

    #[Test]
    public function delete_service_accepts_unclaimed_pending_order(): void
    {
        $client = $this->makeClient();

        $order = $this->makeOrder($client, [
            'status'     => OrderStatus::Pending,
            'claimed_by' => null,
        ]);

        $service = app(\App\Services\DeleteClientOrderService::class);
        $creditsRefunded = $service->execute($order, $client);

        // No debit tx existed, so no credits refunded
        $this->assertFalse($creditsRefunded);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  E. Blade Route Regression Checks
    // ═══════════════════════════════════════════════════════════════════════

    #[Test]
    public function blade_announcements_route_still_works_for_admin(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/admin/announcements');

        $response->assertOk();
    }

    #[Test]
    public function blade_pricing_route_still_works_for_admin(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/admin/pricing');

        $response->assertOk();
    }

    #[Test]
    public function blade_client_links_route_still_works_for_admin(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/admin/client-links');

        $response->assertOk();
    }

    #[Test]
    public function blade_client_dashboard_still_works(): void
    {
        $client = $this->makeClient();
        $user = $this->makeClientUser($client);

        $response = $this->actingAs($user)->get('/dashboard');

        // Phase 10: /dashboard redirects clients to /client-panel
        $response->assertRedirect();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 10 — Stage 2 GET Redirects
 *
 * Verifies that every Blade GET dashboard route now issues a
 * 302 redirect to the correct Filament panel URL.
 *
 * POST/DELETE routes are NOT changed and are NOT tested here.
 * The Filament panels themselves are tested in ClientUploadFilamentTest,
 * OrderRequeueTest, and the per-resource Filament tests.
 */
class Phase10RedirectTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeClient(): array
    {
        $client = Client::create([
            'name'           => 'Test Client',
            'slots'          => 10,
            'slots_consumed' => 0,
            'credit_balance' => 5,
            'price_per_file' => 50.00,
            'status'         => 'active',
        ]);

        $user = User::factory()->create([
            'role'      => 'client',
            'client_id' => $client->id,
            'status'    => 'active',
        ]);

        return [$client, $user];
    }

    private function makeVendor(): User
    {
        return User::factory()->create([
            'role'          => 'vendor',
            'status'        => 'active',
            'portal_number' => 200001,
        ]);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role'          => 'admin',
            'status'        => 'active',
            'portal_number' => 100001,
        ]);
    }

    // ─── Client Dashboard Redirect ────────────────────────────────────────────

    #[Test]
    public function get_client_dashboard_redirects_to_client_panel(): void
    {
        [, $user] = $this->makeClient();

        $response = $this->actingAs($user)->get('/client/dashboard');

        $response->assertRedirect('/client-panel');
    }

    #[Test]
    public function named_route_client_dashboard_redirects_to_client_panel(): void
    {
        [, $user] = $this->makeClient();

        $response = $this->actingAs($user)->get(route('client.dashboard'));

        $response->assertRedirect('/client-panel');
    }

    #[Test]
    public function unauthenticated_get_client_dashboard_redirects_to_login(): void
    {
        $response = $this->get('/client/dashboard');

        // Unauthenticated → auth middleware redirects to /login, not to Filament
        $response->assertRedirect();
        $this->assertStringContainsString('login', $response->headers->get('Location'));
    }

    // ─── Vendor Dashboard Redirect ────────────────────────────────────────────

    #[Test]
    public function get_vendor_dashboard_redirects_to_vendor_panel(): void
    {
        $vendor = $this->makeVendor();

        $response = $this->actingAs($vendor)->get('/dashboard');

        $response->assertRedirect('/vendor-panel');
    }

    #[Test]
    public function named_route_dashboard_redirects_vendor_to_vendor_panel(): void
    {
        $vendor = $this->makeVendor();

        $response = $this->actingAs($vendor)->get(route('dashboard'));

        $response->assertRedirect('/vendor-panel');
    }

    // ─── Admin on /dashboard → Admin Panel ────────────────────────────────────

    #[Test]
    public function get_dashboard_as_admin_redirects_to_filament_admin(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertRedirect('/filament-admin');
    }

    // ─── Admin Dashboard Redirect ─────────────────────────────────────────────

    #[Test]
    public function get_admin_dashboard_redirects_to_filament_admin(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertRedirect('/filament-admin');
    }

    #[Test]
    public function named_route_admin_dashboard_redirects_to_filament_admin(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertRedirect('/filament-admin');
    }

    // ─── Vendor Earnings Redirect ─────────────────────────────────────────────

    #[Test]
    public function get_earnings_redirects_to_vendor_earning_history(): void
    {
        $vendor = $this->makeVendor();

        $response = $this->actingAs($vendor)->get('/earnings');

        $response->assertRedirect('/vendor-panel/earning-history');
    }

    #[Test]
    public function named_route_vendor_earnings_redirects_correctly(): void
    {
        $vendor = $this->makeVendor();

        $response = $this->actingAs($vendor)->get(route('vendor.earnings'));

        $response->assertRedirect('/vendor-panel/earning-history');
    }

    // ─── Finance Dashboard Redirect ───────────────────────────────────────────

    #[Test]
    public function get_admin_finance_dashboard_redirects_to_filament_finance(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/admin/finance/dashboard');

        $response->assertRedirect('/filament-finance');
    }

    #[Test]
    public function named_route_finance_dashboard_redirects_to_filament_finance(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.finance.dashboard'));

        $response->assertRedirect('/filament-finance');
    }

    // ─── Unchanged Routes — Verify POST routes still reach controllers ─────────

    #[Test]
    public function client_dashboard_pulse_still_accessible_after_get_redirect(): void
    {
        [, $user] = $this->makeClient();

        // The pulse endpoint is NOT redirected — it should still respond
        $response = $this->actingAs($user)->get('/client/dashboard/pulse');

        // Pulse endpoint returns JSON (not a redirect)
        $response->assertOk();
        $response->assertJsonStructure(['signature', 'checked_at']);
    }

    #[Test]
    public function vendor_dashboard_pulse_still_accessible_after_get_redirect(): void
    {
        $vendor = $this->makeVendor();

        $response = $this->actingAs($vendor)->getJson('/dashboard/pulse');

        // Pulse endpoint returns JSON (not a redirect)
        $response->assertOk();
        $response->assertJsonStructure(['signature', 'checked_at']);
    }
}

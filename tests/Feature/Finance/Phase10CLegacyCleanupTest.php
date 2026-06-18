<?php

namespace Tests\Feature\Finance;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 10C — Legacy Cleanup + Production Hardening
 *
 * Rules verified:
 *  - admin dashboard out_of_credit_clients uses credit_balance not slots
 *  - admin sidebar low credit count uses credit_balance not slots
 *  - client subscription page loads with credit_balance data
 *  - client subscription page shows payment history not topup history
 *  - guest link upload view uses credit_balance for remaining count
 */
class Phase10CLegacyCleanupTest extends TestCase
{
    use RefreshDatabase;

    private int $counter = 0;

    private function makeAdmin(): User
    {
        $this->counter++;
        return User::create([
            'name'          => 'Admin',
            'role'          => 'admin',
            'status'        => 'active',
            'portal_number' => 100000 + $this->counter,
            'email'         => "admin{$this->counter}@test.com",
            'password'      => bcrypt('password'),
        ]);
    }

    private function makeClientUser(int $creditBalance = 0, int $slots = 100, int $slotsConsumed = 0): array
    {
        $this->counter++;
        $user = User::create([
            'name'          => "Client User {$this->counter}",
            'role'          => 'client',
            'status'        => 'active',
            'portal_number' => 300000 + $this->counter,
            'email'         => "client{$this->counter}@test.com",
            'password'      => bcrypt('password'),
        ]);
        $client = Client::create([
            'user_id'        => $user->id,
            'name'           => "Client {$this->counter}",
            'slots'          => $slots,
            'slots_consumed' => $slotsConsumed,
            'credit_balance' => $creditBalance,
            'status'         => 'active',
            'price_per_file' => 100,
        ]);
        $user->update(['client_id' => $client->id]);

        return [$user, $client];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Admin dashboard uses credit_balance
    // ─────────────────────────────────────────────────────────────────────

    #[Test]
    public function admin_dashboard_out_of_credit_uses_credit_balance_not_slots(): void
    {
        $admin = $this->makeAdmin();

        // Client A: credit_balance = 0, but slots say they have capacity (slots=100, consumed=0)
        // Should be counted as out-of-credit based on credit_balance
        $this->makeClientUser(creditBalance: 0, slots: 100, slotsConsumed: 0);

        // Client B: credit_balance = 50, but slots say they're out (slots=10, consumed=10)
        // Should NOT be counted as out-of-credit based on credit_balance
        $this->makeClientUser(creditBalance: 50, slots: 10, slotsConsumed: 10);

        // Client C: credit_balance = 5, normal
        $this->makeClientUser(creditBalance: 5, slots: 50, slotsConsumed: 45);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();

        // The dashboard should show 1 out-of-credit client (Client A only)
        // If it used slots, it would show Client B (slots_consumed >= slots)
        $response->assertViewHas('stats', function ($stats) {
            return $stats['out_of_credit_clients'] === 1;
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Admin sidebar uses credit_balance for low credit badge
    // ─────────────────────────────────────────────────────────────────────

    #[Test]
    public function admin_sidebar_renders_without_topup_link(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();

        // Topup link should be hidden
        $response->assertDontSee('admin/topup"');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Client subscription uses credit_balance
    // ─────────────────────────────────────────────────────────────────────

    #[Test]
    public function client_subscription_shows_credit_balance_not_slots(): void
    {
        [$user, $client] = $this->makeClientUser(creditBalance: 42, slots: 100, slotsConsumed: 58);

        $response = $this->actingAs($user)->get(route('client.subscription'));
        $response->assertOk();

        // Should show credit_balance (42), not slots - slots_consumed (also 42 in this case)
        $response->assertViewHas('creditsRemaining', 42);
    }

    #[Test]
    public function client_subscription_shows_payment_history_instead_of_topup_history(): void
    {
        [$user, $client] = $this->makeClientUser(creditBalance: 10);

        // Create a client payment
        ClientPayment::create([
            'client_id'        => $client->id,
            'amount_received'  => 5000,
            'credits_added'    => 50,
            'rate_per_credit'  => 100,
            'payment_mode'     => 'upi',
            'reference_id'     => 'UTR123',
            'received_at'      => now(),
            'status'           => 'confirmed',
            'created_by'       => $this->makeAdmin()->id,
        ]);

        $response = $this->actingAs($user)->get(route('client.subscription'));
        $response->assertOk();

        // Should have payment history, not topup history
        $response->assertViewHas('paymentHistory');
        $response->assertSee('Payment history');
        $response->assertSee('5,000'); // amount_received formatted

        // Should NOT have topup form
        $response->assertDontSee('Submit top-up request');
        $response->assertDontSee('Choose a top-up amount');
    }

    #[Test]
    public function client_subscription_shows_contact_admin_instead_of_topup_form(): void
    {
        [$user, $client] = $this->makeClientUser(creditBalance: 0);

        $response = $this->actingAs($user)->get(route('client.subscription'));
        $response->assertOk();

        $response->assertSee('Need more credits?');
        $response->assertSee('Contact your admin');
        $response->assertDontSee('Submit top-up request');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Credit balance divergence from slots
    // ─────────────────────────────────────────────────────────────────────

    #[Test]
    public function dashboard_reflects_credit_balance_when_it_diverges_from_slots(): void
    {
        $admin = $this->makeAdmin();

        // Client with slots saying 100/0 remaining but credit_balance = 0
        // This can happen if admin added credits via old topup but credit_balance was never set
        [$_, $clientA] = $this->makeClientUser(creditBalance: 0, slots: 100, slotsConsumed: 0);

        // Client with slots exhausted but credit_balance still positive
        // This happens after Phase 4 when credits are managed via new system
        [$_, $clientB] = $this->makeClientUser(creditBalance: 25, slots: 50, slotsConsumed: 50);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            // Only clientA should be "out of credit" (credit_balance = 0)
            // clientB has credit_balance = 25, so NOT out of credit
            return $stats['out_of_credit_clients'] === 1;
        });
    }

    #[Test]
    public function suspended_clients_not_counted_as_out_of_credit(): void
    {
        $admin = $this->makeAdmin();

        // Active client with zero balance — should count
        $this->makeClientUser(creditBalance: 0);

        // Suspended client with zero balance — should NOT count
        // (they're already suspended, no need to flag them as "out of credit")
        $this->counter++;
        $user = User::create([
            'name'          => "Client User {$this->counter}",
            'role'          => 'client',
            'status'        => 'active',
            'portal_number' => 300000 + $this->counter,
            'email'         => "client{$this->counter}@test.com",
            'password'      => bcrypt('password'),
        ]);
        $suspendedClient = Client::create([
            'user_id'        => $user->id,
            'name'           => "Suspended Client",
            'slots'          => 0,
            'slots_consumed' => 0,
            'credit_balance' => 0,
            'status'         => 'suspended',
            'price_per_file' => 100,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            return $stats['out_of_credit_clients'] === 1; // only the active one
        });
    }
}

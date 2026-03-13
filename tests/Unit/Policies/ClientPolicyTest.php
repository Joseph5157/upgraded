<?php

namespace Tests\Unit\Policies;

use App\Models\Client;
use App\Models\User;
use App\Policies\ClientPolicy;
use PHPUnit\Framework\TestCase;

class ClientPolicyTest extends TestCase
{
    private ClientPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ClientPolicy();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function admin(): User
    {
        return new User(['id' => 1, 'role' => 'admin', 'is_super_admin' => false]);
    }

    private function clientUserWithClientId(int $clientId): User
    {
        return new User(['id' => 10, 'role' => 'client', 'client_id' => $clientId]);
    }

    private function vendor(): User
    {
        return new User(['id' => 20, 'role' => 'vendor']);
    }

    private function clientRecord(int $id = 5): Client
    {
        $client = new Client();
        $client->id = $id; // id is not fillable; must be assigned directly
        return $client;
    }

    // ─── viewAny ──────────────────────────────────────────────────────────────

    public function test_admin_can_view_client_list(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin()));
    }

    public function test_client_user_cannot_view_client_list(): void
    {
        $this->assertFalse($this->policy->viewAny($this->clientUserWithClientId(5)));
    }

    public function test_vendor_cannot_view_client_list(): void
    {
        $this->assertFalse($this->policy->viewAny($this->vendor()));
    }

    // ─── view ─────────────────────────────────────────────────────────────────

    public function test_admin_can_view_any_client(): void
    {
        $this->assertTrue($this->policy->view($this->admin(), $this->clientRecord(5)));
    }

    public function test_client_user_can_view_own_client_record(): void
    {
        $user = $this->clientUserWithClientId(5);
        $client = $this->clientRecord(5);
        $this->assertTrue($this->policy->view($user, $client));
    }

    public function test_client_user_cannot_view_another_clients_record(): void
    {
        $user = $this->clientUserWithClientId(5);
        $client = $this->clientRecord(99);
        $this->assertFalse($this->policy->view($user, $client));
    }

    public function test_vendor_cannot_view_client_record(): void
    {
        $this->assertFalse($this->policy->view($this->vendor(), $this->clientRecord(5)));
    }

    // ─── updateSlots ──────────────────────────────────────────────────────────

    public function test_admin_can_update_slots(): void
    {
        $this->assertTrue($this->policy->updateSlots($this->admin(), $this->clientRecord()));
    }

    public function test_client_user_cannot_update_slots(): void
    {
        $this->assertFalse($this->policy->updateSlots($this->clientUserWithClientId(5), $this->clientRecord(5)));
    }

    public function test_vendor_cannot_update_slots(): void
    {
        $this->assertFalse($this->policy->updateSlots($this->vendor(), $this->clientRecord()));
    }

    // ─── refill ───────────────────────────────────────────────────────────────

    public function test_admin_can_refill_client(): void
    {
        $this->assertTrue($this->policy->refill($this->admin(), $this->clientRecord()));
    }

    public function test_client_user_cannot_refill(): void
    {
        $this->assertFalse($this->policy->refill($this->clientUserWithClientId(5), $this->clientRecord(5)));
    }

    public function test_vendor_cannot_refill(): void
    {
        $this->assertFalse($this->policy->refill($this->vendor(), $this->clientRecord()));
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_admin_can_update_client(): void
    {
        $this->assertTrue($this->policy->update($this->admin(), $this->clientRecord()));
    }

    public function test_client_user_cannot_update_client(): void
    {
        $this->assertFalse($this->policy->update($this->clientUserWithClientId(5), $this->clientRecord(5)));
    }

    public function test_vendor_cannot_update_client(): void
    {
        $this->assertFalse($this->policy->update($this->vendor(), $this->clientRecord()));
    }
}

<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\VendorPayout;
use App\Policies\VendorPayoutPolicy;
use PHPUnit\Framework\TestCase;

class VendorPayoutPolicyTest extends TestCase
{
    private VendorPayoutPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new VendorPayoutPolicy();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function admin(): User
    {
        return new User(['id' => 1, 'role' => 'admin', 'is_super_admin' => false]);
    }

    private function vendorWithId(int $id): User
    {
        $user = new User(['role' => 'vendor']);
        $user->id = $id; // id is not fillable; must be assigned directly
        return $user;
    }

    private function client(): User
    {
        return new User(['id' => 99, 'role' => 'client']);
    }

    private function payoutForVendor(int $userId): VendorPayout
    {
        return new VendorPayout(['user_id' => $userId]);
    }

    // ─── viewAny ──────────────────────────────────────────────────────────────

    public function test_admin_can_view_payout_list(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin()));
    }

    public function test_vendor_cannot_view_payout_list(): void
    {
        $this->assertFalse($this->policy->viewAny($this->vendorWithId(5)));
    }

    public function test_client_cannot_view_payout_list(): void
    {
        $this->assertFalse($this->policy->viewAny($this->client()));
    }

    // ─── create ───────────────────────────────────────────────────────────────

    public function test_admin_can_create_payout(): void
    {
        $this->assertTrue($this->policy->create($this->admin()));
    }

    public function test_vendor_cannot_create_payout(): void
    {
        $this->assertFalse($this->policy->create($this->vendorWithId(5)));
    }

    public function test_client_cannot_create_payout(): void
    {
        $this->assertFalse($this->policy->create($this->client()));
    }

    // ─── view ─────────────────────────────────────────────────────────────────

    public function test_admin_can_view_any_payout(): void
    {
        $payout = $this->payoutForVendor(5);
        $this->assertTrue($this->policy->view($this->admin(), $payout));
    }

    public function test_vendor_can_view_own_payout(): void
    {
        $vendor = $this->vendorWithId(5);
        $payout = $this->payoutForVendor(5);
        $this->assertTrue($this->policy->view($vendor, $payout));
    }

    public function test_vendor_cannot_view_another_vendors_payout(): void
    {
        $vendor = $this->vendorWithId(5);
        $payout = $this->payoutForVendor(99);
        $this->assertFalse($this->policy->view($vendor, $payout));
    }

    public function test_client_cannot_view_any_payout(): void
    {
        $payout = $this->payoutForVendor(5);
        $this->assertFalse($this->policy->view($this->client(), $payout));
    }
}

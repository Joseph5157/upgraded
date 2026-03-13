<?php

namespace Tests\Unit\Policies;

use App\Models\RefundRequest;
use App\Models\User;
use App\Policies\RefundRequestPolicy;
use PHPUnit\Framework\TestCase;

class RefundRequestPolicyTest extends TestCase
{
    private RefundRequestPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new RefundRequestPolicy();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function admin(): User
    {
        return new User(['id' => 1, 'role' => 'admin', 'is_super_admin' => false]);
    }

    private function vendor(): User
    {
        return new User(['id' => 2, 'role' => 'vendor']);
    }

    private function client(): User
    {
        return new User(['id' => 3, 'role' => 'client']);
    }

    private function refund(string $status = 'pending'): RefundRequest
    {
        return new RefundRequest(['status' => $status]);
    }

    // ─── viewAny ──────────────────────────────────────────────────────────────

    public function test_admin_can_view_all_refund_requests(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin()));
    }

    public function test_vendor_cannot_view_refund_request_list(): void
    {
        $this->assertFalse($this->policy->viewAny($this->vendor()));
    }

    public function test_client_cannot_view_refund_request_list(): void
    {
        $this->assertFalse($this->policy->viewAny($this->client()));
    }

    // ─── create ───────────────────────────────────────────────────────────────

    public function test_client_can_create_refund_request(): void
    {
        $this->assertTrue($this->policy->create($this->client()));
    }

    public function test_vendor_cannot_create_refund_request(): void
    {
        $this->assertFalse($this->policy->create($this->vendor()));
    }

    public function test_admin_cannot_create_refund_request(): void
    {
        $this->assertFalse($this->policy->create($this->admin()));
    }

    // ─── approve ──────────────────────────────────────────────────────────────

    public function test_admin_can_approve_pending_refund(): void
    {
        $this->assertTrue($this->policy->approve($this->admin(), $this->refund('pending')));
    }

    public function test_admin_cannot_approve_already_approved_refund(): void
    {
        $this->assertFalse($this->policy->approve($this->admin(), $this->refund('approved')));
    }

    public function test_admin_cannot_approve_rejected_refund(): void
    {
        $this->assertFalse($this->policy->approve($this->admin(), $this->refund('rejected')));
    }

    public function test_vendor_cannot_approve_refund(): void
    {
        $this->assertFalse($this->policy->approve($this->vendor(), $this->refund('pending')));
    }

    public function test_client_cannot_approve_refund(): void
    {
        $this->assertFalse($this->policy->approve($this->client(), $this->refund('pending')));
    }

    // ─── reject ───────────────────────────────────────────────────────────────

    public function test_admin_can_reject_pending_refund(): void
    {
        $this->assertTrue($this->policy->reject($this->admin(), $this->refund('pending')));
    }

    public function test_admin_cannot_reject_already_approved_refund(): void
    {
        $this->assertFalse($this->policy->reject($this->admin(), $this->refund('approved')));
    }

    public function test_admin_cannot_reject_already_rejected_refund(): void
    {
        $this->assertFalse($this->policy->reject($this->admin(), $this->refund('rejected')));
    }

    public function test_vendor_cannot_reject_refund(): void
    {
        $this->assertFalse($this->policy->reject($this->vendor(), $this->refund('pending')));
    }

    public function test_client_cannot_reject_refund(): void
    {
        $this->assertFalse($this->policy->reject($this->client(), $this->refund('pending')));
    }
}

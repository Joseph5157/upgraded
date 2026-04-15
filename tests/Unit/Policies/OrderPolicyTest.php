<?php

namespace Tests\Unit\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Policies\OrderPolicy;
use PHPUnit\Framework\TestCase;

class OrderPolicyTest extends TestCase
{
    private OrderPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new OrderPolicy();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function user(array $attrs = []): User
    {
        $attrs = array_merge([
            'id'           => 1,
            'role'         => 'vendor',
            'status'       => 'active',
            'is_super_admin' => false,
        ], $attrs);
        $user = new User($attrs);
        $user->id = $attrs['id']; // id is not fillable; must be assigned directly
        return $user;
    }

    private function order(array $attrs = []): Order
    {
        return new Order(array_merge([
            'status'     => OrderStatus::Pending,
            'claimed_by' => null,
            'client_id'  => 10,
        ], $attrs));
    }

    // ─── claim ────────────────────────────────────────────────────────────────

    public function test_vendor_can_claim_pending_unclaimed_order(): void
    {
        $vendor = $this->user(['id' => 1, 'role' => 'vendor', 'status' => 'active']);
        $order  = $this->order(['status' => OrderStatus::Pending, 'claimed_by' => null]);

        $this->assertTrue($this->policy->claim($vendor, $order));
    }

    public function test_frozen_vendor_cannot_claim(): void
    {
        $vendor = $this->user(['role' => 'vendor', 'status' => 'frozen']);
        $order  = $this->order(['status' => OrderStatus::Pending, 'claimed_by' => null]);

        $this->assertFalse($this->policy->claim($vendor, $order));
    }

    public function test_vendor_cannot_claim_already_claimed_order(): void
    {
        $vendor = $this->user(['id' => 1, 'role' => 'vendor', 'status' => 'active']);
        $order  = $this->order(['status' => OrderStatus::Pending, 'claimed_by' => 99]);

        $this->assertFalse($this->policy->claim($vendor, $order));
    }

    public function test_vendor_cannot_claim_processing_order(): void
    {
        $vendor = $this->user(['role' => 'vendor', 'status' => 'active']);
        $order  = $this->order(['status' => OrderStatus::Processing, 'claimed_by' => null]);

        $this->assertFalse($this->policy->claim($vendor, $order));
    }

    public function test_vendor_cannot_claim_delivered_order(): void
    {
        $vendor = $this->user(['role' => 'vendor', 'status' => 'active']);
        $order  = $this->order(['status' => OrderStatus::Delivered, 'claimed_by' => null]);

        $this->assertFalse($this->policy->claim($vendor, $order));
    }

    public function test_admin_cannot_claim_orders(): void
    {
        $admin = $this->user(['role' => 'admin', 'status' => 'active']);
        $order = $this->order(['status' => OrderStatus::Pending, 'claimed_by' => null]);

        $this->assertFalse($this->policy->claim($admin, $order));
    }

    // ─── unclaim ──────────────────────────────────────────────────────────────

    public function test_owner_can_unclaim_pending_order(): void
    {
        $vendor = $this->user(['id' => 5]);
        $order  = $this->order(['status' => OrderStatus::Pending, 'claimed_by' => 5]);

        $this->assertTrue($this->policy->unclaim($vendor, $order));
    }

    public function test_non_owner_cannot_unclaim(): void
    {
        $vendor = $this->user(['id' => 5]);
        $order  = $this->order(['status' => OrderStatus::Pending, 'claimed_by' => 99]);

        $this->assertFalse($this->policy->unclaim($vendor, $order));
    }

    public function test_owner_cannot_unclaim_processing_order(): void
    {
        $vendor = $this->user(['id' => 5]);
        $order  = $this->order(['status' => OrderStatus::Processing, 'claimed_by' => 5]);

        $this->assertFalse($this->policy->unclaim($vendor, $order));
    }

    // ─── process ──────────────────────────────────────────────────────────────

    public function test_owner_can_process_order(): void
    {
        $vendor = $this->user(['id' => 5]);
        $order  = $this->order(['status' => OrderStatus::Pending, 'claimed_by' => 5]);

        $this->assertTrue($this->policy->process($vendor, $order));
    }

    public function test_admin_can_process_any_order(): void
    {
        $admin = $this->user(['id' => 2, 'role' => 'admin']);
        $order = $this->order(['status' => OrderStatus::Pending, 'claimed_by' => 99]);

        $this->assertTrue($this->policy->process($admin, $order));
    }

    public function test_vendor_cannot_process_order_they_do_not_own(): void
    {
        $vendor = $this->user(['id' => 5, 'role' => 'vendor']);
        $order  = $this->order(['status' => OrderStatus::Pending, 'claimed_by' => 99]);

        $this->assertFalse($this->policy->process($vendor, $order));
    }

    public function test_cannot_process_cancelled_order(): void
    {
        $vendor = $this->user(['id' => 5]);
        $order  = $this->order(['status' => OrderStatus::Cancelled, 'claimed_by' => 5]);

        $this->assertFalse($this->policy->process($vendor, $order));
    }

    public function test_cannot_process_already_delivered_order(): void
    {
        $vendor = $this->user(['id' => 5]);
        $order  = $this->order(['status' => OrderStatus::Delivered, 'claimed_by' => 5]);

        $this->assertFalse($this->policy->process($vendor, $order));
    }

    // ─── uploadReport ─────────────────────────────────────────────────────────

    public function test_owner_can_upload_report(): void
    {
        $vendor = $this->user(['id' => 5]);
        $order  = $this->order(['status' => OrderStatus::Processing, 'claimed_by' => 5]);

        $this->assertTrue($this->policy->uploadReport($vendor, $order));
    }

    public function test_admin_can_upload_report_for_any_order(): void
    {
        $admin = $this->user(['id' => 2, 'role' => 'admin']);
        $order = $this->order(['status' => OrderStatus::Processing, 'claimed_by' => 99]);

        $this->assertTrue($this->policy->uploadReport($admin, $order));
    }

    public function test_cannot_upload_report_for_cancelled_order(): void
    {
        $vendor = $this->user(['id' => 5]);
        $order  = $this->order(['status' => OrderStatus::Cancelled, 'claimed_by' => 5]);

        $this->assertFalse($this->policy->uploadReport($vendor, $order));
    }

    public function test_cannot_upload_report_for_delivered_order(): void
    {
        $vendor = $this->user(['id' => 5]);
        $order  = $this->order(['status' => OrderStatus::Delivered, 'claimed_by' => 5]);

        $this->assertFalse($this->policy->uploadReport($vendor, $order));
    }

    // ─── deliver ──────────────────────────────────────────────────────────────

    public function test_owner_can_deliver_order(): void
    {
        $vendor = $this->user(['id' => 5]);
        $order  = $this->order(['status' => OrderStatus::Processing, 'claimed_by' => 5]);

        $this->assertTrue($this->policy->deliver($vendor, $order));
    }

    public function test_admin_can_deliver_any_order(): void
    {
        $admin = $this->user(['id' => 2, 'role' => 'admin']);
        $order = $this->order(['status' => OrderStatus::Processing, 'claimed_by' => 99]);

        $this->assertTrue($this->policy->deliver($admin, $order));
    }

    public function test_cannot_deliver_cancelled_order(): void
    {
        $vendor = $this->user(['id' => 5]);
        $order  = $this->order(['status' => OrderStatus::Cancelled, 'claimed_by' => 5]);

        $this->assertFalse($this->policy->deliver($vendor, $order));
    }

    public function test_cannot_redeliver_already_delivered_order(): void
    {
        $vendor = $this->user(['id' => 5]);
        $order  = $this->order(['status' => OrderStatus::Delivered, 'claimed_by' => 5]);

        $this->assertFalse($this->policy->deliver($vendor, $order));
    }


    // ─── delete ───────────────────────────────────────────────────────────────

    public function test_client_can_delete_their_own_order(): void
    {
        $client = $this->user(['id' => 7, 'role' => 'client', 'client_id' => 10]);
        $order  = $this->order(['status' => OrderStatus::Delivered, 'client_id' => 10]);

        $this->assertTrue($this->policy->delete($client, $order));
    }

    public function test_client_cannot_delete_another_clients_order(): void
    {
        $client = $this->user(['id' => 7, 'role' => 'client', 'client_id' => 10]);
        $order  = $this->order(['client_id' => 99]);

        $this->assertFalse($this->policy->delete($client, $order));
    }

    public function test_vendor_cannot_delete_orders(): void
    {
        $vendor = $this->user(['id' => 5, 'role' => 'vendor']);
        $order  = $this->order(['client_id' => 10]);

        $this->assertFalse($this->policy->delete($vendor, $order));
    }

    // ─── cancel ───────────────────────────────────────────────────────────────

    public function test_client_can_cancel_their_own_pending_order(): void
    {
        $client = $this->user(['id' => 7, 'role' => 'client', 'client_id' => 10]);
        $order  = $this->order(['status' => OrderStatus::Pending, 'client_id' => 10]);

        $this->assertTrue($this->policy->cancel($client, $order));
    }

    public function test_vendor_cannot_cancel_orders(): void
    {
        $vendor = $this->user(['id' => 5, 'role' => 'vendor']);
        $order  = $this->order(['status' => OrderStatus::Pending, 'client_id' => 10]);

        $this->assertFalse($this->policy->cancel($vendor, $order));
    }

    public function test_client_cannot_cancel_another_clients_order(): void
    {
        $client = $this->user(['id' => 7, 'role' => 'client', 'client_id' => 10]);
        $order  = $this->order(['status' => OrderStatus::Pending, 'client_id' => 99]);

        $this->assertFalse($this->policy->cancel($client, $order));
    }

    public function test_client_cannot_cancel_non_pending_order(): void
    {
        $client = $this->user(['id' => 7, 'role' => 'client', 'client_id' => 10]);
        $order  = $this->order(['status' => OrderStatus::Processing, 'client_id' => 10]);

        $this->assertFalse($this->policy->cancel($client, $order));
    }

    public function test_client_cannot_cancel_delivered_order(): void
    {
        $client = $this->user(['id' => 7, 'role' => 'client', 'client_id' => 10]);
        $order  = $this->order(['status' => OrderStatus::Delivered, 'client_id' => 10]);

        $this->assertFalse($this->policy->cancel($client, $order));
    }

    // ─── forceDelete ──────────────────────────────────────────────────────────

    public function test_admin_can_force_delete_order(): void
    {
        $admin = $this->user(['id' => 2, 'role' => 'admin']);
        $order = $this->order();

        $this->assertTrue($this->policy->forceDelete($admin, $order));
    }

    public function test_vendor_cannot_force_delete_order(): void
    {
        $vendor = $this->user(['id' => 5, 'role' => 'vendor']);
        $order  = $this->order();

        $this->assertFalse($this->policy->forceDelete($vendor, $order));
    }
}

<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use PHPUnit\Framework\TestCase;

class UserPolicyTest extends TestCase
{
    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(array $attrs): User
    {
        $user = new User($attrs);
        if (isset($attrs['id'])) {
            $user->id = $attrs['id']; // id is not fillable; must be assigned directly
        }
        return $user;
    }

    private function superAdmin(int $id = 1): User
    {
        return $this->makeUser(['id' => $id, 'role' => 'admin', 'is_super_admin' => true, 'status' => 'active']);
    }

    private function regularAdmin(int $id = 2): User
    {
        return $this->makeUser(['id' => $id, 'role' => 'admin', 'is_super_admin' => false, 'status' => 'active']);
    }

    private function vendor(int $id = 3): User
    {
        return $this->makeUser(['id' => $id, 'role' => 'vendor', 'is_super_admin' => false, 'status' => 'active']);
    }

    private function client(int $id = 4): User
    {
        return $this->makeUser(['id' => $id, 'role' => 'client', 'is_super_admin' => false, 'status' => 'active']);
    }

    // ─── create ───────────────────────────────────────────────────────────────

    public function test_super_admin_can_create_admin_accounts(): void
    {
        $this->assertTrue($this->policy->create($this->superAdmin(), 'admin'));
    }

    public function test_regular_admin_cannot_create_admin_accounts(): void
    {
        $this->assertFalse($this->policy->create($this->regularAdmin(), 'admin'));
    }

    public function test_vendor_cannot_create_admin_accounts(): void
    {
        $this->assertFalse($this->policy->create($this->vendor(), 'admin'));
    }

    public function test_regular_admin_can_create_vendor_accounts(): void
    {
        $this->assertTrue($this->policy->create($this->regularAdmin(), 'vendor'));
    }

    public function test_regular_admin_can_create_client_accounts(): void
    {
        $this->assertTrue($this->policy->create($this->regularAdmin(), 'client'));
    }

    public function test_vendor_cannot_create_any_accounts(): void
    {
        $this->assertFalse($this->policy->create($this->vendor(), 'vendor'));
        $this->assertFalse($this->policy->create($this->vendor(), 'client'));
    }

    public function test_unknown_role_is_always_denied(): void
    {
        $this->assertFalse($this->policy->create($this->superAdmin(), 'superuser'));
    }

    // ─── freeze ───────────────────────────────────────────────────────────────

    public function test_regular_admin_can_freeze_vendor(): void
    {
        $this->assertTrue($this->policy->freeze($this->regularAdmin(), $this->vendor()));
    }

    public function test_regular_admin_can_freeze_client(): void
    {
        $this->assertTrue($this->policy->freeze($this->regularAdmin(), $this->client()));
    }

    public function test_regular_admin_cannot_freeze_another_admin(): void
    {
        $target = $this->regularAdmin(id: 99);
        $this->assertFalse($this->policy->freeze($this->regularAdmin(), $target));
    }

    public function test_super_admin_can_freeze_regular_admin(): void
    {
        $this->assertTrue($this->policy->freeze($this->superAdmin(), $this->regularAdmin()));
    }

    public function test_super_admin_cannot_freeze_themselves(): void
    {
        $superAdmin = $this->superAdmin(id: 1);
        $this->assertFalse($this->policy->freeze($superAdmin, $superAdmin));
    }

    public function test_super_admin_cannot_freeze_another_super_admin(): void
    {
        $superAdmin1 = $this->superAdmin(id: 1);
        $superAdmin2 = new User(['id' => 2, 'role' => 'admin', 'is_super_admin' => true]);

        $this->assertFalse($this->policy->freeze($superAdmin1, $superAdmin2));
    }

    public function test_admin_cannot_freeze_themselves(): void
    {
        $admin = $this->regularAdmin(id: 2);
        $this->assertFalse($this->policy->freeze($admin, $admin));
    }

    public function test_vendor_cannot_freeze_anyone(): void
    {
        $this->assertFalse($this->policy->freeze($this->vendor(), $this->client()));
    }

    // ─── unfreeze ─────────────────────────────────────────────────────────────

    public function test_unfreeze_follows_same_rules_as_freeze(): void
    {
        // Regular admin can unfreeze vendors/clients
        $this->assertTrue($this->policy->unfreeze($this->regularAdmin(), $this->vendor()));
        $this->assertTrue($this->policy->unfreeze($this->regularAdmin(), $this->client()));

        // Regular admin cannot unfreeze another admin
        $this->assertFalse($this->policy->unfreeze($this->regularAdmin(), $this->regularAdmin(id: 99)));

        // Super admin can unfreeze regular admin
        $this->assertTrue($this->policy->unfreeze($this->superAdmin(), $this->regularAdmin()));

        // Vendor cannot unfreeze anyone
        $this->assertFalse($this->policy->unfreeze($this->vendor(), $this->client()));
    }

    // ─── delete ───────────────────────────────────────────────────────────────

    public function test_regular_admin_can_delete_vendor(): void
    {
        $this->assertTrue($this->policy->delete($this->regularAdmin(), $this->vendor()));
    }

    public function test_regular_admin_can_delete_client(): void
    {
        $this->assertTrue($this->policy->delete($this->regularAdmin(), $this->client()));
    }

    public function test_regular_admin_cannot_delete_another_admin(): void
    {
        $target = $this->regularAdmin(id: 99);
        $this->assertFalse($this->policy->delete($this->regularAdmin(), $target));
    }

    public function test_super_admin_can_delete_regular_admin(): void
    {
        $this->assertTrue($this->policy->delete($this->superAdmin(), $this->regularAdmin()));
    }

    public function test_nobody_can_delete_super_admin(): void
    {
        $superAdmin = $this->superAdmin(id: 1);

        $this->assertFalse($this->policy->delete($this->superAdmin(id: 9), $superAdmin));
        $this->assertFalse($this->policy->delete($this->regularAdmin(), $superAdmin));
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = $this->regularAdmin(id: 2);
        $this->assertFalse($this->policy->delete($admin, $admin));
    }

    // ─── restore ──────────────────────────────────────────────────────────────

    public function test_restore_follows_same_rules_as_delete(): void
    {
        $this->assertTrue($this->policy->restore($this->regularAdmin(), $this->vendor()));
        $this->assertFalse($this->policy->restore($this->regularAdmin(), $this->regularAdmin(id: 99)));
        $this->assertTrue($this->policy->restore($this->superAdmin(), $this->regularAdmin()));
    }

    // ─── forceDelete ──────────────────────────────────────────────────────────

    public function test_admin_can_permanently_delete_vendor(): void
    {
        $this->assertTrue($this->policy->forceDelete($this->regularAdmin(), $this->vendor()));
    }

    public function test_admin_can_permanently_delete_client(): void
    {
        $this->assertTrue($this->policy->forceDelete($this->regularAdmin(), $this->client()));
    }

    public function test_admin_cannot_permanently_delete_another_admin(): void
    {
        $target = $this->regularAdmin(id: 99);
        $this->assertFalse($this->policy->forceDelete($this->regularAdmin(), $target));
    }

    public function test_even_super_admin_cannot_permanently_delete_admin_accounts(): void
    {
        $this->assertFalse($this->policy->forceDelete($this->superAdmin(), $this->regularAdmin()));
    }

    public function test_vendor_cannot_permanently_delete_anyone(): void
    {
        $this->assertFalse($this->policy->forceDelete($this->vendor(), $this->client()));
    }

    public function test_user_cannot_permanently_delete_themselves(): void
    {
        $admin = $this->regularAdmin(id: 2);
        $this->assertFalse($this->policy->forceDelete($admin, $admin));
    }
}

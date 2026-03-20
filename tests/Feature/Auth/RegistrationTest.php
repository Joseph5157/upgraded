<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registration via /register is intentionally disabled — all accounts
 * are provisioned by admins via the admin dashboard.
 * These tests are marked as skipped to document the design decision.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_disabled(): void
    {
        // The /register route has been removed. All accounts are admin-provisioned.
        $response = $this->get('/register');

        $response->assertStatus(404);
    }
}

<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_confirmation_route_is_not_available(): void
    {
        $this->get('/confirm-password')->assertNotFound();
        $this->post('/confirm-password', [
            'password' => 'password',
        ])->assertNotFound();
    }
}

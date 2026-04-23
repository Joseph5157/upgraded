<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_token_login_can_be_completed(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'portal_number' => 4004,
            'activated_at' => now(),
            'telegram_chat_id' => '555123456',
            'login_token' => 'telegram-token-123',
            'login_token_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->get(route('telegram.login', ['token' => 'telegram-token-123']));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('client.dashboard'));
        $this->assertNull($user->fresh()->login_token);
        $this->assertNull($user->fresh()->login_token_expires_at);
    }

    public function test_telegram_token_login_cannot_be_reused(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'portal_number' => 4005,
            'activated_at' => now(),
            'telegram_chat_id' => '555123457',
            'login_token' => 'telegram-token-456',
            'login_token_expires_at' => now()->addMinutes(5),
        ]);

        $this->get(route('telegram.login', ['token' => 'telegram-token-456']))
            ->assertRedirect(route('client.dashboard'));

        $this->post('/logout');

        $retry = $this->get(route('telegram.login', ['token' => 'telegram-token-456']));

        $retry->assertRedirect(route('login'));
        $retry->assertSessionHasErrors('link');
        $this->assertGuest();
        $this->assertNull($user->fresh()->login_token);
    }

    public function test_telegram_token_login_rejects_expired_or_unknown_tokens(): void
    {
        $response = $this->get(route('telegram.login', ['token' => 'missing-token']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('link');
        $this->assertGuest();
    }

    public function test_expired_telegram_token_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'portal_number' => 4006,
            'activated_at' => now(),
            'telegram_chat_id' => '555123458',
            'login_token' => 'telegram-token-expired',
            'login_token_expires_at' => now()->subMinute(),
        ]);

        $response = $this->get(route('telegram.login', ['token' => 'telegram-token-expired']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('link');
        $this->assertGuest();
        $this->assertSame('telegram-token-expired', $user->fresh()->login_token);
    }
}

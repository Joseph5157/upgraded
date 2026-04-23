<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Portal ID');
        $response->assertSee('Send Login Code');
    }

    public function test_users_can_authenticate_using_portal_id_and_otp(): void
    {
        $now = CarbonImmutable::parse('2026-04-23 18:15:00', config('app.timezone'));
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'role' => 'vendor',
            'portal_number' => 1001,
            'activated_at' => now(),
            'telegram_chat_id' => '123456789',
        ]);

        $this->post(route('login.send-otp'), [
            'portal_number' => $user->portal_number,
        ])->assertSessionHas('otp_sent');

        $otp = $user->refresh()->otp;

        $response = $this->post(route('login.verify-otp'), [
            'portal_number' => $user->portal_number,
            'otp' => $otp,
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
        $this->assertSame(
            $now->startOfDay()->addDay()->toDateTimeString(),
            $user->fresh()->session_expires_at?->toDateTimeString()
        );

        Carbon::setTestNow();
    }

    public function test_otp_cannot_be_reused_after_successful_login(): void
    {
        $user = User::factory()->create([
            'role' => 'vendor',
            'portal_number' => 1002,
            'activated_at' => now(),
            'telegram_chat_id' => '123456789',
        ]);

        $this->post(route('login.send-otp'), [
            'portal_number' => $user->portal_number,
        ])->assertSessionHas('otp_sent');

        $otp = $user->refresh()->otp;

        $this->post(route('login.verify-otp'), [
            'portal_number' => $user->portal_number,
            'otp' => $otp,
        ])->assertRedirect(route('dashboard'));

        $this->post('/logout');

        $retry = $this->post(route('login.verify-otp'), [
            'portal_number' => $user->portal_number,
            'otp' => $otp,
        ]);

        $retry->assertSessionHasErrors('otp');
        $this->assertGuest();
    }

    public function test_login_code_send_fails_closed_when_telegram_delivery_fails(): void
    {
        $this->app->instance(TelegramService::class, new class extends TelegramService {
            public function sendMessage(string $chatId, string $text, ?array $replyMarkup = null, array $options = []): bool
            {
                return false;
            }
        });

        $user = User::factory()->create([
            'role' => 'vendor',
            'portal_number' => 1003,
            'activated_at' => now(),
            'telegram_chat_id' => '123456789',
        ]);

        $response = $this->post(route('login.send-otp'), [
            'portal_number' => $user->portal_number,
        ]);

        $response->assertSessionHasErrors('portal_number');
        $this->assertGuest();
        $this->assertNull($user->fresh()->otp);
        $this->assertNull($user->fresh()->otp_expires_at);
    }

    public function test_users_without_telegram_link_cannot_request_otp(): void
    {
        $user = User::factory()->create([
            'role' => 'vendor',
            'portal_number' => 2002,
            'activated_at' => now(),
            'telegram_chat_id' => null,
        ]);

        $response = $this->post(route('login.send-otp'), [
            'portal_number' => $user->portal_number,
        ]);

        $response->assertSessionHasErrors('portal_number');
        $this->assertGuest();
    }

    public function test_login_code_requests_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'role' => 'vendor',
            'portal_number' => 1004,
            'activated_at' => now(),
            'telegram_chat_id' => '987654321',
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('login.send-otp'), [
                'portal_number' => $user->portal_number,
            ])->assertSessionHas('otp_sent');
        }

        $response = $this->post(route('login.send-otp'), [
            'portal_number' => $user->portal_number,
        ]);

        $response->assertStatus(429);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create([
            'role' => 'vendor',
            'portal_number' => 3003,
            'activated_at' => now(),
            'telegram_chat_id' => '987654321',
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}

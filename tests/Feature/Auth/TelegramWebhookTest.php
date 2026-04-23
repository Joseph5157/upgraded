<?php

namespace Tests\Feature\Auth;

use App\Models\PendingInvite;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function fakeTelegramService(): void
    {
        $this->app->instance(TelegramService::class, new class extends TelegramService {
            public array $messages = [];

            public function sendMessage(string $chatId, string $text, ?array $replyMarkup = null, array $options = []): bool
            {
                $this->messages[] = compact('chatId', 'text', 'replyMarkup', 'options');

                return true;
            }
        });
    }

    public function test_invite_token_is_consumed_once_during_webhook_activation(): void
    {
        $this->fakeTelegramService();
        config(['services.telegram.webhook_secret' => 'webhook-secret']);

        $invite = PendingInvite::create([
            'name' => 'New Vendor',
            'role' => 'vendor',
            'invite_token' => 'invite-token-123',
            'expires_at' => now()->addDay(),
        ]);

        $payload = [
            'message' => [
                'chat' => ['id' => 777123456],
                'text' => '/start invite_' . $invite->invite_token,
            ],
        ];

        $response = $this->postJson(route('telegram.webhook', ['secret' => 'webhook-secret']), $payload);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseMissing('pending_invites', ['id' => $invite->id]);
        $this->assertDatabaseHas('users', [
            'telegram_chat_id' => '777123456',
            'role' => 'vendor',
            'name' => 'New Vendor',
            'portal_number' => 5000,
        ]);

        $this->postJson(route('telegram.webhook', ['secret' => 'webhook-secret']), $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(1, User::where('telegram_chat_id', '777123456')->count());
    }

    public function test_telegram_link_token_is_consumed_once_during_webhook_linking(): void
    {
        $this->fakeTelegramService();
        config(['services.telegram.webhook_secret' => 'webhook-secret']);

        $user = User::factory()->create([
            'role' => 'vendor',
            'portal_number' => 6001,
            'activated_at' => now(),
            'telegram_link_token' => 'link-token-123',
            'telegram_chat_id' => null,
            'telegram_connected_at' => null,
        ]);

        $payload = [
            'message' => [
                'chat' => ['id' => 888123456],
                'text' => '/start link-token-123',
            ],
        ];

        $this->postJson(route('telegram.webhook', ['secret' => 'webhook-secret']), $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $user->refresh();
        $this->assertSame('888123456', (string) $user->telegram_chat_id);
        $this->assertNotNull($user->telegram_connected_at);
        $this->assertNull($user->telegram_link_token);

        $this->postJson(route('telegram.webhook', ['secret' => 'webhook-secret']), $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(1, User::where('telegram_chat_id', '888123456')->count());
    }
}

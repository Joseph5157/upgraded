<?php

namespace Tests\Feature\Telegram;

use App\Models\Client;
use App\Models\Order;
use App\Models\TelegramActionToken;
use App\Models\TelegramEventLog;
use App\Models\User;
use App\Services\Telegram\TelegramActionTokenService;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelegramInlineIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $this->mockConsoleOutput = false;
        parent::setUp();
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    private function fakeBot(): array
    {
        $fake = new class extends TelegramService {
            public array $sent      = [];
            public array $answered  = [];
            public array $edited    = [];

            public function sendMessage(string $chatId, string $text, array $options = []): int|false
            {
                $this->sent[] = compact('chatId', 'text', 'options');
                return 1;
            }

            public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): bool
            {
                $this->answered[] = compact('callbackQueryId', 'text', 'showAlert');
                return true;
            }

            public function editMessageText(string $chatId, int|string $messageId, string $text, ?array $replyMarkup = null, array $extraOptions = []): ?array
            {
                $this->edited[] = compact('chatId', 'messageId', 'text', 'replyMarkup');
                return ['ok' => true];
            }
        };

        $this->app->instance(TelegramService::class, $fake);

        return ['fake' => $fake];
    }

    private function webhookPayload(array $message): array
    {
        return ['message' => $message];
    }

    private function callbackPayload(string $callbackData, string $telegramUserId = '999', int $messageId = 42): array
    {
        return [
            'callback_query' => [
                'id'      => 'cq-' . Str::random(8),
                'from'    => ['id' => $telegramUserId],
                'data'    => $callbackData,
                'message' => [
                    'message_id' => $messageId,
                    'chat'       => ['id' => $telegramUserId],
                ],
            ],
        ];
    }

    private function secret(): string
    {
        config(['telegram.webhook_secret' => 'test-secret-xyz']);
        config(['services.telegram.webhook_secret' => 'test-secret-xyz']);
        return 'test-secret-xyz';
    }

    private function webhookRoute(): string
    {
        return route('telegram.webhook', ['secret' => $this->secret()]);
    }

    // ──────────────────────────────────────────────────────────────
    // 1. Webhook security
    // ──────────────────────────────────────────────────────────────

    public function test_webhook_rejects_missing_secret(): void
    {
        config(['telegram.webhook_secret' => 'real-secret']);
        config(['services.telegram.webhook_secret' => 'real-secret']);

        // Route requires {secret} param — omitting it yields 404 (no matching route)
        $this->postJson('/telegram/webhook/', [])
            ->assertStatus(404);
    }

    public function test_webhook_rejects_wrong_secret(): void
    {
        config(['telegram.webhook_secret' => 'correct-secret']);
        config(['services.telegram.webhook_secret' => 'correct-secret']);

        $this->postJson(route('telegram.webhook', ['secret' => 'wrong-secret']), [])
            ->assertStatus(403);
    }

    public function test_webhook_accepts_correct_secret_and_returns_ok(): void
    {
        $this->fakeBot();

        $this->postJson($this->webhookRoute(), [])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    // ──────────────────────────────────────────────────────────────
    // 2. Account linking — /start link_<token>
    // ──────────────────────────────────────────────────────────────

    public function test_valid_link_token_connects_account(): void
    {
        $this->fakeBot();

        $user = User::factory()->create([
            'role'                           => 'vendor',
            'portal_number'                  => 7001,
            'activated_at'                   => now(),
            'telegram_link_token'            => 'valid-link-token',
            'telegram_link_token_expires_at' => now()->addMinutes(15),
            'telegram_chat_id'               => null,
            'telegram_connected_at'          => null,
        ]);

        $payload = $this->webhookPayload([
            'chat' => ['id' => '111222333'],
            'text' => '/start link_valid-link-token',
        ]);

        $this->postJson($this->webhookRoute(), $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $user->refresh();
        $this->assertSame('111222333', (string) $user->telegram_chat_id);
        $this->assertNotNull($user->telegram_connected_at);
        $this->assertNull($user->telegram_link_token);
        $this->assertNull($user->telegram_link_token_expires_at);
    }

    public function test_expired_link_token_is_rejected(): void
    {
        $this->fakeBot();

        $user = User::factory()->create([
            'role'                           => 'vendor',
            'portal_number'                  => 7002,
            'activated_at'                   => now(),
            'telegram_link_token'            => 'expired-token',
            'telegram_link_token_expires_at' => now()->subMinute(),
            'telegram_chat_id'               => null,
        ]);

        $payload = $this->webhookPayload([
            'chat' => ['id' => '444555666'],
            'text' => '/start link_expired-token',
        ]);

        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        $user->refresh();
        // Account should NOT be linked
        $this->assertNull($user->telegram_chat_id);
        $this->assertNull($user->telegram_link_token);
    }

    public function test_reused_link_token_does_not_link_second_account(): void
    {
        $this->fakeBot();

        $user = User::factory()->create([
            'role'                           => 'vendor',
            'portal_number'                  => 7003,
            'activated_at'                   => now(),
            'telegram_link_token'            => 'one-time-token',
            'telegram_link_token_expires_at' => now()->addMinutes(15),
            'telegram_chat_id'               => null,
        ]);

        $payload = $this->webhookPayload([
            'chat' => ['id' => '777888999'],
            'text' => '/start link_one-time-token',
        ]);

        // First tap — succeeds
        $this->postJson($this->webhookRoute(), $payload)->assertOk();
        $user->refresh();
        $this->assertSame('777888999', (string) $user->telegram_chat_id);

        // Second tap — same payload, token already consumed
        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        // Still only one user with that chat ID
        $this->assertSame(1, User::where('telegram_chat_id', '777888999')->count());
    }

    public function test_unknown_start_payload_returns_help(): void
    {
        ['fake' => $fake] = $this->fakeBot();

        $payload = $this->webhookPayload([
            'chat' => ['id' => '123'],
            'text' => '/start',
        ]);

        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        $this->assertNotEmpty($fake->sent);
        $this->assertStringContainsString('Connect', $fake->sent[0]['text']);
    }

    // ──────────────────────────────────────────────────────────────
    // 3. Callback query — action token validation
    // ──────────────────────────────────────────────────────────────

    public function test_expired_action_token_is_denied(): void
    {
        ['fake' => $fake] = $this->fakeBot();

        $token = TelegramActionToken::create([
            'token'         => (string) Str::uuid(),
            'action_type'   => TelegramActionToken::ACTION_ORDER_VIEW,
            'expires_at'    => now()->subMinute(),
            'status'        => TelegramActionToken::STATUS_ACTIVE,
        ]);

        $payload = $this->callbackPayload('a:' . $token->token, '12345');

        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        $this->assertNotEmpty($fake->answered);
        $this->assertStringContainsString('expired', $fake->answered[0]['text']);
    }

    public function test_used_action_token_is_denied(): void
    {
        ['fake' => $fake] = $this->fakeBot();

        $token = TelegramActionToken::create([
            'token'      => (string) Str::uuid(),
            'action_type' => TelegramActionToken::ACTION_ORDER_VIEW,
            'expires_at'  => now()->addMinutes(30),
            'status'      => TelegramActionToken::STATUS_USED,
            'used_at'     => now()->subMinute(),
        ]);

        $payload = $this->callbackPayload('a:' . $token->token, '12345');

        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        $this->assertNotEmpty($fake->answered);
        $this->assertStringContainsString('expired', $fake->answered[0]['text']);
    }

    public function test_unlinked_telegram_user_is_denied_on_callback(): void
    {
        ['fake' => $fake] = $this->fakeBot();

        $token = TelegramActionToken::create([
            'token'        => (string) Str::uuid(),
            'action_type'  => TelegramActionToken::ACTION_ORDER_VIEW,
            'expires_at'   => now()->addMinutes(30),
            'status'       => TelegramActionToken::STATUS_ACTIVE,
        ]);

        // Telegram user ID 99999 has no linked portal account
        $payload = $this->callbackPayload('a:' . $token->token, '99999');

        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        $this->assertNotEmpty($fake->answered);
        $this->assertStringContainsString('not linked', $fake->answered[0]['text']);

        // Denied event should be logged
        $this->assertDatabaseHas('telegram_event_logs', [
            'telegram_user_id' => '99999',
            'event_type'       => 'callback.denied.unlinked',
            'status'           => TelegramEventLog::STATUS_DENIED,
        ]);
    }

    public function test_wrong_role_is_denied_on_admin_only_action(): void
    {
        ['fake' => $fake] = $this->fakeBot();

        // Client user trying to use an admin-only action token
        $clientUser = User::factory()->create([
            'role'             => 'client',
            'telegram_chat_id' => '55566677',
            'activated_at'     => now(),
        ]);

        $token = TelegramActionToken::create([
            'token'                => (string) Str::uuid(),
            'action_type'          => TelegramActionToken::ACTION_PAYMENT_APPROVE_REQUEST,
            'telegram_user_id'     => null,
            'required_role'        => 'admin',
            'expires_at'           => now()->addMinutes(30),
            'status'               => TelegramActionToken::STATUS_ACTIVE,
        ]);

        $payload = $this->callbackPayload('a:' . $token->token, '55566677');

        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        $this->assertNotEmpty($fake->answered);
        $this->assertStringContainsString('permission', $fake->answered[0]['text']);

        $this->assertDatabaseHas('telegram_event_logs', [
            'telegram_user_id' => '55566677',
            'status'           => TelegramEventLog::STATUS_DENIED,
        ]);
    }

    public function test_action_token_locked_to_different_telegram_user_is_denied(): void
    {
        ['fake' => $fake] = $this->fakeBot();

        // Admin user linked to chat 111
        User::factory()->create([
            'role'             => 'admin',
            'telegram_chat_id' => '111',
            'activated_at'     => now(),
        ]);

        // Token locked to chat 111
        $token = TelegramActionToken::create([
            'token'            => (string) Str::uuid(),
            'action_type'      => TelegramActionToken::ACTION_ORDER_VIEW,
            'telegram_user_id' => '111',
            'required_role'    => 'admin',
            'expires_at'       => now()->addMinutes(30),
            'status'           => TelegramActionToken::STATUS_ACTIVE,
        ]);

        // Tap comes from chat 222 (different admin)
        User::factory()->create([
            'role'             => 'admin',
            'telegram_chat_id' => '222',
            'activated_at'     => now(),
        ]);

        $payload = $this->callbackPayload('a:' . $token->token, '222');

        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        $this->assertNotEmpty($fake->answered);
        $this->assertStringContainsString('permission', $fake->answered[0]['text']);
    }

    // ──────────────────────────────────────────────────────────────
    // 4. Phase 2 safe action — vendor assignment accept
    // ──────────────────────────────────────────────────────────────

    public function test_vendor_can_accept_assigned_order(): void
    {
        ['fake' => $fake] = $this->fakeBot();

        $client = Client::create(['name' => 'Test Client', 'status' => 'active', 'credit_balance' => 10]);
        $vendor = User::factory()->create([
            'role'             => 'vendor',
            'telegram_chat_id' => '333444555',
            'activated_at'     => now(),
        ]);

        $order = Order::create([
            'client_id'        => $client->id,
            'claimed_by'       => $vendor->id,
            'status'           => 'claimed',
            'token_view'       => uniqid('tok_'),
            'files_count'      => 1,
            'credits_consumed' => 1,
            'source'           => 'account',
            'due_at'           => now()->addDay(),
        ]);

        $token = TelegramActionToken::create([
            'token'             => (string) Str::uuid(),
            'action_type'       => TelegramActionToken::ACTION_VENDOR_ASSIGNMENT_ACCEPT,
            'subject_type'      => Order::class,
            'subject_id'        => $order->id,
            'telegram_user_id'  => '333444555',
            'required_role'     => 'vendor',
            'expires_at'        => now()->addMinutes(30),
            'status'            => TelegramActionToken::STATUS_ACTIVE,
        ]);

        $payload = $this->callbackPayload('a:' . $token->token, '333444555');

        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        // Token should now be marked used
        $token->refresh();
        $this->assertSame(TelegramActionToken::STATUS_USED, $token->status);

        // Callback answered with success
        $this->assertNotEmpty($fake->answered);
        $this->assertStringContainsString('accepted', strtolower($fake->answered[0]['text']));

        // Message edited
        $this->assertNotEmpty($fake->edited);

        // Audit log written
        $this->assertDatabaseHas('telegram_event_logs', [
            'telegram_user_id' => '333444555',
            'event_type'       => 'callback.vendor.assignment.accepted',
            'status'           => TelegramEventLog::STATUS_SUCCESS,
        ]);
    }

    public function test_vendor_cannot_accept_another_vendors_assignment(): void
    {
        ['fake' => $fake] = $this->fakeBot();

        $client = Client::create(['name' => 'Test Client', 'status' => 'active', 'credit_balance' => 10]);
        $rightVendor = User::factory()->create([
            'role'             => 'vendor',
            'telegram_chat_id' => '777',
            'activated_at'     => now(),
        ]);
        $wrongVendor = User::factory()->create([
            'role'             => 'vendor',
            'telegram_chat_id' => '888',
            'activated_at'     => now(),
        ]);

        $order = Order::create([
            'client_id'        => $client->id,
            'claimed_by'       => $rightVendor->id,
            'status'           => 'claimed',
            'token_view'       => uniqid('tok_'),
            'files_count'      => 1,
            'credits_consumed' => 1,
            'source'           => 'account',
            'due_at'           => now()->addDay(),
        ]);

        // Token is for rightVendor, locked to chat 777
        $token = TelegramActionToken::create([
            'token'            => (string) Str::uuid(),
            'action_type'      => TelegramActionToken::ACTION_VENDOR_ASSIGNMENT_ACCEPT,
            'subject_type'     => Order::class,
            'subject_id'       => $order->id,
            'telegram_user_id' => '777',
            'required_role'    => 'vendor',
            'expires_at'       => now()->addMinutes(30),
            'status'           => TelegramActionToken::STATUS_ACTIVE,
        ]);

        // wrongVendor (chat 888) tries to tap the button
        $payload = $this->callbackPayload('a:' . $token->token, '888');

        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        // Token must still be active (not used)
        $token->refresh();
        $this->assertSame(TelegramActionToken::STATUS_ACTIVE, $token->status);

        $this->assertStringContainsString('permission', $fake->answered[0]['text']);
    }

    public function test_double_tap_on_vendor_accept_does_not_double_process(): void
    {
        ['fake' => $fake] = $this->fakeBot();

        $client = Client::create(['name' => 'Test Client', 'status' => 'active', 'credit_balance' => 10]);
        $vendor = User::factory()->create([
            'role'             => 'vendor',
            'telegram_chat_id' => '999111222',
            'activated_at'     => now(),
        ]);

        $order = Order::create([
            'client_id'        => $client->id,
            'claimed_by'       => $vendor->id,
            'status'           => 'claimed',
            'token_view'       => uniqid('tok_'),
            'files_count'      => 1,
            'credits_consumed' => 1,
            'source'           => 'account',
            'due_at'           => now()->addDay(),
        ]);

        $token = TelegramActionToken::create([
            'token'            => (string) Str::uuid(),
            'action_type'      => TelegramActionToken::ACTION_VENDOR_ASSIGNMENT_ACCEPT,
            'subject_type'     => Order::class,
            'subject_id'       => $order->id,
            'telegram_user_id' => '999111222',
            'required_role'    => 'vendor',
            'expires_at'       => now()->addMinutes(30),
            'status'           => TelegramActionToken::STATUS_ACTIVE,
        ]);

        $payload = $this->callbackPayload('a:' . $token->token, '999111222');

        // First tap — succeeds
        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        $token->refresh();
        $this->assertSame(TelegramActionToken::STATUS_USED, $token->status);

        // Second tap — denied (token already used)
        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        // Only 1 success log, not 2
        $this->assertSame(1, TelegramEventLog::where('event_type', 'callback.vendor.assignment.accepted')->count());
    }

    // ──────────────────────────────────────────────────────────────
    // 5. /credits command uses credit_balance
    // ──────────────────────────────────────────────────────────────

    public function test_credits_command_shows_credit_balance_not_slots(): void
    {
        ['fake' => $fake] = $this->fakeBot();

        $client = Client::create([
            'name'           => 'Credit Client',
            'status'         => 'active',
            'credit_balance' => 42,
            'slots'          => 100,      // legacy column — should be ignored
            'slots_consumed' => 30,       // legacy column — should be ignored
        ]);

        $user = User::factory()->create([
            'role'             => 'client',
            'telegram_chat_id' => '456789',
            'activated_at'     => now(),
            'client_id'        => $client->id,
        ]);

        $payload = $this->webhookPayload([
            'chat' => ['id' => '456789'],
            'text' => '/credits',
        ]);

        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        $this->assertNotEmpty($fake->sent);
        $this->assertStringContainsString('42', $fake->sent[0]['text']);
        // Must NOT show the legacy slot numbers
        $this->assertStringNotContainsString('70', $fake->sent[0]['text']); // 100-30=70 slots
    }

    // ──────────────────────────────────────────────────────────────
    // 6. /unlink command
    // ──────────────────────────────────────────────────────────────

    public function test_unlink_command_clears_telegram_fields(): void
    {
        $this->fakeBot();

        $user = User::factory()->create([
            'role'                  => 'vendor',
            'telegram_chat_id'      => '111999',
            'telegram_connected_at' => now()->subDay(),
            'activated_at'          => now(),
        ]);

        $payload = $this->webhookPayload([
            'chat' => ['id' => '111999'],
            'text' => '/unlink',
        ]);

        $this->postJson($this->webhookRoute(), $payload)->assertOk();

        $user->refresh();
        $this->assertNull($user->telegram_chat_id);
        $this->assertNull($user->telegram_connected_at);
        $this->assertNull($user->telegram_link_token);
    }

    // ──────────────────────────────────────────────────────────────
    // 7. TelegramActionTokenService unit helpers
    // ──────────────────────────────────────────────────────────────

    public function test_action_token_service_creates_and_validates_token(): void
    {
        $service = app(TelegramActionTokenService::class);

        $token = $service->create(
            actionType: TelegramActionToken::ACTION_ORDER_VIEW,
        );

        $this->assertDatabaseHas('telegram_action_tokens', [
            'token'       => $token->token,
            'action_type' => TelegramActionToken::ACTION_ORDER_VIEW,
            'status'      => TelegramActionToken::STATUS_ACTIVE,
        ]);

        $validated = $service->validate($token->token);
        $this->assertNotNull($validated);
        $this->assertSame($token->token, $validated->token);
    }

    public function test_action_token_service_marks_used(): void
    {
        $service = app(TelegramActionTokenService::class);

        $token = $service->create(actionType: TelegramActionToken::ACTION_ORDER_VIEW);
        $user  = User::factory()->create(['role' => 'admin', 'activated_at' => now()]);

        $service->markUsed($token, $user->id);

        $token->refresh();
        $this->assertSame(TelegramActionToken::STATUS_USED, $token->status);
        $this->assertSame($user->id, $token->used_by_user_id);
    }

    public function test_action_token_service_prunes_expired(): void
    {
        $service = app(TelegramActionTokenService::class);

        TelegramActionToken::create([
            'token'        => (string) Str::uuid(),
            'action_type'  => TelegramActionToken::ACTION_ORDER_VIEW,
            'expires_at'   => now()->subHour(),
            'status'       => TelegramActionToken::STATUS_ACTIVE,
        ]);

        $pruned = $service->pruneExpired();
        $this->assertSame(1, $pruned);

        $this->assertDatabaseMissing('telegram_action_tokens', [
            'status' => TelegramActionToken::STATUS_ACTIVE,
        ]);
    }

    public function test_callback_data_round_trips_correctly(): void
    {
        $service = app(TelegramActionTokenService::class);
        $token   = $service->create(actionType: TelegramActionToken::ACTION_ORDER_VIEW);

        $callbackData = $service->callbackData($token);
        $this->assertStringStartsWith('a:', $callbackData);

        $parsed = $service->parseCallbackData($callbackData);
        $this->assertSame($token->token, $parsed);
    }

    public function test_parse_callback_data_returns_null_for_unknown_format(): void
    {
        $service = app(TelegramActionTokenService::class);

        $this->assertNull($service->parseCallbackData('approve_payment_123'));
        $this->assertNull($service->parseCallbackData(''));
        $this->assertNull($service->parseCallbackData('b:some-uuid'));
    }
}

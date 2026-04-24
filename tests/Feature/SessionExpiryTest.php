<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SessionExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[DataProvider('dashboardRoutes')]
    public function test_expired_dashboard_sessions_redirect_to_login_with_friendly_message(string $routeName, array $userAttributes): void
    {
        Carbon::setTestNow(CarbonImmutable::parse('2026-04-23 18:15:00', config('app.timezone')));

        $user = User::factory()->create(array_merge([
            'status' => 'active',
            'activated_at' => now(),
            'telegram_chat_id' => '123456789',
            'session_expires_at' => now()->subMinute(),
        ], $userAttributes));

        $response = $this->actingAs($user)->get(route($routeName));

        $response->assertRedirect(route('login', ['expired' => 1]));
        $response->assertSessionHas('error', 'Your session expired. Please sign in again.');
        $this->assertGuest();

        Carbon::setTestNow();
    }

    public function test_expired_vendor_claim_request_returns_login_payload(): void
    {
        Carbon::setTestNow(CarbonImmutable::parse('2026-04-23 18:15:00', config('app.timezone')));

        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'activated_at' => now(),
            'telegram_chat_id' => '123456789',
            'session_expires_at' => now()->subMinute(),
        ]);

        $client = Client::create([
            'name' => 'Expiry Client',
            'email' => 'expiry-client@example.com',
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'token_view' => 'expiry-claim-test',
            'files_count' => 1,
            'status' => OrderStatus::Pending,
            'claimed_by' => null,
            'due_at' => now()->addMinutes(20),
            'source' => 'account',
        ]);

        $response = $this->actingAs($vendor)->post(route('orders.claim', $order), [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response->assertStatus(419);
        $response->assertJson([
            'message' => 'Your session expired. Please sign in again.',
            'redirect' => route('login', ['expired' => 1]),
        ]);
        $this->assertGuest();

        Carbon::setTestNow();
    }

    public function test_browser_token_mismatch_redirects_to_login_with_friendly_message(): void
    {
        Route::middleware('web')->post('/__session-expiry-browser', function (): never {
            throw new TokenMismatchException();
        });

        $response = $this->post('/__session-expiry-browser');

        $response->assertRedirect(route('login', ['expired' => 1]));
        $response->assertSessionHas('error', 'Your session expired. Please sign in again.');
        $this->assertGuest();
    }

    public function test_ajax_token_mismatch_returns_419_json_and_login_redirect(): void
    {
        Route::middleware('web')->post('/__session-expiry-ajax', function (): never {
            throw new TokenMismatchException();
        });

        $response = $this->withHeaders([
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_ACCEPT' => 'application/json',
        ])->post('/__session-expiry-ajax');

        $response->assertStatus(419);
        $response->assertJson([
            'message' => 'Your session expired. Please sign in again.',
            'redirect' => route('login', ['expired' => 1]),
        ]);
        $this->assertGuest();
    }

    public static function dashboardRoutes(): array
    {
        return [
            'vendor' => [
                'dashboard',
                [
                    'role' => 'vendor',
                ],
            ],
            'client' => [
                'client.dashboard',
                [
                    'role' => 'client',
                ],
            ],
            'admin' => [
                'admin.dashboard',
                [
                    'role' => 'admin',
                ],
            ],
        ];
    }
}

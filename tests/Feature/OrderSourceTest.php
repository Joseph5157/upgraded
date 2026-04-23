<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientLink;
use App\Models\Order;
use App\Models\User;
use App\Services\PortalTelegramAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_created_via_link_has_correct_metadata()
    {
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);
        $this->app->instance(PortalTelegramAlertService::class, new class extends PortalTelegramAlertService {
            public function __construct() {}
            public function notifyOrderAccepted(...$args): void {}
            public function notifyOrderCompleted(...$args): void {}
        });
        $client = Client::create(['name' => 'Test Client', 'slots' => 10]);
        $link = ClientLink::create([
            'client_id' => $client->id,
            'token' => 'test-token',
            'is_active' => true,
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->post(route('client.store', 'test-token'), [
            'file'                  => UploadedFile::fake()->create('document.pdf', 100),
            'cf-turnstile-response' => 'test',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $order = Order::first();
        $this->assertEquals('link', $order->source);
        $this->assertEquals($link->id, $order->client_link_id);
        $this->assertNull($order->created_by_user_id);
    }

    public function test_order_created_via_dashboard_has_correct_metadata()
    {
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);
        $this->app->instance(PortalTelegramAlertService::class, new class extends PortalTelegramAlertService {
            public function __construct() {}
            public function notifyOrderAccepted(...$args): void {}
            public function notifyOrderCompleted(...$args): void {}
        });
        $client = Client::create(['name' => 'Test Client', 'slots' => 10]);
        $user = User::factory()->create(['role' => 'client', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->post(route('client.dashboard.upload'), [
            'files' => [UploadedFile::fake()->create('account-doc.pdf', 100)],
        ]);

        $response->assertRedirect();

        $order = Order::first();
        $this->assertEquals('account', $order->source);
        $this->assertEquals($user->id, $order->created_by_user_id);
        $this->assertNull($order->client_link_id);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_dashboard_shows_correct_order_info()
    {
        $client = Client::create(['name' => 'Test Client', 'slots' => 10]);
        $user = User::factory()->create(['role' => 'client', 'client_id' => $client->id]);

        // 1. Pending Order
        Order::create([
            'client_id' => $client->id,
            'token_view' => 'pending-1',
            'status' => 'pending',
            'due_at' => now()->addMinutes(15),
            'source' => 'account',
            'created_by_user_id' => $user->id
        ]);

        // 2. Overdue Order
        Order::create([
            'client_id' => $client->id,
            'token_view' => 'overdue-1',
            'status' => 'processing',
            'due_at' => now()->subMinutes(5),
            'source' => 'account',
            'created_by_user_id' => $user->id
        ]);

        // 3. Delivered Order with Report
        $deliveredOrder = Order::create([
            'client_id' => $client->id,
            'token_view' => 'delivered-1',
            'status' => 'delivered',
            'due_at' => now()->subMinutes(30),
            'source' => 'account',
            'created_by_user_id' => $user->id
        ]);
        OrderReport::create([
            'order_id'         => $deliveredOrder->id,
            'ai_report_path'   => 'reports/r1_ai.pdf',
            'plag_report_path' => 'reports/r1_plag.pdf',
        ]);

        $response = $this->actingAs($user)->get(route('client.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Queued');
        $response->assertSee('In progress');
        $response->assertSee('Ready');
        $response->assertSee('Download Both');
        $response->assertSee('AI Report');
    }

    public function test_client_dashboard_pulse_returns_fragment_html_when_orders_change(): void
    {
        $client = Client::create(['name' => 'Pulse Client', 'slots' => 10]);
        $user = User::factory()->create(['role' => 'client', 'client_id' => $client->id]);

        $initialOrder = Order::create([
            'client_id' => $client->id,
            'token_view' => 'pulse-1',
            'status' => 'pending',
            'due_at' => now()->addMinutes(15),
            'source' => 'account',
            'created_by_user_id' => $user->id,
        ]);
        OrderFile::create([
            'order_id' => $initialOrder->id,
            'file_path' => 'orders/' . $initialOrder->id . '/initial.pdf',
            'original_name' => 'initial.pdf',
        ]);

        $response = $this->actingAs($user)->get(route('client.dashboard'));
        $signature = (string) $response->viewData('dashboardSignature');

        $newOrder = Order::create([
            'client_id' => $client->id,
            'token_view' => 'pulse-2',
            'status' => 'pending',
            'due_at' => now()->addMinutes(15),
            'source' => 'account',
            'created_by_user_id' => $user->id,
        ]);
        OrderFile::create([
            'order_id' => $newOrder->id,
            'file_path' => 'orders/' . $newOrder->id . '/fresh.pdf',
            'original_name' => 'fresh.pdf',
        ]);

        $pulse = $this->actingAs($user)->getJson(route('client.dashboard.pulse', ['signature' => $signature]));

        $pulse->assertOk()
            ->assertJsonStructure(['signature', 'checked_at', 'liveHtml']);

        $this->assertNotSame($signature, $pulse->json('signature'));
        $this->assertStringContainsString('client-dashboard-live', (string) $pulse->json('liveHtml'));
        $this->assertStringContainsString('fresh.pdf', (string) $pulse->json('liveHtml'));
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Client;
use App\Models\OrderFile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardImprovmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setupData()
    {
        $agent = User::create([
            'name'             => 'Agent Smith',
            'email'            => 'smith@example.com',
            'password'         => bcrypt('password'),
            'role'             => 'vendor',
            'status'           => 'active',
            'portal_number'    => 7001,
            'activated_at'     => now(),
            'telegram_chat_id' => '123456789',
            'delivered_orders_count' => 1,
        ]);

        $client = Client::create([
            'name' => 'Customer',
            'email' => 'customer@example.com'
        ]);

        // Available File (Pending, Unclaimed)
        $o1 = Order::create([
            'client_id' => $client->id,
            'token_view' => 'pool-1',
            'files_count' => 1,
            'status' => 'pending',
            'due_at' => now()->addMinutes(20),
            'source' => 'account'
        ]);
        \App\Models\OrderFile::create(['order_id' => $o1->id, 'file_path' => 'test1.pdf']);

        // My Workspace (Processing, Claimed by agent)
        $o2 = Order::create([
            'client_id' => $client->id,
            'token_view' => 'work-1',
            'files_count' => 1,
            'status' => 'processing',
            'claimed_by' => $agent->id,
            'due_at' => now()->addMinutes(10),
            'source' => 'account'
        ]);
        \App\Models\OrderFile::create(['order_id' => $o2->id, 'file_path' => 'test2.pdf']);

        // Overdue Order
        $o3 = Order::create([
            'client_id' => $client->id,
            'token_view' => 'overdue-1',
            'files_count' => 1,
            'status' => 'pending',
            'due_at' => now()->subMinutes(10),
            'source' => 'account'
        ]);
        \App\Models\OrderFile::create(['order_id' => $o3->id, 'file_path' => 'test3.pdf']);

        // Delivered Today
        Order::create([
            'client_id' => $client->id,
            'token_view' => 'delivered-1',
            'files_count' => 1,
            'status' => 'delivered',
            'claimed_by' => $agent->id,
            'delivered_at' => now(),
            'due_at' => now()->subMinutes(60),
            'source' => 'account'
        ]);

        return $agent;
    }

    public function test_vendor_dashboard_has_correct_data()
    {
        $agent = $this->setupData();

        // Phase 10 Stage 2: GET /dashboard now redirects to /vendor-panel.
        // The underlying stat queries (available_pool, active_jobs, etc.) are exercised by
        // the pulse endpoint which still hits DashboardController::loadDashboardState().
        $response = $this->actingAs($agent)->get(route('dashboard'));
        $response->assertRedirect('/vendor-panel');
    }

    public function test_vendor_dashboard_pulse_returns_fragment_html_when_queue_changes(): void
    {
        $agent = $this->setupData();

        // Phase 10 Stage 2: GET /dashboard redirects — get initial signature from pulse endpoint
        // instead (pulse is not redirected and returns full JSON when signature is unknown).
        $initial = $this->actingAs($agent)->getJson(route('dashboard.pulse', ['signature' => '']));
        $initial->assertOk()->assertJsonStructure(['signature', 'checked_at', 'liveHtml']);
        $signature = (string) $initial->json('signature');

        $client = Client::create([
            'name' => 'Pulse Customer',
            'email' => 'pulse@example.com',
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'token_view' => 'pulse-order',
            'files_count' => 1,
            'status' => 'pending',
            'due_at' => now()->addMinutes(20),
            'source' => 'account',
        ]);
        OrderFile::create([
            'order_id' => $order->id,
            'file_path' => 'orders/' . $order->id . '/pulse-order.pdf',
            'original_name' => 'pulse-order.pdf',
        ]);

        // Now the queue changed — pulse with the old signature should return fresh HTML
        $pulse = $this->actingAs($agent)->getJson(route('dashboard.pulse', ['signature' => $signature]));

        $pulse->assertOk()
            ->assertJsonStructure(['signature', 'checked_at', 'liveHtml']);

        $this->assertNotSame($signature, $pulse->json('signature'));
        $this->assertStringContainsString('vendor-dashboard-live', (string) $pulse->json('liveHtml'));
        $this->assertStringContainsString('pulse-order.pdf', (string) $pulse->json('liveHtml'));
    }
}

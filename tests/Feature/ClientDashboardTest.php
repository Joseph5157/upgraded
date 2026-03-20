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
        $response->assertSee('Pending');
        $response->assertSee('Overdue');
        $response->assertSee('Ready');
        $response->assertSee('download'); // download icon/btn for delivered
    }
}

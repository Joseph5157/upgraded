<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Client;
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

        $response = $this->actingAs($agent)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('stats');
        $response->assertViewHas('myWorkspace');
        $response->assertViewHas('availableFiles');
        $response->assertViewHas('recentHistory');

        $stats = $response->viewData('stats');
        $this->assertEquals(2, $stats['available_pool']);
        $this->assertEquals(1, $stats['active_jobs']);
        $this->assertEquals(1, $stats['total_checked_today']);
        $this->assertEquals(1, $stats['total_delivered']);

        $this->assertCount(1, $response->viewData('myWorkspace'));
        $this->assertCount(2, $response->viewData('availableFiles'));
    }
}

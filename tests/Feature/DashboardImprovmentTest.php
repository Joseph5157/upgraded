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
            'name' => 'Agent Smith',
            'email' => 'smith@example.com',
            'password' => bcrypt('password'),
            'role' => 'vendor',
            'email_verified_at' => now(),
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
        $this->assertEquals(2, $stats['available_pool']); // pool-1 and overdue-1 are both pending/unclaimed
        $this->assertEquals(1, $stats['active_jobs']);    // work-1
        $this->assertEquals(1, $stats['total_checked_today']); // delivered-1
        $this->assertEquals(1, $stats['overdue_count']); // Only overdue-1 is pending/unclaimed and due in the past
        // Let's re-calculate: 
        // 1. pool-1 (due in 20m) -> NOT overdue
        // 2. work-1 (due in 10m) -> NOT overdue
        // 3. overdue-1 (due -10m ago) -> OVERDUE
        // 4. delivered-1 (due -60m ago) -> Delivered, so NOT included in overdue count (status != delivered)
        // So overdue_count should be 1.
        $this->assertEquals(1, $stats['overdue_count']);

        $this->assertCount(1, $response->viewData('myWorkspace'));
        $this->assertCount(2, $response->viewData('availableFiles'));
    }
}

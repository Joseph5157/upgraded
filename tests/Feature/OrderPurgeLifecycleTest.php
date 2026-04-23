<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderPurgeLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function mockLifecycleDisk(array $existingPaths = [], array $contents = []): object
    {
        $disk = new class($existingPaths, $contents) {
            public array $deletedPaths = [];
            public array $deletedDirectories = [];

            public function __construct(
                protected array $existingPaths,
                protected array $contents,
            ) {}

            public function exists(string $path): bool
            {
                return in_array($path, $this->existingPaths, true);
            }

            public function get(string $path): string
            {
                return (string) ($this->contents[$path] ?? '');
            }

            public function readStream(string $path)
            {
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, $this->get($path));
                rewind($stream);

                return $stream;
            }

            public function delete(string $path): bool
            {
                $this->deletedPaths[] = $path;

                return true;
            }

            public function deleteDirectory(string $path): bool
            {
                $this->deletedDirectories[] = $path;

                return true;
            }

            public function makeDirectory(string $path): bool
            {
                return true;
            }
        };

        Storage::shouldReceive('disk')->with('r2')->andReturn($disk);

        return $disk;
    }

    public function test_purge_order_files_removes_delivered_report_only_orders_even_without_source_files(): void
    {
        $client = Client::create([
            'name' => 'Retention Client',
            'slots' => 10,
            'slots_consumed' => 0,
            'status' => 'active',
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'token_view' => 'retention-order',
            'files_count' => 1,
            'status' => OrderStatus::Delivered,
            'delivered_at' => now()->subDays(2),
            'due_at' => now()->subDay(),
            'source' => 'account',
        ]);

        OrderReport::create([
            'order_id' => $order->id,
            'ai_report_path' => 'reports/' . $order->id . '/ai/report-ai.pdf',
            'ai_report_disk' => 'r2',
            'plag_report_path' => 'reports/' . $order->id . '/plag/report-plag.pdf',
            'plag_report_disk' => 'r2',
        ]);
        $disk = $this->mockLifecycleDisk([
            'reports/' . $order->id . '/ai/report-ai.pdf',
            'reports/' . $order->id . '/plag/report-plag.pdf',
        ], [
            'reports/' . $order->id . '/ai/report-ai.pdf' => 'ai',
            'reports/' . $order->id . '/plag/report-plag.pdf' => 'plag',
        ]);

        $this->artisan('app:purge-order-files', ['--days' => 1])
            ->assertExitCode(0);

        $this->assertContains('reports/' . $order->id . '/ai/report-ai.pdf', $disk->deletedPaths);
        $this->assertContains('reports/' . $order->id . '/plag/report-plag.pdf', $disk->deletedPaths);
        $this->assertDatabaseMissing('order_reports', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }
}

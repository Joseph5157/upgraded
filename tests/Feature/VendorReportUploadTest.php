<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use App\Services\UploadVendorReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery\MockInterface;
use Tests\TestCase;

class VendorReportUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_report_returns_domain_error_message_for_non_storage_failures(): void
    {
        [$vendor, $order] = $this->createVendorOrder();

        $this->mock(UploadVendorReportService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('execute')
                ->once()
                ->andThrow(new \RuntimeException("Order must be in 'processing' status before delivery."));
        });

        $response = $this->actingAs($vendor)->post(route('orders.report', $order), [
            'ai_report' => UploadedFile::fake()->create('ai-report.pdf', 20, 'application/pdf'),
            'plag_report' => UploadedFile::fake()->create('plag-report.pdf', 20, 'application/pdf'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'error' => "Order must be in 'processing' status before delivery.",
            ]);
    }

    public function test_upload_report_keeps_storage_failures_as_server_errors(): void
    {
        [$vendor, $order] = $this->createVendorOrder();

        $this->mock(UploadVendorReportService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('execute')
                ->once()
                ->andThrow(new \RuntimeException('Unable to write file at location: reports/1/ai/test.pdf'));
        });

        $response = $this->actingAs($vendor)->post(route('orders.report', $order), [
            'ai_report' => UploadedFile::fake()->create('ai-report.pdf', 20, 'application/pdf'),
            'plag_report' => UploadedFile::fake()->create('plag-report.pdf', 20, 'application/pdf'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response
            ->assertStatus(500)
            ->assertJson([
                'error' => 'Report upload failed while saving files to storage. Please try again. If the issue continues, contact admin.',
            ]);
    }

    /**
     * @return array{0: User, 1: Order}
     */
    protected function createVendorOrder(): array
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $client = Client::create([
            'name' => 'Client Upload',
            'email' => 'client-upload@example.com',
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'token_view' => 'vendor-upload-test',
            'files_count' => 1,
            'status' => 'claimed',
            'claimed_by' => $vendor->id,
            'claimed_at' => now(),
            'due_at' => now()->addMinutes(20),
            'source' => 'account',
        ]);

        return [$vendor, $order];
    }
}
